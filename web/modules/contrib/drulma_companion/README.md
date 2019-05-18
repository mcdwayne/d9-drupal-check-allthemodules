# Drulma companion

Drulma companion is a helper module for
the [Drulma theme](https://www.drupal.org/project/drulma).
It is built to use the [Bulma](https://bulma.io/) CSS framework.

## Features

* Implements blocks with configurations from Bulma adapted to Drupal.
* Adds Fontawesome 5 template suggestions for inputs, submit buttons, etc.
* Provides a command to generate a subtheme with Drush. `drush generate drulma`

## Installation

Due to relying on external PHP libraries from packagist.org
to implement the subtheme generator
the module can only be installed using composer.

```
composer require drupal/drulma_companion:^1
```

This will also download the drulma theme.

Have look at
[Composer template for Drupal projects](https://github.com/drupal-composer/drupal-project)
if you are not familiar on how to manage Drupal projects with composer.

To able able to use the fontawesome 5 icons the
[Libraries provider fontawesome](https://www.drupal.org/project/lp_fontawesome)
module needs to be installed since fontawesome is optional for Bulma.

## Blocks

* Navbar with branding: Add a Bulma navbar that can have a logo
and the site name with two optional menus on the left and the
right end of the navbar. It is recommended to position it at
the top of the page or the header of a hero.
* Bulma tabs: Drupal primary and secondary tabs implemented as Bulma tabs.
* Menu as Bulma tabs: A drupal menu displayed as Bulma tabs.

Both tabs block are meant to be postioned at the footer of a hero.

## Block class module

This module depends on the block class module because Bulma
uses classes to modify the display of elements. With block class
is easy to add `container` or `section` class to blocks so the display
is transformed. It is preferred to have a setting configuration
on the block so this just a helper for all the blocks
that are not part of Drulma.

## Subtheming

Drush 9 Does not support commands coming from themes
so that is why this command lives in this module.

## Contributions

The project is open to improvements on how to override
Drupal markup to make it more adapted to Bulma but also
feel free to open any discussion about how to make Bulma
and Drupal play nicely together.

Patches on drupal.org are accepted but merge requests on
[gitlab](https://gitlab.com/upstreamable/drulma-companion) are preferred.

## Real time communication

You can join the [#drulma](https://drupalchat.me/channel/drulma)
channel on [drupalchat.me](https://drupalchat.me).
