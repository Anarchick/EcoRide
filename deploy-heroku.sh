# !/bin/bash

# Script de déploiement pour Heroku
# Doit être éxécuté sur la branche 'main'

set -e  # Arrêter le script en cas d'erreur

# Vérifier que nous sommes sur la bonne branche
BRANCH=$(git branch --show-current)
if [ "$BRANCH" != "main" ]; then
    echo "Vous devez être sur la branche 'main' pour déployer"
    exit 1
fi

# Build les assets avant le déploiement
php bin/console asset-map:compile

# Installer Heroku CLI
# https://devcenter.heroku.com/articles/heroku-cli

heroku login

# Créer l'application Heroku (une seule fois)
#heroku create ecoride-20250803 --region eu

heroku config:set APP_ENV=prod
heroku config:set APP_DEBUG=0
heroku config:set APP_SECRET=$(php -r 'echo bin2hex(random_bytes(16));')

# Déployer
git add .
git commit -m "Deploy to Heroku"
git push heroku main

# Installer les dépendances
heroku run composer require symfony/requirements-checker
heroku run composer install --no-dev --optimize-autoloader

# Exécuter les migrations
heroku run php bin/console doctrine:database:create --if-not-exists --no-interaction
heroku run php bin/console doctrine:migrations:migrate --no-interaction

# Vider le cache
heroku run php bin/console cache:clear --env=prod

heroku open
