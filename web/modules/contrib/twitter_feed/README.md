INTRODUCTION
------------

The Twitter Feed module displays a configurable list of tweets in a block.
It does so using the Twitter REST API and the jQuery Timeago plugin to
display dates in a friendly, relative format (eg. 4 hours ago).

No assumptions about CSS.
Responses are cached.
No extra dependencies. Just install and go.
Output is fully themeable.

REQUIREMENTS
------------

None.

INSTALLATION
------------

 * Install as you would normally install a contributed Drupal module. See:
   https://www.drupal.org/documentation/install/modules-themes/modules-8
   for further information.
 * Add the timeago library to your site libraries folder. The module is
   expecting libraries/timeago/jquery.timeago.js
   Download: https://github.com/rmm5t/jquery-timeago/releases
   See: https://www.drupal.org/node/1440066

CONFIGURATION
-------------

 * There are 2 places you have to configure Twitter Feed:

   - Administration » Configuration » Web Services » Twitter Feed:

     This is the place where the Twitter API settings can be tuned.
     You can also set the locale for the jQuery timeago module.

   - Block placement screen:

     Here you can customize some display settings.

 * Overrides:

You can override the API Key / Secret per environment with
these settings in settings.local.php:

```
$config['twitter_feed.settings']['twitter_feed_api_key'] = 'KEY';
$config['twitter_feed.settings']['twitter_feed_api_secret'] = 'SECRET';
```

MAINTAINERS
-----------

Current maintainers:
 * Felipe Fidelix (Fidelix) - https://www.drupal.org/user/786032
