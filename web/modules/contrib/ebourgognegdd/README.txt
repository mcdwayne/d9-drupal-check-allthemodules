
-- RÉSUMÉ --

Ce plugin vous permettra de créer et d'intégrer à votre site un bloc affichant les annuaires e-bourgogne (http://www.e-bourgogne.fr).

Pour une description complète du projet, visitez la page :
  http://drupal.org/project/ebourgogneannuaires
Pour soumettre des rapports de bug et des suggestions, ou suivre les changements :
  http://drupal.org/project/issues/ebourgogneannuaires

-- PRÉ-REQUIS --

Pour fonctionner, le module e-bourgogne Guide des Droits et Démarches (GDD) nécessite que le module CKEditor soit installé et activé.


-- INSTALLATION --

* L'installation se fait de manière classique, voir :
  http://drupal.org/documentation/install/modules-themes/modules-8


-- CONFIGURATION --

* Allez dans Administration » Extensions et ouvrez la page de configuration du module. Renseignez votre clé d'API e-bourgogne (fournie par votre administrateur d'organisme) et cliquez sur "Enregistrer la clé" (si votre clé est valide, vous verrez apparaître les options de configuration du module).

* Allez ensuite dans  Administration » Structure » Mise en page des blocs ; dans la zone où vous souhaitez ajouter le module d'inscription, cliquez sur "Placer bloc", puis séléctionnez le bloc "Configuration block for e-bourgogne guides".

* Dans le panneau de configuration du module e-bourgogne Annuaire ; pour créez un bloc Annuaire, vous devez :
  
  - choisir le niveau de guide e-bourgogne que vous souhaitez afficher (qu'il s'agisse d'un guide ou d'une catégorie de guides)

  - indiquer un titre de bloc

  - (facultatif) indiquer l'url d'une feuille de style CSS que vous souhaitez appliquer au guide

  - cliquez sur "Enregistrer le bloc"

* Configurez le placement et l'affichage de ce bloc comme vous le souhaitez.


-- FAQ --

Q: Où puis-je récupérer ma clé d'API ?
R: La clé d'API permettant d'accéder aux services e-bourgogne via les plugins doit vous être fournie par l'administrateur de votre entité. Elle doit ensuite être renseignée dans le panneau de configuration du plugin e-bourgogne.

Q: Comment configurer mes guides ?
R: La configuration des guides se fait via e-bourgogne (http://wwww.e-bourgogne.fr), dans le service Mon Service Public » Mon guide des démarches administratives. 

Q: Puis-je afficher plusieurs guides sur une même page ?
R: Il est fortement déconseillé d'intégrer plusieurs annuaires sur une même page. Cela reste possible, mais peut conduire à des comportements anormaux des guides.


-- CONTACT --

Current maintainers :
