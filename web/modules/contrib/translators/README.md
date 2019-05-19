CONTENTS OF THIS FILE
---------------------

 * Introduction
 * Requirements
 * Recommended modules
 * Installation
 * Configuration
 * Maintainers


INTRODUCTION
------------
Makes it possible for users to register their translation skills and then get
and improved translation UX, as well as help site builders to manage the site's
translators.


FEATURES
--------

**Content translations**

 * If the user doesn't have registered any translation skills, they can be
   requested to do so on all pages that are filtered by the user's translation
   skills.
 * The list of languages in the translation overview can be filtered by the
   user's translation skills. However, the list can also be configured to
   always display the original language.
 * Views results can be filtered by the user's translation skills,
   see new config alternatives on Translation language filter named
   `Limit languages to translation skills`.
 * Views results can also be filtered by the the target language filter used in
   [Translation Views](https://www.drupal.org/project/translation_views)
 * The language selector has a group output so the user's translation skills
   are always displayed first.
 * Source language in translation form can be automatically set to one of the
   user's translation skills. It will first try to find one of the registered
   source language skills, and alternatively try to pre-set the translation
   source to one of the registered target language skills.
 * New permissions are provided: gives the ability to create, edit, and delete
   content/translations only to the user's translation skills. Users with these
   permissions can also be limited to only translate content that already have
   translations in one of their source language skills.


**Interface translations**

 * The user can translate interface text from other languages than English. 
 * The language selector has a group output so the user's translation skills
   are always displayed first.
 * A new permission is provided to create and edit interface translations in
   the user's translation skills.


REQUIREMENTS
------------

 * [Language combination](https://www.drupal.org/project/language_combination)


RECOMMENDED MODULES
-------------------

The user can limit the target language filter to the users registered
translation skill when using
 * [Translation Views](https://www.drupal.org/project/translation_views)


INSTALLATION
------------

 * Install as you would normally install a contributed Drupal module. Visit:
   https://www.drupal.org/node/1897420 for further information.


CONFIGURATION
-------------

Configuration is done at `/admin/config/regional/translators` and
`admin/people/permissions`.

### Language combination field
The site builder needs to choose a language combination field in the user
entity where the user can register their translations skills. A language
combination field is added and configured to be used when installing the
module.

### Content translation
The sub-module, Content Translators, provides these configuration options:
* Filter the translation overview to translation skills
* Always display original language in translation tab
* Provide warning message on pages filtered by translation skills when user
  have not yet registered any translation skills
* Preset source language to translation skills
* Enable Content Translators permissions
* Only allow to translate if source language is a registered source skill

Once `Content Translators permissions` is enabled you can further configure the
permissions for different user roles to create, edit, and delete content and
translations into the user's translation skills.

### Interface translation
The sub-module, Interface Translators, provides a new permission to translate
the interface into the user's translation skills.


MAINTAINERS
-----------

Developed by  
 * [vlad.dancer](https://drupal.org/u/vladdancer)
 * [Valentine94](https://www.drupal.org/u/valentine94)

Designed by
 * [matsbla](https://drupal.org/u/matsbla)
