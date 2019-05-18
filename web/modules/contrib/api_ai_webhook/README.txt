CONTENTS OF THIS FILE
---------------------

* Introduction
* Requirements
* Recommended Modules
* Installation
* Configuration
* Maintainers


INTRODUCTION
------------

This module integrates your Drupal website with Dialogflow[1] (ex Api.AI)
Webooks, letting your Dialogflow agents talk with your website, respond
to Intents request and fill slots.

Version 1.x is compatible only with Dialogflow V1.
Version 2.x (work in progress) is compatible only with Dialogflow V2.


REQUIREMENTS
------------

Dialogflow Webhook requires the PHP library iboldurev/api-ai-php[2] version 0.2.5.
If you have a composer-based project you are covered, otherwise you'll need
download and autoload it manually. Check "Installing modules' Composer
dependencies"[3] and also "Using Composer to manage Drupal site dependencies"[4]
for some approaches on how to handle this.


RECOMMENDED MODULES
-------------------

Developers and Site builder may want to use Chatbot API module and take advantage
of its Intents pluggable system in order to simplify the implementation of their
Intents logic, as well as its chatbot_api_entities submodule to dynamically push
Drupal content to Dialogflow entities.


INSTALLATION
------------

Install the module as you would normally install a contributed Drupal
module.


CONFIGURATION
--------------

    1. Navigate to Administration > Extend and enable the Optimizely module.
    2. Navigate to Administration > Configuration > Web Services > Api.AI Webhook Configuration.
    3. Configure the preferred authentication method.

The settings are not stored in the configuration, for security reason. They
are instead stored in the states. That means if you deploy an import the
config, Dialogflow settings are not deployed and you need to move them across
environment in another way, or re-submit the form in your final enviornment(s).

You are now ready to receive POST from Dialogflow. In the agent webhook
configuration use the URL:

  https://your-website.com/api.ai/webhook

Please note although it's not mentioned anywhere, it looks like HTTP over SSL
(HTTPS) seems required. Using and HTTP only URL can fail silently.


MAINTAINERS
-----------

The 8.x-1.x branch was created by:

 * Gabriele Maira (gambry) - https://www.drupal.org/u/gambry

Additional work and new fetures have been introduced with the great work
done by:

 * Lee Rowlands (larowlan) - https://www.drupal.org/u/larowlan

Sponsored by Manifesto[5]


LINKS
-----

[1] https://dialogflow.com/
[2] https://packagist.org/packages/iboldurev/api-ai-php
[3] https://www.drupal.org/docs/8/extending-drupal-8/installing-modules-composer-dependencies
[4] https://www.drupal.org/node/2718229
[5] https://manifesto.co.uk
