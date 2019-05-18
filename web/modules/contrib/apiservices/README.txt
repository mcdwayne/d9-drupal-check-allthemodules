
CONTENTS OF THIS FILE
---------------------

  * About API Services
  * Features
  * Installation
  * Author

ABOUT API SERVICES
------------------

The API services module provides a framework for managing third-party API
endpoints using configurable entities.

Developers can then create requests to these endpoints using an API provider
implementation, and send the requests using an API client service.

FEATURES
--------

Endpoints are configuration entities:
  Since each endpoint is a configuration entity, they can be managed using
  Drupal's configuration API. This allows each endpoint to be accessible to
  other modules, enabled or disabled individually, and have dependencies that
  are automatically managed by Drupal.

Custom API providers:
  Every third-party API has unique features. API providers are how requests
  are customized accordingly. For example, providers can support setting
  placeholders in an endpoint's path. They also allow for simple GET requests
  and more complex methods, such as POST or PATCH requests, to be implemented.

Send requests and manage responses using a client service:
  Implementing a client service allows you to control how a request is sent and
  what happens to all those server responses. For example, the default
  implementation provided by this module sends requests using the HTTP client
  included with Drupal and caches responses using a specified backend.

INSTALLATION
------------

1. Download and extract the API Services module.

  You can obtain the latest release of this module from:
    <https://www.drupal.org/project/blizzardapi>

  Extract the downloaded files into the /modules or /sites/*/modules directory
  of your Drupal installation. Then enable the module at the Administration >
  Extend page.

AUTHOR
------

MattA <https://www.drupal.org/u/matta>
