<?php
// seed.php — crée les deux comptes et quelques adhérents.
// Lancement : docker compose exec api php sql/seed.php   (rejouable : il vide les tables avant)
declare(strict_types=1);
require __DIR__ . '/../src/db.php';

$algo = defined('PASSWORD_ARGON2ID') ? PASSWORD_ARGON2ID : PASSWORD_DEFAULT;

$pdo = db();
$pdo->exec('TRUNCATE jeton, utilisateur, adherent RESTART IDENTITY CASCADE');

$u = $pdo->prepare('INSERT INTO utilisateur (identifiant, mot_de_passe, role) VALUES (:i, :m, :r)');
$u->execute([':i' => 'gestionnaire', ':m' => password_hash('MotDePasse!2027', $algo), ':r' => 'gestionnaire']);
$u->execute([':i' => 'lecteur',      ':m' => password_hash('Lecture!2027', $algo),    ':r' => 'lecteur']);

$a = $pdo->prepare(
    'INSERT INTO adherent (nom, prenom, date_naissance, courriel, telephone, date_adhesion)
     VALUES (:nom, :prenom, :naissance, :courriel, :telephone, :adhesion)'
);
$adherents = [
    ['Benali', 'Yanis',  '2012-04-02', 'yanis.b@club.fr',  '0601020304', '2026-09-07'],
    ['Moreau', 'Jade',   '2013-09-15', 'jade.m@club.fr',   null,         '2026-09-07'],
    ['Traore', 'Sekou',  '2014-01-20', 'sekou.t@club.fr',  '0611223344', '2026-09-09'],
    ['Lopes',  'Ines',   '2000-06-11', 'ines.l@club.fr',   null,         '2026-09-10'],
    ['Durand', 'Paul',   '1985-03-03', 'paul.d@club.fr',   '0699887766', '2026-09-12'],
];
foreach ($adherents as [$nom, $prenom, $naissance, $courriel, $telephone, $adhesion]) {
    $a->execute([':nom' => $nom, ':prenom' => $prenom, ':naissance' => $naissance,
                 ':courriel' => $courriel, ':telephone' => $telephone, ':adhesion' => $adhesion]);
}

echo 'Seed terminé : 2 comptes (', $algo === PASSWORD_DEFAULT ? 'PASSWORD_DEFAULT' : 'PASSWORD_ARGON2ID',
     '), ', count($adherents), " adhérents.\n";
