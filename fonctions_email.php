<?php
require "config.php";
require "PHPMailer/Exception.php";
require "PHPMailer/PHPMailer.php";
require "PHPMailer/SMTP.php";

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

function envoyerEmail($destinataire, $objet, $message, $piecesJointes = []) {

    $mail = new PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = EMAIL_EXPEDITEUR;
        $mail->Password   = EMAIL_MOT_DE_PASSE;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;
        $mail->CharSet    = 'UTF-8';

        $mail->setFrom(EMAIL_EXPEDITEUR, 'EmailHub');
        $mail->addAddress($destinataire);

        $mail->Subject = $objet;
        $mail->Body    = $message;

        foreach ($piecesJointes as $chemin) {
            if (file_exists($chemin)) {
                $mail->addAttachment($chemin);
            }
        }

        $mail->send();
        return true;

    } catch (Exception $e) {
        echo "Erreur d'envoi : {$mail->ErrorInfo}<br>";
        return false;
    }
}
