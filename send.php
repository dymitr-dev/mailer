<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

require(__DIR__ . '/vendor/autoload.php');
require(__DIR__ . '/config.php');

send();

function send(): void
{
    $subject = $_REQUEST['subject'];
    $referrer = $_SERVER['HTTP_REFERER'];
    $ip = getSenderIp();
    $name = $_REQUEST['name'];
    $email = $_REQUEST['email'];
    $message = $_REQUEST['message'];
    $gRecaptchaResponse = $_REQUEST['gRecaptchaResponse'];

    $isDataValid = isset($subject) && isset($email) && isset($message);

    if (RECAPTCHA_ENABLED) {
        verifyRecaptcha($gRecaptchaResponse, $ip);
    }

    if ($isDataValid) {
        sendEmail(SMTP::DEBUG_OFF, $subject, $referrer, $ip, $name, $email, $message);

        if (DATA_SAVING_ENABLED) {
            saveDataToDb($referrer, $ip, $name, $email, $message);
        }
    } else {
        error_log('Incorrect input data!');
    }

    exit;
}

function verifyRecaptcha($gRecaptchaResponse, $ip): void
{
    $recaptcha = new ReCaptcha\ReCaptcha(RECAPTCHA_SECRET_KEY);
    $resp = $recaptcha->verify($gRecaptchaResponse, $ip);
    if (!$resp->isSuccess()) {
        error_log('reCAPTCHA Error: ' . implode(', ', $resp->getErrorCodes()));
        exit;
    }
}

function sendEmail($debugMode, $subject, $referrer, $ip, $name, $email, $message): void
{
    try {
        $mail = new PHPMailer(true);
        $mail->SMTPDebug = $debugMode;
        $mail->isSMTP();
        $mail->Host = SMTP_HOST;
        $mail->SMTPAuth = true;
        $mail->Username = SMTP_USER;
        $mail->Password = SMTP_PASS;
        $mail->SMTPSecure = 'tls';
        $mail->Port = SMTP_PORT;
        $mail->SMTPOptions = [
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true
            ]
        ];
        $mail->isHTML(true);
        $mail->addAddress(RECIPIENT_ADDRESS, RECIPIENT_NAME);
        $mail->Subject = $subject;
        $mail->Body = '';

        $mail->setFrom(SMTP_USER, $name);
        $mail->addReplyTo($email, $name);

        $mail->Body .= '<b>Referrer: </b>' . $referrer . '<br>';
        $mail->Body .= '<b>IP: </b>' . $ip . '<br>';
        $mail->Body .= '<br>';
        $mail->Body .= '<b>Name: </b>' . $name . '<br>';
        $mail->Body .= '<b>Email: </b>' . $email . '<br>';
        $mail->Body .= '<b>Message: </b>' . str_replace('\n', '<br>', $message);

        $mail->send();
    } catch (Exception $e) {
        error_log("Mailer Error: {$mail->ErrorInfo}");
    }
}

function saveDataToDb($referrer, $ip, $name, $email, $message): void
{
    try {
        $db = new PDO('mysql:host=' . DB_HOST . ';port=' . DB_PORT . ';dbname=' . DB_NAME . ';charset=utf8mb4', DB_USER, DB_PASS);
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $stmtInsertContactMessage = $db->prepare('INSERT INTO contact_form_messages(referrer, ip, name, email, message)'
            . ' VALUES(:referrer, :ip, :name, :email, :message)');
        $stmtInsertContactMessage->execute([
            ':referrer' => $referrer,
            ':ip' => $ip,
            ':name' => $name,
            ':email' => $email,
            ':message' => $message
        ]);
    } catch (PDOException $ex) {
        error_log($ex);
    }
}

function getSenderIp(): string
{
    if ($_SERVER['HTTP_CLIENT_IP']) {
        $ip = $_SERVER['HTTP_CLIENT_IP'];
    } elseif ($_SERVER['HTTP_X_FORWARDED_FOR']) {
        $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
    } elseif ($_SERVER['HTTP_X_FORWARDED']) {
        $ip = $_SERVER['HTTP_X_FORWARDED'];
    } elseif ($_SERVER['HTTP_FORWARDED_FOR']) {
        $ip = $_SERVER['HTTP_FORWARDED_FOR'];
    } elseif ($_SERVER['HTTP_FORWARDED']) {
        $ip = $_SERVER['HTTP_FORWARDED'];
    } elseif ($_SERVER['REMOTE_ADDR']) {
        $ip = $_SERVER['REMOTE_ADDR'];
    } else {
        $ip = 'UNKNOWN';
    }
    return $ip;
}
