# SecureAccess - Système d'Authentification Sécurisé

## Vue d'ensemble

SecureAccess est un système d'authentification web PHP complet qui met en œuvre plusieurs fonctionnalités de sécurité avancées. Le projet fournit une solution robuste pour la gestion des utilisateurs avec authentification à deux facteurs (2FA), gestion des mots de passe, et journalisation des activités.

Fonctionnalités principales

- **Système d'authentification** avec nom d'utilisateur et mot de passe
- **Authentification à deux facteurs (2FA)** par envoi de code par email
- **Gestion des mots de passe**
  - Réinitialisation sécurisée par email
  - Hachage des mots de passe avec `password_hash()`
  - Validation des mots de passe (minimum 8 caractères)
- **Gestion des utilisateurs**
  - Inscription avec validation des données
  - Gestion des rôles (administrateur/utilisateur)
  - Sessions sécurisées
- **Sécurité**
  - Protection contre les injections SQL avec PDO et requêtes préparées
  - Gestion des tokens d'authentification avec expiration
  - Notifications par email pour les actions sensibles
- **Journalisation**
  - Traçage des activités des utilisateurs
  - Journalisation des erreurs

## Structure du projet

```
secureaccess/
├── config.php              # Configuration de la base de données et fonctions utilitaires
├── mail_helper.php         # Fonctions d'envoi d'email avec PHPMailer
├── login.php               # Gestion de l'authentification
├── register.php            # Inscription des utilisateurs
├── two_factor.php          # Vérification du code 2FA
├── forgot_password.php     # Demande de réinitialisation de mot de passe
├── reset_password.php      # Réinitialisation de mot de passe
├── welcome.php             # Page d'accueil après connexion
├── css/                    # Feuilles de style CSS
│   ├── login.css
│   ├── register.css
│   ├── two_factor.css
│   ├── forgot_password.css
│   ├── reset_password.css
│   └── welcome.css
└── views/                  # Templates HTML
    ├── login_view.php
    ├── register_views.php
    ├── two_factor_view.php
    ├── forgot_password_view.php
    ├── reset_password_view.php
    └── welcome_view.php
```

## Prérequis

- PHP 7.4 ou supérieur
- Serveur MySQL/MariaDB
- Composer (pour l'installation des dépendances)
- Extension PDO PHP activée
- PHPMailer (installé via Composer)

## Installation

1. Clonez le dépôt dans votre répertoire web:
   ```
   git clone https://github.com/gabin13/SecureAccess
   ```

2. Installez les dépendances avec Composer:
   ```
   cd secureaccess
   composer install
   ```

3. Créez la base de données et les tables nécessaires:
   ```sql
   CREATE DATABASE secureaccess;
   
   USE secureaccess;
   
   CREATE TABLE users (
     id INT AUTO_INCREMENT PRIMARY KEY,
     username VARCHAR(100) NOT NULL UNIQUE,
     email VARCHAR(255) NOT NULL UNIQUE,
     password VARCHAR(255) NOT NULL,
     role ENUM('user', 'admin') NOT NULL DEFAULT 'user',
     created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
   );
   
   CREATE TABLE auth_tokens (
     id INT AUTO_INCREMENT PRIMARY KEY,
     user_id INT NOT NULL,
     token_type ENUM('two_factor', 'password_reset') NOT NULL,
     token VARCHAR(255) NOT NULL,
     expires_at DATETIME NOT NULL,
     used BOOLEAN DEFAULT FALSE,
     created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
     FOREIGN KEY (user_id) REFERENCES users(id)
   );
   ```

4. Modifiez les informations de connexion à la base de données dans `config.php`:
   ```php
   define('DB_HOST', 'votre_host');
   define('DB_USER', 'votre_utilisateur');
   define('DB_PASS', 'votre_mot_de_passe');
   define('DB_NAME', 'secureaccess');
   ```

5. Configurez les paramètres SMTP dans `mail_helper.php` pour l'envoi d'emails:
   ```php
   // Remplacez avec vos informations SMTP
   $user = 'votre_email@gmail.com';
   $pass = 'votre_mot_de_passe_app';
   ```
   Ne pas oublier de configurer la clé d'application sur votre compte gmail

## Utilisation

1. Accédez à la page d'inscription pour créer un nouveau compte:
   ```
   http://votre-serveur/secureaccess/register.php
   ```

2. Connectez-vous avec vos identifiants:
   ```
   http://votre-serveur/secureaccess/login.php
   ```

3. Validez le code 2FA envoyé par email pour compléter la connexion

## Mode développement

Le système intègre un mode développement qui affiche les codes 2FA et les liens de réinitialisation de mot de passe directement dans l'interface en cas d'échec d'envoi d'email, ce qui facilite les tests locaux.

## Sécurité

- Les mots de passe sont hachés avec l'algorithme par défaut de PHP (bcrypt)
- Les tokens d'authentification expirent après un délai défini
- Les sessions utilisateur sont protégées
- Les requêtes SQL utilisent des requêtes préparées via PDO
- Les données de formulaire sont validées côté serveur

## Personnalisation

Le système utilise une interface responsive avec des feuilles de style CSS minimalistes qui peuvent être facilement personnalisées selon vos besoins.

## Journalisation

Les activités utilisateur sont enregistrées dans un fichier `user_logs.txt` avec horodatage, nom d'utilisateur et action effectuée. Les erreurs système sont journalisées dans `error.log`.

## Améliorations possibles

- Intégration de CAPTCHA pour la protection contre les attaques par force brute
- Limitation des tentatives de connexion
- Implémentation de JWT pour une API RESTful
- Ajout d'un système de permissions plus avancé
- Interface d'administration

## Remarque importante

Ce projet contient des identifiants de connexion SMTP dans le code source. Dans un environnement de production, il est fortement recommandé de stocker ces informations dans des variables d'environnement.
