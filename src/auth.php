<?php
// auth.php — les jetons. creer_jeton() et jeton_presente() sont fournies ;
// CORRIGÉ — version professeur, ne pas distribuer.
declare(strict_types=1);

/**
 * Crée un jeton en base et renvoie sa valeur.
 * $type : 'acces' ou 'rafraichissement' — $duree : en SECONDES (15 min = 900, 7 j = 604800).
 */
function creer_jeton(int $utilisateurId, string $type, int $duree): string
{
    $valeur = bin2hex(random_bytes(32));      // 64 caractères hexadécimaux
    $req = db()->prepare(
        'INSERT INTO jeton (valeur, type, utilisateur_id, expire_le)
         VALUES (:valeur, :type, :uid, now() + make_interval(secs => :duree))'
    );
    $req->execute([':valeur' => $valeur, ':type' => $type, ':uid' => $utilisateurId, ':duree' => $duree]);
    return $valeur;
}

/** Renvoie la valeur du jeton de l'en-tête « Authorization: Bearer … », ou null s'il est absent ou mal formé. */
function jeton_presente(): ?string
{
    $entete = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
    if (preg_match('/^Bearer\s+([a-f0-9]{64})$/', $entete, $m) !== 1) {
        return null;
    }
    return $m[1];
}

/**
 * Vérifie le jeton d'accès présenté et renvoie la ligne utilisateur (id, identifiant, role).
 * Répond 401 avec l'en-tête WWW-Authenticate: Bearer dans TOUS les cas d'échec :
 * en-tête absent, en-tête mal formé, jeton inconnu, jeton expiré, jeton révoqué.
 * ⚠ Requête préparée obligatoire sur la table jeton.
 */
function exiger_jeton(): array
{
    $defi = ['WWW-Authenticate' => 'Bearer realm="api-club"'];
    $valeur = jeton_presente();
    if ($valeur === null) {
        erreur(401, 'authentification', 'Jeton absent', [], $defi);
    }
    $req = db()->prepare(
        "SELECT u.id, u.identifiant, u.role
           FROM jeton j JOIN utilisateur u ON u.id = j.utilisateur_id
          WHERE j.valeur = :valeur AND j.type = 'acces'
            AND j.revoque IS FALSE AND j.expire_le > now()"
    );
    $req->execute([':valeur' => $valeur]);
    $ligne = $req->fetch();
    if ($ligne === false) {
        erreur(401, 'authentification', 'Jeton invalide ou expiré', [], $defi);
    }
    return $ligne;
}

/**
 * Vérifie le jeton, puis le rôle. Répond 403 si le rôle ne convient pas.
 * Le 403 n'a PAS d'en-tête WWW-Authenticate : l'authentification a réussi.
 */
function exiger_role(string $role): array
{
    $utilisateur = exiger_jeton();            // 401 si le jeton ne va pas
    if ($utilisateur['role'] !== $role) {
        erreur(403, 'droits', 'Rôle insuffisant');
    }
    return $utilisateur;
}
