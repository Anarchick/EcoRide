# EcoRide

**URL** : https://ecoride-20250803-89085d9ca1d5.herokuapp.com/

## Technologies utilisées
- Composer
- NPM
- PHP v8.2.12
- Symfony v7.3 --webapp
- MariaDB v11.8.2 (v10.11.10 sur Heroku)
- Déployé sur Heroku avec Nginx
- TypeScript
- Sass (via extension VS Code)
- Bootstrap v5.3.7
- Doctrine
- DoctrineExtensionsBundle
- Symfony/mailer
- Symfony/uid
- Symfony/ux-icons
- Symfony/ux-live-component
- HTMX
- Chart.js

**dev**
- Docker compose
- PhpMyAdmin
- Git (remote Github et Heroku)
- BrowserSync
- EsLint
- PHPUnit
- doctrine-fixtures-bundle
- Faker (Faker-car)
- esBuild

## Installation
❗*Ne pas cloner ce projet dans un dossier protégé tel que Xampp/htdocs/*

**préalables**
1. Installer PHP v8.2.12+ et lier à variable environnement du pc
2. Installer GIT
3. Installer Composer
5. Intsaller Node.js.
6. (Optionel mais recommandé) Installer SymfonyCLI
7. (Optionel mais recommandé) Installer Docker

*Note*: Sans Docker, il sera nécessaire d'installer et configurer manuellement MariaDB.

**variables environnement**

Créer un fichier .env.local ou autre .local avec:
```ini
FIXTURE_ADMIN_PASSWORD=MotDePasseAChanger
FIXTURE_PASSWORD=MotDePasseAChanger
```

**build**

## Déploiement Heroku

## Tester le site

Afin de tester la recherche sur Heroku, j'ai mis en place des fixtures pour les trajets.

Actuellement seuls des trajets Paris vers Lyon ou Lyon vers Paris sont disponibles, et ce, sur une période de +30 jours à compter de la date d'envoi du dossier ECF.
