<?php
    $current_page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    
    echo '<nav class="pagination-container">
            <ul class="pagination">';

    // Nút Previous
    if ($current_page > 1) {
        echo '<li class="page-item"><a class="page-link" href="?page='.($current_page - 1).'">«</a></li>';
    }

    // Các con số trang
    for ($i = 1; $i <= $total_page; $i++) {
        $activeClass = ($i == $current_page) ? 'active' : '';
        echo '<li class="page-item '.$activeClass.'"><a class="page-link" href="?page='.$i.'">'.$i.'</a></li>';
    }

    // Nút Next
    if ($current_page < $total_page) {
        echo '<li class="page-item"><a class="page-link" href="?page='.($current_page + 1).'">»</a></li>';
    }

    echo '  </ul>
          </nav>';
?>