CONTENTS OF THIS FILE
---------------------
- Introduction
- Annotorious Library
- Requirements
- Installation
- Configuration


INTRODUCTION
------------
Image Annotator module allows users to annotate on node images. 

Users can highlight a particular portion of node image
by drawing a rectangle over the image and adding a note to it.

Adding, deleting, updating and viewing of these annotations can easily
be controlled by permission configurations.
It also supports multi-valued image fields.

Image based service can highly leverage the advantage of this module.
Check 'CONFIGURATION' section regarding module usage.


ANNOTORIOUS LIBRARY:
---------------------
Annotorious, a JS API that allows drawing and commenting on images
on your Web page. For more details follow http://annotorious.github.io.


REQUIREMENTS
------------
Drupal Libraries API Module
Annotorious Library

 
INSTALLATION
------------
(1) Create a 'libraries' folder within the DRUPAL_ROOT.
Here, DRUPAL_ROOT defines the root directory of the Drupal installation.

(2) Download Annotorious library.
Download link: https://github.com/annotorious/annotorious/releases/tag/v0.6.4

Now, Extract this library and rename the library folder to 'annotorious'.
Place this library in 'libraries' folder, matching below path for library files:
- DRUPAL_ROOT/libraries/annotorious/annotorious.min.js
- DRUPAL_ROOT/libraries/annotorious/css/annotorious.css

(3) Now, install this Image Annotation module.

Install as you would normally install a contributed Drupal module. 
See: https://www.drupal.org/documentation/install/modules-themes/modules-8


CONFIGURATION
-------------
(1)  Image Annotator Configuration:
To configure, find "Configure" link at admin/modules against the module name,
or at admin/config find Image Annotator configuration block.

The configuration could be done per node type basis.
Enable Annotation for the desired node type(s) and provide valid 
image field name to be annotatable. 
Image field name specified above should be the machine name of the image.

Example, to make Article node type's "Image" field annotatable, then
- Go to configuration page of Image Annotator module
- Check "Enable Image Annotation" checkbox for ARTICLE
- Provide machine name of Image field, in our case, it is "field_image"
- Click Save button

Machine name of the image field(Article type) could be found at  
admin/structure/types/manage/article/fields.

Note: CLEAR CACHE action might be required after saving configurations.

(2) Image Annotator Permissions:
Control image annotations by various users
by configuring permissions at admin/people/permissions#module-img_annotator.

Available user permissions:
- Add any annotations
- Edit any annotations
- View any annotations
- Add annotations to own node
- Edit annotations of own node
- View annotations of own node
