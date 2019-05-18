
CONTENTS OF THIS FILE
---------------------

 * Introduction
 * Configuration
 * Features
 * Requirements
 * More information

INTRODUCTION
------------

The Concurrent URL negotiation module provides concurrent prefix
and domain language detection. Makes it possible so that
'example.com/en' and 'example.com' could both result in english
while 'example.ro' in romanian.

It does so by replacing the core URL language negotiation plugin.

CONFIGURATION
-------------

This module has only one configuration, for the negotiation
plugin, that can be found here:
Administration > Configuration > Regional and language > Languages
> Detection and Selection > Concurrent URL (configure)

Here you can set the domain and prefixes for the languages. If the
case cross-domain authentication can also be enabled from here.

For each enabled language you can set the domain and prefixes to
be checked against.

 * domain: can be only a single domain name or the special string
   {domain-any} that matches any domain name.
 * prefixes: can be a single URL path prefix or multiple separated
   by the '|' character.

FEATURES
--------

 * Multi-prefix
   Multiple prefixes can be set for one language to be matched.

 * Cross-domain authentication
   When multiple distinct domains are set for negotiations, this
   module also provides cross-domain authentication.
   If a user is logged in on 'example.com', but not on 'example.ro',
   and he navigates from 'example.com' to 'example.ro', he will be
   automatically logged in. Additionally when a user logs out on
   one domain, he will also be logged out on the other domains from
   which he was automatically logged in.

REQUIREMENTS
------------

No special requirements.

MORE INFORMATION
----------------

 * When installed from drush the default domain will be set to
   '{domain-any}', which matches any domain name.
 * Even if the provided plugin is not enabled for 'Interface text
   language detection' it will still be used for language detected
   by URL.
