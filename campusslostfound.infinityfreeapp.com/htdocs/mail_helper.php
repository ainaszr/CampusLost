<?php
require_once __DIR__ . '/email_config.php';

function campuslost_send_email($toEmail, $toName, $subject, $htmlContent) {
    global $BREVO_API_KEY, $CAMPUSLOST_SENDER_EMAIL, $CAMPUSLOST_SENDER_NAME;
    if (!$BREVO_API_KEY || $BREVO_API_KEY === 'PASTE_YOUR_BREVO_API_KEY_HERE') {
        error_log('CampusLost email skipped: Brevo API key is not configured.');
        return false;
    }
    if (!filter_var($toEmail, FILTER_VALIDATE_EMAIL)) return false;

    $payload = json_encode([
        'sender' => ['name' => $CAMPUSLOST_SENDER_NAME, 'email' => $CAMPUSLOST_SENDER_EMAIL],
        'to' => [['email' => $toEmail, 'name' => $toName]],
        'subject' => $subject,
        'htmlContent' => $htmlContent
    ]);

    $ch = curl_init('https://api.brevo.com/v3/smtp/email');
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            'accept: application/json',
            'api-key: ' . $BREVO_API_KEY,
            'content-type: application/json'
        ],
        CURLOPT_POSTFIELDS => $payload,
        CURLOPT_TIMEOUT => 15
    ]);
    $response = curl_exec($ch);
    $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    if ($response === false || $httpCode < 200 || $httpCode >= 300) {
        error_log('CampusLost Brevo email failed. HTTP ' . $httpCode . ' Response: ' . (string)$response);
        curl_close($ch);
        return false;
    }
    curl_close($ch);
    return true;
}

function campuslost_email_user_by_id($conn, $userId, $subject, $message) {
    $stmt = $conn->prepare('SELECT name, email FROM users WHERE user_id = ? LIMIT 1');
    if (!$stmt) return false;
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $stmt->close();
    if (!$user) return false;
    $safeName = htmlspecialchars($user['name'], ENT_QUOTES, 'UTF-8');
    $safeMessage = nl2br(htmlspecialchars($message, ENT_QUOTES, 'UTF-8'));
    $html = '<div style="font-family:Arial,sans-serif;max-width:600px;margin:auto">'
          . '<h2>CampusLost</h2><p>Hello ' . $safeName . ',</p><p>' . $safeMessage . '</p>'
          . '<p>Please log in to CampusLost to view the latest update.</p>'
          . '<p style="color:#64748b;font-size:12px">This is an automated CampusLost notification.</p></div>';
    return campuslost_send_email($user['email'], $user['name'], $subject, $html);
}

function campuslost_email_admins($conn, $subject, $message) {
    $result = $conn->query("SELECT name, email FROM users WHERE LOWER(role) = 'admin'");
    if (!$result) return false;
    $sent = false;
    while ($admin = $result->fetch_assoc()) {
        $safeName = htmlspecialchars($admin['name'], ENT_QUOTES, 'UTF-8');
        $safeMessage = nl2br(htmlspecialchars($message, ENT_QUOTES, 'UTF-8'));
        $html = '<div style="font-family:Arial,sans-serif;max-width:600px;margin:auto">'
              . '<h2>CampusLost</h2><p>Hello ' . $safeName . ',</p><p>' . $safeMessage . '</p>'
              . '<p>Please log in to the CampusLost admin dashboard to review it.</p>'
              . '<p style="color:#64748b;font-size:12px">This is an automated CampusLost notification.</p></div>';
        if (campuslost_send_email($admin['email'], $admin['name'], $subject, $html)) $sent = true;
    }
    return $sent;
}
?>
