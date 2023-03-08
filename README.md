# Projet d'extension Typo3 pour OpenAgenda

## Contexte

OpenAgenda édite un outil collaboratif permettant la publication de programmations événementielles en données ouvertes: https://openagenda.com

La société existe depuis 2015 et est composée aujourd'hui de 6 salariés dont 4 développeurs, tous en France et fonctionnons en télétravail depuis le début de la pandémie. Nous développons en node.js/react.

Nos clients sont pour l'essentiel des territorialités, des ministères et des associations. Ils nous utilisent soit pour présenter une programmation continue, soit pour des opérations ponctuelles.

Quelques exemples:

 * La métropole de Bordeaux: https://openagenda.com/bordeaux-metropole
 * Les manifestations culturelles nationales du ministère de la Culture: https://openagenda.com/ndm-2023
 * La ligue de protection des oiseaux: https://openagenda.com/nuit-de-la-chouette

Nous proposons depuis quelques années des extensions [présentées ici](https://developers.openagenda.com/tag/60-plugins/) (une librairie nodeJs, un plugin Wordpress et un module Drupal) qui fonctionnent de la même manière: elles permettent à un intégrateur de travailler la présentation d'un agenda dans un site en s'appuyant sur les fonctionnalités proposées par [notre API](https://developers.openagenda.com/) et notre [librairie de filtres](https://www.npmjs.com/package/@openagenda/react-filters).

Un sondage effectué en fin 2022 a révélé qu'un nombre important de nos utilisateurs utilise Typo3: nous souhaitons en conséquence engager le développement d'une extension Typo3 qui présenterait les mêmes fonctionnalités que les extensions déjà proposées et qui sera publiée sous licence MIT sur le dépot https://github.com/OpenAgenda/typo3 

## Fonctionnement

Le plugin permettrait à l'intégrateur de gérer une ou plusieurs intégrations d'agendas. Chaque intégration permettrait la personnalisation de deux types de vues:

 * Une vue liste, présentant les événements de l'agenda ainsi qu'un jeu de filtres
 * Une vue détail d'un événement.

L'intégrateur partirait d'un gabarit par défaut, présentant les filtres usuels (recherche texte, calendrier, carte) intégrables par des shortcodes dérivés de ceux proposés par la librairie [@openagenda/react-filters](https://www.npmjs.com/package/@openagenda/react-filters). Cette librairie est utilisée aujourd'hui pour générer les filtres présentés sur les pages publiques et d'administration d'agendas sur [OpenAgenda](https://openagenda.com/zonefranche?lang=fr) et sur les extensions déjà publiée ([un autre exemple](https://festival.bar-bars.com/programmation/festival-bar-bars-2022/), cette fois avec le plugin WP)

[@openagenda/react-filters](https://www.npmjs.com/package/@openagenda/react-filters) propose le nécessaire pour créer un petit controleur js qui assurera le lien entre les composants js de la page et le controleur Typo3 en charge de préparer l'appel API vers OpenAgenda qui permettra la génération du contenu à mettre à jour sur la page lors d'une recherche.

Un exemple de cheminement d'un rafraichissement d'une vue liste pour illustrer le fonctionnement désiré de l'extension:

1. L'utilisateur active un filtre sur la page via un composant généré par @openagenda/react-filters. Un filtre est placé sur la page via un shortcode, capable de générer le HTML du filtre comme présenté [ici](https://github.com/OpenAgenda/oa-public/blob/main/react-filters/example/views/index.ejs) (voir les divs avec attribut `oa-data-filter` 
2. Un hook du contrôleur js de la page est appelé avec la nouvelle valeur assemblée du filtre, un objet JS adapté à la synthaxe demandée par l'API OA. Voici ce controleur sur le [module Wordpress](https://github.com/OpenAgenda/wordpress/blob/main/assets/js/main.js) et sur [@openagenda/react-filters](https://github.com/OpenAgenda/oa-public/blob/main/react-filters/example/assets/main.js).. en particulier l'objet assigné à `window.oa`. Le hook envoie la valeur assemblée du filtre au controleur typo3 en charge de regénérer et de rendre le HTML qui viendra remplacer la liste des événements.
3. Le contrôleur Typo3 lit la valeur màj des filtres sur la requête
3.1. ... charge la configuration de l'intégration de l'agenda de la db (ou d'une cache) qui comprend l'identifiant de l'agenda, une clé API, un éventuel pré-filtre et construit la requête get à faire à l'API OpenAgenda. Un SDK est proposé [ici](https://github.com/OpenAgenda/sdk-php) et sera utile pour cette partie.
3.2 lance l'appel API pour récupérer les événements correspondants
3.3 applique les événements au gabarit du bloc de liste pour générer un HTML
3.4 renvoie le rendu au controleur Js
4. Le controleur JS applique le HTML récupéré sur le bloc de liste. Les autres éléments de la page (filtres placés en périphérie, l'entête de la liste..) ne sont pas modifiés par ce remplacement.

## Configuration globale de l'extension

Les informations suivantes sont utiles à garder au niveau d'une configuration générale à une installation de l'extension sur un site:

 * **La clé API** à utiliser lors des appels est le plus souvent celle d'un utilisateur OpenAgenda, administrateur des agendas qui seront intégrés sur le site.
 * **Autoriser les contenus embarqués**: contrôle une option à ajouter sur lors des appels API qui permet de récupérer les descriptifs des événements avec contenus multimédias intégrés
 * **Durée de la cache**: Lorsqu'un contenu est chargé de l'API, il est placé dans une cache pour une période d'une demi-heure par défaut.
 
## Configuration d'une intégration

La configuration de chaque intégration rassemble les informations suivantes:

 * **L'identifiant de l'agenda**: visible en pied de barre latérale des pages agendas sur OpenAgenda.
 * **La route où sera affichée l'intégration**. Par exemple: `/culture` pour que la liste des événements soit visible à l'URL `https://site-typ.o3/culture` et le détail sur `https://site-typ.o3/culture/events/le-slug-de-levenement`.
 * **Un préfiltre** - celui-ci est systématiquement appliqué lors de chaque appel API: il permet de limiter l'intégration à un sous-ensemble d'événements d'un agenda. Par exemple: un pré-filtre "Théâtre" permet d'afficher les programmations de théâtres saisies sur l'OpenAgenda à intégrer. La configuration peut prendre la forme d'un filtre sous forme d'URL, récupéré de la page publique de l'agenda ou bien de son administration.
 * **Un préfiltre temporel** - prend la forme d'une case à cocher sur l'UI de configuration. Il n'est appliqué que sur les appels pour la vue liste et seulement quand aucun filtre temporel n'est défini par le visiteur (clés timings ou relative). Il applique la valeur `?relative[]=current&relative[]=upcoming` à la requête pour ne faire remonter que les événements en cours et à venir.
 * Optionnellement, **une clé API** autre que celle précisée sur la configuration générale.
 * **Les gabarits personnalisés**. Un pour la liste, un autre pour pour l'événement.


## Détails fonctionnels

### Contexte de navigation

Celui-ci est généré pour chaque lien d'item de liste, se place en face d'un paramètre de navigation et permet de maintenir un contexte de navigation permettant l'utilisateur de naviguer d'une fiche détail à la suivante pour une requête donnée: trois liens peuvent être alors placés sur la fiche détail: précédent, suivant, retour à la liste. Ces liens permettent à un contrôleur placé derrière une route de décoder le contexte passé pour la direction à prendre pour requêter l'API pour connaitre l'événement à afficher et lancer une redirection.

### Les gabarits

Les gabarits permettant l'affichage des événements sont personnalisables. Il sera utile en mode de développement de permettre à l'intégrateur de lui offrir sur un url dérivé de la vue affichée une vue "data" qui lui permettra de visualiser les données présentes dans les événements: elles ne se limitent pas aux données standard présentées sur la documentation de l'API. Beaucoup d'agendas permettent la saisie de données additionnelle aux formats multiples. Par exemple, sur [@openagenda/agenda-portal](https://www.npmjs.com/package/@openagenda/agenda-portal) l'ajout d'un ?data=1 dans l'URL en développement affiche une variante JSON présentant toutes les données accessibles au gabarit à cet URL.

Ces données doivent être facilement accessibles à l'intégrateur pour sa personnalisation

L'option "includeLabels" devra être utilisée pour récupérer les labels liés aux champ à choix

Le gabarit par défaut, point de départ de l'intégrateur lors de son intégration, répliquera la structure de page des pages liste & événements présentes sur OpenAgenda, de manières suffisamment neutre pour que le minimum d'ajustements soient nécessaires pour l'adapter au site hébergeant.

Le gabarit par défaut affichera en information complémentaire un lien pointant vers la page OpenAgenda de l'agenda: "Voir sur OpenAgenda". L'intégrateur sera libre de retirer ce lien.

### Exports

Les programmations étant proposées en données ouvertes, des liens d'exports sont proposées sur les pages agendas, permettant la reprise de la sélection courante: les urls d'exports sont ceux présentés sur OpenAgenda, les valeurs des filtres sont celles présentées sur l'URL complétées des éventuels préfiltres: une route Typo3 devra donc construire un lien de redirection en prenant le filtre de l'utilisateurs, les préfiltres configurés et le format demandé (csv, xlsx, pdf, ical, rss)

### Favoris, totaux, filtres actifs

[@openagenda/react-filters](https://www.npmjs.com/package/@openagenda/react-filters) propose des shortcodes qui permettent l'ajout de "widgets" Js fonctionnels pour:

 * Afficher le total des événements correspondant à une recherche (à placer en tête de liste en général)
 * Afficher des items cliquables représentant chaque filtre actif, un clic supprime le filtre correspondant
 * Afficher un filtre "favoris" et un toggle à afficher sur chaque item de liste (ou sur la vue de détail) permettant l'ajout ou le retrait d'un événement sur la liste.

Ces widgets devront être présents sur les gabarits par défaut.

### Vue détail événement: les horaires

Les horaires s'affichent sur une vue les listant par mois, similairement à ce qui est proposé sur [OpenAgenda](https://openagenda.com/sqy/events/au-fil-des-mots-932363), où [ici](https://www.saint-quentin-en-yvelines.fr/fr/agenda-de-saint-quentin-en-yvelines/au-fil-des-mots-932363?oac=eyJpbmRleCI6MCwidG90YWwiOjE5MSwiZmlsdGVycyI6eyJyZWxhdGl2ZSI6WyJjdXJyZW50IiwidXBjb21pbmciXSwiZGV0YWlsZWQiOjF9fQ%3D%3D)

Un traitement sera nécessaire pour ventiler les horaires selon les mois puis les semaines. La fonction php effectuant ce traitement pour le module Drupal [OpenagendaEventProcessor](https://github.com/OpenAgenda/drupal/blob/master/openagenda/src/OpenagendaEventProcessor.php) pourra être reprise pour ce traitement.

### Bloc de prévisualisation

Les sites intégrant une page agenda affichent fréquemment un bloc sur la page d'accueil présentant une sélection d'événements. Souvent les prochains événements à venir. L'extension doit permettre à l'intégrateur de placer un tel bloc lié à un des agendas intégrés sur une page dédiée du site. Un clic sur un des événements présentés par le bloc renvoie l'utilisateur vers la vue détaillée de l'événement dans le contexte de l'agenda.
