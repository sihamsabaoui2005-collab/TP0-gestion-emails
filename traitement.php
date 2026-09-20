<?php
// TP-0 - Partie 2 : traitement des emails, sous forme de fonction reutilisable
// Reprend exactement la logique de partie1.php, mais avec des chemins en parametres
// (au lieu de chemins fixes) pour pouvoir etre appelee depuis lancer_traitement.php.

/**
 * Lit la liste d'emails a $cheminListe, separe les valides des invalides,
 * ecrit les fichiers de resultat dans $dossierResultats, et retourne un resume.
 *
 * @return array{nbValides:int, nbInvalides:int, domaines:array}
 */
function traiterEmails(string $cheminListe, string $dossierResultats): array
{
    // le dossier de resultats peut ne pas encore exister (premier lancement)
    if (!is_dir($dossierResultats)) {
        mkdir($dossierResultats, 0777, true);
    }

    $emails = file($cheminListe, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if ($emails === false) {
        $emails = array();
    }

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

    file_put_contents($dossierResultats . '/Emailinvalide.txt', implode("\n", $emailsInvalides));

    sort($emailsValides);

    file_put_contents($dossierResultats . '/Emailst.txt', implode("\n", $emailsValides));

    // ETAPE 6 : on separe les emails par domaine
    // $domainesUtilises sert juste a savoir si on a deja cree le fichier
    // du domaine pendant CETTE execution de la fonction.
    $domainesUtilises = array();

    foreach ($emailsValides as $email) {

        $parties = explode('@', $email);
        $domaine = strtolower($parties[1]);
        $domaine = str_replace('.', '_', $domaine);

        $nomFichier = $dossierResultats . '/' . $domaine . '.txt';

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

    return array(
        'nbValides'   => count($emailsValides),
        'nbInvalides' => count($emailsInvalides),
        'domaines'    => $domainesUtilises,
    );
}
