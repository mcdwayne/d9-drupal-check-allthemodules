# config_export_ignore
This module allows to ignore specified configuration entities from being
exported by config split.

Config split allows to split configuration, but in some cases you want to
ignore files from being exported at all.
Something like `migrate_plus` configuration files.
So you can specify all these configuration entities in config from and it
won't be exported.

# Other ways to prevent config from being exported
1. Drupal core default config export command also allows us to do that.

`drush cex --skip-modules=devel`

2. Drush CMI tools also let's you do this [link]


[link]: https://github.com/previousnext/drush_cmi_tools
