CONTENTS OF THIS FILE
---------------------
* Introduction
* Requirements
* Recommended modules
* Installation
* Configuration
* Troubleshooting
* Contributing
* Credits
* Maintainers


INTRODUCTION
------------

This module allows you to create Marketo form blocks on your Drupal sites.

This module comes with a new field type so each entity can have it's own unique form.

The provided CKEditor plugin allows editors to easily embed a Marketo Form into content by adding the token:
```
[marketo-form:FORM_ID]
```


REQUIREMENTS
------------
* Core block module
* Core field module


INSTALLATION
------------
* Enable module
* Enter Marketo API key at Administration >> Configuration >> Web Services >> Marketo Forms


CONFIGURATION
-------------
* Visit Administration >> Structure >> Block
* Click 'Place block' give your block a name and then select the appropriate form from the provided dropdown.


TROUBLESHOOTING
---------------
* Clear Drupal cache after adding or changing a Marketo key


CONTRIBUTING
------------
* Create an issue and attach a patch.
* Master branch is default for new contributions.

MAINTAINERS
-----------
* Loganathane Virassamy - https://github.com/loganathane
