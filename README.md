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

**variables d'environnement**

Créer un fichier .env.local et .env.test.local avec:
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

**Démarrer les conteneurs Docker**
```bash
docker compose up
```

**Mise en place schema BDD**

Les opérations sont à répéter avec le paramètre --env=test
```bash
php bin/console doctrine:create --if-not-exists
php bin/console doctrine:migrations:migrate
php bin/console cache:clear
```
Cela créera les bases de données ecoride et ecoride_test

**Démarrer le serveur de développement**

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
# Se placer dans la branche master qui contient un .gitignore adapté pour Heroku
git checkout master

# La compilation des assets ne fonctionne pas sur Heroku
# Le .gitignore de la branche master est configuré pour Heroku
npm run build:prod
npm run ts:prod
php bin/console asset-map:compile

heroku login
# A faire une seule fois
heroku create ecoride-20250803 --region eu
# Envoyer le commit sur Heroku et attendre qu'il installe les paquets
git push heroku master

# Pas besoin sur Heroku, il le fait automatiquement
#heroku run "composer install --no-dev --optimize-autoloader"

# Définir les variables d'environnement sur Heroku
heroku config:set APP_ENV=prod
heroku config:set APP_DEBUG=0
heroku config:set APP_SECRET=$(php -r 'echo bin2hex(random_bytes(16));')
heroku config:set DATABASE_URL=urlFourniParJawsDB
heroku config:set SPEC_SHAPER_ENCRYPT_KEY=$(php -r "echo base64_encode(openssl_random_pseudo_bytes(32)))"

# Créer la base de données, appliquer les migrations et vider le cache 
heroku run "php bin/console doctrine:create --if-not-exists"
heroku run php bin/console doctrine:migrations:migrate
heroku run "php bin/console cache:clear --env=prod"

heroku open
```

## Tester le site

1. Afin de tester la recherche sur Heroku, j'ai mis en place des fixtures pour les trajets et l'utilisateur.

2. Actuellement seuls des trajets Paris vers Lyon ou Lyon vers Paris sont disponibles, et ce, jusqu'au 14 octobre 2025.

3. En vous connectant avec le compte admin, vous pouvez accéder à /admin qui a un accès restreint et qui affiche un chart.js en async.

4. Après votre connexion réussie, le /profil affiche 3 boutons utilitaires pour pallier aux fonctionnalités non implémentées. Celui nommé "Créer un trajet" nécessite une recherche "Annecy vers Lyon demain jusqu'à 4 personnes.

## Diagrammes
le dossier diagrams/ contient des .erd et .mmd

Il est nécessaire d'avoir les extensions VS Code ERD Editor et mermaid Chart afin de les visualiser.
