INTRODUCTION
------------

Module provides a toggle widget for Boolean field type using bootstrap
toggle library.


INSTALLATION
------------

Install as you would normally install a Drupal contributed module.

Download bootstrap toggle library from here:
https://github.com/minhur/bootstrap-toggle/archive/master.zip
(for more information about this library visit this page:
http://www.bootstraptoggle.com)

Place this library under libraries folder in docroot and rename this
library folder to 'bootstrp_toggle'

This module requires bootstrap theme for add/edit pages to work properly.
So make sure you have bootstrap theme as default theme and don't use
your admin theme for add/edit pages if you use any.

REQUIREMENTS
------------

This module requires bootstrap theme for all places where you need
to use this toggle button that means at all add/edit forms.

Libraries module is also required.


CONFIGURATION
-------------

1. Go to manage fields of the content type you wish to have a toggle button
and add a Boolean field.
2. Go to manage form display tab and under widget column select
'Bootstrap Toggle' for your field.
3. Configure the settings for that toggle button and update those settings.
4. Finally save the manage form display and you are done.

Try out different combination of settings to get a different look
of toggle button.


MAINTAINERS
-----------

Current maintainer:
  * Abhishek Pareek (abhishek_pareek) - https://www.drupal.org/user/3231365

This project has been sponsored by:
  * Innoraft Solutions - https://www.drupal.org/node/2145877
