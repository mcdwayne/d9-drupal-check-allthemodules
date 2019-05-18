This module provides a minimal wrapper around the Google APIs Client Library.

It does/and will only do two things:
  - Provide a simple to administer authentication settings page
  - Provide a single service which can be used to obtain an authenticated client to the various supported Google services, like Google Calendar, Google Drive, etc.

Requirements:
  - Key module (https://www.drupal.org/project/key)
  - Google API Client (https://github.com/google/google-api-php-client)

Installing via composer will automatically download all required libraries.

Notes:
  - This module is meant to be used as a base by other modules providing deeper integration to various Google services.
  - This module only provides an authenticated client via a service that can be injected into your controllers/blocks/plugins/etc.
  - For a list of the various Google services which could be supported, see the separate google-api-php-client-services repository.
  - Because the maintainer is unable to maintain this for every possible Google service, the available services are defined by a minimal plugin based system. You can add your own services by implementing this plugin in your own module. However, you are encouraged to submit it as a patch to this module. Any patch in the issue queue that adds a new service will be accepted provided that it meets Drupal coding standards and does not do anything "extra".
