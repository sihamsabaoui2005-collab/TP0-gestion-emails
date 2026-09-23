<?php
// Partie 3 + Partie 4 : ajout d'une adresse avec confirmation par CODE envoyé par email
// Étape 1 : l'utilisateur saisit l'adresse -> vérifications -> envoi d'un code à 6 chiffres
// Étape 2 : l'utilisateur saisit le code reçu -> si le code est bon, l'adresse est ajoutée
session_start(); // la session garde l'adresse en cours de confirmation entre les 2 étapes

require "validation_avancee.php";
require "fonctions_email.php";

$dossierResultats = __DIR__ . '/resultats';
$cheminListe = $dossierResultats . '/Emailst.txt';

$message = '';
$typeMessage = '';

// Envoie le code par email. Retourne true si l'envoi a réussi.
function envoyerCodeParEmail(string $email, string $dossierResultats): bool
{
    $code = creerCodeConfirmation($email, $dossierResultats);
    $minutes = DUREE_VALIDITE_CODE / 60;

    $objet = "Votre code de confirmation - EmailHub";
    $contenu = "Bonjour,\n\n"
             . "Voici votre code de confirmation pour ajouter votre adresse à la liste EmailHub :\n\n"
             . "    $code\n\n"
             . "Ce code est valable $minutes minutes. Si vous n'êtes pas à l'origine de cette demande, ignorez ce message.";

    return envoyerEmail($email, $objet, $contenu);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // ---------- Étape 1 : saisie de l'adresse ----------
    if ($action === 'envoyer_code') {
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
        } elseif (envoyerCodeParEmail($nouvelleAdresse, $dossierResultats)) {
            $_SESSION['email_a_confirmer'] = $nouvelleAdresse; // on passe à l'étape 2
            $message = "Un code de confirmation a été envoyé à « $nouvelleAdresse ».";
            $typeMessage = 'succes';
        } else {
            supprimerCodeConfirmation($nouvelleAdresse, $dossierResultats);
            $message = "Impossible d'envoyer l'email de confirmation.";
            $typeMessage = 'erreur';
        }
    }

    // ---------- Étape 2 : saisie du code ----------
    elseif ($action === 'verifier_code' && isset($_SESSION['email_a_confirmer'])) {
        $email = $_SESSION['email_a_confirmer'];
        $codeSaisi = trim($_POST['code'] ?? '');

        $erreur = verifierCodeConfirmation($email, $codeSaisi, $dossierResultats);

        if ($erreur === '') {
            // on revérifie les doublons (l'adresse a pu être ajoutée entre-temps)
            if (adresseExisteDeja($email, $cheminListe)) {
                $message = "L'adresse « $email » est déjà dans la liste.";
                $typeMessage = 'erreur';
            } else {
                ajouterAdresseALaListe($email, $dossierResultats);
                $message = "Adresse « $email » confirmée et ajoutée avec succès.";
                $typeMessage = 'succes';
            }
            unset($_SESSION['email_a_confirmer']); // retour à l'étape 1
        } else {
            $message = $erreur;
            $typeMessage = 'erreur';

            // si le code n'existe plus (expiré ou trop d'essais), on revient à l'étape 1
            if (!isset(lireAdressesEnAttente($dossierResultats)[$email])) {
                unset($_SESSION['email_a_confirmer']);
            }
        }
    }

    // ---------- Renvoyer un nouveau code ----------
    elseif ($action === 'renvoyer_code' && isset($_SESSION['email_a_confirmer'])) {
        $email = $_SESSION['email_a_confirmer'];
        if (envoyerCodeParEmail($email, $dossierResultats)) {
            $message = "Un nouveau code a été envoyé à « $email ».";
            $typeMessage = 'succes';
        } else {
            $message = "Impossible d'envoyer l'email de confirmation.";
            $typeMessage = 'erreur';
        }
    }

    // ---------- Annuler et changer d'adresse ----------
    elseif ($action === 'annuler' && isset($_SESSION['email_a_confirmer'])) {
        supprimerCodeConfirmation($_SESSION['email_a_confirmer'], $dossierResultats);
        unset($_SESSION['email_a_confirmer']);
    }
}

// Quelle étape afficher ?
$emailEnCours = $_SESSION['email_a_confirmer'] ?? '';
$etapeCode = ($emailEnCours !== '');
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
        <?php if (!$etapeCode): ?>
            <!-- Étape 1 : saisie de l'adresse -->
            <p class="explication">Saisissez une nouvelle adresse email. Un code de confirmation lui sera envoyé avant son ajout à la liste.</p>

            <form method="post" id="formAjout" novalidate>
                <input type="hidden" name="action" value="envoyer_code">
                <div class="champ">
                    <label for="adresse">Adresse email</label>
                    <input type="email" id="adresse" name="adresse" placeholder="exemple@domaine.com" required>
                    <div id="erreurClient" style="color:#ffb3b3; font-size:13px; margin-top:6px; display:none;"></div>
                </div>
                <button type="submit" class="bouton">Envoyer le code</button>
            </form>
        <?php else: ?>
            <!-- Étape 2 : saisie du code reçu -->
            <p class="explication">Entrez le code à 6 chiffres envoyé à « <?= htmlspecialchars($emailEnCours) ?> ».</p>

            <form method="post" id="formCode" novalidate>
                <input type="hidden" name="action" value="verifier_code">
                <div class="champ">
                    <label for="code">Code de confirmation</label>
                    <input type="text" id="code" name="code" placeholder="123456" maxlength="6" inputmode="numeric" autocomplete="one-time-code" required>
                    <div id="erreurClient" style="color:#ffb3b3; font-size:13px; margin-top:6px; display:none;"></div>
                </div>
                <button type="submit" class="bouton">Confirmer</button>
            </form>

            <!-- boutons secondaires : renvoyer un code ou changer d'adresse -->
            <div class="boutons-secondaires">
                <form method="post">
                    <input type="hidden" name="action" value="renvoyer_code">
                    <button type="submit" class="bouton secondaire">Renvoyer le code</button>
                </form>
                <form method="post">
                    <input type="hidden" name="action" value="annuler">
                    <button type="submit" class="bouton secondaire">Changer d'adresse</button>
                </form>
            </div>
        <?php endif; ?>

        <?php if ($message !== ''): ?>
            <div class="message <?= htmlspecialchars($typeMessage) ?>"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>
    </div>
</div>

<script>
    // Validation cote CLIENT (en plus de la validation cote serveur en PHP au-dessus)
    var erreurDiv = document.getElementById('erreurClient');

    // Étape 1 : format de l'adresse
    var formAjout = document.getElementById('formAjout');
    if (formAjout) {
        formAjout.addEventListener('submit', function (e) {
            var champ = document.getElementById('adresse');
            var regex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

            if (!regex.test(champ.value.trim())) {
                e.preventDefault(); // on bloque l'envoi si le format est incorrect
                erreurDiv.textContent = "Format d'adresse invalide.";
                erreurDiv.style.display = 'block';
            } else {
                erreurDiv.style.display = 'none';
            }
        });
    }

    // Étape 2 : le code doit contenir exactement 6 chiffres
    var formCode = document.getElementById('formCode');
    if (formCode) {
        formCode.addEventListener('submit', function (e) {
            var champ = document.getElementById('code');

            if (!/^[0-9]{6}$/.test(champ.value.trim())) {
                e.preventDefault();
                erreurDiv.textContent = "Le code doit contenir 6 chiffres.";
                erreurDiv.style.display = 'block';
            } else {
                erreurDiv.style.display = 'none';
            }
        });
    }
</script>
</body>
</html>