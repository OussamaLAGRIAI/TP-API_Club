-- schema.sql — base de l'API club. Chargé automatiquement au premier démarrage du service db.
-- NE PAS MODIFIER : le sujet du TP est l'API, pas la base.

-- La table de la séquence 2, inchangée
CREATE TABLE adherent (
    id_adherent    INTEGER GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    nom            VARCHAR(60)  NOT NULL,
    prenom         VARCHAR(60)  NOT NULL,
    date_naissance DATE         NOT NULL,
    courriel       VARCHAR(120) NOT NULL UNIQUE,
    telephone      VARCHAR(20),
    date_adhesion  DATE         NOT NULL DEFAULT CURRENT_DATE
);

-- Les comptes qui appellent l'API
CREATE TABLE utilisateur (
    id           INTEGER GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    identifiant  VARCHAR(40)  NOT NULL UNIQUE,
    mot_de_passe VARCHAR(255) NOT NULL,              -- empreinte password_hash, jamais le mot de passe
    role         VARCHAR(20)  NOT NULL CHECK (role IN ('gestionnaire', 'lecteur'))
);

-- Les jetons délivrés par POST /jetons
CREATE TABLE jeton (
    valeur         CHAR(64)     PRIMARY KEY,         -- 32 octets aléatoires en hexadécimal
    type           VARCHAR(20)  NOT NULL CHECK (type IN ('acces', 'rafraichissement')),
    utilisateur_id INTEGER      NOT NULL REFERENCES utilisateur (id) ON DELETE CASCADE,
    expire_le      TIMESTAMPTZ  NOT NULL,
    revoque        BOOLEAN      NOT NULL DEFAULT FALSE
);
