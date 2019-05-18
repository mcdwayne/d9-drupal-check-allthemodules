# Brid.TV integration for Drupal

Integrates the Brid.TV video service provider into Drupal.

# Requirements

This module has no requirements, but integrates with
the Media system and Paragraphs, if installed.

The module has been initially build to work with
the Thunder distribution (drupal.org/project/thunder).
If you're using a different system and experience problems,
feel free to report your problems in the issue queue.

# Features overview

Once installed, the module provides
  - A new media type "Brid.TV Video" (requires the Media system to be installed)
  - A new paragraph type "Brid.TV Embedded Video" (Paragraphs module required)
  - Optional synchronization of the videos with the provider.
  - for developers: multiple services for using video data from Brid.TV.

Further planned features (not existing yet):
  - An entity browser for browsing through existing Brid.TV Videos
    (requires the Entity Browser module to be installed)
  - An upload section for directly uploading new videos to Brid.TV.

# Installation and configuration

1. You need credentials for an API authorization. The easiest way to do this
is to first login into the Brid.TV CMS (https://cms.brid.tv) and select your
site under the MANAGE VIDEOS option on the main left-hand menu. Once selected,
click on the SETTINGS option right below your domain name and you should see
the API section. See also
https://developer.brid.tv/brid-platform/php-api-client/authorization.

2. Install the module as usual. For common module instructions, have a look at
https://www.drupal.org/docs/8/extending-drupal-8/installing-drupal-8-modules.

3. Configure the module properly, by at least adding your API credentials.
The module configuration can be found at /admin/config/bridtv. The module
adds some further permissions which can be configured at
/admin/people/permissions.

4. Start synchronization of the Brid.TV data with your CMS. You can do this
by either using the UI via admin/bridtv/sync or by running the Drush
command bridtv-sync (Drush 8) or bridtv:sync (Drush 9).

**NOTE**: Make sure the API credentials can only be read and accessed by
responsible persons. The credentials are being saved into the configuration,
without any encryption.

# How to use

@todo ...

