<?php
// Partie 4 : page ouverte quand l'utilisateur clique sur le lien reçu par email
require "validation_avancee.php";

$dossierResultats = __DIR__ . '/resultats';
$cheminListe = $dossierResultats . '/Emailst.txt';
$dureeValidite = 24 * 60 * 60; // le lien est valable 24 heures

$jeton = $_GET['jeton'] ?? '';
$enAttente = lireAdressesEnAttente($dossierResultats);

if ($jeton === '' || !isset($enAttente[$jeton])) {
    $message = "Lien de confirmation invalide ou déjà utilisé.";
    $typeMessage = 'erreur';
} else {
    $email = $enAttente[$jeton]['email'];
    $date  = $enAttente[$jeton]['date'];

    // dans tous les cas, le jeton ne sert qu'une seule fois
    unset($enAttente[$jeton]);
    enregistrerAdressesEnAttente($enAttente, $dossierResultats);

    if (time() - $date > $dureeValidite) {
        $message = "Ce lien a expiré. Veuillez ajouter l'adresse à nouveau.";
        $typeMessage = 'erreur';
    } elseif (adresseExisteDeja($email, $cheminListe)) {
        $message = "L'adresse « $email » est déjà dans la liste.";
        $typeMessage = 'erreur';
    } else {
        ajouterAdresseALaListe($email, $dossierResultats);
        $message = "Adresse « $email » confirmée et ajoutée avec succès.";
        $typeMessage = 'succes';
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<title>Confirmation - EmailHub</title>
<link rel="stylesheet" href="style_pages.css">
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
        <h2>Confirmation de l'adresse</h2>
        <div class="message <?= htmlspecialchars($typeMessage) ?>"><?= htmlspecialchars($message) ?></div>
    </div>
</div>
</body>
</html>