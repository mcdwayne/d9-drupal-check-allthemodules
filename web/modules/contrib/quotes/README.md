## CONTENTS OF THIS FILE

* Status
* Requirements
* Recommended modules
* Installation
* Configuration
* Theming
* Notes on upgrading
* Credits

## Status

This branch is currently just a stub to hold the port of **Quotes** to
Drupal 8.

## Introduction

The **Quotes** module allows users to maintain a list of quotations
that they find notable, humorous, famous, infamous, or otherwise
worthy of sharing with website visitors. The quotes can be displayed
in any number of administrator-defined blocks. These blocks will
display either a randomly-selected quote or the most recent quote
based on the restrictions of each block. Blocks can be configured to
restrict to certain nodes, roles, users, or categories.

* For a full description of the module, visit the [project page][1].

* To submit bug reports and feature suggestions, or to track changes
  visit the project's [issue tracker][2].

* For community documentation, visit the [documentation page][3].

## Requirements

* Advanced help hint[4]:
  Will link standard help text to online help and advanced help.

## Recommended modules

* [Advanced Help][5]:
  When this module is enabled, display of the project's `README.md`
  will be rendered when you visit
  `help/anonymous_publishing/README.md`.
* [Markdown filter][6]:
  When this module is enabled, display of the project's `README.md`
  will be rendered with the markdown filter.

## Installation

1. Install as you would normally install a contributed drupal
   module. See: [Installing modules][7] for further information.

2. Enable the **Quotes** module on the *Modules* list page.  The
   database tables will be created automagically for you at this
   point.

3. Check the *Status report* to see if it necessary to run the
   database update script.

5. Proceed to configure the module, as described in the configuraton
   section below.



## Configuration

First, set permissions as desired on admin/people/permissions.

The quotes module has five permissions:

* administer quotes:
    allows users to administer the quotes module and
    create/configure/delete quote blocks
* create quotes:
    allows users to create quotes (they cannot edit quotes without the
    'edit own quotes' permisssion)
* import quotes:
    allows users to import quotes (they cannot edit quotes without the
    'edit own quotes' permission)
* edit own quotes:
    allows users to create, edit, and delete quotes
* promote quotes to block:
    allows users to promote their quotes so that they will appear in
    any defined quote blocks


Then go to the *Configuration » Content authoring » Quotes* to create
and configure the quote blocks.

## Theming

The display of quotes is themeable using two functions:

* `theme_quotes_quote()` which displays a single quote/author;
* `theme_quotes_page()` which displays a list of quotes.

The default implementation of `theme_quotes_quote()` uses two CSS
classes to allow you to control the display of quotes. The quote
itself uses `quotes-quote`. The author/attribution uses
`quotes-author`.

## Notes on upgrading

If you are upgrading from a pre-4.5 version, you will need to export
and re-import your existing quotes from your pre-4.5 database or
upgrade to Drupal 4.6, to 4.7, and then to 5.

If you are upgrading from Drupal 5, please update to Drupal 6.16. This
is also a required step if your planning to skip Drupal 6 and go
straight to Drupal 7. A note of caution, if you do skip Drupal 6 in
your migration path be sure that you log into Drupal 6.16 and enable
all your modules before proceeding with the upgrade to Drupal 7. If
you do not do this, then you will lose all your quotes as Drupal 7
wipes out all node bodies if it does not find where module has been
enabled before the upgrade.

## Credits

[jhriggs][9] (original author)
[ctmattice1][10] (former maintainer)
[NancyDru][11] (former maintainer)
[gisle][12] (current maintainer)

Development of the Drupal 7 version is sponsored by [Hannemyr Nye Medier AS][13].

[1]: https://drupal.org/project/quotes
[2]: https://drupal.org/project/issues/quotes
[3]: https://drupal.org/node/24389
[4]: https://www.drupal.org/project/advanced_help_hint
[5]: https://www.drupal.org/project/advanced_help
[6]: https://www.drupal.org/project/markdown
[7]: https://drupal.org/documentation/install/modules-themes/modules-7

[9]: https://www.drupal.org/user/3883
[10]: https://www.drupal.org/u/ctmattice1
[11]: https://www.drupal.org/u/nancydru
[12]: https://www.drupal.org/u/gisle
[13]: http://hannemyr.com/hnm/
