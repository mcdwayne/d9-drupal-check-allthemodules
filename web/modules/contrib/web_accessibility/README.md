Web Accessibility.
------------------

INTRODUCTION
------------

Web accessibility module provides a quick and easy way for checking your
content accessibility issues across different services. By default,
the module provides the following services.

- [W3 Link Checker](https://validator.w3.org/checklink).
- [W3 Markup Validation Service](https://validator.w3.org/check).
- [WAVE (Web Accessbility Evaluation Tool](http://wave.webaim.org.

REQUIREMENTS
------------
This module requires the following modules:

* Views (https://drupal.org/project/coder)

INSTALLATION
------------
Before installing this module, Please make sure the Composer is updated
properly. Especially coder module.

1) composer global require drupal/coder.
2) To enable the module via drush use the below comment.

    drush en web_accessibility.

CONFIGURATION
-------------
You can add your own extra services in the following path:

1) Goto /admin/config/system/web_accessibility.
2) Configure your extra services.
3) Don't forget to clear the cache.

USAGE
-----
You should be able to find a new tab section (Web Accessibility Services) in
your node edit page. This section includes links to the accessibility services
to validate your content. Note that the content must be public.

CONTRIBUTING
------------

1. Make sure an issue exists at 
https://www.drupal.org/project/issues/web_accessibility.
2. Submit a patch.
3. Set the issue to "needs review".

Thank you!

MAINTAINERS
-----------

Christoph Breidert, https://www.drupal.org/u/breidert.
Maksym Tsypliakov, https://www.drupal.org/u/cmd87.
Ruben Teijeiro, https://www.drupal.org/u/rteijeiro.

CREDITS
-------

[1xINTERNET](https://www.1xinternet.de) is the supporting organization and
contributed to the development of the module.
