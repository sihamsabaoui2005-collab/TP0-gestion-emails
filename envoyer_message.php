<?php
require "fonctions_email.php";

$cheminListeValides = __DIR__ . '/resultats/Emailst.txt';
$emailsValides = file_exists($cheminListeValides) ? file($cheminListeValides, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) : [];

$message = '';
$typeMessage = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $destinataires = $_POST['destinataires'] ?? [];
    $objet = trim($_POST['objet'] ?? '');
    $contenu = trim($_POST['contenu'] ?? '');

    $pieceJointe = [];
    if (isset($_FILES['piece_jointe']) && $_FILES['piece_jointe']['error'] === UPLOAD_ERR_OK) {
        $chemin = sys_get_temp_dir() . '/' . basename($_FILES['piece_jointe']['name']);
        move_uploaded_file($_FILES['piece_jointe']['tmp_name'], $chemin);
        $pieceJointe[] = $chemin;
    }

    if (empty($destinataires) || $objet === "" || $contenu === "") {
        $message = "Destinataire(s), objet et contenu sont obligatoires.";
        $typeMessage = 'erreur';
    } else {
        $compteur = 0;
        foreach ($destinataires as $email) {
            if (in_array($email, $emailsValides, true) && envoyerEmail($email, $objet, $contenu, $pieceJointe)) {
                $compteur++;
            }
        }
        $message = "Message envoyé à $compteur destinataire(s).";
        $typeMessage = $compteur > 0 ? 'succes' : 'erreur';
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<title>Envoyer un message - EmailHub</title>
<link rel="stylesheet" href="style_pages.css">
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
        <p class="explication">Rédigez un message et choisissez ses destinataires.</p>

        <?php if (empty($emailsValides)): ?>
            <div class="message erreur">Lancez d'abord le traitement.</div>
        <?php else: ?>
            <form method="post" enctype="multipart/form-data">
                <div class="liste-cases">
                    <label>
                        <input type="checkbox" id="toutSelectionner">
                        <strong>Tout sélectionner</strong>
                    </label>
                    <?php foreach ($emailsValides as $email): ?>
                        <label>
                            <input type="checkbox" class="case-destinataire" name="destinataires[]" value="<?= htmlspecialchars($email) ?>">
                            <?= htmlspecialchars($email) ?>
                        </label>
                    <?php endforeach; ?>
                </div>
                <div class="champ">
                    <label for="objet">Objet</label>
                    <input type="text" id="objet" name="objet" required>
                </div>
                <div class="champ">
                    <label for="contenu">Contenu</label>
                    <textarea id="contenu" name="contenu" required></textarea>
                </div>
                <div class="champ">
                    <label for="piece_jointe">Pièce jointe (optionnel)</label>
                    <input type="file" id="piece_jointe" name="piece_jointe">
                </div>
                <button type="submit" class="bouton">Envoyer le message</button>
            </form>
            <script>
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
