<?php
session_start();
include 'db.php';

// Jika sudah login, kembalikan ke index
if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Ambil input kode admin (jika ada)
    $input_code = isset($_POST['admin_code']) ? $_POST['admin_code'] : '';

    // --- PENGATURAN KODE RAHASIA ---
    // Ganti "RAHASIA_NEGARA" dengan kata sandi pilihan Yang Mulia
    $secret_admin_key = "CITA-CITAKU MENJADI DEWA"; 
    // -------------------------------

    // Validasi input password
    if ($password !== $confirm_password) {
        header("Location: index.php?pesan=password_mismatch");
        exit;
    } 
    
    // Cek username apakah sudah ada
    $check_query = "SELECT id FROM users WHERE username = '$username'";
    $check_result = $conn->query($check_query);

    if ($check_result->num_rows > 0) {
        header("Location: index.php?pesan=username_taken");
        exit;
    } 
    
    // --- PENENTUAN ROLE ---
    $role = 'user'; // Default rakyat biasa
    if ($input_code === $secret_admin_key) {
        $role = 'admin'; // Jadi pejabat jika kode benar
    }

    // Proses Hash & Insert
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    $stmt = $conn->prepare("INSERT INTO users (username, password, role) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $username, $hashed_password, $role);
    
    if ($stmt->execute()) {
        header("Location: index.php?pesan=sukses");
        exit;
    } else {
        header("Location: index.php?pesan=error_system");
        exit;
    }
}
?>