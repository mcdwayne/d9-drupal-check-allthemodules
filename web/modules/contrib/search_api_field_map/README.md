INTRODUCTION
------------

The Search API Field Map module facilitates indexing data from multiple Drupal sites into a single Apache Solr search index.

 * For a full description of the module, visit the project page: 

 * Please use GitHub submit bug reports and feature suggestions, or to track changes:
  https://github.com/palantirnet/search_api_field_map
  
  
REQUIREMENTS
------------

This module requires the following modules:

 * Search API (https://www.drupal.org/project/search_api)
 * Token (https://www.drupal.org/project/token)

Additionally, it is recommended to configure Search API to send data to an Apache Solr server.


INSTALLATION
------------
 
  * Install as you would normally install a contributed Drupal module. Visit:
   https://www.drupal.org/documentation/install/modules-themes/modules-8
   for further information.
   
   
CONFIGURATION
------------

On each site included in the mapped field search, you will need to:

    1. Configure a Search API server to connect to the shared Solr index
    2. [Follow these detailed instructions](docs/usage.md) to configure your fields.


TROUBLESHOOTING & FAQ
---------------------

TBD


MAINTAINERS
-----------

Current maintainers:
* Avi Schwab (froboy) - https://www.drupal.org/u/froboy
* Ken Rickard (agentrickard) - https://www.drupal.org/u/agentrickard
* Malak Desai (MalakDesai) - https://www.drupal.org/u/malakdesai
* Matthew Carmichael (mcarmichael21) - https://www.drupal.org/u/mcarmichael21

This project has been sponsored by:
* Palantir.net (https://palantir.net)
