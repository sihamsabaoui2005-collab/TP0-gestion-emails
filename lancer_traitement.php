<?php
// TP-0 - Partie 2 : page qui declenche le traitement de la liste courante
// Vide resultats/ puis le regenere entierement a partir de depots/emails.txt.

require __DIR__ . '/traitement.php';

$cheminListe = __DIR__ . '/depots/emails.txt';
$dossierResultats = __DIR__ . '/resultats';

$message = '';
$typeMessage = '';
$resume = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!file_exists($cheminListe)) {
        $message = "Téléversez d'abord un fichier.";
        $typeMessage = 'erreur';
    } else {
        // on vide entierement resultats/ avant de le regenerer, pour ne garder
        // aucun fichier d'un ancien traitement (ex. un domaine qui n'existe plus)
        if (is_dir($dossierResultats)) {
            foreach (scandir($dossierResultats) as $nomFichier) {
                if ($nomFichier === '.' || $nomFichier === '..') {
                    continue;
                }
                unlink($dossierResultats . '/' . $nomFichier);
            }
        }

        $resume = traiterEmails($cheminListe, $dossierResultats);
        $message = "Traitement termine avec succes.";
        $typeMessage = 'succes';
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<title>Lancer le traitement - EmailHub</title>
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
            <img src="icons/lancer_traitement.png" alt="">
        </div>
        <h2>Lancer le traitement</h2>
        <p class="explication">Analyse la liste courante (depots/emails.txt) et regenere tous les fichiers de resultats.</p>

        <form method="post">
            <button type="submit" class="bouton">Lancer le traitement</button>
        </form>

        <?php if ($message !== ''): ?>
            <div class="message <?= htmlspecialchars($typeMessage) ?>">
                <?= htmlspecialchars($message) ?>
                <?php if ($resume !== null): ?>
                    <ul style="margin:10px 0 0 0; padding-left:18px;">
                        <li><?= htmlspecialchars((string) $resume['nbValides']) ?> email(s) valide(s)</li>
                        <li><?= htmlspecialchars((string) $resume['nbInvalides']) ?> email(s) invalide(s)</li>
                        <li><?= htmlspecialchars((string) count($resume['domaines'])) ?> domaine(s) : <?= htmlspecialchars(implode(', ', $resume['domaines'])) ?></li>
                    </ul>
                    <div style="margin-top:14px;">
                        <a href="telecharger_fichiers.php" class="bouton" style="display:inline-block;">Télécharger les fichiers →</a>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>

</div>
</body>
</html>
