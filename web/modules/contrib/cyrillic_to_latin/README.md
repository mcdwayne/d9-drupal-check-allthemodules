# CYRILLIC TO LATIN

## CONTENTS OF THIS FILE

  * Introduction
  * Requirements
  * Installation
  * Configuration
  * Author

## INTRODUCTION

Cyrillic to Latin module will convert strings that are passed through Drupal t() 
function and string/text field values. If you are using the Address module, 
country name will also be converted to latin.

## REQUIREMENTS

No special requirements.

## INSTALLATION

Use Composer to install the module:

```
composer require drupal/cyrillic_to_latin
```

and then enable it with Drush:

```
drush en cyrillic_to_latin -y
```

## CONFIGURATION

You can enable/disable string conversion here:

```
'/admin/config/regional/cyrillic-to-latin'
```

### AUTHOR

Goran Nikolovski  
Website: http://www.gorannikolovski.com  
Drupal.org: https://www.drupal.org/u/gnikolovski  
Email: nikolovski84@gmail.com  

Company: Studio Present, Subotica, Serbia  
Website: http://www.studiopresent.com  
Drupal: https://www.drupal.org/studio-present  
Email: info@studiopresent.com  
