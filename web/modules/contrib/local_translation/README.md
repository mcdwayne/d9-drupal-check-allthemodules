Local Translation
=============

INTRODUCTION
------------
Makes it possible for users to register their translation skills and then get content and interface translations filtered by those languages. The module will add a language combination field to user entity on installation. If the user have not registered their translation skills they are asked to registered them on their user page. The module also provide possibility to create, eidt and delete interface translations, content and translations only limited to the users translation skills.

FEATURES
------------

**Content translations**
- The list of language in the tranlation tab is filtered by the users translation skills.
- If user doesn't have registered any translation skills they are requested to do so.
- Source language in translation form is automatically set to users translation permission.
- New permissions: give possibility to create, eidt and delete content/translations only to the users translation skills. Users are then also limited to only translate content that have translations in one of their source languages.
- Possibility to filter nodes in views based on the users translation skills, see new config alternatives on Translation language filter.

**Interface translations**
- The user can see another source language than the sites default language
- The language selector have a group output so the users translation skills are always dipslayed first.
- New permission to create and edit interface translation only limited to the users translation skills.

REQUIREMENTS
------------
- Drupal 8
- [Language combination](https://www.drupal.org/project/language_combination)


INSTALLATION
------------
Install module as usually.


CONFIGURATION
-------------
Configuration is done at `/admin/config/regional/local_translation`.

### Language combination field
You need to choose a language combination field in the user entity where the user can register their translations skills. A language combination field is added and configured to be used when installing the module.

### Local Translation Content permissions
The sub-module Local Translation Content also provide possibility to create, eidt and delete content and translations only limited to the users translation skills. However to use these permissions you need to enable them first.

### Local Translation Interface permissions
The sub-module Local Translation Interface provide possibility to create, eidt and delete interface translations only limited to the users translation skills. 


MAINTAINERS
-----------
Developed by  
[vlad.dancer](https://drupal.org/u/vladdancer)  
[Valentine94](https://www.drupal.org/u/valentine94)  
Designed by  
[matsbla](https://drupal.org/u/matsbla)
