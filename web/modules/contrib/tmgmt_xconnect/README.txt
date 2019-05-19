X-Connect Translator (tmgmt_xconnect)
=====================================

The X-Connect translator is a plugin for the Translation Management Tool (TMGMT)
and provides support to send and receive translation jobs to the AMPLEXOR
Global Content Management (GCM) language services : http://goo.gl/xCQ4em.



Installation instructions
=========================

The X-Connect Translator is built for Drupal 8 and is a translator plugin for
the TMGMT module (https://www.drupal.org/project/tmgmt).

To use the X-Connect Translator you need to install the TMGMT module and its
dependencies.

* Download the module and unpack it in the modules directory.
* Get the AMPLEXOR/X-Connect PHP library by running the following command:
  composer require amplexor/xconnect
* Install the module through the Extend site functionality page (admin/modules).
  The module will be listed under the name "X-Connect Translator".
  The required dependencies will be installed with it.


Configuration
=============

Open the Translation Management Translators page
(admin/config/regional/tmgmt_translator) this lists all the available
translators for the TMGMT module. The X-Connect translator will be listed as
"X-Connect (auto created)".

Tip: You can add extra X-Connect translators by clicking on the "Add Translator"
link and adding a new translator using the "X-Connect" translator plugin.

Click on "Edit" to configure an existing translator.


Order
-----

When a Job is is submitted to the translation service, an order file is added
to it. This to identify who has ordered the translation and providing extra
info about the translation:

Scroll down and open the "Order" fieldset, fill in the order parameters as
received from the translation service:

* Client ID : The client ID to order the translations for.
* Confidential : Is the content for the translation confidential?
* Needs confirmation : Should there be a confirmation sent when the translation
  is ready?
* Needs quotation : Should a quotation be created and sent before the
  translation is performed?


Connection
----------

Scroll down and open the "Connection" fieldset, fill in the connection
parameters as received from the AMPLEXOR Translation service:

* Connection type : What type of connection to use for the translation service.
* Timeout : The translation service server timeout in milliseconds.
* Port : The translation service server port number.
* Host : The translation service server hostname.
* Username : The username to connect to the translation service.
* Password : The password to connect to the translation service.
* Request folder : The directory on the translation service server where the
  translation order files should be saved.
* Receive folder : The directory on the translation service server where the
  translated order files will be available.
* Processed folder : The directory where the translated order files should be
  moved to when the translation is processed (received) by the TMGMT module.
  This to inform the translation service that a translation has been picked-up
  and is processed.


Cron
----

As the AMPLEXOR translation service is a Human translation service, translations
are not processed in real-time. The X-Connect translator uses the Drupal cron
process to scan the service to see if there are translations available. The
connection configuration has two more settings about the cron:

* Cron status : If you don't want to automatically receive the translations, you
  can disable the cron integration for the Translator.
* Limit : By default all translated orders will be processed during the cron run.
  If there are to much translations available at the same moment, the cron could
  run out of time and stop without completing the translations processing and
  without performing other cron tasks. This option allows you to limit the number
  of translations to process during a single cron run.


Debug
-----

You can optionally choose to enable debug mode.

* Enable debug mode : Check to enable debugging mode.


Remote Language Mappings
------------------------

Scroll down and open the "Remote Language Mappings" fieldset. Drupal uses by
default a short language indication string (eg. en, fr, nl, ...).

The Remote Language Mappings shows a list of enabled website languages, you can
fill in the proper language code as required by the translation service.

The list of AMPLEXOR translation language codes can be obtained on request.


Usage
=====

Use the TMGMT user interface to select the content that needs to be translated
(admin/tmgmt/sources) or use the "Translate" tab on content.

When requesting a new translation, select the "X-Connect" Translator as the
translation service. Add optional instructions for the translator and a
reference. Fill in the amount of days to the deadline and your email address.

The translation order will be sent immediately to the GCM service.



Manual send & receive
=====================

When there was a communication problem when sending the request, orders will be
kept to be sent later. You can send them by going to the Jobs overview
(admin/tmgmt) and click on the submit link for the Job you want to send.

There is also a dedicated X-Connect management page (admin/tmgmt/x-connect).
You can manually trigger actions to be performed for each X-Connect powered
translator on the platform:

* Send : will send all unprocessed jobs for the specific translator service.
* Scan : will scan the remote service and report about the number of processed
  jobs that are ready to be picked up.
* Receive : will pick up any processed (translated) request and import them back
  into the platform.
