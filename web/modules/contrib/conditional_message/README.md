CONTENTS OF THIS FILE
---------------------

* Introduction
* Requirements
* Installation
* Configuration
* FAQ
* Maintainers


INTRODUCTION
------------

Conditional message is designed to display custom messages to a targeted
audience. Some conditions are available by default to cover common cases.
This module tries to follow the best practices regarding JavaScript and CSS
aggregation and allows the site to be cached as usual. As a result it provides
a personalized experience to the user while not degrading performance.
Integration with other modules to expand its functionality is underway.


REQUIREMENTS
------------

This module has no required dependencies outside of Drupal core.


INSTALLATION
------------

Install like any other module. Visit the documentation for details:
https://www.drupal.org/node/1897420


CONFIGURATION
-------------

1. Navigate to Administration > Extend and enable the module.
2. Navigate to Administration > Configuration > User Interface > Conditional
   Message.
3. Select the conditions upon which the message will be triggered.
4. Enter the message to be displayed.
5. Optionally: Select a different color or position for the message.
6. Save configuration.

You can target a container by entering the id or class as in CSS. The default
is to use 'body' as the container.


FAQ
---

Q: How does it work behind the scenes?

A: This module inserts a very small JavaScript snippet into every page that
consults a non-cached callback path via AJAX. The callback path provides
information if the message should be displayed or not (other checks are made in
the front-end using JavaScript). If the message should be displayed, the snippet
inserts the message into the page dynamically via JS.

Q: How does the option "once per session" really works?

A: It uses javascript's localStorage to store session data. The data persists
after closing the tab or window and will persist until the browser cache is
cleared or when the following command is typed in a browser's console:
"localStorage.removeItem('conditionalMessageReadStatus');".

Q: How does the module checks for content types?

A: The content types are checked via JS by searching in the body tag a class
that corresponds to "page-node-type-[your content type]".


MAINTAINERS
-----------

* William Ranvaud (wranvaud) - https://www.drupal.org/user/1058108
