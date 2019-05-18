CONTENTS OF THIS FILE
---------------------

* Introduction
* Requirements
* Installation
* Configuration
* Maintainers


INTRODUCTION
------------

This module automatically improves the layout of 404 (page not found)
and 401 and 402 (not authorized) pages. If the current page is an 404
(or 401 or 402) page then it replaces the page content with a themed
message.
It does not replace other page items, so the error page will
still show your header and footer, and thus have your site styling intact.

The 404 (page not found) page will show following message:
'The requested page could not be found.' (this is translatable)

The 401 and 402 (not authorized) pages will show following message:
'Sorry, you are not authorized to access this page.' (this is translatable)

Under each message, a button 'Go to homepage' is added automatically.

If you want you can further style these messages by targeting following
classes in your css:
- .c-system-message (wrapper around the message)
- .c-system-message__text (text itself)
- .c-system-message__button (the button to the homepage)

REQUIREMENTS
------------

This module has no other requirements outside of Drupal core.


INSTALLATION
------------

Install the better_status_messages module as you would normally install a
contributed Drupal
module:
- require the repository:
```
composer require drupal/better_page_not_found --prefer-dist
```
- enable the module:
```
drush en better_page_not_found -y
```


CONFIGURATION
--------------

Just enable the module and it works, no configuration needed.
If you want different styling, then just target the css classes
in your own stylesheets.


MAINTAINERS
-----------

The 8.x.1.x branch was created by:

 * Joery Lemmens (flyke) - https://www.drupal.org/u/flyke
