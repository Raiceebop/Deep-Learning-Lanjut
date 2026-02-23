<?php
session_start();
include 'db.php';

// Jika sudah login, lempar ke dashboard
if (isset($_SESSION['user_id'])) {
  header("Location: dashboard.php");
  exit;
}

$error = '';
$success = '';

// Menangkap pesan dari register.php
if (isset($_GET['pesan'])) {
  if ($_GET['pesan'] == 'sukses') {
    $success = "Akun berhasil dibuat, Yang Mulia! Silakan login.";
  } elseif ($_GET['pesan'] == 'password_mismatch') {
    $error = "Password konfirmasi tidak cocok.";
  } elseif ($_GET['pesan'] == 'username_taken') {
    $error = "Username sudah digunakan rakyat lain.";
  } elseif ($_GET['pesan'] == 'error_system') {
    $error = "Terjadi kesalahan sistem.";
  }
}

// Proses LOGIN
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
      $_SESSION['role'] = $row['role']; // Simpan role di session
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
<!doctype html>
<html lang="id">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Eduguide AI</title>
  <link href="https://fonts.googleapis.com/css2?family=Great+Vibes&family=Poppins:wght@300;400;600;800&display=swap"
    rel="stylesheet" />
  <link rel="stylesheet" href="fix.css">
</head>

<body>
  <nav>
    <div class="logo">eduguide AI</div>
    <a href="#about">about us</a>
  </nav>
  <header>
    <div class="hero-content">
      <div class="welcome-text">Welcome</div>
      <h1>JELAJAHI KAMPUS</h1>
      <h1>INDONESIA DENGAN AI</h1>
      <a href="javascript:void(0)" class="login-btn" id="openLoginBtn">Login</a>
    </div>
  </header>

  <section id="about" class="about-us">
    <h2>Tentang Eduguide AI</h2>
    <p>Eduguide AI adalah platform berbasis kecerdasan buatan yang dirancang khusus untuk membantu siswa menjelajahi dan memilih perguruan tinggi di Indonesia. Dengan bantuan AI canggih, platform ini memberikan rekomendasi personal, informasi akurat, dan panduan lengkap untuk memudahkan perjalanan pendidikan Anda.</p>

    <h3>Apa yang Bisa Dilakukan Eduguide AI?</h3>
    <ul>
      <li><strong>Chat Interaktif:</strong> Tanyakan apa saja tentang universitas, jurusan, atau proses pendaftaran melalui percakapan langsung dengan AI.</li>
      <li><strong>Rekomendasi Personalisasi:</strong> Dapatkan saran kampus dan program studi yang sesuai dengan minat, nilai, dan lokasi Anda.</li>
      <li><strong>Informasi Lengkap:</strong> Akses data tentang biaya kuliah, fasilitas kampus, akreditasi, dan prospek karir setelah lulus.</li>
      <li><strong>Perbandingan Mudah:</strong> Bandingkan beberapa universitas secara berdampingan untuk membantu Anda memutuskan.</li>
    </ul>

    <h3>Tujuan Dibuatnya Eduguide AI</h3>
    <p>Eduguide AI dibuat dengan tujuan utama untuk mendemokratisasi akses informasi pendidikan tinggi di Indonesia. Banyak siswa yang kesulitan mendapatkan panduan yang tepat karena keterbatasan informasi atau waktu. Dengan Eduguide AI, kami ingin memastikan setiap siswa dapat membuat keputusan yang tepat dan percaya diri dalam memilih masa depan akademik mereka.</p>

    <h3>Keunggulan dan Manfaat</h3>
    <p><strong>Keunggulan:</strong> Eduguide AI menggunakan teknologi AI terdepan untuk memberikan rekomendasi yang akurat dan personal. Antarmuka yang sederhana dan ramah pengguna membuatnya mudah digunakan oleh siapa saja. Data yang kami gunakan selalu diperbarui dari sumber terpercaya.</p>
    <p><strong>Manfaat:</strong> Hemat waktu dalam mencari informasi, dapatkan bantuan kapan saja (24/7), kurangi risiko kesalahan dalam memilih universitas, dan tingkatkan peluang sukses di dunia pendidikan.</p>

    <h3>Kenapa Harus Eduguide AI?</h3>
    <p>Karena Eduguide AI fokus secara spesifik pada ekosistem pendidikan Indonesia, memahami konteks lokal, dan memberikan solusi yang inovatif. Tidak seperti platform umum, kami dirancang khusus untuk membantu siswa Indonesia mencapai impian akademik mereka dengan cara yang lebih cerdas dan efisien.</p>

    <h3>Tim Pengembang</h3>
    <div class="team">
      <div class="member">
        <h4>Vicky Pamungkas</h4>
        <p>Project Lead & Jelek Coy Jangan Gitu Lah GANTI!!</p>
      </div>
      <div class="member">
        <h4>Ratu Silma Amalia</h4>
        <p>Report Compiler & Siap Ketua Aku Ngikut</p>
      </div>
      <div class="member">
        <h4>Gilang Atha Anantyo Putra</h4>
        <p>Backend Developer & Udahlah Gemini Aja & Komplen Tanpa Solusi</p>
      </div>
      <div class="member">
        <h4>Ahmad Rai Fatkaozi</h4>
        <p>Frontend Developer & Udah Gasi Gitu Aja</p>
      </div>
    </div>
  </section>

  <div id="authModal" class="modal" style="<?php echo ($error || $success) ? 'display:flex;' : ''; ?>">
    <div class="modal-content">
      <span class="close-btn" id="closeModalBtn">&times;</span>

      <h2 id="formTitle" style="margin-bottom: 20px">Login</h2>

      <?php if ($error): ?>
        <div style="background: #ffcccc; color: #990000; border: 1px solid #990000; padding: 10px; margin-bottom: 10px; border-radius: 5px;">
          <?php echo $error; ?>
        </div>
      <?php endif; ?>

      <?php if ($success): ?>
        <div style="background: #ccffcc; color: #006600; border: 1px solid #006600; padding: 10px; margin-bottom: 10px; border-radius: 5px;">
          <?php echo $success; ?>
        </div>
      <?php endif; ?>

      <form id="authForm" action="index.php" method="post">

        <div class="form-group" id="nameField">
          <label>Nama Lengkap</label>
          <input type="text" name="username" placeholder="Nama lengkap" />
        </div>

        <div class="form-group">
          <label>Password</label>
          <input type="password" name="password" placeholder="Masukkan password..." required />
        </div>

        <div class="form-group" id="confirmPassField" style="display: none;">
          <label>Confirm Password</label>
          <input type="password" name="confirm_password" placeholder="Ulangi Password">
        </div>

        <div class="form-group" id="adminCodeField" style="display: none;">
          <label>Kode Admin (Opsional)</label>
          <input type="text" name="admin_code" placeholder="Masukkan kode rahasia...">
          <small style="font-size: 0.8em; color: gray;">*Ekhmm 🤫</small>
        </div>

        <button type="submit" class="submit-btn" id="formSubmitBtn">
          Masuk
        </button>
      </form>

      <p class="toggle-text" id="toggleText">
        Belum punya akun? <span id="switchAuth" style="cursor: pointer; color: blue; text-decoration: underline;">Daftar sekarang</span>
      </p>
    </div>
  </div>
  
  <script>
    // Ambil elemen-elemen dari HTML
    const modal = document.getElementById("authModal");
    const openBtn = document.getElementById("openLoginBtn");
    const closeBtn = document.getElementById("closeModalBtn");
    const switchBtn = document.getElementById("switchAuth");

    // Elemen form
    const authForm = document.getElementById("authForm");
    const formTitle = document.getElementById("formTitle");
    const submitBtn = document.getElementById("formSubmitBtn");
    const toggleText = document.getElementById("toggleText");

    // Field input
    const nameField = document.getElementById("nameField"); 
    const confirmPassField = document.getElementById("confirmPassField"); 
    const adminCodeField = document.getElementById("adminCodeField"); // Elemen baru

    let isLoginMode = true; // Status awal

    // 1. Fungsi Buka Modal
    openBtn.addEventListener("click", () => {
      modal.style.display = "flex";
      setLoginMode(); // Reset ke login
    });

    // 2. Fungsi Tutup Modal
    closeBtn.addEventListener("click", () => {
      modal.style.display = "none";
    });

    window.addEventListener("click", (e) => {
      if (e.target === modal) {
        modal.style.display = "none";
      }
    });

    // 3. Fungsi Ganti Mode (Login <-> Register)
    switchBtn.addEventListener("click", () => {
      if (isLoginMode) {
        setRegisterMode();
      } else {
        setLoginMode();
      }
    });

    // --- LOGIKA REGISTER ---
    function setRegisterMode() {
      isLoginMode = false;

      formTitle.textContent = "Registrasi";
      submitBtn.textContent = "Daftar";
      authForm.action = "register.php";

      // Tampilkan Field Register
      nameField.style.display = "block";
      confirmPassField.style.display = "block";
      adminCodeField.style.display = "block"; // Munculkan input kode admin

      // Ubah label
      nameField.querySelector('label').textContent = "Username / Nama Lengkap";

      toggleText.innerHTML = 'Sudah punya akun? <span id="switchAuth" style="cursor: pointer; color: blue; text-decoration: underline;">Login disini</span>';

      document.getElementById("switchAuth").addEventListener("click", setLoginMode);
    }

    // --- LOGIKA LOGIN ---
    function setLoginMode() {
      isLoginMode = true;

      formTitle.textContent = "Login";
      submitBtn.textContent = "Masuk";
      authForm.action = "index.php";

      // Tampilkan Field Login (Username tetap ada)
      nameField.style.display = "block";

      // Sembunyikan Field Register
      confirmPassField.style.display = "none";
      adminCodeField.style.display = "none"; // Sembunyikan input kode admin

      // Ubah label
      nameField.querySelector('label').textContent = "Username";

      toggleText.innerHTML = 'Belum punya akun? <span id="switchAuth" style="cursor: pointer; color: blue; text-decoration: underline;">Daftar sekarang</span>';

      document.getElementById("switchAuth").addEventListener("click", setRegisterMode);
    }
  </script>
</body>

</html>