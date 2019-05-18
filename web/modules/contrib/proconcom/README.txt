Pro and Con Comments

------------------------------ SUMMARY ------------------------------

The Procon module provides the link to 'Add argument' on the node,
enable the 'Enable Pros and Cons arguments by default' option under 
'Publishing Options' in your content type to allow adding Arguments on nodes. 
You also need to enable this under 'Comment and argument settings'
for existing nodes on which you need Arguments.


---------------------------- REQUIREMENTS ---------------------------

Views: Procon creates a view for listing all arguments on a node.


------------------------- Additional Modules ------------------------

Fivestar: If you want Ratings with your argument to rate the node
    you can add a new fivestar field in 'Argument' content type.
    This will be shown on the node.


---------------------------- INSTALLATION ---------------------------

* Install as usual, see http://drupal.org/node/895232 for further information.
* Enable 'Enable Pros and Cons arguments by default' for content types 
  and existing nodes.
* There is also an additional checkbox 'Allow users to add only 1 argument per node'
 Check this if you want only one argument on a node, this might be helpful
  in case you don't want users to rate a node multiple times.


--------------------------- CONFIGURATION ---------------------------

* Configure user permissions in Administration » People » Permissions:

  - Node access permissions for 'Argument' type contents

    Users require these permissions to add arguments. Users won't 
    have option to add argument unless these permission is granted.


------------------------------ CONTACT ------------------------------
Current maintainer:
* Vindesh Mohariya https://www.drupal.org/u/vindesh  

