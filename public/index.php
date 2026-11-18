<?php
// index.php — le routage de l'API. Toutes les requêtes passent ici (php -S … public/index.php).
// CORRIGÉ — version professeur, ne pas distribuer.
declare(strict_types=1);

require __DIR__ . '/../src/db.php';
require __DIR__ . '/../src/http.php';
require __DIR__ . '/../src/auth.php';

// Ce que voit le client : les noms du contrat, pas ceux des colonnes.
const COLONNES = 'id_adherent AS id, nom, prenom, date_naissance AS naissance,
                  courriel, telephone, date_adhesion AS adhesion';

function trouver_adherent(int $id): array|false
{
    $req = db()->prepare('SELECT ' . COLONNES . ' FROM adherent WHERE id_adherent = :id');
    $req->execute([':id' => $id]);
    return $req->fetch();
}

// ---------- Routes faites ----------

function lister_adherents(): never
{
    exiger_jeton();                                           // lecteur ou gestionnaire
    json_out(200, db()->query('SELECT ' . COLONNES . ' FROM adherent ORDER BY nom')->fetchAll());
}

function lire_adherent(int $id): never
{
    exiger_jeton();
    $adherent = trouver_adherent($id);
    if ($adherent === false) {
        erreur(404, 'introuvable', 'Adhérent inconnu');
    }
    json_out(200, $adherent);
}

function valider_adherent(array $c): void
{
    $err = [];
    foreach (['nom', 'prenom'] as $champ) {
        $v = trim((string) ($c[$champ] ?? ''));
        if ($v === '' || mb_strlen($v) > 60) {
            $err[] = ['champ' => $champ, 'probleme' => 'vide ou plus de 60 caractères'];
        }
    }
    if (!filter_var($c['courriel'] ?? '', FILTER_VALIDATE_EMAIL)) {
        $err[] = ['champ' => 'courriel', 'probleme' => 'format invalide'];
    }
    $naissance = (string) ($c['naissance'] ?? '');
    $d = DateTime::createFromFormat('!Y-m-d', $naissance);
    if ($d === false || $d->format('Y-m-d') !== $naissance) {      // refuse le 31 février
        $err[] = ['champ' => 'naissance', 'probleme' => 'date invalide'];
    }
    if (mb_strlen((string) ($c['telephone'] ?? '')) > 20) {
        $err[] = ['champ' => 'telephone', 'probleme' => 'plus de 20 caractères'];
    }
    if ($err !== []) {
        erreur(422, 'validation', 'Données refusées', $err);
    }
}

function parametres(array $c): array
{
    return [':nom' => $c['nom'], ':prenom' => $c['prenom'], ':naissance' => $c['naissance'],
            ':courriel' => $c['courriel'], ':telephone' => $c['telephone'] ?? null];
}

// ---------- Routes de la partie B ----------

function obtenir_jeton(): never
{
    $c = read_json_body();                                    // 415, 400
    if (!isset($c['identifiant'], $c['mot_de_passe'])) {
        erreur(422, 'validation', 'Identifiant et mot de passe requis');
    }
    $req = db()->prepare('SELECT id, mot_de_passe FROM utilisateur WHERE identifiant = :id');
    $req->execute([':id' => $c['identifiant']]);
    $u = $req->fetch();
    if ($u === false || !password_verify($c['mot_de_passe'], $u['mot_de_passe'])) {
        erreur(401, 'authentification', 'Identifiants refusés', [], ['WWW-Authenticate' => 'Bearer']);
    }
    json_out(200, [
        'acces'            => creer_jeton($u['id'], 'acces', 900),           // 15 min
        'rafraichissement' => creer_jeton($u['id'], 'rafraichissement', 604800), // 7 j
        'type'             => 'Bearer',
        'expire_dans'      => 900,
    ]);
}

function rafraichir_jeton(): never
{
    $c = read_json_body();                                    // 415, 400
    if (!isset($c['rafraichissement'])) {
        erreur(422, 'validation', 'Jeton de rafraîchissement requis');
    }
    $req = db()->prepare(
        "SELECT utilisateur_id FROM jeton
          WHERE valeur = :valeur AND type = 'rafraichissement'
            AND revoque IS FALSE AND expire_le > now()"
    );
    $req->execute([':valeur' => $c['rafraichissement']]);
    $uid = $req->fetchColumn();
    if ($uid === false) {
        erreur(401, 'authentification', 'Jeton de rafraîchissement invalide');
    }
    json_out(200, ['acces' => creer_jeton((int) $uid, 'acces', 900), 'type' => 'Bearer', 'expire_dans' => 900]);
}

function creer_adherent(): never
{
    $c = read_json_body();                                    // 415, 400
    exiger_role('gestionnaire');                              // 401, 403
    valider_adherent($c);                                     // 422
    try {
        $req = db()->prepare(
            'INSERT INTO adherent (nom, prenom, date_naissance, courriel, telephone)
             VALUES (:nom, :prenom, :naissance, :courriel, :telephone)
             RETURNING id_adherent'
        );
        $req->execute(parametres($c));
    } catch (PDOException $e) {
        if ($e->getCode() === '23505') {
            erreur(409, 'conflit', 'Courriel déjà utilisé');
        }
        throw $e;                                             // le reste : 500, journalisé
    }
    $id = (int) $req->fetchColumn();
    json_out(201, trouver_adherent($id), ['Location' => "/adherents/$id"]);
}

function remplacer_adherent(int $id): never
{
    $c = read_json_body();                                    // 415, 400
    exiger_role('gestionnaire');                              // 401, 403
    if (trouver_adherent($id) === false) {
        erreur(404, 'introuvable', 'Adhérent inconnu');
    }
    valider_adherent($c);                                     // 422
    try {
        $req = db()->prepare(
            'UPDATE adherent
                SET nom = :nom, prenom = :prenom, date_naissance = :naissance,
                    courriel = :courriel, telephone = :telephone
              WHERE id_adherent = :id'
        );
        $req->execute(parametres($c) + [':id' => $id]);
    } catch (PDOException $e) {
        if ($e->getCode() === '23505') {
            erreur(409, 'conflit', 'Courriel déjà pris par un autre adhérent');
        }
        throw $e;
    }
    json_out(200, trouver_adherent($id));
}

function supprimer_adherent(int $id): never
{
    exiger_role('gestionnaire');                              // 401, 403
    $req = db()->prepare('DELETE FROM adherent WHERE id_adherent = :id');
    $req->execute([':id' => $id]);
    if ($req->rowCount() === 0) {
        erreur(404, 'introuvable', 'Adhérent inconnu');      // choix 404 de A3
    }
    json_out(204);                                            // aucun corps
}

// ---------- Le routage ----------

$methode = $_SERVER['REQUEST_METHOD'];
$chemin  = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

if ($chemin === '/jetons' && $methode === 'POST') {
    obtenir_jeton();
} elseif ($chemin === '/jetons/rafraichir' && $methode === 'POST') {
    rafraichir_jeton();
} elseif ($chemin === '/adherents') {
    match ($methode) {
        'GET'   => lister_adherents(),
        'POST'  => creer_adherent(),
        default => erreur(405, 'methode', 'Méthode non autorisée', [], ['Allow' => 'GET, POST']),
    };
} elseif (preg_match('#^/adherents/(\d+)$#', $chemin, $m)) {
    match ($methode) {
        'GET'    => lire_adherent((int) $m[1]),
        'PUT'    => remplacer_adherent((int) $m[1]),
        'DELETE' => supprimer_adherent((int) $m[1]),
        default  => erreur(405, 'methode', 'Méthode non autorisée', [], ['Allow' => 'GET, PUT, DELETE']),
    };
} else {
    erreur(404, 'introuvable', 'Ressource inconnue');
}
