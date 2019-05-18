Alexander's Printing API
------------------------

CONTENTS OF THIS FILE
---------------------

-   Introduction
-   Requirements
-   Installation
-   Configuration
-   Troubleshooting
-   Maintainers

INTRODUCTION
------------

This module aims to integrate Alexander's printing API into Drupal so
customers of the website can order prints directly from them. The module
itself does not really do anything and relies on third party integration,
opting to come with a simple pre-build Commerce module.

See [the site API
docs](https://app.swaggerhub.com/apis-docs/apa/apipostback.divvy.systems/1.0.0#/)
and [Alexanders API
docs](https://devapi.divvy.systems/swagger/index.html?url=/swagger/v1/swagger.json#/)
for reference.

REQUIREMENTS
------------

Does not presently have any technical requirements, but does not do much on
it's own, so a custom module may be required. Come with simple Commerce
module with hooks to translate Commerce orders into Alexanders orders.

INSTALLATION
------------

Installation is fairly standard, either by manually uploading the module
or using composer.

``` {.bash}
composer require drupal/alexanders
```

CONFIGURATION
-------------

Configuration can be found at `/admin/alexanders`. API keys are randomly
generated but will not be saved until the configuration has been saved.
Two keys are provided - a real and a sandbox key, prefixed to give a
better perspective. Here you can also configure the Alexander's API key
and whether to use a sandbox, which will give more detailed watchdog
logs.

TROUBLESHOOTING
---------------

Check watchdog for errors - most cases, the order doesn't have a
shipping method (which Alexanders requires), or you haven't configured
the right API key. Also keep an eye on whether the sandbox is enabled.

MAINTAINERS
-----------

Current maintainers:

-   Gabriel Simmer (gmem) - https://drupal.org/u/gmem
    -   Primary maintainer
-   Josh Miller (joshmiller) - https://drupal.org/u/joshmiller
    -   Support/review, secondary maintainer

This projects has been sponsored by:

ACRO MEDIA INC

Acro Media is a Drupal Commerce agency redefining the online retail
experience and frees organizations from the limitations of restrictive
proprietary platforms. By leveraging Drupal and Drupal Commerce, we
empower businesses to adapt technology for their existing business
systems and create ideal experiences for their customers.
