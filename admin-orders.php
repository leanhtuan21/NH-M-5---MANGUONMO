<?php
session_start();
require_once __DIR__ . '/db_connect.php';

$sql = "SELECT * FROM orders ORDER BY id DESC";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Admin - Quản lý đơn hàng</title>

<style>
body {
    font-family: Segoe UI, sans-serif;
    background: #f3f4f6;
    padding: 30px;
}

h2 {
    margin-bottom: 20px;
}

/* TABLE */
table {
    width: 100%;
    background: white;
    border-collapse: collapse;
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 10px 25px rgba(0,0,0,0.05);
}

th {
    background: #0f172a;
    color: white;
    padding: 14px;
}

td {
    padding: 14px 10px;
    text-align: center;
    border-bottom: 1px solid #eee;
    vertical-align: middle;
}

/* STATUS BADGE */
.badge {
    padding: 6px 14px;
    border-radius: 20px;
    font-weight: 600;
    font-size: 13px;
}

.pending { background: #fef3c7; color: #d97706; }
.paid { background: #dbeafe; color: #2563eb; }
.shipping { background: #ede9fe; color: #7c3aed; }
.completed { background: #d1fae5; color: #059669; }

/* ACTION FORM */
.action-form {
    display: flex;
    justify-content: center;
    align-items: center;
    gap: 8px;
}

/* SELECT */
select {
    height: 36px;
    padding: 0 10px;
    border-radius: 8px;
    border: 1px solid #ddd;
    font-weight: 500;
    min-width: 110px;
}

select:focus {
    outline: none;
    border-color: #6366f1;
}

/* BUTTON */
.btn-update {
    height: 36px;
    padding: 0 14px;
    background: linear-gradient(135deg, #4f46e5, #6366f1);
    border: none;
    color: white;
    border-radius: 8px;
    cursor: pointer;
    font-weight: 600;
    transition: 0.2s;
}

.btn-update:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
}

.disabled {
    color: #9ca3af;
    font-style: italic;
    font-weight: 500;
}
</style>
</head>

<body>

<h2>📦 Quản lý đơn hàng</h2>

<table>
<tr>
    <th>ID</th>
    <th>User</th>
    <th>Thanh toán</th>
    <th>Tổng tiền</th>
    <th>Trạng thái</th>
    <th>Cập nhật</th>
</tr>

<?php while ($row = $result->fetch_assoc()): ?>
<?php
$statusRaw = strtolower($row['status']);
$status = strtoupper($row['status']);

$badgeClass = match($status) {
    'PENDING' => 'badge pending',
    'PAID' => 'badge paid',
    'SHIPPING' => 'badge shipping',
    'COMPLETED' => 'badge completed',
    default => 'badge'
};

/* ===== ADMIN FLOW =====
   PENDING -> PAID
   PAID -> SHIPPING
*/
$allowedOptions = [];

if ($statusRaw === 'pending') {
    $allowedOptions = ['PAID'];
} elseif ($statusRaw === 'paid') {
    $allowedOptions = ['SHIPPING'];
}
?>

<tr>
    <td>#<?= $row['id'] ?></td>
    <td><?= $row['user_id'] ?></td>

    <td>
        <?= $row['payment_method'] === 'cod' ? '🚚 COD' : '💳 QR' ?>
    </td>

    <td><?= number_format($row['total_amount']) ?> đ</td>

    <td>
        <span class="<?= $badgeClass ?>">
            <?= $status ?>
        </span>
    </td>

    <td>
        <?php if (!empty($allowedOptions)): ?>
        <form method="POST" action="update-order-status.php" class="action-form">
            <input type="hidden" name="order_id" value="<?= $row['id'] ?>">

            <select name="status">
                <?php foreach ($allowedOptions as $opt): ?>
                    <option value="<?= $opt ?>"><?= $opt ?></option>
                <?php endforeach; ?>
            </select>

            <button class="btn-update">Cập nhật</button>
        </form>
        <?php else: ?>
            <span class="disabled">Đã khóa</span>
        <?php endif; ?>
    </td>
</tr>
<?php endwhile; ?>

</table>

</body>
</html>