README - Body Style Image
-----------------------------

CONTENTS OF THIS FILE
---------------------
   
 * Introduction
 * Installation
 * Configuration
 * Features

INTRODUCTION
------------

Apply image style to node images which are uploaded through CKEditor, IMCE.

INSTALLTION
-----------

1) Copy body_style_image directory to your modules directory.

2) Enable the module at module configuration page.

CONFIGURATION
-------------
How to Configure Body Style Image?
 
 - Use the administration configuration page and help (Body Style Image module)
 
 - Select content type and Image Style which you want to apply.
 
 - Configuration URL
   admin/config/development/body-style-image page.
   
How to Use Body Style Image?
   
   7.x Example: 
   
   if (!empty($newbody_content)) {
     print render($newbody_content);
   }
   else {
     print render($content);
   }
   
   8.x Example:
   
   {% if newbody_content %}
    {{ newbody_content|raw }}
   {% else %}
	{{ content }}
   {% endif %}
   
   Override template :
   7.x:
   
   Check whether its empty or not then Print '$newbody_content' variable to
   'node.tpl.php' or 'node--[CONTENT_TYPE].tpl.php' file.
   
   8.x:
   
   Check whether its empty or not then Print '$newbody_content' variable to
   'node.html.twig' or 'node--[type].html.twig' file.
  
Features
--------

- Apply Image style to node body content.
- Image Style will be applied for Images which are uploaded through CKEditor,
  IMCE.
