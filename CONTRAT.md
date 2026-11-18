# Contrat de l'API club — correction de la partie A

Ce contrat est la **référence commune** de la partie B : c'est lui que le squelette suit, et c'est lui que vous implémentez. Si votre binôme a validé un contrat différent en partie A, vous pouvez garder le vôtre — notez alors ici, en fin de fichier, chaque écart et sa raison.

## A1. Les ressources

### Adhérent

| Champ | Type | Obligatoire | Règle |
|---|---|---|---|
| `id` | entier | — | attribué par le serveur, jamais envoyé |
| `nom` | chaîne | oui | non vide, 60 caractères maximum |
| `prenom` | chaîne | oui | non vide, 60 caractères maximum |
| `naissance` | chaîne | oui | date réelle au format `AAAA-MM-JJ` |
| `courriel` | chaîne | oui | adresse valide, unique |
| `telephone` | chaîne | non | 20 caractères maximum |
| `adhesion` | chaîne | — | date attribuée par le serveur |

Le JSON dit `naissance`, la colonne dit `date_naissance` : c'est permis. Le client ne voit que le contrat.

### Jeton

Le jeton est une ressource : on le crée avec `POST /jetons`. La création renvoie :

```json
{
  "acces": "7f3a9c2e…",
  "rafraichissement": "b81d04aa…",
  "type": "Bearer",
  "expire_dans": 900
}
```

`expire_dans` est en secondes. Jamais de mot de passe ni d'empreinte dans la réponse.

## A2. Les opérations

| Besoin | Opération | Rôle | Succès |
|---|---|---|---|
| 1. obtenir un accès | `POST /jetons` | aucun | `200` + les deux jetons |
| 2. renouveler l'accès | `POST /jetons/rafraichir` | jeton de rafraîchissement | `200` + nouveau jeton d'accès |
| 3. lister | `GET /adherents` | lecteur ou gestionnaire | `200` + liste |
| 4. consulter | `GET /adherents/{id}` | lecteur ou gestionnaire | `200` + un adhérent |
| 5. inscrire | `POST /adherents` | gestionnaire | `201` + `Location` |
| 6. corriger | `PUT /adherents/{id}` | gestionnaire | `200` + l'adhérent |
| 6. supprimer | `DELETE /adherents/{id}` | gestionnaire | `204`, sans corps |

### POST /jetons — obtenir un accès

| Rubrique | Contrat |
|---|---|
| Auth | aucune — c'est elle qu'on obtient |
| Corps | `{"identifiant", "mot_de_passe"}` · `Content-Type: application/json` |
| Succès | `200` + `{acces, rafraichissement, type, expire_dans}` |
| Erreurs | `415` mauvais `Content-Type` · `400` JSON illisible · `422` champ manquant · `401` identifiants refusés, **même message** que l'identifiant ou le mot de passe soit faux |

```bash
curl -i -X POST http://localhost:8000/jetons \
     -H 'Content-Type: application/json' \
     -d '{"identifiant":"gestionnaire","mot_de_passe":"MotDePasse!2027"}'
```

### POST /jetons/rafraichir — renouveler

| Rubrique | Contrat |
|---|---|
| Auth | le jeton de rafraîchissement, dans le corps |
| Corps | `{"rafraichissement"}` |
| Succès | `200` + `{acces, type, expire_dans}` |
| Erreurs | `415` · `400` · `422` champ absent · `401` jeton de rafraîchissement inconnu, expiré, révoqué — **ou jeton d'accès présenté à sa place** |

```bash
curl -i -X POST http://localhost:8000/jetons/rafraichir \
     -H 'Content-Type: application/json' \
     -d "{\"rafraichissement\":\"$RAFRAICHISSEMENT\"}"
```

### GET /adherents et GET /adherents/{id} — lister, consulter

| Rubrique | Contrat |
|---|---|
| Auth | `Authorization: Bearer <acces>` · rôle lecteur ou gestionnaire |
| Corps | aucun |
| Succès | `200` + une liste sur la collection, un objet sur l'élément |
| Erreurs | `401` jeton absent, mal formé, inconnu, expiré, révoqué — avec `WWW-Authenticate: Bearer` · `404` sur `/adherents/{id}` si l'adhérent n'existe pas |

Pas de `403` ici : les deux rôles ont le droit de lire.

```bash
curl -i http://localhost:8000/adherents/1 -H "Authorization: Bearer $JETON"
```

### POST /adherents — inscrire

| Situation | Code | En-tête |
|---|---|---|
| `Content-Type` absent ou faux | `415` | — |
| corps JSON illisible | `400` | — |
| jeton absent, invalide, expiré | `401` | `WWW-Authenticate: Bearer` |
| rôle lecteur | `403` | — |
| champ manquant ou invalide | `422` | — |
| courriel déjà présent | `409` | — |
| création réussie | `201` | `Location: /adherents/{id}` |

Le `422` liste **tous** les champs fautifs. Le `201` renvoie aussi l'adhérent créé.

```bash
curl -i -X POST http://localhost:8000/adherents \
     -H "Authorization: Bearer $JETON" \
     -H 'Content-Type: application/json' \
     -d '{"nom":"Martin","prenom":"Lea","naissance":"2007-03-14","courriel":"lea@club.fr"}'
```

### PUT /adherents/{id} — corriger

| Rubrique | Contrat |
|---|---|
| Auth | Bearer, rôle gestionnaire |
| Corps | la fiche entière — `PUT` remplace, il ne complète pas |
| Succès | `200` + l'adhérent modifié |
| Erreurs | `415` · `400` · `401` · `403` rôle lecteur · `404` adhérent inconnu · `422` champ manquant ou invalide · `409` courriel déjà pris par un autre adhérent |

Un champ absent du corps d'un `PUT` est une erreur `422`, pas une valeur conservée. Garder les champs absents, c'est `PATCH` — qu'on n'expose pas.

### DELETE /adherents/{id} — supprimer

| Rubrique | Contrat |
|---|---|
| Auth | Bearer, rôle gestionnaire |
| Corps | aucun |
| Succès | `204` — aucun corps, ni `{}`, ni `null` |
| Erreurs | `401` · `403` rôle lecteur · `404` identifiant inexistant (décision 3) |

### Verbe non exposé

Tout verbe non exposé sur une URI connue répond `405` avec l'en-tête `Allow` : `GET, POST` sur `/adherents`, `GET, PUT, DELETE` sur `/adherents/{id}`.

## A3. Les décisions

**1. Le format d'erreur unique.** Une seule forme pour tous les codes d'erreur, `details` vide quand aucun champ n'est en cause. Le client écrit un seul analyseur.

```json
{
  "erreur": "validation",
  "message": "Données refusées",
  "details": [
    {"champ": "courriel", "probleme": "format invalide"},
    {"champ": "naissance", "probleme": "date invalide"}
  ]
}
```

**2. 400 ou 422.** `400` = je n'arrive pas à te lire (JSON coupé, corps vide). `422` = je te lis et je refuse (nom absent, courriel sans `@`, naissance au 31 février). `409` = la donnée est juste, mais l'état l'interdit (courriel déjà en base).

**3. DELETE sur un identifiant qui n'existe pas : `404`.** Le client apprend que l'adhérent n'existait pas, ce qui révèle une erreur d'identifiant. L'état du serveur est le même dans les deux conventions ; celle-ci est choisie et tenue.

**4. La durée de vie des jetons.** Accès : **15 minutes** — il circule à chaque appel, c'est lui qu'on risque de perdre ; volé, il ne sert qu'un quart d'heure. Rafraîchissement : **7 jours** — il ne sort qu'au renouvellement, il évite de redemander le mot de passe toutes les quinze minutes.

**5. Ce qui ne sort jamais de l'API.** Le mot de passe, ni en clair ni en empreinte. Le jeton dans l'URL — toujours dans l'en-tête `Authorization`. Le message d'exception (`getMessage()`, requête SQL, chemin de fichier). Le détail d'un échec de connexion : même message que l'identifiant ou le mot de passe soit faux.

## Modifications en cours de partie B

<!-- Une ligne par changement : ce qui change, et pourquoi. -->
