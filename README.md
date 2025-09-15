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
```bash
composer install
npm run build:watch
php bin/console asset-map:compile
```

**Démarer les containers docker**
```bash
docker compose up
```

**Démarer le serveur de dévellopement**

```bash
# Nécéssite SymfonyCLI
symfony serve
# browser-sync
nmp run dev
```

**url en développement**
site : localhost:8000
phpmyadmin: localhost:8080
mailer: localhost:8025 

## Déploiement Heroku
```bash
# La compilation des assets ne fonctionne pas sur heroku
# Le .gitignore de la branche master est configurer pour heroku
php bin/console asset-map:compile

heroku login
# A faire une seule fois
heroku create ecoride-20250803 --region eu
git push heroku master

heroku config:set APP_ENV=prod
heroku config:set APP_DEBUG=0
heroku config:set APP_SECRET=$(php -r 'echo bin2hex(random_bytes(16));')
heroku config:set DATABASE_URL=urlFourniParJawsDB

heroku run "php bin/console doctrine:create --if-not-exists"
heroku run php bin/console doctrine:migrations:migrate
heroku run "php bin/console cache:clear --env=prod"

heroku open
```

## Tester le site

1. Afin de tester la recherche sur Heroku, j'ai mis en place des fixtures pour les trajets et l'utilisateur.

2. Actuellement seuls des trajets Paris vers Lyon ou Lyon vers Paris sont disponibles, et ce, sur une période d'environ +30 jours à compter de la date d'envoi du dossier ECF.

3. En vous connectant avec le compte admin, vous pouvez accéder à /admin qui a un accès restreint et qui affiche un chart.js en async

4. Après votre connexion réussie, le /profil affiche 3 boutons utilitaires pour pallier aux fonctionnalités non implémentées. Celui nommé "Créer un trajet" nécessite une recherche "Annecy vers Lyon demain jusqu'à 4 personnes

## Diagrammes
le dossier diagrams/ contient des .erd et .mmd

Il est nécéssaire d'avoir les extension ERD Editor et mermaid Chart afin de les visualiser.
