<?php
session_start();
include 'db.php';

// Jika sudah login, lempar ke dashboard
if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit;
}

$error = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $password = $_POST['password'];

    $sql = "SELECT * FROM users WHERE username = '$username'";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        // Verifikasi hash password
        if (password_verify($password, $row['password'])) {
            $_SESSION['user_id'] = $row['id'];
            $_SESSION['username'] = $row['username'];
            $_SESSION['role'] = $row['role'];
            header("Location: dashboard.php");
            exit;
        } else {
            $error = "Password salah, Yang Mulia.";
        }
    } else {
        $error = "Pengguna tidak ditemukan.";
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - EduGuide AI</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="sketch-container" style="max-width: 400px; text-align: center;">
        <h2>EduGuide AI Login</h2>
        <p>Silakan masuk untuk konsultasi.</p>
        
        <?php if($error): ?>
            <div style="background: #ffcccc; border: 2px solid black; padding: 10px; margin-bottom: 10px;">
                <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="">
            <input type="text" name="username" placeholder="Username" required>
            <input type="password" name="password" placeholder="Password" required>
            <button type="submit" style="width: 100%;">MASUK</button>
        </form>
        
        <div style="margin-top: 20px; border-top: 2px dashed black; padding-top: 10px;">
            <p>Belum punya akun?</p>
            <a href="register.php"><button style="width: 100%; background: #e0f7fa;">DAFTAR AKUN BARU</button></a>
        </div>
    </div>
</body>
</html>