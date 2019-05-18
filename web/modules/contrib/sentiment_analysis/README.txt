INTRODUCTION
-------------
* The module name (Sentiment Analysis) itself describes everything 
  about this module.

* Supports in entity types like node, block, comment.

* This module provides a field type ("Sensitiment Analysis")

* This module needs an additional API key(3rd Party) to check 
  user inputted text and return the result of sentiment(If negative).

* Goto manage fields in entity , you can add Sentiment Analysis field
  under General Section.

* Removed Sentiment Analysis comment module in D8 (as comment is also entity in D8).

* Goto admin/config/sentiment-analysis/list for list of results.

REQUIREMENTS
-------------

* Needs External API key(3rd Party) from https://www.havenondemand.com.

* Internet connection to hit API with inputted value and get output.

INSTALLATION
-------------

* Install as you would normally install a contributed Drupal 8 module. See:
  https://www.drupal.org/documentation/install/modules-themes/modules-8
  for further information.

USAGE
------
 * As most of the site(s), they are having user inputted value(s) 
   by their user, again to evaluate those value(s), admin/other permitted
   role(s) will check the content and then post in their site(s).

 * After enabling this module, there is no need to check manually for 
   the sentiment description of user inputted value(s).

MAINTAINER
-----------
Current maintainers:
 * A AjayKumar Reddy (ajaykumarreddy1392) - https://www.drupal.org/user/3261994
 * Fabian Fernandes (fabian.fernandes_30) - https://www.drupal.org/user/3046083
