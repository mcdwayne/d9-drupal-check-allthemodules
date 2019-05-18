Just Giving API
===============

Setup
=====

You'll need to create and App ID, todo this head here (https://developer.justgiving.com/admin/applications)
to register for a developer account. The account should be linked to or the same as your charity account login.
Then head here (https://developer.justgiving.com/admin/applications) to retrieve it.


You'll also need your Charity ID found here (https://www.justgiving.com/charities/Settings/charity-profile)

INSTALLING Just Giving SDK
==========================

Just Giving SDK is not setup as a composer package and therefore you'll need to 
add some manualy configuration to your composer file for the package to register in the namespace. 
Add the following to your project composer file and run composer update:

Add the following to your composer file:

1) Custom Package install, choose from one of the two below, occassionally I had 
difficulty getting the GIT versions to work, if it doesn't download try the
HTTPS method below:

`  
"require": {
        "JustGiving/justgiving": "*",
   },
   "repositories": [
     {
       "type": "package",
       "package": {
         "name": "JustGiving/justgiving",
         "type": "drupal-vendor",
         "version": "dev-master",
         "source": {
           "url": "https://github.com/JustGiving/JustGiving.Api.Sdk.git",
           "type": "git",
           "reference": "origin/master"
         }
       }
     }
   ]
 `

Alternative method to download the zip.

`{
            "type": "package",
            "package": {
                "name": "JustGiving/justgiving",
                "version": "1.0",
                "type": "drupal-vendor",
                "dist": {
                    "url": "https://github.com/JustGiving/JustGiving.Api.Sdk/archive/master.zip",
                    "type": "zip"
                }
            }
        }
`

2) Add a path for the vendors folder, registers the vendor folder as a custom
install location.

`"extra": {
         "installer-paths": {
             ...
             "vendor/{$name}": [
                 "type:drupal-vendor"
             ]
         },`

3) Add a path mapping, allows the discovery by autoload of the Just Giving folder:

`    "autoload": {
         "classmap": [
             "scripts/composer/ScriptHandler.php",
             "vendor/JustGiving/justgiving/php/",
             "vendor/JustGiving/justgiving/php/JustGivingClient.php"
         ]
     },`