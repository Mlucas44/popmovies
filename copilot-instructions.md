# Contexte du Projet : Popmovie
Ce projet est un site web Drupal au design soigné, dédié aux cinéphiles. Il s'interface avec l'API TMDB pour afficher des films (nouveautés, à venir, festival de Cannes, etc.) consultables à travers divers filtres.

Fonctionnalités clés à garder en tête :
- Listes personnalisées pour l'utilisateur (Favoris, Films vus).
- Système de notifications/rappels pour les sorties de films à venir.
- Informations de disponibilité (plateformes de streaming/VOD où le film est disponible).
- Site intégralement multilingue.

# Stack Technique
- CMS : Drupal (PHP)
- Frontend : Twig, JavaScript Vanilla (ES6+), Tailwind CSS
- Data : API TMDB
- Outils : Composer, Drush

# Architecture
- La racine web (docroot) est le dossier `/web`
- Le thème principal se trouve dans `/web/themes/custom/tmdb_theme`
- Les modules métiers se trouveront dans `/web/modules/custom/`

# Règles de Développement & Conventions (PHP/Drupal)
- Multilinguisme : Toujours utiliser les fonctions de traduction natives (ex: `t()` ou `$this->t()` en PHP, `{% trans %}` ou `|t` en Twig) pour toutes les chaînes de caractères affichées.
- Standards : Respecter strictement les Drupal Coding Standards.
- Objets : Privilégier l'injection de dépendances (Dependency Injection) et éviter au maximum les appels statiques de type `\Drupal::service()`.
- Rendu : Utiliser des Render Arrays, ne jamais construire de HTML directement dans le PHP.
- Performance : Toujours penser à l'invalidation des caches (Cache tags, Cache contexts) lors de la création de contrôleurs ou de blocs.

# Règles Frontend (JavaScript & Tailwind)
- Tailwind CSS : Toujours privilégier les classes utilitaires de Tailwind. Lire et utiliser en priorité les variables définies dans `tailwind.config.js` (notamment pour les thèmes et couleurs personnalisés).
- JS (Drupal Behaviors) : Tout le code JavaScript doit absolument être encapsulé dans des `Drupal.behaviors`.
- JS (Vanilla) : Ne pas utiliser jQuery, faire du Vanilla JS moderne.
- JS (Exécutions multiples) : Toujours utiliser `once()` dans les behaviors pour s'assurer qu'un script ne s'attache pas plusieurs fois (notamment après des requêtes AJAX).
