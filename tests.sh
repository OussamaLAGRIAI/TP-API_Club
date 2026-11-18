#!/usr/bin/env bash
# tests.sh — jeu de tests de l'API club. Le serveur doit tourner sur le port 8000.
# À COMPLÉTER au fil de la partie B : chaque route terminée ajoute son appel ici.
set -u
BASE='http://localhost:8000'

echo '--- 1. Liste sans jeton — attendu 401 + WWW-Authenticate'
curl -i -s "$BASE/adherents"
echo

echo '--- 2. Obtention du jeton gestionnaire — attendu 200'
curl -i -s -X POST "$BASE/jetons" \
  -H 'Content-Type: application/json' \
  -d '{"identifiant":"gestionnaire","mot_de_passe":"MotDePasse!2027"}'
echo

JETON=$(curl -s -X POST "$BASE/jetons" \
  -H 'Content-Type: application/json' \
  -d '{"identifiant":"gestionnaire","mot_de_passe":"MotDePasse!2027"}' \
  | sed -n 's/.*"acces":"\([^"]*\)".*/\1/p')

echo '--- 3. Liste avec jeton — attendu 200'
curl -i -s "$BASE/adherents" -H "Authorization: Bearer $JETON"
echo

# --- 4. et suivants : à écrire (voir le sujet, partie B et partie C)
