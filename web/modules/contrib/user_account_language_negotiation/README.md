User Account Language Negotiation module
========================================

INTRODUCTION
------------
This module does two things differently from Drupal 8 core:

1. Allows language switchers to save the new language in the user
   account of the logged-in user. This allows the site to transition
   to the user's previously preferred language during login.
2. In addition, it shows languages in their translation. What this
   means is visible in the picture to the right.

You need this module only if your multilingual site has users that
are typically logged in.


REQUIREMENTS
------------
- PHP 7.0 or higher
- A Drupal site with more than 1 language enabled


INSTALLATION
------------
1. Download the module. I suggest using Composer:
   `$ composer require 'drupal/user_account_language_negotiation:^1.0'`
2. Enable the module, e.g. using
   `$ drush en user_account_language_negotiation`


CONFIGURATION
-------------
3. Go to Detection and selection
   (/admin/config/regional/language/detection).
4. Uncheck all plugins except this module's **User account saver**.
   Only if **User account saver** is the only enabled plugin we can
   be sure that we manage the transition to the user's previously
   preferred language during login.
5. Make sure your users have a way of switching the language. If
   you haven't set up anything yet, you can to the block list
   (/admin/structure/block) and add Drupal Core's "Language switcher"
   block.

### Inner workings
Technically speaking, this module just supplies a LanguageNegotiation
plugin. It leaves the visual part of picking a language to other
modules. A typically setup is to use Core's "Language switcher"
block, optionally in conjunction with the Language Icons module. The
picture above is from this setup.

This module acts in 3 situations:

1. When the user opens a page, Drupal needs to know which language
   the page should be rendered in. If you are curious, see the
   `getLangcode()` function in the code.
2. When the user just switched the language: save it in his or her
   account (`processInbound()`).
3. When the language switcher block needs to know which languages
   exist (`getLanguageSwitchLinks()`).


MAINTAINERS
-----------
Current maintainer:
 * https://www.drupal.org/u/gogowitsch
