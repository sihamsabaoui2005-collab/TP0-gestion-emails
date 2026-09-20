<?php
// TP-0 - Partie 2 : page de televersement du fichier d'emails
// Sauvegarde toujours le fichier envoye sous depots/emails.txt (le nom d'origine est ignore),
// pour que le reste de l'application ait toujours un seul chemin fixe a lire.

$dossierDepots = __DIR__ . '/depots';
$cheminDestination = $dossierDepots . '/emails.txt';

$tailleMaxOctets = 2 * 1024 * 1024; // 2 Mo, largement suffisant pour une liste d'emails

$message = '';
$typeMessage = ''; // 'succes' ou 'erreur'

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!isset($_FILES['fichierEmails']) || $_FILES['fichierEmails']['error'] === UPLOAD_ERR_NO_FILE) {
        $message = "Veuillez choisir un fichier .txt avant d'envoyer.";
        $typeMessage = 'erreur';
    } elseif ($_FILES['fichierEmails']['error'] !== UPLOAD_ERR_OK) {
        $message = "Le televersement a echoue, veuillez reessayer.";
        $typeMessage = 'erreur';
    } elseif ($_FILES['fichierEmails']['size'] > $tailleMaxOctets) {
        $message = "Le fichier est trop volumineux (limite : 2 Mo).";
        $typeMessage = 'erreur';
    } else {
        // on verifie l'extension .txt sur le nom d'origine (le champ HTML accept=".txt" n'est qu'une aide visuelle)
        $nomOriginal = $_FILES['fichierEmails']['name'];
        $extension = strtolower(pathinfo($nomOriginal, PATHINFO_EXTENSION));

        if ($extension !== 'txt') {
            $message = "Seuls les fichiers .txt sont acceptes.";
            $typeMessage = 'erreur';
        } else {
            // le dossier depots peut ne pas encore exister au tout premier lancement
            if (!is_dir($dossierDepots)) {
                mkdir($dossierDepots, 0777, true);
            }

            if (move_uploaded_file($_FILES['fichierEmails']['tmp_name'], $cheminDestination)) {
                $message = "Fichier televerse avec succes. Il remplace la liste courante (depots/emails.txt).";
                $typeMessage = 'succes';
            } else {
                $message = "Impossible d'enregistrer le fichier sur le serveur.";
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
<title>Televerser - EmailHub</title>
<link rel="stylesheet" href="style.css">
</head>
<body class="accent-bleu">
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
            <svg width="26" height="26" viewBox="0 0 24 24" fill="none"><path d="M12 16V8m0 0l-3 3m3-3l3 3" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><path d="M7 18a4 4 0 01-.6-7.96A5 5 0 0117 9.06 3.5 3.5 0 0116.5 16H7z" stroke="white" stroke-width="2"/></svg>
        </div>
        <h2>Téléverser la liste d'emails</h2>
        <p class="explication">Choisissez un fichier .txt : il remplacera la liste courante (depots/emails.txt).</p>

        <form method="post" enctype="multipart/form-data">
            <div class="champ">
                <label for="fichierEmails">Fichier .txt</label>
                <input type="file" id="fichierEmails" name="fichierEmails" accept=".txt" required>
            </div>
            <button type="submit" class="bouton">Téléverser</button>
        </form>

        <?php if ($message !== ''): ?>
            <div class="message <?= htmlspecialchars($typeMessage) ?>">
                <?= htmlspecialchars($message) ?>
                <?php if ($typeMessage === 'succes'): ?>
                    <div style="margin-top:14px;">
                        <a href="lancer_traitement.php" class="bouton" style="display:inline-block;">Lancer le traitement →</a>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>

</div>
</body>
</html>
