# Visual Website Optimizer module for Drupal

The Visual Website Optimizer module automatically includes the VWO javascript in your site pages. [Visual Website Optimizer][1] is an easy to use A/B split, and multivariate testing tool. VWO uses small snippets of javascript inserted into the head of each page to perform its tests; the Visual Website Optimizer module for Drupal automates the configuration and inclusion of those snippets.

The Visual Website Optimizer module requires an active VWO account. After installing the module, you need to configure it for use with your VWO account by entering your Account ID via settings page. Once configured, Visual Website Optimizer tests will be automatically included in every page, until you deactivate or disable the module.

[1]: https://vwo.com/

### Installation

1.  Extract the module to your site specific, or all sites modules directory. eg
     *  sites/<yoursitedir>/modules, or
     *  sites/default/modules, or
     *  sites/all/modules

2.  Enable the module in the admin/modules menu

3.  Sign up for Visual Website Optimizer account and obtain Account ID from provided javascript listing. Look for the numeric value after "var _vis_opt_account_id = " (synch) or "var account_id=" (async) at the top of the code, or copy and paste all of the code into the Account ID Extractor: <yoursite>/admin/config/system/visual_website_optimizer/vwoid

4.  Enter your Account ID and enable the module on the settings page at <yoursite>/admin/config/system/visual_website_optimizer

5.  Head to the VWO website and configure your tests.

6.  ..

7.  Profit!

### Asynchronous vs Synchronous javascript

VWO now highly recommend using the Asynchronous loading of the javascript. The module now defaults to this mode for all new installs, but existing users will remain on their current settings.

The setting to change between Synchronous and Asynchronous remains.

Please see the VWO blog posting on the subject:
https://vwo.com/blog/asynchronous-code/

### Credits

This module is based on the work of Will Ronco of Awesome Software (http://awesome-software.net/), submitted as a module to node #759278. Updated by Ted Cooper to use most recent javascript from VWO, prepare for inclusion on Drupal.org, and include a few additional features. The D8 version was finally instigated after prompting from IT-Cru with a partial patch.
See Git or CHANGELOG.txt for full list of changes.

