CONTENTS OF THIS FILE
---------------------

 * Introduction
 * Requirements
 * Installation
 * Configuration
 * Maintainers


INTRODUCTION
------------

The Sa11y module is a client for connecting to an a11y API to check your sitemap
and individual nodes for a11y issues.


 * For a full description of the module visit:
   https://www.drupal.org/project/sa11y

 * To submit bug reports and feature suggestions, or to track changes visit:
   https://www.drupal.org/project/issues/sa11y


REQUIREMENTS
------------

This module requires the following outside of Drupal core.

 * Simple XML Sitemap - https://www.drupal.org/project/issues/simple_sitemap
 * API key from https://www.sa11y.me/drupal/beta


INSTALLATION
------------

 * Install the Sa11y module as you would normally install a contributed Drupal
   module. Visit https://www.drupal.org/node/1897420 for further information.


CONFIGURATION
-------------

    1. Navigate to Administration > Extend and enable the module.
    2. Configure at Administration > Configuration > Web Services > Sa11y to
       configure the Sa11y client.
    3. In the Setting horizontal tab, enter the sa11y API key which can be
       obtained from signing up at Sa11y.me.
    4. Select which rules to apply to your scans: WCAG 2.0 Level A, WCAG 2.0
       Level AA, Section 508, Best Practice, or Cutting-edge techniques. Select
       none to use all rules.
    5. Fill in Inclusions and Exclusions.
    6. Select how frequently you want to automatically check for accessibility
       issues: Daily or Weekly.
    7. Whenever the site checks for issues, it can notify a list of users via
       email. Enter each address on a separate line. If blank, no emails will be
       sent.
    8. Save configuration.
    9. Navigate to the Generate horizontal tab to generate reports.
    10. Reports are listed in the Reports tab.


MAINTAINERS
-----------

 * Bryan Sharpe - https://www.drupal.org/u/b_sharpe
