<?php
require "validation_avancee.php";
require "fonctions_email.php";

$dossierResultats = __DIR__ . '/resultats';
$cheminListe = $dossierResultats . '/Emailst.txt';

$message = '';
$typeMessage = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nouvelleAdresse = trim($_POST['adresse'] ?? '');

    // Validation cote SERVEUR avancée : syntaxe + domaine + MX (Partie 4)
    // (la validation cote CLIENT est faite en JS plus bas)
    $erreur = validerEmailAvance($nouvelleAdresse);

    if ($erreur !== '') {
        $message = $erreur;
        $typeMessage = 'erreur';
    } elseif (adresseExisteDeja($nouvelleAdresse, $cheminListe)) {
        $message = "Cette adresse existe déjà dans la liste.";
        $typeMessage = 'erreur';
    } else {
        // Partie 4 : au lieu d'ajouter directement, on envoie un email de confirmation
        $jeton = creerJetonConfirmation($nouvelleAdresse, $dossierResultats);
        $lien = 'http://' . $_SERVER['HTTP_HOST'] . '/confirmer_adresse.php?jeton=' . $jeton;

        $objet = "Confirmez votre adresse - EmailHub";
        $contenu = "Bonjour,\n\n"
                 . "Pour confirmer l'ajout de votre adresse à la liste EmailHub, cliquez sur ce lien :\n"
                 . $lien . "\n\n"
                 . "Ce lien est valable 24 heures. Si vous n'êtes pas à l'origine de cette demande, ignorez ce message.";

        if (envoyerEmail($nouvelleAdresse, $objet, $contenu)) {
            $message = "Un email de confirmation a été envoyé à « $nouvelleAdresse ». L'adresse sera ajoutée après confirmation.";
            $typeMessage = 'succes';
        } else {
            $message = "Impossible d'envoyer l'email de confirmation.";
            $typeMessage = 'erreur';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<title>Ajouter une adresse - EmailHub</title>
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
        <div class="badge">
            <img src="icons/ajouter_adresse.png" alt="">
        </div>
        <h2>Ajouter une adresse</h2>
        <p class="explication">Saisissez une nouvelle adresse email. Un email de confirmation lui sera envoyé avant son ajout à la liste.</p>

        <form method="post" id="formAjout" novalidate>
            <div class="champ">
                <label for="adresse">Adresse email</label>
                <input type="email" id="adresse" name="adresse" placeholder="exemple@domaine.com" required>
                <div id="erreurClient" style="color:#ffb3b3; font-size:13px; margin-top:6px; display:none;"></div>
            </div>
            <button type="submit" class="bouton">Ajouter</button>
        </form>

        <?php if ($message !== ''): ?>
            <div class="message <?= htmlspecialchars($typeMessage) ?>"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>
    </div>
</div>

<script>
    // Validation cote CLIENT (en plus de la validation cote serveur en PHP au-dessus)
    document.getElementById('formAjout').addEventListener('submit', function (e) {
        var champ = document.getElementById('adresse');
        var erreurDiv = document.getElementById('erreurClient');
        var regex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

        if (!regex.test(champ.value.trim())) {
            e.preventDefault(); // on bloque l'envoi si le format est incorrect
            erreurDiv.textContent = "Format d'adresse invalide.";
            erreurDiv.style.display = 'block';
        } else {
            erreurDiv.style.display = 'none';
        }
    });
</script>
</body>
</html>