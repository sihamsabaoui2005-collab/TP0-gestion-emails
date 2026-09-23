<?php
// Partie 4 (facultative) : validation avancée d'une adresse email
// Toutes les vérifications sont regroupées ici pour être réutilisées.

// 1) Vérifier la syntaxe de l'adresse
function verifierSyntaxe(string $email): bool
{
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

// 2) Vérifier que le domaine existe (il a au moins une adresse IP ou un serveur mail)
function verifierDomaine(string $domaine): bool
{
    return checkdnsrr($domaine, 'A')
        || checkdnsrr($domaine, 'AAAA')
        || checkdnsrr($domaine, 'MX');
}

// 3) Vérifier que le domaine a un serveur de messagerie (enregistrement MX)
function verifierMX(string $domaine): bool
{
    return checkdnsrr($domaine, 'MX');
}

// Récupérer le domaine d'une adresse (la partie après le @)
function extraireDomaine(string $email): string
{
    $parties = explode('@', $email);
    return strtolower(end($parties));
}

// Lance les 3 vérifications dans l'ordre.
// Retourne '' si tout est bon, sinon le message d'erreur.
function validerEmailAvance(string $email): string
{
    if (!verifierSyntaxe($email)) {
        return "Adresse email invalide (syntaxe incorrecte).";
    }

    $domaine = extraireDomaine($email);

    if (!verifierDomaine($domaine)) {
        return "Le domaine « $domaine » n'existe pas.";
    }

    if (!verifierMX($domaine)) {
        return "Le domaine « $domaine » n'a pas de serveur de messagerie (MX).";
    }

    return '';
}

// Vérifier si l'adresse est déjà dans la liste
// (comparaison exacte : Ali@gmail.com et ali@gmail.com sont considérées comme différentes)
function adresseExisteDeja(string $email, string $cheminListe): bool
{
    if (!file_exists($cheminListe)) {
        return false;
    }
    $emailsExistants = file($cheminListe, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    return in_array($email, array_map('trim', $emailsExistants), true);
}

// Ajouter l'adresse dans Emailst.txt (triée) et dans le fichier de son domaine
// (même logique qu'avant dans ajouter_adresse.php, déplacée ici pour être réutilisée)
function ajouterAdresseALaListe(string $email, string $dossierResultats): void
{
    if (!is_dir($dossierResultats)) {
        mkdir($dossierResultats, 0777, true);
    }

    $cheminListe = $dossierResultats . '/Emailst.txt';
    $emailsExistants = file_exists($cheminListe)
        ? file($cheminListe, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES)
        : [];

    $emailsExistants[] = $email;
    sort($emailsExistants);
    file_put_contents($cheminListe, implode("\n", $emailsExistants));

    $domaine = str_replace('.', '_', extraireDomaine($email));
    $cheminDomaine = $dossierResultats . '/' . $domaine . '.txt';
    file_put_contents($cheminDomaine, $email . "\n", FILE_APPEND);
}

// ---------- 4) Confirmation par code envoyé par email ----------
// Les adresses en attente de confirmation sont gardées dans resultats/en_attente.json
// sous la forme : { "email": { "code": "482915", "date": 1234567890, "tentatives": 0 } }

const DUREE_VALIDITE_CODE = 10 * 60; // le code est valable 10 minutes
const TENTATIVES_MAX = 3;            // nombre maximum d'essais avec un mauvais code

function lireAdressesEnAttente(string $dossierResultats): array
{
    $chemin = $dossierResultats . '/en_attente.json';
    if (!file_exists($chemin)) {
        return [];
    }
    $contenu = json_decode(file_get_contents($chemin), true);
    return is_array($contenu) ? $contenu : [];
}

function enregistrerAdressesEnAttente(array $enAttente, string $dossierResultats): void
{
    if (!is_dir($dossierResultats)) {
        mkdir($dossierResultats, 0777, true);
    }
    file_put_contents($dossierResultats . '/en_attente.json', json_encode($enAttente, JSON_PRETTY_PRINT));
}

// Crée un code à 6 chiffres pour l'adresse et le sauvegarde. Retourne le code.
// Si un ancien code existait pour cette adresse, il est remplacé.
function creerCodeConfirmation(string $email, string $dossierResultats): string
{
    $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT); // ex : "048291"
    $enAttente = lireAdressesEnAttente($dossierResultats);
    $enAttente[$email] = ['code' => $code, 'date' => time(), 'tentatives' => 0];
    enregistrerAdressesEnAttente($enAttente, $dossierResultats);
    return $code;
}

// Supprime le code en attente d'une adresse (code utilisé, expiré ou annulé)
function supprimerCodeConfirmation(string $email, string $dossierResultats): void
{
    $enAttente = lireAdressesEnAttente($dossierResultats);
    unset($enAttente[$email]);
    enregistrerAdressesEnAttente($enAttente, $dossierResultats);
}

// Vérifie le code saisi par l'utilisateur.
// Retourne '' si le code est bon, sinon le message d'erreur.
function verifierCodeConfirmation(string $email, string $codeSaisi, string $dossierResultats): string
{
    $enAttente = lireAdressesEnAttente($dossierResultats);

    if (!isset($enAttente[$email])) {
        return "Aucun code en attente pour cette adresse. Veuillez saisir votre adresse à nouveau.";
    }

    $infos = $enAttente[$email];

    // le code a expiré
    if (time() - $infos['date'] > DUREE_VALIDITE_CODE) {
        supprimerCodeConfirmation($email, $dossierResultats);
        return "Ce code a expiré. Veuillez saisir votre adresse à nouveau.";
    }

    // mauvais code
    if (!hash_equals($infos['code'], $codeSaisi)) {
        $infos['tentatives']++;

        if ($infos['tentatives'] >= TENTATIVES_MAX) {
            supprimerCodeConfirmation($email, $dossierResultats);
            return "Trop de tentatives incorrectes. Le code a été annulé, veuillez saisir votre adresse à nouveau.";
        }

        $enAttente[$email] = $infos;
        enregistrerAdressesEnAttente($enAttente, $dossierResultats);
        $reste = TENTATIVES_MAX - $infos['tentatives'];
        return "Code incorrect. Il vous reste $reste tentative(s).";
    }

    // bon code : il ne sert qu'une seule fois
    supprimerCodeConfirmation($email, $dossierResultats);
    return '';
}