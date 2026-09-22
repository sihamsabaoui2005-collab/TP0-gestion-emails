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

// ---------- 4) Confirmation par email ----------
// Les adresses en attente de confirmation sont gardées dans resultats/en_attente.json
// sous la forme : { "jeton": { "email": "...", "date": 1234567890 } }

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

// Crée un jeton secret pour l'adresse et le sauvegarde. Retourne le jeton.
function creerJetonConfirmation(string $email, string $dossierResultats): string
{
    $jeton = bin2hex(random_bytes(16)); // 32 caractères aléatoires
    $enAttente = lireAdressesEnAttente($dossierResultats);
    $enAttente[$jeton] = ['email' => $email, 'date' => time()];
    enregistrerAdressesEnAttente($enAttente, $dossierResultats);
    return $jeton;
}