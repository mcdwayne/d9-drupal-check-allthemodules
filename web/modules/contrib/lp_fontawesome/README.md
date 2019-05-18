# Libraries provider fontawesome

Libraries provider is just a library definition
for the [Fontawesome library](https://github.com/FortAwesome/Font-Awesome/)
with the extra information needed to be configured by
[Libraries provider](https://www.drupal.org/project/libraries_provider).

By default it will load the Fontawesome library from the
[jsdelivr CDN](https://www.jsdelivr.com/).

Note that libraries provider is not a required module since
the definition of the library is fine out of the box.
Install libraries provider when you need to change some of the defaults
this module stablishes like the version or load it
from the local filesystem.

## Installation

It is recommended to install this module using composer.

```
composer require drupal/lp_fontawesome:^5
```

Have look at
[Composer template for Drupal projects](https://github.com/drupal-composer/drupal-project)
if you are not familiar on how to manage Drupal projects with composer.

## Contributions

Patches on drupal.org are accepted but merge requests on
[gitlab](https://gitlab.com/upstreamable/drupal-lp-fontawesome) are preferred.

## Real time communication

You can join the [#libraries-provider](https://drupalchat.me/channel/libraries-provider)
channel on [drupalchat.me](https://drupalchat.me).

## Notes

This project started as a way to use icons on the
[Drulma theme](https://www.drupal.org/project/drulma)
so at the moment it covers the optional loading of
Fontawesome 5.
