Description
-----------
This module implements a new field type that allows videos to be uploaded
directly to the Ooyala video hosting platform. This module requires an Ooyala
account (http://www.ooyala.com/). Free trials of Ooyala are also available at
http://www.ooyala.com/free_trial.

Requirements
------------
This module requires version 2.2.1 of Plupload to be available on the libraries
folder in the docroot. This library can be found here
https://github.com/moxiecode/plupload/archive/v2.2.1.tar.gz.

Installation
------------
After enabling the module and signing up for an Ooyala account, you'll need to
set some options both in Drupal and in the Ooyala Backlot.

Sign into http://backlot.ooyala.com and visit the "Account" tab. Under the
sub-tab for "Developers", find the "API Key" and "API Secret". Copy and paste
these values into the Ooyala settings in your Drupal site at
Administer >> Configuration >> Media >> Ooyala settings
(admin/config/media/ooyala/settings). Save the Drupal settings.

Now, add Ooyala fields to your content types. You will be able to use the Ooyala
Video Field to upload new videos to Ooyala as well as referencing existing
videos on your node.
