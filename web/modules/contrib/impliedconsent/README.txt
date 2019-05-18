CONTENTS OF THIS FILE
---------------------

 * Introduction
 * Requirements
 * Installation
 * Configuration

INTRODUCTION
------------
Adds Drupal support to the Implied Consent JavaScript tool. The script
implements an implied consent notice to comply with the UK's implementation of
the EU cookie laws.

REQUIREMENTS
------------
This module requires the following modules:
 * Implied Consent Library (https://github.com/dennisinteractive/implied-consent)

INSTALLATION
------------
 * Install as you would normally install a contributed drupal module.
 * Either download the implied-consent library and extract it to /libraries
   Or use composer with the following in the root composer.json:

{
    "type": "package",
    "package": {
        "name": "dennisinteractive/implied-consent",
        "version": "1.1.0",
        "type": "drupal-library",
        "source": {
            "url": "https://github.com/dennisinteractive/implied-consent.git",
            "type": "git",
            "reference": "1.1.0"
        },
        "dist": {
            "url": "https://github.com/dennisinteractive/implied-consent/archive/1.1.0.zip",
            "type": "zip"
        }
    }
}


CONFIGURATION
-------------
 * /admin/config/system/impliedconsent
