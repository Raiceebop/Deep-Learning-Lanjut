<?php
session_start();
include 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

$role = $_SESSION['role'];
$username = $_SESSION['username'];
$user_id = $_SESSION['user_id']; // Penting untuk query user

// Logout Logic
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: index.php");
    exit;
}

// --- LOGIKA UPDATE PERSONA (KHUSUS ADMIN) ---
$current_persona = ""; 

if ($_SESSION['role'] == 'admin') {
    // 1. Jika tombol simpan ditekan
    if (isset($_POST['update_persona'])) {
        $new_persona = $_POST['persona_text'];
        $stmt_update = $conn->prepare("UPDATE settings SET setting_value = ? WHERE setting_key = 'system_prompt'");
        $stmt_update->bind_param("s", $new_persona);
        if ($stmt_update->execute()) {
            echo "<script>alert('Titah Yang Mulia telah dilaksanakan! Persona berhasil diubah.');</script>";
        } else {
            echo "<script>alert('Gagal mengubah persona.');</script>";
        }
    }

    // 2. Ambil Persona saat ini
    $query_setting = "SELECT setting_value FROM settings WHERE setting_key = 'system_prompt'";
    $res_setting = $conn->query($query_setting);
    if ($res_setting->num_rows > 0) {
        $row_setting = $res_setting->fetch_assoc();
        $current_persona = $row_setting['setting_value'];
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EduGuide AI - Sketch Mode</title>
    <link href="https://fonts.googleapis.com/css2?family=Patrick+Hand&display=swap" rel="stylesheet">
    
    <style>
        /* --- GAYA SKETSA HITAM PUTIH --- */
        :root {
            --black: #2d2d2d;
            --paper: #fdfbf7;
            --shadow: 4px 4px 0px #2d2d2d;
        }

        body {
            font-family: 'Patrick Hand', cursive;
            background-color: #f0f0f0;
            background-image: radial-gradient(#d1d1d1 1px, transparent 1px);
            background-size: 20px 20px;
            margin: 0;
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            color: var(--black);
        }

        .sketch-container {
            background: var(--paper);
            border: 3px solid var(--black);
            border-radius: 255px 15px 225px 15px / 15px 225px 15px 255px;
            box-shadow: var(--shadow);
            width: 90%;
            max-width: 900px; /* Diperlebar sedikit agar sidebar muat */
            padding: 25px;
            position: relative;
        }

        .navbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 2px dashed var(--black);
            padding-bottom: 15px;
            margin-bottom: 20px;
        }

        h3 {
            font-size: 32px;
            margin: 0;
            text-transform: uppercase;
            letter-spacing: 2px;
        }

        .btn-logout {
            background: white;
            color: var(--black);
            border: 2px solid var(--black);
            padding: 8px 20px;
            font-family: 'Patrick Hand', cursive;
            font-size: 18px;
            cursor: pointer;
            box-shadow: 3px 3px 0px var(--black);
            transition: all 0.2s;
        }
        .btn-logout:hover {
            transform: translate(2px, 2px);
            box-shadow: 1px 1px 0px var(--black);
            background: #ffcccc;
        }

        /* --- LAYOUT BARU (SIDEBAR + CHAT) --- */
        .layout-grid {
            display: flex;
            gap: 20px;
            height: 500px; /* Tinggi Fix */
            align-items: stretch;
        }

        /* SIDEBAR KIRI */
        .sidebar {
            width: 30%;
            border-right: 2px dashed var(--black);
            padding-right: 15px;
            overflow-y: auto;
            scrollbar-width: thin;
            scrollbar-color: var(--black) #f0f0f0;
        }

        .session-item {
            display: block;
            padding: 10px;
            margin-bottom: 10px;
            border: 2px solid var(--black);
            background: #fff;
            text-decoration: none;
            color: var(--black);
            font-size: 14px;
            transition: 0.2s;
            cursor: pointer;
        }

        .session-item:hover, .session-item.active {
            background: var(--black);
            color: white;
            transform: translate(-2px, -2px);
            box-shadow: 2px 2px 0px #ccc;
        }

        .btn-new-chat {
            display: block;
            width: 100%;
            padding: 10px;
            background: #e0f7fa;
            border: 2px solid var(--black);
            font-family: 'Patrick Hand', cursive;
            font-weight: bold;
            cursor: pointer;
            margin-bottom: 20px;
            text-align: center;
            text-decoration: none;
            color: black;
            box-sizing: border-box;
        }
        .btn-new-chat:hover { background: #b2ebf2; }

        /* AREA CHAT KANAN */
        .chat-area-wrapper {
            width: 70%;
            display: flex;
            flex-direction: column;
            position: relative;
        }

        .chat-box {
            flex: 1; /* Isi sisa ruang */
            overflow-y: auto;
            padding: 15px;
            border: 2px solid var(--black);
            background: #fff;
            display: flex;
            flex-direction: column;
            gap: 15px;
            scrollbar-width: thin;
            scrollbar-color: var(--black) #fff;
        }

        .message {
            padding: 10px 15px;
            border: 2px solid var(--black);
            max-width: 80%;
            line-height: 1.4;
            font-size: 16px;
            position: relative;
        }
        .message.user {
            background: var(--black);
            color: white;
            align-self: flex-end;
            border-radius: 20px 20px 0 20px;
            box-shadow: -3px 3px 0px rgba(0,0,0,0.2);
        }
        .message.bot {
            background: white;
            color: var(--black);
            align-self: flex-start;
            border-radius: 20px 20px 20px 0;
            box-shadow: 3px 3px 0px rgba(0,0,0,0.2);
        }
        .message strong { text-decoration: underline; font-weight: bold; }

        .input-area {
            display: flex;
            gap: 10px;
            margin-top: 15px;
        }
        #userInput {
            flex: 1;
            padding: 15px;
            font-family: 'Patrick Hand', cursive;
            font-size: 16px;
            border: 2px solid var(--black);
            background: white;
            outline: none;
            box-shadow: 3px 3px 0px #ddd;
        }
        #userInput:focus {
            box-shadow: 3px 3px 0px var(--black);
            transform: translateY(-2px);
        }
        .btn-send {
            background: var(--black);
            color: white;
            border: 2px solid var(--black);
            width: 50px;
            font-size: 20px;
            cursor: pointer;
            box-shadow: 3px 3px 0px #999;
            font-family: 'Patrick Hand', cursive;
        }
        .btn-send:hover {
            background: white;
            color: var(--black);
            transform: translate(2px, 2px);
            box-shadow: 1px 1px 0px var(--black);
        }

        .loading-container {
            display: none;
            margin-top: 5px;
            padding-left: 10px;
            font-style: italic;
            font-size: 14px;
        }
        .dots::after {
            content: '.';
            animation: dots 1.5s steps(5, end) infinite;
        }
        @keyframes dots {
            0%, 20% { content: '.'; } 40% { content: '..'; }
            60% { content: '...'; } 80% { content: '....'; } 100% { content: '.....'; }
        }

        /* TABLE ADMIN */
        table {
            width: 100%;
            border-collapse: collapse;
            border: 2px solid var(--black);
            margin-top: 10px;
        }
        th, td { border: 1px solid var(--black); padding: 10px; text-align: left; }
        th { background: var(--black); color: white; }
        tr:nth-child(even) { background-color: #f2f2f2; }

        /* RESPONSIF HP */
        @media (max-width: 768px) {
            .layout-grid { flex-direction: column; height: auto; }
            .sidebar { width: 100%; border-right: none; border-bottom: 2px dashed var(--black); margin-bottom: 20px; max-height: 200px; }
            .chat-area-wrapper { width: 100%; height: 500px; }
        }
    </style>
</head>
<body>

<div class="sketch-container">
    <div class="navbar">
        <div>
            <h3>EduGuide AI ✏️</h3>
            <span>
                Halo, <b><?php echo htmlspecialchars($username); ?></b> 
                <?php echo ($role == 'admin') ? '(Admin)' : '(Mahasiswa)'; ?>
            </span>
        </div>
        <a href="?logout=true"><button class="btn-logout">X Keluar</button></a>
    </div>

    <?php if ($role == 'admin'): ?>
        
        <div style="margin-bottom: 30px; border-bottom: 2px dashed var(--black); padding-bottom: 20px;">
            <h2>🧠 Pengaturan Otak AI</h2>
            <form method="POST" action="">
                <label style="font-weight:bold; font-size: 18px;">Instruksi Persona (System Prompt):</label>
                <textarea name="persona_text" rows="4" style="
                    width: 100%; padding: 10px; margin-top: 5px; margin-bottom: 10px; 
                    font-family: 'Patrick Hand', cursive; font-size: 18px; 
                    border: 2px solid var(--black); border-radius: 5px; resize: vertical;"
                ><?php echo htmlspecialchars($current_persona); ?></textarea>
                
                <button type="submit" name="update_persona" style="
                    background: var(--black); color: white; border: 2px solid var(--black); 
                    padding: 10px 20px; font-family: 'Patrick Hand', cursive; 
                    font-size: 18px; cursor: pointer; box-shadow: 3px 3px 0px #999;">
                    💾 Simpan Perubahan
                </button>
            </form>
        </div>

        <div style="margin-bottom: 20px;">
            <h2>📋 Log Percakapan</h2>
            <form method="GET" action="">
                <input type="number" name="filter_id" placeholder="Cari ID User..." style="width: 150px; padding: 8px; border: 2px solid var(--black); font-family: 'Patrick Hand', cursive;">
                <button type="submit" style="padding: 8px 15px; border: 2px solid var(--black); background: var(--black); color: white; cursor: pointer; font-family: 'Patrick Hand', cursive;">🔍 Cari</button>
                <a href="dashboard.php" style="margin-left: 10px; color: var(--black);">Reset</a>
            </form>
        </div>

        <div style="overflow-x:auto;">
            <table>
                <thead>
                    <tr><th>Jam</th><th>User</th><th>Tanya</th><th>Jawab</th></tr>
                </thead>
                <tbody>
                    <?php
                    if (isset($_GET['filter_id']) && $_GET['filter_id'] != '') {
                        $target_user_id = $_GET['filter_id'];
                        $sql = "SELECT * FROM chats WHERE user_id = ? ORDER BY id DESC LIMIT 20";
                        $stmt = $conn->prepare($sql);
                        $stmt->bind_param("i", $target_user_id);
                        $stmt->execute();
                        $result = $stmt->get_result();
                        echo "<tr><td colspan='4' style='background:#ddd; text-align:center;'>Sedang menampilkan riwayat User ID: <b>$target_user_id</b></td></tr>";
                    } else {
                        $sql = "SELECT * FROM chats ORDER BY timestamp DESC LIMIT 10";
                        $result = $conn->query($sql);
                    }

                    if ($result->num_rows > 0) {
                        while($row = $result->fetch_assoc()) {
                            echo "<tr>";
                            echo "<td>" . date('H:i', strtotime($row["timestamp"])) . "</td>";
                            echo "<td style='text-align:center;'>" . $row["user_id"] . "</td>";
                            $limit_text = isset($_GET['filter_id']) ? 100 : 30; 
                            $user_msg = htmlspecialchars($row["user_message"]);
                            echo "<td>" . (strlen($user_msg) > $limit_text ? substr($user_msg, 0, $limit_text) . "..." : $user_msg) . "</td>";
                            $bot_msg = htmlspecialchars($row["bot_response"]);
                            echo "<td>" . (strlen($bot_msg) > $limit_text ? substr($bot_msg, 0, $limit_text) . "..." : $bot_msg) . "</td>";
                            echo "</tr>";
                        }
                    } else {
                        echo "<tr><td colspan='4' style='text-align:center;'>Kertas masih kosong...</td></tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>

    <?php else: ?>
        
        <?php 
            // Cek Session ID yang aktif
            $active_session_id = isset($_GET['session_id']) ? $_GET['session_id'] : null;
        ?>

        <div class="layout-grid">
            
            <div class="sidebar">
                <a href="dashboard.php" class="btn-new-chat">+ 📝 Kertas Baru</a>
                
                <h4 style="border-bottom: 2px solid var(--black); padding-bottom: 5px; margin-top: 0;">Riwayat Coretan</h4>
                <?php
                $sql_sessions = "SELECT * FROM chat_sessions WHERE user_id = '$user_id' ORDER BY created_at DESC";
                $res_sessions = $conn->query($sql_sessions);

                if ($res_sessions->num_rows > 0) {
                    while($sess = $res_sessions->fetch_assoc()) {
                        $isActive = ($active_session_id == $sess['id']) ? 'active' : '';
                        $title = htmlspecialchars(substr($sess['title'], 0, 25));
                        if(strlen($sess['title']) > 25) $title .= '...';
                        
                        echo '<a href="?session_id='.$sess['id'].'" class="session-item '.$isActive.'">';
                        echo '<strong>'.$title.'</strong>';
                        echo '<br><span style="font-size:12px; opacity:0.8;">📅 '.date('d/m H:i', strtotime($sess['created_at'])).'</span>';
                        echo '</a>';
                    }
                } else {
                    echo "<p style='font-size:14px; font-style:italic;'>Belum ada riwayat...</p>";
                }
                ?>
            </div>

            <div class="chat-area-wrapper">
                <div class="chat-box" id="chatBox">
                    <?php
                    if ($active_session_id) {
                        // JIKA KLIK RIWAYAT: TAMPILKAN CHAT SESI ITU
                        // Validasi dulu apakah sesi ini milik user ini (Security)
                        $check_owner = $conn->query("SELECT id FROM chat_sessions WHERE id = '$active_session_id' AND user_id = '$user_id'");
                        
                        if ($check_owner->num_rows > 0) {
                            $sql_chats = "SELECT * FROM chats WHERE session_id = '$active_session_id' ORDER BY id ASC";
                            $res_chats = $conn->query($sql_chats);
                            
                            function formatTextPHP($text) {
                                $text = htmlspecialchars($text);
                                $text = preg_replace('/\*\*(.*?)\*\*/', '<strong>$1</strong>', $text);
                                $text = nl2br($text);
                                return $text;
                            }

                            if ($res_chats->num_rows > 0) {
                                while($chat = $res_chats->fetch_assoc()) {
                                    echo '<div class="message user">' . formatTextPHP($chat['user_message']) . '</div>';
                                    if($chat['bot_response']) {
                                        echo '<div class="message bot">' . formatTextPHP($chat['bot_response']) . '</div>';
                                    }
                                }
                            } else {
                                echo '<div style="text-align:center; color:#888; margin-top:20px;">Halaman ini masih kosong...</div>';
                            }
                        } else {
                            echo '<div style="text-align:center; color:red;">Sesi tidak ditemukan atau bukan milik Anda.</div>';
                        }
                    } else {
                        // JIKA KERTAS BARU (DEFAULT)
                        echo '<div class="message bot">
                                <strong>Haloooooo '.htmlspecialchars($username).'!</strong><br>
                                Masih ragu antara ikut "passion" atau "kata ortu", coba ceritakan dulu kamu tertarik dibidang apa ?  🖊️
                              </div>';
                    }
                    ?>
                </div>

                <div class="loading-container" id="loadingArea">
                    <span id="loadingText">Sedang menulis</span><span class="dots"></span>
                </div>

                <div class="input-area">
                    <input type="hidden" id="currentSessionId" value="<?php echo htmlspecialchars($active_session_id); ?>">
                    
                    <input type="text" id="userInput" placeholder="Coretkan pesan di sini..." autocomplete="off">
                    <button class="btn-send" onclick="sendMessage()">→</button>
                </div>
            </div>
        </div>

        <script>
            const chatBox = document.getElementById('chatBox');
            const userInput = document.getElementById('userInput');
            const loadingArea = document.getElementById('loadingArea');
            const loadingText = document.getElementById('loadingText');
            const sessionInput = document.getElementById('currentSessionId');

            const sketchPhrases = [
                "Sedang mengukir jawaban", "Mencari referensi di buku tua",
                "Menajamkan pensil", "Membalik halaman", "Meracik kata-kata"
            ];

            // Auto scroll bawah
            window.onload = function() {
                chatBox.scrollTo({ top: chatBox.scrollHeight, behavior: 'smooth' });
            };

            userInput.addEventListener("keypress", function(event) {
                if (event.key === "Enter") sendMessage();
            });

            function appendMessage(text, sender) {
                const div = document.createElement('div');
                div.classList.add('message', sender);
                let formatted = text.replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>').replace(/\n/g, '<br>');
                div.innerHTML = formatted; 
                chatBox.appendChild(div);
                chatBox.scrollTo({ top: chatBox.scrollHeight, behavior: 'smooth' });
            }

            function sendMessage() {
                const message = userInput.value.trim();
                const currentSession = sessionInput.value;

                if (!message) return;

                appendMessage(message, 'user');
                userInput.value = '';
                userInput.disabled = true;
                
                const randomPhrase = sketchPhrases[Math.floor(Math.random() * sketchPhrases.length)];
                loadingText.innerText = randomPhrase;
                loadingArea.style.display = 'block';
                chatBox.scrollTo({ top: chatBox.scrollHeight, behavior: 'smooth' });

                fetch('api_chat.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ 
                        message: message,
                        session_id: currentSession // Kirim ID Sesi
                    })
                })
                .then(response => response.json())
                .then(data => {
                    loadingArea.style.display = 'none';
                    userInput.disabled = false;
                    userInput.focus();

                    if (data.status === 'success') {
                        appendMessage(data.response, 'bot');
                        
                        // JIKA INI CHAT PERTAMA DI KERTAS BARU:
                        // Reload halaman agar URL berubah jadi ?session_id=123 dan muncul di sidebar
                        if (!currentSession && data.session_id) {
                            window.location.href = "dashboard.php?session_id=" + data.session_id;
                        }

                    } else {
                        appendMessage("Maaf, tinta habis (Error: " + data.message + ")", 'bot');
                    }
                })
                .catch(error => {
                    loadingArea.style.display = 'none';
                    userInput.disabled = false;
                    appendMessage("Koneksi terputus...", 'bot');
                });
            }
        </script>
    <?php endif; ?>

</div>

</body>
</html>