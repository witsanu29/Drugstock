<?php
/**
 * ==============================
 * 📢 Telegram Notify Helper
 * ==============================
 * ใช้เรียก: sendTelegram("ข้อความ")
 */

function sendTelegram($message) {

    $botToken = "8208291606:AAELVUF_t652KSiYBi7F4MWe2_3WlK931TY";  // 🔴 ใส่ Token ใหม่
    $chatId   = "7297350083";

    $url = "https://api.telegram.org/bot{$botToken}/sendMessage";

    $postData = [
        'chat_id' => $chatId,
        'text' => $message,
        'parse_mode' => 'HTML'
    ];

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);

    $response = curl_exec($ch);

    if (curl_errno($ch)) {
        error_log("Telegram Error: " . curl_error($ch));
    }

    curl_close($ch);
}