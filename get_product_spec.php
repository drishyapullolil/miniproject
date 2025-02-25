<?php
require_once 'db.php';

if (isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $query = "SELECT * FROM product_specifications WHERE id = $id";
    $result = mysqli_query($conn, $query);
    
    if ($result && mysqli_num_rows($result) > 0) {
        $data = mysqli_fetch_assoc($result);
        echo json_encode($data);
    } else {
        echo json_encode(['error' => 'Record not found']);
    }
} else {
    echo json_encode(['error' => 'No ID provided']);
}
?> 