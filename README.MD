# Acheto — Le bon choix à chaque fois.

Plateforme marocaine de petites annonces : publier, rechercher, consulter et gérer des annonces
de vente de produits et de services. Interface en **arabe (RTL)** et en **français (LTR)**,
mode clair et mode sombre, prix en dirhams (DH).

## Technologies

HTML5, CSS3, Bootstrap 5, JavaScript, PHP, MySQL (PDO), XAMPP.

## Fonctionnalités

- Inscription, connexion, déconnexion, profil et changement de mot de passe
- Publication d'annonces en 4 étapes (catégorie, détails, photos, contact) avec aperçu avant l'envoi
- Validation par l'administrateur : approuver, refuser avec motif, supprimer, mettre à la une
- Une annonce approuvée expire automatiquement après 60 jours
- Recherche par mots-clés, filtres (catégorie, ville, prix min/max) et tri (date, prix)
- Page de détails avec galerie, appel, WhatsApp et message au vendeur
- Favoris, boîte de réception des messages du vendeur
- Espace administrateur : annonces, utilisateurs, catégories, villes, messages du site
- Pages : Accueil, Annonces, Contact, À propos, Conditions d'utilisation, Politique de confidentialité

## Installation (en local avec XAMPP)

1. Installer **XAMPP** et démarrer **Apache** et **MySQL** (fermer WAMP s'il est ouvert : mêmes ports).
2. Copier le dossier du projet dans `C:\xampp\htdocs\ACHETO.PROJET`.
3. Ouvrir `http://localhost/phpmyadmin`, onglet **Importer**, et importer `database/acheto.sql`
   (la base `acheto` est créée avec les tables, les catégories et les villes).
4. Dans le dossier `config/`, copier `env.example.php` sous le nom `env.php`
   et y mettre le mot de passe MySQL local (laisser vide si MySQL n'a pas de mot de passe).
5. Ouvrir `http://localhost/ACHETO.PROJET/`.

> `config/env.php` n'est **jamais** publié sur GitHub (voir `.gitignore`).

## Créer le compte administrateur

1. S'inscrire normalement depuis la page Inscription (par exemple `admin@acheto.ma`).
2. Dans phpMyAdmin, onglet **SQL** de la base `acheto` :

```sql
UPDATE utilisateurs SET role = 'admin' WHERE email = 'admin@acheto.ma';"# acheto" 
