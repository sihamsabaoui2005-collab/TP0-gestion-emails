<?php
// TP-0 - Partie 2 : envoi d'un message (sujet + contenu + piece jointe optionnelle)
// a une selection de destinataires issus de resultats/Emailst.txt, plus une adresse manuelle.

require __DIR__ . '/vendor/phpmailer/src/Exception.php';
require __DIR__ . '/vendor/phpmailer/src/PHPMailer.php';
require __DIR__ . '/vendor/phpmailer/src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception as ExceptionPHPMailer;

$cheminListeValides = __DIR__ . '/resultats/Emailst.txt';
$cheminConfig = __DIR__ . '/config_locale.php';
$tailleMaxPieceJointe = 5 * 1024 * 1024; // 5 Mo, upload libre mais raisonnable

// on lit les destinataires possibles depuis Emailst.txt (une ligne = une adresse)
$destinatairesPossibles = array();
if (file_exists($cheminListeValides)) {
    $destinatairesPossibles = file($cheminListeValides, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if ($destinatairesPossibles === false) {
        $destinatairesPossibles = array();
    }
}

$message = '';
$typeMessage = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $sujet = trim($_POST['sujet'] ?? '');
    $contenu = trim($_POST['contenu'] ?? '');
    $adresseManuelle = trim($_POST['adresseManuelle'] ?? '');
    $destinatairesCoches = $_POST['destinataires'] ?? array();

    // on construit la liste finale des destinataires : cases cochees + adresse manuelle
    $destinatairesFinaux = array();

    if (is_array($destinatairesCoches)) {
        foreach ($destinatairesCoches as $adresse) {
            // on ne garde que les adresses reellement presentes dans Emailst.txt
            if (in_array($adresse, $destinatairesPossibles, true)) {
                $destinatairesFinaux[] = $adresse;
            }
        }
    }

    if ($adresseManuelle !== '') {
        $destinatairesFinaux[] = $adresseManuelle;
    }

    $destinatairesFinaux = array_unique($destinatairesFinaux);

    // on rejette tout saut de ligne dans le sujet et l'adresse manuelle (anti-injection d'en-tetes)
    if (strpbrk($sujet, "\r\n") !== false || strpbrk($adresseManuelle, "\r\n") !== false) {
        $message = "Le sujet et l'adresse ne doivent pas contenir de saut de ligne.";
        $typeMessage = 'erreur';
    } elseif ($sujet === '' || $contenu === '') {
        $message = "Le sujet et le contenu sont obligatoires.";
        $typeMessage = 'erreur';
    } elseif (count($destinatairesFinaux) === 0) {
        $message = "Veuillez choisir au moins un destinataire.";
        $typeMessage = 'erreur';
    } else {
        // on verifie que CHAQUE destinataire final est une adresse valide
        $adressesInvalides = array();
        foreach ($destinatairesFinaux as $adresse) {
            if (!filter_var($adresse, FILTER_VALIDATE_EMAIL)) {
                $adressesInvalides[] = $adresse;
            }
        }

        if (count($adressesInvalides) > 0) {
            $message = 'Adresse(s) invalide(s) : ' . implode(', ', $adressesInvalides);
            $typeMessage = 'erreur';
        } elseif (!file_exists($cheminConfig)) {
            $message = "Configuration SMTP manquante (config_locale.php absent).";
            $typeMessage = 'erreur';
        } else {
            // piece jointe optionnelle : on verifie sa taille si elle est fournie
            $cheminPieceJointeTemp = null;
            $nomPieceJointe = null;
            $erreurPieceJointe = false;

            if (isset($_FILES['pieceJointe']) && $_FILES['pieceJointe']['error'] !== UPLOAD_ERR_NO_FILE) {
                if ($_FILES['pieceJointe']['error'] !== UPLOAD_ERR_OK) {
                    $message = "Le televersement de la piece jointe a echoue.";
                    $typeMessage = 'erreur';
                    $erreurPieceJointe = true;
                } elseif ($_FILES['pieceJointe']['size'] > $tailleMaxPieceJointe) {
                    $message = "La piece jointe est trop volumineuse (limite : 5 Mo).";
                    $typeMessage = 'erreur';
                    $erreurPieceJointe = true;
                } else {
                    $cheminPieceJointeTemp = $_FILES['pieceJointe']['tmp_name'];
                    $nomPieceJointe = basename($_FILES['pieceJointe']['name']);
                }
            }

            if (!$erreurPieceJointe) {
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
                    foreach ($destinatairesFinaux as $adresse) {
                        $mailer->addAddress($adresse);
                    }

                    $mailer->Subject = $sujet;
                    $mailer->Body = $contenu;

                    if ($cheminPieceJointeTemp !== null) {
                        $mailer->addAttachment($cheminPieceJointeTemp, $nomPieceJointe);
                    }

                    $mailer->send();

                    $message = 'Message envoye avec succes a ' . count($destinatairesFinaux) . ' destinataire(s).';
                    $typeMessage = 'succes';
                } catch (ExceptionPHPMailer $e) {
                    $message = "Echec de l'envoi : " . $mailer->ErrorInfo;
                    $typeMessage = 'erreur';
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<title>Envoyer un message - EmailHub</title>
<link rel="stylesheet" href="style.css">
</head>
<body class="accent-cyan">
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
            <img src="icons/envoyer_message.png" alt="">
        </div>
        <h2>Envoyer un message</h2>
        <p class="explication">Redigez un message et choisissez ses destinataires.</p>

        <?php if (empty($destinatairesPossibles)): ?>
            <div class="message erreur">Lancez d'abord le traitement.</div>
        <?php else: ?>
            <form method="post" enctype="multipart/form-data">
                <div class="liste-cases">
                    <label>
                        <input type="checkbox" id="toutSelectionner">
                        <strong>Tout selectionner</strong>
                    </label>
                    <?php foreach ($destinatairesPossibles as $adresse): ?>
                        <label>
                            <input type="checkbox" class="case-destinataire" name="destinataires[]" value="<?= htmlspecialchars($adresse) ?>">
                            <?= htmlspecialchars($adresse) ?>
                        </label>
                    <?php endforeach; ?>
                </div>

                <div class="champ">
                    <label for="adresseManuelle">Ajouter une adresse (optionnel)</label>
                    <input type="text" id="adresseManuelle" name="adresseManuelle" placeholder="exemple@domaine.com">
                </div>

                <div class="champ">
                    <label for="sujet">Sujet</label>
                    <input type="text" id="sujet" name="sujet" required>
                </div>

                <div class="champ">
                    <label for="contenu">Contenu</label>
                    <textarea id="contenu" name="contenu" required></textarea>
                </div>

                <div class="champ">
                    <label for="pieceJointe">Piece jointe (optionnel)</label>
                    <input type="file" id="pieceJointe" name="pieceJointe">
                </div>

                <button type="submit" class="bouton">Envoyer</button>
            </form>

            <script>
                // pas de logique metier ici : juste une commodite d'interface (cocher/decocher tout)
                document.getElementById('toutSelectionner').addEventListener('change', function () {
                    document.querySelectorAll('.case-destinataire').forEach(function (case_) {
                        case_.checked = this.checked;
                    }, this);
                });
            </script>
        <?php endif; ?>

        <?php if ($message !== ''): ?>
            <div class="message <?= htmlspecialchars($typeMessage) ?>"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>
    </div>

</div>
</body>
</html>
