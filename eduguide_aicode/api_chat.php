<?php
session_start();
include 'db.php';
header('Content-Type: application/json');

// --- KONFIGURASI ---
// Masukkan API KEY Groq
$apiKey = "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";
$modelName = "llama-3.3-70b-versatile";
// -------------------

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Silakan login terlebih dahulu']);
    exit;
}

$inputJSON = file_get_contents('php://input');
$input = json_decode($inputJSON, TRUE);
$user_message = isset($input['message']) ? $input['message'] : '';
// Tangkap Session ID (jika user sedang membuka chat lama)
$session_id   = isset($input['session_id']) ? $input['session_id'] : null;
$user_id      = $_SESSION['user_id'];

if (empty($user_message)) {
    echo json_encode(['status' => 'error', 'message' => 'Pesan kosong']);
    exit;
}

// --- 1. LOGIKA SESI (PENTING!) ---
// Jika tidak ada session_id (artinya user di "Kertas Baru"), kita buat sesi baru di DB
if (!$session_id) {
    // Judul otomatis dari 30 huruf pertama pesan user
    $title = substr($user_message, 0, 30) . "...";
    
    $stmt_sess = $conn->prepare("INSERT INTO chat_sessions (user_id, title) VALUES (?, ?)");
    $stmt_sess->bind_param("is", $user_id, $title);
    if ($stmt_sess->execute()) {
        $session_id = $conn->insert_id; // Ambil ID sesi yang baru dibuat
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Gagal membuat sesi']);
        exit;
    }
}

// --- 2. AMBIL PERSONA (WATAK AI) ---
$system_instruction = "Kamu adalah asisten yang membantu."; // Default

$sql_persona = "SELECT setting_value FROM settings WHERE setting_key = 'system_prompt' LIMIT 1";
$res_persona = $conn->query($sql_persona);

if ($res_persona && $res_persona->num_rows > 0) {
    $row_p = $res_persona->fetch_assoc();
    $system_instruction = $row_p['setting_value'];
}

// Siapkan Array Pesan Awal
$messages = [
    [
        "role" => "system",
        "content" => $system_instruction
    ]
];

// --- 3. AMBIL HISTORY CHAT (KHUSUS SESI INI) ---
// Kita hanya mengambil chat yang session_id-nya COCOK.
$sql = "SELECT user_message, bot_response FROM chats WHERE session_id = ? ORDER BY id DESC LIMIT 10";
$stmt_history = $conn->prepare($sql);
$stmt_history->bind_param("i", $session_id);
$stmt_history->execute();
$result_history = $stmt_history->get_result();

$history_chunk = [];

while ($row = $result_history->fetch_assoc()) {
    $history_chunk[] = ["role" => "user", "content" => $row['user_message']];
    // Pastikan bot response tidak null
    $bot_reply = $row['bot_response'] ? $row['bot_response'] : "...";
    $history_chunk[] = ["role" => "assistant", "content" => $bot_reply];
}

// Balik urutan (Masa lalu -> Masa kini)
$history_chunk = array_reverse($history_chunk);

// Gabungkan ke array utama
$messages = array_merge($messages, $history_chunk);

// --- 4. TAMBAHKAN PESAN BARU ---
$messages[] = [
    "role" => "user",
    "content" => $user_message
];

// --- 5. KIRIM KE GROQ ---
$apiUrl = "https://api.groq.com/openai/v1/chat/completions";

$payload = [
    "model" => $modelName,
    "messages" => $messages,
    "temperature" => 0.7
];

$ch = curl_init($apiUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Content-Type: application/json",
    "Authorization: Bearer " . $apiKey
]);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
// Matikan verifikasi SSL untuk localhost (opsional, nyalakan di hosting)
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);
curl_close($ch);

// --- 6. PROSES JAWABAN ---
$bot_response = "";
$status = "success";

if ($curlError) {
    $status = "error";
    $bot_response = "Koneksi Error: " . $curlError;
} elseif ($httpCode != 200) {
    $status = "error";
    $jsonResp = json_decode($response, true);
    $errMsg = isset($jsonResp['error']['message']) ? $jsonResp['error']['message'] : $response;
    $bot_response = "Error API ($httpCode): " . $errMsg;
} else {
    $responseData = json_decode($response, true);
    if (isset($responseData['choices'][0]['message']['content'])) {
        $bot_response = $responseData['choices'][0]['message']['content'];
    } else {
        $bot_response = "Maaf, AI diam saja.";
    }
}

// --- 7. SIMPAN CHAT KE DB (DENGAN SESSION ID) ---
if ($status == "success") {
    $final_response = $bot_response ?: "No response";
    // Perhatikan: Kolom session_id ditambahkan di sini
    $stmt = $conn->prepare("INSERT INTO chats (user_id, session_id, user_message, bot_response) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("iiss", $user_id, $session_id, $user_message, $final_response);
    $stmt->execute();
}

// --- 8. RESPONSE KE FRONTEND ---
echo json_encode([
    'status' => 'success',
    'response' => $bot_response,
    'session_id' => $session_id, // Penting: Kirim balik ID sesi agar JS bisa update URL
    'timestamp' => date('H:i')
]);
?>