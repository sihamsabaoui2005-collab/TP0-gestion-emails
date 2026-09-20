<?php
// Partie 1, sous forme de fonction réutilisable (chemins en paramètres)

function traiterEmails(string $cheminListe, string $dossierResultats): array
{
    if (!is_dir($dossierResultats)) {
        mkdir($dossierResultats, 0777, true);
    }

    $emails = file($cheminListe, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if ($emails === false) {
        $emails = array();
    }

    $emailsValides = array();
    $emailsInvalides = array();

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

    file_put_contents($dossierResultats . '/Emailinvalide.txt', implode("\n", $emailsInvalides));

    sort($emailsValides);
    file_put_contents($dossierResultats . '/Emailst.txt', implode("\n", $emailsValides));

    $domainesUtilises = array();
    foreach ($emailsValides as $email) {
        $parties = explode('@', $email);
        $domaine = strtolower($parties[1]);
        $domaine = str_replace('.', '_', $domaine);
        $nomFichier = $dossierResultats . '/' . $domaine . '.txt';

        if (!in_array($domaine, $domainesUtilises)) {
            file_put_contents($nomFichier, $email . "\n");
            $domainesUtilises[] = $domaine;
        } else {
            file_put_contents($nomFichier, $email . "\n", FILE_APPEND);
        }
    }

    return array(
        'nbValides'   => count($emailsValides),
        'nbInvalides' => count($emailsInvalides),
        'domaines'    => $domainesUtilises,
    );
}
