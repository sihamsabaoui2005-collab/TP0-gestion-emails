<?php
require "fonctions_email.php";

$dossierResultats = __DIR__ . '/resultats';
$fichiersDisponibles = array();
if (is_dir($dossierResultats)) {
    foreach (scandir($dossierResultats) as $nomFichier) {
        if ($nomFichier === '.' || $nomFichier === '..') continue;
        if (is_file($dossierResultats . '/' . $nomFichier)) {
            $fichiersDisponibles[] = $nomFichier;
        }
    }
}

$message = '';
$typeMessage = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $destinataire = trim($_POST['destinataire'] ?? '');
    $fichiersChoisis = $_POST['fichiers'] ?? [];

    if (!filter_var($destinataire, FILTER_VALIDATE_EMAIL)) {
        $message = "Adresse email invalide.";
        $typeMessage = 'erreur';
    } elseif (empty($fichiersChoisis)) {
        $message = "Choisissez au moins un fichier.";
        $typeMessage = 'erreur';
    } else {
        $chemins = array();
        foreach ($fichiersChoisis as $nomCoche) {
            $nomNettoye = basename($nomCoche);
            if (in_array($nomNettoye, $fichiersDisponibles, true)) {
                $chemins[] = $dossierResultats . '/' . $nomNettoye;
            }
        }
        if (empty($chemins)) {
            $message = "Aucun fichier valide sélectionné.";
            $typeMessage = 'erreur';
        } else {
            $ok = envoyerEmail($destinataire, "Fichiers générés - EmailHub", "Bonjour,\n\nVeuillez trouver ci-joint les fichiers demandés.", $chemins);
            $message = $ok ? "Fichiers envoyés à $destinataire." : "Échec de l'envoi.";
            $typeMessage = $ok ? 'succes' : 'erreur';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<title>Envoyer les fichiers - EmailHub</title>
<link rel="stylesheet" href="style_pages.css">
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
        <p class="explication">Choisissez les fichiers à envoyer en pièce jointe à une adresse email.</p>

        <?php if (empty($fichiersDisponibles)): ?>
            <div class="message erreur">Lancez d'abord le traitement.</div>
        <?php else: ?>
            <form method="post">
                <div class="liste-cases">
                    <?php foreach ($fichiersDisponibles as $f): ?>
                        <label>
                            <input type="checkbox" name="fichiers[]" value="<?= htmlspecialchars($f) ?>">
                            <?= htmlspecialchars($f) ?>
                        </label>
                    <?php endforeach; ?>
                </div>
                <div class="champ">
                    <label for="destinataire">Adresse destinataire</label>
                    <input type="email" id="destinataire" name="destinataire" required>
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
