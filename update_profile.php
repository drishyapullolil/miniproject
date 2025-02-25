<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['profile_pic'])) {
    $target_dir = "uploads/profile_pictures/";
    if (!file_exists($target_dir)) {
        mkdir($target_dir, 0777, true);
    }

    // Validate file type and size
    $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif'];
    $file_extension = strtolower(pathinfo($_FILES["profile_pic"]["name"], PATHINFO_EXTENSION));
    $max_file_size = 2 * 1024 * 1024; // 2MB

    if (!in_array($file_extension, $allowed_extensions)) {
        echo json_encode(['success' => false, 'message' => 'Invalid file type. Only JPG, JPEG, PNG, and GIF are allowed.']);
        exit();
    }

    if ($_FILES["profile_pic"]["size"] > $max_file_size) {
        echo json_encode(['success' => false, 'message' => 'File size exceeds the maximum allowed size of 2MB.']);
        exit();
    }

    // Generate a unique filename
    $new_filename = "profile_" . $user_id . "_" . time() . ".webp";
    $target_file = $target_dir . $new_filename;

    // Convert the uploaded image to WebP format
    $uploaded_image = $_FILES["profile_pic"]["tmp_name"];
    switch ($file_extension) {
        case 'jpg':
        case 'jpeg':
            $image = imagecreatefromjpeg($uploaded_image);
            break;
        case 'png':
            $image = imagecreatefrompng($uploaded_image);
            break;
        case 'gif':
            $image = imagecreatefromgif($uploaded_image);
            break;
        default:
            echo json_encode(['success' => false, 'message' => 'Unsupported image format.']);
            exit();
    }

    // Save the image in WebP format
    if (imagewebp($image, $target_file, 80)) { // 80 is the quality (0-100)
        imagedestroy($image); // Free up memory

        // Update the profile picture path in the database
        $update_query = "UPDATE users SET profile_pic = ? WHERE id = ?";
        $stmt = $conn->prepare($update_query);
        $stmt->bind_param("si", $target_file, $user_id);
        if ($stmt->execute()) {
            $_SESSION['profile_pic'] = $target_file;
            echo json_encode(['success' => true, 'message' => 'Profile picture updated successfully!']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error updating profile picture in database.']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Error converting image to WebP format.']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'No file uploaded.']);
}
?>