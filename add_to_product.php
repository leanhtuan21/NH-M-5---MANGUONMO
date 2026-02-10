<?php
session_start();
include 'db_connect.php';

// Bật report lỗi SQL cho dễ debug (có thể tắt khi lên production)
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

/**
 * ✅ Hàm response JSON chuẩn
 */
function jsonResponse($success, $message, $data = [], $statusCode = 200) {
    http_response_code($statusCode);
    header('Content-Type: application/json; charset=utf-8');

    $res = [
        'success' => $success,
        'message' => $message
    ];

    if (!empty($data)) {
        $res['data'] = $data;
    }

    echo json_encode($res, JSON_UNESCAPED_UNICODE);
    exit();
}

try {

    // ✅ Chỉ cho phép POST
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        jsonResponse(false, 'Truy cập không hợp lệ', [], 405);
    }

    // ✅ Bắt buộc đăng nhập
    if (!isset($_SESSION['user_id'])) {
        jsonResponse(false, 'Bạn chưa đăng nhập', [], 401);
    }

    $user_id     = (int) $_SESSION['user_id'];
    $product_id  = (int) ($_POST['product_id'] ?? 0);
    $quantity    = (int) ($_POST['product_quantity'] ?? 0);
    $weight_gram = (int) ($_POST['weight_unit'] ?? 0);

    // 1. Kiểm tra ID sản phẩm và Số lượng trước
if ($product_id <= 0 || $quantity <= 0) {
    echo json_encode(['success' => false, 'message' => 'Dữ liệu sản phẩm không hợp lệ']);
    exit();
}

// 2. Nếu không nhận được khối lượng (weight_gram = 0), nghĩa là không có biến thể nào để chọn
// => Coi như là HẾT HÀNG
if ($weight_gram <= 0) {
    echo json_encode(['success' => false, 'message' => 'Sản phẩm này hiện tại đã hết hàng']);
    exit();
}

    /**
     * ✅ Lấy sản phẩm + tồn kho theo khối lượng
     */
    $stmt = $conn->prepare("
        SELECT 
            p.name, 
            p.price, 
            p.tax_percent, 
            pw.stock_quantity
        FROM products p
        JOIN product_weights pw 
            ON pw.product_id = p.id 
           AND pw.weight_gram = ?
        WHERE p.id = ?
    ");
    $stmt->bind_param("ii", $weight_gram, $product_id);
    $stmt->execute();
    $res = $stmt->get_result();

    if ($res->num_rows == 0) {
        jsonResponse(false, 'Biến thể khối lượng không tồn tại', [], 404);
    }

    $product = $res->fetch_assoc();
    $stmt->close();

    $product_name = $product['name'];
    $base_price   = (float) $product['price']; // giá cho 100g
    $tax          = (float) $product['tax_percent'];
    $stock        = (int) $product['stock_quantity'];

    // ✅ Hết hàng theo khối lượng
    if ($stock <= 0) {
        jsonResponse(false, 'Sản phẩm đã hết hàng cho khối lượng này', [], 409);
    }

    // ✅ Tính giá theo gram + VAT
    $price_by_gram   = $base_price * ($weight_gram / 100);
    $price_after_tax = $price_by_gram * (1 + $tax / 100);

    /**
     * ✅ Kiểm tra cart theo product_id + weight_gram
     */
    $check = $conn->prepare("
        SELECT id, quantity 
        FROM cart 
        WHERE user_id = ? AND product_id = ? AND weight_gram = ?
    ");
    $check->bind_param("iii", $user_id, $product_id, $weight_gram);
    $check->execute();
    $result = $check->get_result();

    if ($row = $result->fetch_assoc()) {

        $new_qty = $row['quantity'] + $quantity;

        if ($new_qty > $stock) {
            jsonResponse(false, "Chỉ còn $stock sản phẩm trong kho cho khối lượng này", [
                'stock' => $stock
            ], 409);
        }

        $upd = $conn->prepare("UPDATE cart SET quantity = ? WHERE id = ?");
        $upd->bind_param("ii", $new_qty, $row['id']);
        $upd->execute();
        $upd->close();

    } else {

        if ($quantity > $stock) {
            jsonResponse(false, "Chỉ còn $stock sản phẩm trong kho cho khối lượng này", [
                'stock' => $stock
            ], 409);
        }

        $ins = $conn->prepare("
            INSERT INTO cart (user_id, product_id, product_name, weight_gram, price, quantity, created_at)
            VALUES (?, ?, ?, ?, ?, ?, NOW())
        ");
        $ins->bind_param("iisidi", $user_id, $product_id, $product_name, $weight_gram, $price_after_tax, $quantity);
        $ins->execute();
        $ins->close();
    }

    $check->close();

    /**
     * ✅ Đếm tổng số lượng trong giỏ
     */
    $count = $conn->prepare("SELECT SUM(quantity) AS total FROM cart WHERE user_id = ?");
    $count->bind_param("i", $user_id);
    $count->execute();
    $total = $count->get_result()->fetch_assoc()['total'] ?? 0;
    $count->close();

    $conn->close();

    jsonResponse(true, 'Đã thêm sản phẩm vào giỏ hàng', [
        'cart_count' => (int) $total
    ]);

} catch (Throwable $e) {
    jsonResponse(false, 'Lỗi hệ thống, vui lòng thử lại sau', [
        'debug' => $e->getMessage() // ❌ bỏ khi deploy thật
    ], 500);
}
