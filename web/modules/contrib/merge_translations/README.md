--- INTRODUCTION  -------------------------------------------------------------

This project adds an ability to merge node with different languages to 
one translated node in Drupal 8. It is a similar functionality that exists 
in Drupal 7 where a user can add relations between different nodes with 
different languages. After migrating content from D7 translation system to D8, 
many things changed. Orphans nodes with different language can't be merged. 
This tool adds this functionality for the content editors. 

--- REQUIREMENTS  -------------------------------------------------------------

- set permissions

--- INSTALLATION  -------------------------------------------------------------

- composer require drupal/merge_translations

--- CONFIGURATION  -------------------------------------------------------------

- go to /node/nid/merge_translations
