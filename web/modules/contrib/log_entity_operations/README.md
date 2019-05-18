# INTRODUCTION
Drupal by defaults adds a log message for entity operations when done from 
interface. We miss these entries in logs when it is done through code.

With data coming from remote systems it is always saved in code and we miss the 
log entries for them. Most of the time we also disable revisions for them as we
do not want to make our database heavy with revision entries for data which we 
do not manage.

This project aims to add log entries for such cases. It can be configured to add
a log entry for specific bundle of specific entity type. For instance for e-comm 
site we might be getting products from remote system through APIs (pull or push)
and we do not manage the revisions of it as it is already managed in remote 
systems but we want the logs to check when was it last updated. And most of the 
time we also want to know what was changed in it.

* Project page: https://drupal.org/project/log_entity_operations

* To submit bug reports and feature suggestions, or to track changes:
   https://drupal.org/project/issues/log_entity_operations

# REQUIREMENTS
It requires only Drupal CORE.

# INSTALLATION
Install as any other contrib module, no specific configuration required for
installation.

# CONFIGURATION
[Coming soon]
