<?php
session_start();
require_once '../config/config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');

    $input = json_decode(file_get_contents('php://input'), true);

    if (isset($input['image_id'])) {
        $imageId = $input['image_id'];

        // Pobierz informacje o obrazie
        $stmt = $pdo->prepare("SELECT image_url FROM gallery WHERE id = :id");
        $stmt->execute(['id' => $imageId]);
        $image = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($image) {
            // Usuń obrazek z serwera
            $filePath = __DIR__ . '/../' . $image['image_url'];
            if (file_exists($filePath)) {
                unlink($filePath);
            }

            // Usuń rekord z bazy danych
            $stmt = $pdo->prepare("DELETE FROM gallery WHERE id = :id");
            $stmt->execute(['id' => $imageId]);

            echo json_encode(['status' => 'success', 'message' => 'Zdjęcie zostało usunięte.']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Nie znaleziono zdjęcia.']);
        }
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Brak ID zdjęcia.']);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Nieprawidłowe żądanie.']);
}
