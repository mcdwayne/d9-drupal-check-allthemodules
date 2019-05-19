# Update Runner Drupal module
This module allows you to configure processors that extend the behaviour of the Drupal Core update module running customizable code when available updates are detected for core or contributed modules.


When available updates are detected, automated jobs are scheduled to be run when possible. These jobs are associated with processor plugins configured in your site to perform customizable update actions.

The default processor plugins present in the module push a code update to a remote code repository with information about the update available. This is only used to perform a push in the repository that can trigger a CI pipeline build job and therefore a new build based on the need of an automatic update.

Available processors:
- Github push
- Bitbucket push

Configuration for github
------------------------

Configuration for bitbucket
------------------------

Configuration for gitlab
------------------------


The module is currently in an early development phase.
