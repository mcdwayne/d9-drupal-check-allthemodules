CONTENTS OF THIS FILE
---------------------

* Introduction
* Requirements
* Recommended Modules
* Installation
* Configuration
* Maintainers
* Links


INTRODUCTION
------------

In the era of Chatbots and Personal Assistants like Alexa[1], Dialogflow[2]
(ex Api.AI), Google Home, Wit.ai & Co. this module tries to create
common layer serving Drupal content to any of these services.

In a common Drupal 'headless' scenario you want to serve the content of
your website through as many service as possible, including but not
limiting to Amazon Echo, a Facebook bot, custom devices using Dialogflow,
etc.
Normally you will require separated custom pieces of code for every
chatbot/personal assistant platform protocol, duplicating effort and
points of failure.
Using Chatbot API you write your code once without caring about handling
the requests and responses.

Developers and Site builder may want to use Chatbot API module and take
advantage of its Intents pluggable system in order to simplify the
implementation of their Intents logic, as well as its chatbot_api_entities
submodule to dynamically push Drupal content to remote conversational services
(i.e. Alexa and Dialoglfow).

See the documentation pages for further details and examples:
https://www.drupal.org/docs/8/modules/chatbot-api

REQUIREMENTS
------------

Chatbot API doesn't require any external libraries, as it's entirely based
on Drupal Core.
However this module by itself doesn't do anything. You should install it
only if another module asks for it or you want to build your own
integration with a Naturale Language Processor or any conversation service
supporting intents, entities, contexts,etc.


RECOMMENDED MODULES
-------------------

Currently the following platforms are supported:
- Alexa[2] , by using the internal `chatbot_api_alexa` submodule together with
the Alexa module[3].
- Dialogflow[1], by using the internal `chatbot_api_ai` submodule together with
the Dialogflow Webhook module[4].


INSTALLATION
------------

Install the module as you would normally install a contributed Drupal
module.


CONFIGURATION
-------------

The module basic functionality doesn't have any configuration, however if you
use advanced features like the Views integration or the Entities automated push
you may require some setup. See the documentation[5] for additional info.


MAINTAINERS
-----------

The 8.x-1.x branch was created by:

 * Gabriele Maira (gambry) - https://www.drupal.org/u/gambry

Additional work and new features have been introduced with the great work
done by:

 * Lee Rowlands (larowlan) - https://www.drupal.org/u/larowlan

Sponsored by Manifesto[7]


LINKS
-----

[1] https://dialogflow.com/
[2] https://developer.amazon.com/alexa-skills-kit
[3] https://drupal.org/project/alexa
[4] https://drupal.org/project/api_ai_webhook
[5] https://www.drupal.org/docs/8/modules/chatbot-api
[6] https://manifesto.co.uk/chatbot-api-drupal-8-tutorial/
[7] https://manifesto.co.uk
