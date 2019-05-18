CONTENTS OF THIS FILE
=====================

 - Introduction
 - Requirements
 - Configuration
 - Usage
 - Authors/Maintainers

Introduction
============
Google Cloud Vision API enables developers to understand the content of an image by encapsulating powerful machine learning models in an easy to use REST API.
For a full description of the module, visit the project page
  https://www.drupal.org/project/google_vision

Requirements
============
This module requires the following modules:
  - File Entity (https://www.drupal.org/project/file_entity)

Configuration
=============
Enable the module and set API key on the page /admin/config/media/google_vision. To learn more on how to generate your API key, visit https://cloud.google.com/vision/docs/auth-template/cloud-api-auth

Usage
=====
Google Vision API offers the following features:
 - Taxonomy tagging of image files using Label Detection
 - Safe Search Detection
 - Filling Alt Text attribute of image file
 - User Emotion Detection
 - Displaying the similar image files

Taxonomy tagging using Label Detection
--------------------------------------
Label Detection feature of the Google Cloud Vision API has been used for taxonomy tagging purpose.
Note: The number of results returned by the Vision API for labels is configurable. The user can set the value on /admin/config/media/google_vision. If not set, the default value is considered to be 5.

Step by step instructions are given below:
1. Create taxonomy vocabulary for your labels (tags).
2. Add new field into your file entity (can be done through module file_entity).
  This field should be reference to your taxonomy vocabulary and enable the option "Enable Google Vision".
3. Now each time when you will create/update your file (image)
  the module google vision will add labels for you file into your field.

Safe Search Detection
---------------------
Safe Search Detection feature is available and configurable for each of the image fields present for the entity types, including nodes, users, comments, etc.

Step by step instructions for using the Safe Search Detection feature is given below:
1. Go to the Manage fields section of the entity type of whose image field you like to configure.
2. Click the Edit operation corresponding to the desired image field.
3. Enable the "Enable Safe Search" checkbox and save the settings.
4. Now each time when you will upload an image in that image field, it
  would validate the image for explicit or violent contents, and would not allow the creation if any such contents are present.

Filling Alt Text attribute of image file
----------------------------------------
Google Vision API module allows auto filling of the Alt Text attribute of the image files using Label Detection/Logo Detection/Landmark Detection/Optical Character Detection, as is chosen by the end user.

Step by step instructions are given below:
1. Go to the Manage fields section of the "Image" file type.
2. Click the Edit operation corresponding to the Alt Text field.
3. Select the desired feature from the options listed under
  "FILL ALT TEXT BY" using which you would like to fill the Alt Text and save the settings.
4. Now each time you create/update an image file, you will find that the
  Alt Text gets filled automatically.

User Emotion Detection
----------------------
This feature is especially important if you do not want your site users to upload sad/unhappy images as their profile pictures.

Step by step instructions are given below:
1. Go to the Manage fields section of the Account Settings.
2. Click the Edit operation corresponding to the image field.
3. Enable the "Enable Emotion Detection" checkbox and save the settings.
4. Now each time when you will upload an image as your user picture, it
  would check the image for emotions, and would ask the user to upload a happy image (if he looks unhappy).

Displaying the similar image files with similar colors
----------------------------------
Another use case for the Google Vision API module is grouping the similar image files which have the same dominant color (Red, Green or Blue).
The tab shows all the images which are made up of same dominant color.
For example, if an object, say, a belt is of red color, then the Similar Contents tab can be used to show objects like caps, shoes, etc. of the same color component.

Step by step instructions are given below:
1. A new vocabulary, named "Dominant Colors" is automatically created and enabled for the field referencing to a taxonomy vocabulary, which stores the dominant color.
2. Whenever a new file (image) is created/updated, the "Similar Contents"
  (besides the View, Edit and Delete) displays the similar contents.

Author/Maintainers
==================
 - Eugene Ilyin (https://www.drupal.org/u/eugeneilyin)
 - Naveen Valecha (https://www.drupal.org/u/naveenvalecha)
 - Christian López Espínola (https://www.drupal.org/u/penyaskito)
 - Arpit Jalan (https://www.drupal.org/u/ajalan065)
