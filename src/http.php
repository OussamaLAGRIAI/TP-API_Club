<?php
// http.php — les trois outils HTTP de l'API. Fourni, à utiliser tel quel.
declare(strict_types=1);

/** Envoie le code, les en-têtes et le corps JSON, puis arrête le script. Un 204 part sans corps. */
function json_out(int $code, mixed $donnees = null, array $entetes = []): never
{
    http_response_code($code);
    foreach ($entetes as $nom => $valeur) {
        header("$nom: $valeur");
    }
    if ($code !== 204) {
        header('Content-Type: application/json');
        echo json_encode($donnees, JSON_UNESCAPED_UNICODE);
    }
    exit;
}

/** Le format d'erreur unique du contrat : {erreur, message, details}. */
function erreur(int $code, string $type, string $message, array $details = [], array $entetes = []): never
{
    json_out($code, ['erreur' => $type, 'message' => $message, 'details' => $details], $entetes);
}

/** Lit le corps JSON : 415 si le Content-Type n'est pas JSON, 400 si le JSON est illisible. */
function read_json_body(): array
{
    $type = $_SERVER['CONTENT_TYPE'] ?? '';
    if (!str_starts_with($type, 'application/json')) {
        erreur(415, 'format', 'Content-Type attendu : application/json');
    }
    $corps = json_decode(file_get_contents('php://input'), true);
    if (!is_array($corps)) {
        erreur(400, 'syntaxe', 'Corps JSON illisible');
    }
    return $corps;
}
