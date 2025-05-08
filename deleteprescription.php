<?php
session_start();

if (isset($_POST['delete_index'])) {
    $index = (int) $_POST['delete_index'];

    if (isset($_SESSION['prescriptions'][$index])) {
        unset($_SESSION['prescriptions'][$index]);

        // Reindex the array to prevent gaps
        $_SESSION['prescriptions'] = array_values($_SESSION['prescriptions']);

        echo json_encode(['success' => true]);
        exit;
    }
}

echo json_encode(['success' => false]);

