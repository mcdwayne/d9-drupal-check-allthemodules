# INTRODUCTION
This module allow adding prefix in URL for sites with only one language 
enabled.

* Project page: https://drupal.org/project/single_language_url_prefix

* To submit bug reports and feature suggestions, or to track changes:
   https://drupal.org/project/issues/single_language_url_prefix

# REQUIREMENTS
It requires only Drupal CORE. It needs Drupal Core's language module to be
enabled.

# INSTALLATION
Install as any other contrib module, no specific installation step required 
for this module.

# CONFIGURATION
Once installed and a language is enabled, configure langcode for the single 
language in system.

To exclude specific URLs from having the language prefix in URL, visit 
/admin/config/regional/language/single-language-url-prefix

Enter the one url per line and it can be a pattern. Drupal CORE's path matcher
service is used to match the URL. 
