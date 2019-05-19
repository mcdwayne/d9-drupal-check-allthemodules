Webform Config Ignore


Webform Config Ignore adds a filter to configuration import and export to skip webforms and webform options. This allows site editors to change webforms and options lists without having to fear obliterating their work on a config import.

Usage: enable the module.

A setting is available to allow imports and exports on, for example, development.

Add the following to the relevant settings.php to disable the filter:

$settings['webform_config_ignore_disabled'] = TRUE;