<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<title>EmailHub - Gestion des adresses email</title>
<style>

    * { box-sizing: border-box; }

    html, body {
        margin: 0;
        min-height: 100%;
        font-family: 'Segoe UI', Arial, sans-serif;
        color: white;
    }

    /* ---- Fond general : image + overlay leger (pas noir a 90%) ---- */
    body {
        background-image:
            linear-gradient(rgba(3,15,35,0.55), rgba(3,15,35,0.75)),
            url("icons/banniere.jpg");
        background-size: cover;
        background-position: center;
        background-attachment: fixed;
        padding: 45px 0 25px 0;
    }

    /* ---- Conteneur large et centre ---- */
    .conteneur {
        max-width: 1450px;
        width: 92%;
        margin: 0 auto;
    }

    /* ---- En-tete : logo en haut a gauche ---- */
    .entete {
        display: flex;
        align-items: center;
        gap: 12px;
        margin-bottom: 14px;
    }

    .logo {
        width: 38px;
        height: 38px;
        border-radius: 12px;
        background: linear-gradient(135deg, #29b6ff, #0057ff);
        display: flex;
        align-items: center;
        justify-content: center;
        box-shadow: 0 0 18px rgba(0,150,255,0.6);
        flex-shrink: 0;
    }

    .entete h1 {
        font-size: 26px;
        font-weight: 800;
        margin: 0;
        color: white;
    }

    /* ---- Titre : centre dans la largeur du conteneur ---- */
    .titre {
        text-align: center;
        font-size: 40px;
        font-weight: 900;
        line-height: 1.15;
        margin: 6px 0 20px 0;
        text-shadow: 0 0 20px rgba(60,150,255,0.35);
    }

    /* ---- Barre d'upload : capsule large, centree ---- */
    .barre-upload {
        display: flex;
        align-items: center;
        gap: 16px;
        width: 680px;
        max-width: 92%;
        height: 64px;
        background: transparent;
        background-image: none;
        border: 1.5px solid rgba(120,210,255,0.9);
        border-radius: 50px;
        padding: 0 14px 0 28px;
        margin: 0 auto 26px auto;
        box-shadow: 0 0 10px rgba(0,180,255,0.7), 0 0 25px rgba(0,140,255,0.35);
        cursor: pointer;
    }

    #texteFichier {
        flex: 1;
        font-size: 17px;
        font-weight: 500;
    }

    .icone-menu {
        width: 52px;
        height: 52px;
        min-width: 52px;
        max-width: 52px;
        border-radius: 50%;
        flex: 0 0 52px;
        background: transparent;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    #fichierInput { display: none; }

    /* ---- Grilles ---- */
    .grille-haut, .grille-bas {
        display: grid;
        gap: 16px;
        margin-bottom: 16px;
    }

    .grille-haut { grid-template-columns: repeat(3, 1fr); }
    .grille-bas  { grid-template-columns: repeat(3, 1fr); }

    /* ---- Cartes : grandes, image bien visible, un seul texte en bas ---- */
    .carte {
        position: relative;
        display: block;
        min-height: 215px;
        border-radius: 16px;
        overflow: hidden;
        text-decoration: none;
        border: 2.5px solid transparent;
        background-size: cover;
        background-position: center;
        transition: transform 0.3s ease, box-shadow 0.3s ease;
    }

    .grille-bas .carte { min-height: 175px; }

    .carte:hover {
        transform: translateY(-3px) scale(1.01);
    }

    .carte::after {
        content: "";
        position: absolute;
        inset: 0;
        background: linear-gradient(180deg, rgba(5,12,28,0) 55%, rgba(5,12,28,0.82) 100%);
    }

    .carte-texte {
        position: absolute;
        left: 18px;
        right: 18px;
        bottom: 14px;
        z-index: 1;
        display: flex;
        align-items: flex-end;
        justify-content: space-between;
        font-weight: 700;
        font-size: 15px;
        color: white;
        text-shadow: 0 1px 6px rgba(0,0,0,0.7);
    }

    .fleche { font-size: 18px; opacity: 0.9; }

    /* ---- Couleurs neon ---- */
    .cyan   { border-color: #2be8ff; box-shadow: 0 0 10px rgba(43,232,255,0.65), 0 0 26px rgba(43,232,255,0.35); }
    .violet { border-color: #9a7bff; box-shadow: 0 0 10px rgba(154,123,255,0.65), 0 0 26px rgba(154,123,255,0.35); }
    .orange { border-color: #ffa63e; box-shadow: 0 0 10px rgba(255,166,62,0.65), 0 0 26px rgba(255,166,62,0.35); }
    .bleu   { border-color: #3f95ff; box-shadow: 0 0 10px rgba(63,149,255,0.65), 0 0 26px rgba(63,149,255,0.35); }

    .carte:hover.cyan   { box-shadow: 0 0 14px rgba(34,224,255,0.7), 0 0 32px rgba(34,224,255,0.4); }
    .carte:hover.violet { box-shadow: 0 0 14px rgba(139,107,255,0.7), 0 0 32px rgba(139,107,255,0.4); }
    .carte:hover.orange { box-shadow: 0 0 14px rgba(255,154,46,0.7), 0 0 32px rgba(255,154,46,0.4); }
    .carte:hover.bleu   { box-shadow: 0 0 14px rgba(47,139,255,0.7), 0 0 32px rgba(47,139,255,0.4); }

    /* ---- Responsive : uniquement en dessous de 900px ---- */
    @media (max-width: 900px) {
        .grille-haut, .grille-bas { grid-template-columns: repeat(2, 1fr); }
        .titre { font-size: 34px; }
    }

    @media (max-width: 560px) {
        .grille-haut, .grille-bas { grid-template-columns: 1fr; }
        .titre { font-size: 28px; }
    }

</style>
</head>
<body>
<div class="conteneur">

    <!-- En-tete -->
    <div class="entete">
        <div class="logo">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none"><path d="M3 6h18v12H3z" stroke="white" stroke-width="2" stroke-linejoin="round"/><path d="M3 7l9 6 9-6" stroke="white" stroke-width="2" stroke-linejoin="round"/></svg>
        </div>
        <h1>EmailHub</h1>
    </div>

    <!-- Titre centre -->
    <div class="titre">Gestion des<br>adresses email</div>

    <!-- Barre d'upload centree (fonctionnelle) -->
    <form method="post" enctype="multipart/form-data" action="televerser.php">
        <label class="barre-upload" for="fichierInput">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none"><path d="M12 16V8m0 0l-3 3m3-3l3 3" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><path d="M7 18a4 4 0 01-.6-7.96A5 5 0 0117 9.06 3.5 3.5 0 0116.5 16H7z" stroke="white" stroke-width="2"/></svg>
            <span id="texteFichier">Télécharger un fichier Emails.txt</span>
            <span class="icone-menu">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none"><path d="M4 6h16M4 12h16M4 18h10" stroke="white" stroke-width="2" stroke-linecap="round"/></svg>
            </span>
        </label>
        <input type="file" id="fichierInput" name="fichierEmails" accept=".txt"
               onchange="document.getElementById('texteFichier').textContent = this.files[0] ? this.files[0].name : 'Télécharger un fichier Emails.txt'; this.form.submit();">
    </form>

    <!-- Ligne 1 : 3 grandes cartes -->
    <div class="grille-haut">

        <a href="lancer_traitement.php" class="carte cyan" style="background-image:url('icons/lancer_traitement.png')">
            <div class="carte-texte"><span>Lancer le traitement</span><span class="fleche">→</span></div>
        </a>

        <a href="telecharger_fichiers.php" class="carte violet" style="background-image:url('icons/telecharger_fichiers.png')">
            <div class="carte-texte"><span>Télécharger les fichiers générés</span><span class="fleche">→</span></div>
        </a>

        <a href="envoyer_fichiers.php" class="carte orange" style="background-image:url('icons/envoyer_fichiers.png')">
            <div class="carte-texte"><span>Envoyer les fichiers</span><span class="fleche">→</span></div>
        </a>

    </div>

    <!-- Ligne 2 : 4 cartes -->
    <div class="grille-bas">

        <a href="envoyer_message.php" class="carte cyan" style="background-image:url('icons/envoyer_message.png')">
            <div class="carte-texte"><span>Envoyer un message</span><span class="fleche">→</span></div>
        </a>

        <a href="ajouter_adresse.php" class="carte bleu" style="background-image:url('icons/ajouter_adresse.png')">
            <div class="carte-texte"><span>Ajouter une adresse</span><span class="fleche">→</span></div>
        </a>


        <a href="historique.php" class="carte orange" style="background-image:url('icons/historique.png')">
            <div class="carte-texte"><span>Historique des envois</span><span class="fleche">→</span></div>
        </a>

    </div>

</div>
</body>
</html>
