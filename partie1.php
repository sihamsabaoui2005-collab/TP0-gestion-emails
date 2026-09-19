<?php
// TP-0 - Partie 1 : traitement du fichier Emails.txt

$emails = file("Emails.txt", FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

$emailsValides = array();
$emailsInvalides = array();

// ETAPE 1 et 2 : on lit chaque ligne et on verifie si l'email est valide
foreach ($emails as $email) {
    $email = trim($email);

    if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
        if (!in_array($email, $emailsValides)) {
            $emailsValides[] = $email;
        }
    } else {
        $emailsInvalides[] = $email;
    }
}

file_put_contents("Emailinvalide.txt", implode("\n", $emailsInvalides));

sort($emailsValides);

file_put_contents("Emailst.txt", implode("\n", $emailsValides));

// ETAPE 6 : on separe les emails par domaine
// $domainesUtilises sert juste a savoir si on a deja cree le fichier
// du domaine pendant CETTE execution du programme.
$domainesUtilises = array();

foreach ($emailsValides as $email) {

    $parties = explode("@", $email);
    $domaine = $parties[1];
    $domaine = str_replace(".", "_", $domaine);

    $nomFichier = $domaine . ".txt";

    if (!in_array($domaine, $domainesUtilises)) {
        // premiere fois qu'on rencontre ce domaine dans cette execution :
        // on (re)cree le fichier avec ce premier email (efface l'ancien contenu)
        file_put_contents($nomFichier, $email . "\n");
        $domainesUtilises[] = $domaine;
    } else {
        // le fichier existe deja pour ce domaine : on ajoute a la suite
        file_put_contents($nomFichier, $email . "\n", FILE_APPEND);
    }
}

echo "Termine !\n";
?>