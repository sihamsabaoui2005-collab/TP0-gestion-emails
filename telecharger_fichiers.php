<?php
// TP-0 - Partie 2 : page de telechargement des fichiers de resultats
// Securite : le nom demande est compare via basename() a la liste reelle
// des fichiers de resultats/ (scandir), jamais de chemin ../ ou absolu accepte.

$dossierResultats = __DIR__ . '/resultats';

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

// si un fichier est demande en telechargement, on ne repond QUE si son nom
// (nettoye par basename) figure exactement dans la liste reelle ci-dessus
if (isset($_GET['fichier'])) {
    $nomDemande = basename($_GET['fichier']);

    if (in_array($nomDemande, $fichiersDisponibles, true)) {
        $cheminFichier = $dossierResultats . '/' . $nomDemande;

        header('Content-Type: text/plain');
        header('Content-Disposition: attachment; filename="' . $nomDemande . '"');
        header('Content-Length: ' . filesize($cheminFichier));
        readfile($cheminFichier);
        exit;
    } else {
        // nom invalide ou fichier inexistant : on ignore et on reaffiche la page normalement
        $erreurTelechargement = "Fichier demande introuvable.";
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<title>Télécharger les fichiers - EmailHub</title>
<link rel="stylesheet" href="style.css">
</head>
<body class="accent-violet">
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
            <img src="icons/telecharger_fichiers.png" alt="">
        </div>
        <h2>Télécharger les fichiers</h2>
        <p class="explication">Fichiers generes par le dernier traitement.</p>

        <?php if (empty($fichiersDisponibles)): ?>
            <div class="message erreur">Lancez d'abord le traitement.</div>
        <?php else: ?>
            <ul class="liste-fichiers">
                <?php foreach ($fichiersDisponibles as $nomFichier): ?>
                    <li>
                        <span><?= htmlspecialchars($nomFichier) ?></span>
                        <a href="telecharger_fichiers.php?fichier=<?= urlencode($nomFichier) ?>">Télécharger</a>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>

        <?php if (isset($erreurTelechargement)): ?>
            <div class="message erreur"><?= htmlspecialchars($erreurTelechargement) ?></div>
        <?php endif; ?>
    </div>

</div>
</body>
</html>
