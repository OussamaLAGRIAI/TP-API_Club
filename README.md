<<<<<<< HEAD
# API club — squelette de la partie B du TP 5

## Démarrer

```bash
docker compose up -d                       # PostgreSQL 16 puis PHP 8.4, schéma chargé au premier démarrage
docker compose exec api php sql/seed.php   # 2 comptes et 5 adhérents — rejouable
curl -i http://localhost:8000/adherents    # attendu au départ : 500, exiger_jeton() n'est pas écrite
```

Comptes : `gestionnaire` / `MotDePasse!2027` et `lecteur` / `Lecture!2027`.

Repartir d'une base vide : `docker compose down -v`, puis les deux premières commandes.

## Ce qui est fourni, ce qui est à écrire

| Fichier | État |
|---|---|
| `src/db.php` | fourni, ne pas modifier |
| `src/http.php` | fourni : `json_out()`, `erreur()`, `read_json_body()` |
| `src/auth.php` | `creer_jeton()`, `jeton_presente()` fournies · **`exiger_jeton()`, `exiger_role()` à écrire (B1)** |
| `public/index.php` | routage fait · `GET /adherents` et `GET /adherents/{id}` faites · **cinq routes à écrire (B2 à B4)** — elles répondent `501` en attendant |
| `sql/schema.sql`, `sql/seed.php` | fournis, ne pas modifier |
| `compose.yaml`, `Dockerfile` | fournis |
| `CONTRAT.md` | la correction de la partie A — la référence à implémenter |
| `tests.sh` | trois appels donnés, **la suite à écrire au fil de la partie B** |

Le code se modifie à chaud : le dossier est monté dans le conteneur, pas besoin de reconstruire l'image.
=======
# TP-API_Club
Oussama_LAGRIAI &amp; scheril_Valdino
>>>>>>> 6cb8b35c5b19425f05b2342e94824a85a0001252
