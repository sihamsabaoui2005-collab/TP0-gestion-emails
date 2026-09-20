<?php
$dossierResultats = __DIR__ . '/resultats';
$cheminListe = $dossierResultats . '/Emailst.txt';

$message = '';
$typeMessage = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nouvelleAdresse = trim($_POST['adresse'] ?? '');

    // Validation cote SERVEUR (la validation cote CLIENT est faite en JS plus bas)
    if (!filter_var($nouvelleAdresse, FILTER_VALIDATE_EMAIL)) {
        $message = "Adresse email invalide.";
        $typeMessage = 'erreur';
    } else {
        $emailsExistants = file_exists($cheminListe)
            ? file($cheminListe, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES)
            : [];

        if (in_array($nouvelleAdresse, $emailsExistants, true)) {
            $message = "Cette adresse existe déjà dans la liste.";
            $typeMessage = 'erreur';
        } else {
            if (!is_dir($dossierResultats)) {
                mkdir($dossierResultats, 0777, true);
            }

            // on ajoute l'adresse, on retrie, et on reecrit Emailst.txt
            $emailsExistants[] = $nouvelleAdresse;
            sort($emailsExistants);
            file_put_contents($cheminListe, implode("\n", $emailsExistants));

            // on l'ajoute aussi dans le fichier de son domaine
            $parties = explode('@', $nouvelleAdresse);
            $domaine = strtolower($parties[1]);
            $domaine = str_replace('.', '_', $domaine);
            $cheminDomaine = $dossierResultats . '/' . $domaine . '.txt';
            file_put_contents($cheminDomaine, $nouvelleAdresse . "\n", FILE_APPEND);

            $message = "Adresse « $nouvelleAdresse » ajoutée avec succès.";
            $typeMessage = 'succes';
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
        <p class="explication">Saisissez une nouvelle adresse email à ajouter à la liste.</p>

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