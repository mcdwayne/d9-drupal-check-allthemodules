# Nettybot

Helpers for the Nettybot application.

`drush pm-updatestatus` doesn't properly output `stderrs` making it difficult to suppress them. `nb-updatestatus` has an additional feature that suppresses any php errors in the output.

This is useful for automated scripts that rely on clean output from update checks. The Nettybot module can be used as a standalone module (without nettybot.io).

If there are any modules that need to be ignored from update checks simply update `sites/default/settings.php` by adding the line `$config['nb-updatestatus-project-exceptions'] = ['module_name', 'other_module_dont_update'];`.

### Under the hood

* File `nettybot/nettybot.drush.inc` hook [`hook_drush_command`][1] declares drush commands and by convention has command definitions.
* File `nettybot/nettybot.drush.inc` hook `nettybot_drush_command`
declaration `nb-updatestatus` definition `drush_nettybot_nb_updatestatus`

[1]: http://www.drupalcontrib.org/api/drupal/contributions!drush!docs!drush.api.php/function/hook_drush_command/7

