______                          _                         _   _ _                 _ _              
|  _  \                        | |                       | | | (_)               | (_)             
| | | |___ _ __   ___ _ __   __| | ___ _ __   ___ _   _  | | | |_ ___ _   _  __ _| |_ _______ _ __ 
| | | / _ \ '_ \ / _ \ '_ \ / _` |/ _ \ '_ \ / __| | | | | | | | / __| | | |/ _` | | |_  / _ \ '__|
| |/ /  __/ |_) |  __/ | | | (_| |  __/ | | | (__| |_| | \ \_/ / \__ \ |_| | (_| | | |/ /  __/ |   
|___/ \___| .__/ \___|_| |_|\__,_|\___|_| |_|\___|\__, |  \___/|_|___/\__,_|\__,_|_|_/___\___|_|   
          | |                                      __/ |                                           
          |_|                                     |___/                                            
          
This based on the vis.js library:
https://github.com/almende/vis

This module visualizes the dependency hierarchy of all installed modules and profiles.

The base dependency visualizer Drupal module implements the JavaScript library to
display a pretty diagram.


-- REQUIREMENTS --

* vis.js (at least v4.21.0)
  https://github.com/almende/vis

-- INSTALLATION Standard --

1) Download the Drupal module and place it in your modules folder.

2) Download the library from https://github.com/almende/vis and place
it in the Drupal root libraries folder.
So the files should be available under
"DRUPAL_ROOT/libraries/vis",

-- INSTALLATION using Composer --

Prerequisite: You have defined Drupal.org as Composer repository accordingly:
https://www.drupal.org/docs/develop/using-composer/using-composer-to-manage-drupal-site-dependencies#drupal-packagist

The vis.js package is not listed on packagist.org,
so manual steps are required in order to install it through this method.

1) First, copy the following snippet into your project's composer.json file so the correct package is downloaded:


"repositories": [
    {
      "type": "package",
      "package": {
        "version": "v4.21.0",
        "name": "almende/vis",
        "type": "drupal-library",
        "source": {
          "url": "https://github.com/almende/vis.git",
          "type": "git",
          "reference": "v4.21.0"
        },
        "require": {
          "composer/installers": "^1.2.0"
        }
      }
    }
  ]

Probably you want to update the library version to use the latest one.

2) Next, the following snippet must be added into your project's composer.json
file so the javascript library is installed into the correct location:

"extra": {
  "installer-paths": {
    "libraries/{$name}": ["type:drupal-library"]
  }
}

If there are already 'repositories' and/or 'extra' entries in the
composer.json, merge these new entries with the already existing entries.

3) After that, run:

$ composer require almende/vis drupal/dependency_visualizer

The first uses the manual entries you made to install the JavaScript library,
the second adds the Drupal module.

Note: the requirement on the library is not in the module's composer.json
because that would cause problems with automated testing.

-- CONFIGURATION --

1) Activate the module.

2) Visit /dependency_visualizer/visualize with your admin account or grant your user the specified permission.
