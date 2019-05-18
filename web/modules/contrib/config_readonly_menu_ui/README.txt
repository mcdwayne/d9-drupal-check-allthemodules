Config Readonly Menu UI

For the time being, this module requires a patch of config_readonly:
https://www.drupal.org/node/2826274#comment-12157133


To be noticed:
There are places where it can be impossible to move the content menu links.
For example, between two config menu links having the same weight.
This is a logical problem that comes with the Drupal weight system. To work
around this, when designing the site, do not mix content links and config
links in the same menu. Or if you need to, set the config links weights
so that the config links are spaced far enough for content links to be
placed in-between.
