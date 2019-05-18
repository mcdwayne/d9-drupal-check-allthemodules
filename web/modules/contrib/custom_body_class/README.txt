CONTENTS OF THIS FILE
---------------------

 * INTRODUCTION
 * REQUIREMENTS
 * INSTALLATION
 * CONFIGURATION

INTRODUCTION
---------------------

Custom body class module is used to add custom class to <body> tag specific to
node.


REQUIREMENTS
------------

This module doesn't require the help of any other modules.

INSTALLATION
------------
 * Install as you would normally install a contributed Drupal module.

CONFIGURATION
-------------
This module adds a fieldset named "Custom Body Class Settings" in node type
form,
For eg: /node/add/article for article content type.

There are two fields in a fieldset.
1:  A textbox to add custom body class. Add multiple classes separated by space.
2:  A checkbox,which when enabled add a node type as a class in <body> tag.
    For eg: If a node type is article, then 'article' get added as a class in
    <body> tag of the page.
