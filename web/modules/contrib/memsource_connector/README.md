INTRODUCTION
------------

Translate pages and posts while preserving images, layouts and any custom HTML.

The plugin pulls posts and pages from Drupal, parses text and presents it 
to the translator in a clean view. After translation, the pages and posts
are rebuilt with graphics.

With the new plugin for Drupal and the 
[Connector](http://wiki.memsource.com/wiki/Connectors "Memsource Connectors") 
feature, content managers can import blog posts and pages into Memsource 
for translation.

The content can be imported either manually via 
[Add from Online Repository](http://wiki.memsource.com/wiki/Connectors#Creating_New_Jobs_from_Online_Repositories) 
button or [automatically](http://wiki.memsource.com/wiki/Automated_Project_Creation).
Your Drupal website will need to set up languages at 
"Configuration -> REGIONAL AND LANGUAGE -> Content language and translation" 
to manage multilingual pages. 
**Languages in Drupal must match languages of the project.** 
Source language in Memsource must be equal to Site's default language 
in Drupal.
Users can decide which content will be translated (whether draft articles 
or published ones) and also how translated content is 
[uploaded back to their Drupal site](http://wiki.memsource.com/wiki/Connectors#Exporting_Completed_Jobs_to_Online_Repositories), 
i.e. whether posts will be put back as drafts or automatically published.


REQUIREMENTS
------------

This module requires the following modules:

 * Content Translation (https://drupal.org/project/translation)


INSTALLATION
------------
 
 * Install as you would normally install a contributed Drupal module. 
 See: https://drupal.org/documentation/install/modules-themes/modules-8 
 for further information.


CONFIGURATION
-------------

* This module can be configured at 
"Configuration -> REGIONAL AND LANGUAGE -> Memsource Connector".

  - **Memsource Connector authentication token:** 
  This is a random string (automatically generated during 
  the module installation) that will be used as an authentication 
  token by Memsource Cloud to connect to your Drupal server.
  - **Import posts with the following status:**
  Select statuses of articles/pages to be downloaded by Memsource Cloud 
  for translation. The default value is Published.
  - **Set status for exported posts to:** 
  Select a status of translated articles/pages to be uploaded 
  by Memsource Cloud back to Drupal. The default value is Published.
