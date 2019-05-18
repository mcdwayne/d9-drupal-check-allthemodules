## Domain Robots Txt

Use this module when you are using domain system Drupal site and you need a different robots.txt file for each domain. This module generates the robots.txt file dynamically and gives you the chance to edit it, on a per-domain basis, from the web UI.

#### Note: 
You must delete or rename the robots.txt file in the root of your Drupal installation for this module to display its own robots.txt file(s). Also, don't forget about your apache\nginx configuration.

#### How to remove default *robots.txt* file 

if you are using composer to build the site, you can add the following section into the composer.json

    "extra": {
	        "drupal-scaffold": {
               "excludes": [
                   "robots.txt"
               ]
	        }
    }

##### Todo:
* [x] Make something working
* [x] Check default value for settings form on PHP < 7.
* [x] Controller: checks for configs, may be apply cache.
* [x] Add good validation for domain_id on settings form.
* [x] Hook
* [x] DI for drupal_set_message.
* [ ] Add comments and descriptions. (.module, PathProcessor, .info.yml, readme.md )
* [ ] What about tests?
