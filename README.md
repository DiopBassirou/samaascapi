# ðŸ† SAMA ASC - Backend (Laravel API)

Bienvenue sur le dÃ©pÃ´t du Backend de **SAMA ASC**, la plateforme ultime de gestion des Associations Sportives et Culturelles (NavÃ©tanes) au SÃ©nÃ©gal. 
Ce backend agit comme le cÅ“ur de l'application SaaS, offrant une architecture **Clean Code (MVC)** robuste, sÃ©curisÃ©e, et scalable.

## ðŸš€ FonctionnalitÃ©s Principales

- **Authentification SÃ©curisÃ©e** : BasÃ©e sur **Laravel Sanctum** (JWT).
- **Architecture SaaS & Multitenancy** : Chaque utilisateur appartient Ã  une ASC (ex: JAR-MB-4521). Chaque transaction, match, ou joueur est cloisonnÃ© par ASC.
- **Workflow Super Admin** : Les demandes de crÃ©ation d'ASC nÃ©cessitent l'upload d'un rÃ©cÃ©pissÃ© et doivent Ãªtre validÃ©es manuellement (Protection anti-usurpation).
- **PÃ´le Sportif** :
  - Gestion de l'effectif (Joueurs, Postes).
  - Gestion des convocations tactiques (Titulaire, RemplaÃ§ant, Repos).
- **PÃ´le CompÃ©tition** : 
  - SystÃ¨me de Poules et gestion du Calendrier des Matchs.
  - Mise Ã  jour des scores et recalcul automatique du classement (Points, Goal Average).
- **PÃ´le Supporter** :
  - SystÃ¨me de notation des joueurs (1 Ã  10) post-match de maniÃ¨re anonyme (Hachage cryptographique).
- **PÃ´le Financier** :
  - Enregistrement des EntrÃ©es/Sorties.
  - Export PDF professionnel des bilans financiers Ã  la volÃ©e grÃ¢ce Ã  dompdf.

## ðŸ› ï¸ PrÃ©requis

- PHP >= 8.2
- Composer
- SQLite (ou MySQL/PostgreSQL configurÃ©)

## ðŸ“¦ Installation & DÃ©marrage

1. **Cloner le projet** (ou ouvrir le dossier sama_asc_api)
2. **Installer les dÃ©pendances PHP** :
   `ash
   composer install
   `
3. **Configuration de l'environnement** :
   `ash
   cp .env.example .env
   # Pensez Ã  gÃ©nÃ©rer la clÃ© d'application
   php artisan key:generate
   `
4. **Base de DonnÃ©es** :
   Le projet est configurÃ© par dÃ©faut pour utiliser SQLite. Assurez-vous que le fichier database/database.sqlite existe.
   ExÃ©cutez les migrations :
   `ash
   php artisan migrate:fresh --seed
   `
5. **DÃ©marrer le serveur de dÃ©veloppement** :
   `ash
   php artisan serve --host=0.0.0.0 --port=8000
   `
   *Note : L'API sera accessible sur http://localhost:8000/api (ou 10.0.2.2 pour l'Ã©mulateur Android).*

## ðŸ“š Documentation API

Une documentation Swagger est prÃªte Ã  Ãªtre gÃ©nÃ©rÃ©e via L5-Swagger :
`ash
php artisan l5-swagger:generate
`
L'interface de la documentation sera accessible sur http://localhost:8000/api/documentation.
