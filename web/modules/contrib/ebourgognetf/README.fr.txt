
-- RÉSUMÉ --

Ce plugin vous permettra d'intégrer directement dans vos pages les liens vers vos téléformulaires créés dans le service "Mes démarches" d'e-bourgogne (http://www.e-bourgogne.fr).

Pour une description complète du projet, visitez la page :
  http://drupal.org/project/ebourgognetf
Pour soumettre des rapports de bug et des suggestions, ou suivre les changements :
  http://drupal.org/project/issues/ebourgognetf

-- PRÉ-REQUIS --

Pour fonctionner, le module e-bourgogne Téléformulaire nécessite que le module CKEditor soit installé et activé.


-- INSTALLATION --

* L'installation se fait de manière classique, voir :
  http://drupal.org/documentation/install/modules-themes/modules-8


-- CONFIGURATION --

* Allez dans Administration » Extensions et ouvrez la page de configuration du module. Renseignez votre clé d'API e-bourgogne (fournie par votre administrateur d'organisme) et cliquez sur "Enregistrer la clé" (si votre clé est valide, vous verrez apparaître la liste des téléformulaires disponibles pour votre organisme).

* Allez ensuite dans  Administration » Configuration » Rédaction de contenu , et ajoutez à chacun des formats souhaité le bouton d'ajout de lien vers les Téléformulaires e-bourgogne :
  
  - cliquez sur "Configurer" sur le format choisi (il doit utiliser CKEditor comme éditeur)

  - dans la section "Configuration de la barre d'outils" glissez/déposez le bouton "insertion téléformulaires e-bourgogne" (portant le logo e-bourgogne) depuis le panneau "Boutons disponibles" vers le panneau "Barre d'outils active"

* Vous pouvez maintenant ajouter des liens vers vos téléformulaires à votre contenu en utilisant ce bouton depuis l'éditeur.


-- FAQ --

Q: Où puis-je récupérer ma clé d'API ?
R: La clé d'API permettant d'accéder aux services e-bourgogne via les plugins doit vous être fournie par l'administrateur de votre entité. Elle doit ensuite être renseignée dans le panneau de configuration du plugin e-bourgogne.

Q: Comment configurer mes téléformulaires ?
R: La configuration des téléformulaires se fait via e-bourgogne (http://wwww.e-bourgogne.fr), dans le service Mon Service Public en Ligne » Mes démarches. 

-- CONTACT --

Current maintainers :
