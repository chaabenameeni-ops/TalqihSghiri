<?php
/**
 * TalqihSghiri - Chatbot Proxy
 * Ce fichier fait le pont entre votre interface et Google Dialogflow
 */

// إظهار الأخطاء مؤقتاً لمساعدتنا في اكتشاف أي مشكلة في الربط
error_reporting(E_ALL);
ini_set('display_errors', 1);

// تحميل مكتبات Composer - تأكدي أن مجلد vendor موجود في نفس المسار
require_once __DIR__ . '/vendor/autoload.php';

use Google\Cloud\Dialogflow\V2\SessionsClient;
use Google\Cloud\Dialogflow\V2\TextInput;
use Google\Cloud\Dialogflow\V2\QueryInput;

header('Content-Type: application/json');

// استقبال الرسالة من JavaScript (dashboard.php)
$input = json_decode(file_get_contents('php://input'), true);
$userMessage = $input['message'] ?? '';

if ($userMessage) {
    try {
        // إعدادات الاتصال بـ Google Cloud
        // تأكدي أن ملف key.json موجود في مجلد pfe بجانب هذا الملف
        $credentialsPath = __DIR__ . '/key.json'; 
        
        $sessionsClient = new SessionsClient([
            'credentials' => $credentialsPath
        ]);

        // استخدام الـ Project ID الخاص بك: chatbot-490918
        $projectId = 'chatbot-490918';
        $sessionId = 'pfe-session-' . uniqid(); // إنشاء معرف جلسة فريد
        $sessionName = $sessionsClient->sessionName($projectId, $sessionId);

        // إعداد نص الاستعلام باللغة الفرنسية كما هو في مشروعك
        $textInput = new TextInput();
        $textInput->setText($userMessage);
        $textInput->setLanguageCode('fr');

        $queryInput = new QueryInput();
        $queryInput->setText($textInput);

        // إرسال الطلب إلى Dialogflow
        $response = $sessionsClient->detectIntent($sessionName, $queryInput);
        $queryResult = $response->getQueryResult();
        $fulfillmentText = $queryResult->getFulfillmentText();

        // إرسال الرد النهائي للمتصفح
        echo json_encode([
            'reply' => $fulfillmentText ? $fulfillmentText : "Je n'ai pas compris, pouvez-vous répéter ?"
        ]);

        $sessionsClient->close();

    } catch (Exception $e) {
        // في حال حدوث خطأ، سنرسل تفاصيل الخطأ لنفهم المشكلة
        http_response_code(500);
        echo json_encode([
            'reply' => "Erreur technique: " . $e->getMessage()
        ]);
    }
} else {
    echo json_encode(['reply' => "Aucun message reçu."]);
}