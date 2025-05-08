<?php
session_start();
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_index'])) {
    $idx = (int) $_POST['delete_index'];
    if (isset($_SESSION['prescriptions'][$idx])) {
        unset($_SESSION['prescriptions'][$idx]);
        // Reindex
        $_SESSION['prescriptions'] = array_values($_SESSION['prescriptions']);
        echo json_encode(['success' => true]);
        exit;
    }
}


echo json_encode(['success' => false]);
