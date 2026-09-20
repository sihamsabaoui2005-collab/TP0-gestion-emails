<?php
// TP-0 - Partie 2 : envoi d'une selection de fichiers de resultats/ par email (SMTP, PHPMailer)
// Securite : les fichiers coches sont revalides avec basename() + file_exists() contre le
// contenu reel de resultats/, on ne fait jamais confiance aux noms envoyes par le formulaire.

require __DIR__ . '/vendor/phpmailer/src/Exception.php';
require __DIR__ . '/vendor/phpmailer/src/PHPMailer.php';
require __DIR__ . '/vendor/phpmailer/src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception as ExceptionPHPMailer;

$dossierResultats = __DIR__ . '/resultats';
$cheminConfig = __DIR__ . '/config_locale.php';

// on construit la liste reelle des fichiers presents dans resultats/
$fichiersDisponibles = array();
if (is_dir($dossierResultats)) {
    foreach (scandir($dossierResultats) as $nomFichier) {
        if ($nomFichier === '.' || $nomFichier === '..') {
            continue;
        }
        if (is_file($dossierResultats . '/' . $nomFichier)) {
            $fichiersDisponibles[] = $nomFichier;
        }
    }
}

$message = '';
$typeMessage = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $adresseDestinataire = trim($_POST['adresseDestinataire'] ?? '');
    $fichiersCoches = $_POST['fichiers'] ?? array();

    // on rejette tout saut de ligne dans l'adresse (evite l'injection d'en-tetes email)
    if (strpbrk($adresseDestinataire, "\r\n") !== false) {
        $message = "L'adresse ne doit pas contenir de saut de ligne.";
        $typeMessage = 'erreur';
    } elseif (!filter_var($adresseDestinataire, FILTER_VALIDATE_EMAIL)) {
        $message = "Adresse email destinataire invalide.";
        $typeMessage = 'erreur';
    } elseif (!is_array($fichiersCoches) || count($fichiersCoches) === 0) {
        $message = "Veuillez cocher au moins un fichier.";
        $typeMessage = 'erreur';
    } elseif (!file_exists($cheminConfig)) {
        $message = "Configuration SMTP manquante (config_locale.php absent).";
        $typeMessage = 'erreur';
    } else {
        // on ne garde que les noms coches qui existent vraiment dans resultats/
        $fichiersAEnvoyer = array();
        foreach ($fichiersCoches as $nomCoche) {
            $nomNettoye = basename($nomCoche);
            $cheminComplet = $dossierResultats . '/' . $nomNettoye;
            if (in_array($nomNettoye, $fichiersDisponibles, true) && file_exists($cheminComplet)) {
                $fichiersAEnvoyer[] = $cheminComplet;
            }
        }

        if (count($fichiersAEnvoyer) === 0) {
            $message = "Aucun fichier valide selectionne.";
            $typeMessage = 'erreur';
        } else {
            $config = require $cheminConfig;

            $mailer = new PHPMailer(true);
            try {
                $mailer->isSMTP();
                $mailer->Host = $config['smtpHote'];
                $mailer->Port = $config['smtpPort'];
                $mailer->SMTPAuth = true;
                $mailer->Username = $config['smtpUtilisateur'];
                $mailer->Password = $config['smtpMotDePasse'];
                $mailer->CharSet = 'UTF-8';

                $mailer->setFrom($config['adresseExpediteur'], $config['nomExpediteur']);
                $mailer->addAddress($adresseDestinataire);

                $mailer->Subject = 'Fichiers de resultats - EmailHub';
                $mailer->Body = 'Veuillez trouver ci-joint les fichiers demandes.';

                foreach ($fichiersAEnvoyer as $cheminPieceJointe) {
                    $mailer->addAttachment($cheminPieceJointe);
                }

                $mailer->send();

                $message = 'Fichiers envoyes avec succes a ' . $adresseDestinataire . '.';
                $typeMessage = 'succes';
            } catch (ExceptionPHPMailer $e) {
                $message = "Echec de l'envoi : " . $mailer->ErrorInfo;
                $typeMessage = 'erreur';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<title>Envoyer les fichiers - EmailHub</title>
<link rel="stylesheet" href="style.css">
</head>
<body class="accent-orange">
<div class="conteneur">

    <div class="entete">
        <div class="entete-logo">
            <div class="logo">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none"><path d="M3 6h18v12H3z" stroke="white" stroke-width="2" stroke-linejoin="round"/><path d="M3 7l9 6 9-6" stroke="white" stroke-width="2" stroke-linejoin="round"/></svg>
            </div>
            <h1>EmailHub</h1>
        </div>
        <a href="index.php" class="retour">Retour à l'accueil</a>
    </div>

    <div class="carte-page">
        <div class="badge">
            <img src="icons/envoyer_fichiers.png" alt="">
        </div>
        <h2>Envoyer les fichiers</h2>
        <p class="explication">Choisissez les fichiers a envoyer en piece jointe a une adresse email.</p>

        <?php if (empty($fichiersDisponibles)): ?>
            <div class="message erreur">Lancez d'abord le traitement.</div>
        <?php else: ?>
            <form method="post">
                <div class="liste-cases">
                    <?php foreach ($fichiersDisponibles as $nomFichier): ?>
                        <label>
                            <input type="checkbox" name="fichiers[]" value="<?= htmlspecialchars($nomFichier) ?>">
                            <?= htmlspecialchars($nomFichier) ?>
                        </label>
                    <?php endforeach; ?>
                </div>

                <div class="champ">
                    <label for="adresseDestinataire">Adresse destinataire</label>
                    <input type="email" id="adresseDestinataire" name="adresseDestinataire" required>
                </div>

                <button type="submit" class="bouton">Envoyer</button>
            </form>
        <?php endif; ?>

        <?php if ($message !== ''): ?>
            <div class="message <?= htmlspecialchars($typeMessage) ?>"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>
    </div>

</div>
</body>
</html>
