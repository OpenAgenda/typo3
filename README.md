# Extension TYPO3

Notre extension pour TYPO3 vous permet d'intégrer vos agendas hébergés chez https://openagenda.com directement sur votre site TYPO3.

L'extension est publiée sur le répertoire officiel https://extensions.typo3.org et est disponible en logiciel libre sur [github.com](https://github.com/OpenAgenda/typo3).

## Installation et configuration

Après avoir installé l'extension, rendez-vous dans le module "Réglages (Settings)" puis sur le bloc "Extension Configuration". Cliquez sur le bouton "Configure Extension" et configurez l'extension avec les éléments souhaités :

* **OpenAgenda account public key :** La clé permettant l'affichage de votre agenda
* **Include embedded content :** Option permettant d'inclure les descriptions des événements en HTML
* **Only current and upcoming events :** Option permettant d'afficher uniquement les événements en cours et à venir
* **Openagenda default style :** Style par défaut de l'agenda ("agenda" possède des styles prédéfinis)

## Fonctionnement

A l'installation, l'extension crée 2 nouveaux plugins appelés **"OpenAgenda Agenda"** et **"OpenAgenda Prévisualisation"**.

Pour installer les plugins, rendez-vous sur une page et insérez un contenu de type "Plugin". Dans l'onglet "Plugin", choisissez undes 2 plugins disponibles et paramétrez-le.

Le plugin **"OpenAgenda Agenda"** permet au contributeur de gérer une ou plusieurs intégrations d'agendas en mode "Liste". Voici les réglages pour la vue "Liste" :

* **ID du calendrier OpenAgenda** : Identifiant du calendrier à intégrer
* **Nombre d'événements par page** : Nombre d'événements souhaité par page
* **Langue par défaut** : Langue par défaut à utiliser lorsque la localisation du site IInternet n'est pas faisable
* **Nombre de colonnes** : Nombre de colonnes pour l'affichage des événements
* **Lien du fond de carte par défaut** : Lien vers l'image OpenStreetMap pour l'affichage de la carte
* **Préfiltre** : Préfiltre utilisé pour l'affichage de certains événements de l'agenda en toute transparence pour l'utilisateur
* **Afficher le filtre "Carte"** : Affichage du filtre permettant de naviguer sur la carte OpenStreetMap
* **Afficher le filtre "Total"** : Affichage du nombre d'événements correspondants aux résultats de recherche 
* **Afficher le filtre "Active"** : Affichage des filtres actifs
* **Afficher le filtre "Favoris"** : Affichage du filtre permettant d'afficher les événements préalablement mis en favoris
* **Afficher le filtre "Recherche"** : Affichage du filtre de recherche par mots
* **Afficher le filtre "Villes"** : Affichage du filtre avec des choix multiples sur les villes
* **Afficher le filtre "Dates"** : Affichage du filtre par dates
* **Afficher le filtre "Mots-clés"** : Affichage du filtre permettant de trier par mots-clés
* **Afficher le filtre "Champ additionnel"** : Affichage du filtre des champs additionnels paramétrés dans OpenAgenda
* **Noms des "Champs additionnels" (séparés par un point virgule)** : Permet d'afficher les différents champs additionnels paramétrés dans OpenAgenda
* **Afficher le filtre "Relatif"** : Affichage du filtre sur les événements passés, en cours ou à venir

Le plugin **"OpenAgenda Prévisualisation"** permet au contributeur d'afficher une prévisualisation de quelques événements. Voici les réglages pour la vue "Prévisualisation" :

* **ID du calendrier OpenAgenda** : Identifiant du calendrier à intégrer
* **Nombre d'événements en Prévisualisation** : Nombre d'événements souhaité sur le bloc de prévisualisation
* **Langue par défaut** : Langue par défaut à utiliser lorsque la localisation du site IInternet n'est pas faisable
* **Nombre d'événements par colonnes** : Nombre de colonnes pour l'affichage de la prévisualisation des événements
* **Page OpenAgenda** : Lien vers la page qui affiche le mode "Liste" de l'agenda
* **Afficher le lien vers l'agenda** : Affichage du lien permettant d'être redirigé vers le mode "Liste" permettant de naviguer sur la carte OpenStreetMap
* **Préfiltre** : Préfiltre utilisé pour l'affichage de certains événements de l'agenda en toute transparence pour l'utilisateur
