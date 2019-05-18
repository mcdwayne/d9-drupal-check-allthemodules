
CONTENTS OF THIS FILE
---------------------
 * Introduction
 * Requirements
 * Installation
 * Configuration
 * Troubleshooting
 * Maintainers


-- INTRODUCTION --

* This module allows users to comment using Facebook's commenting box
  with as little configuration as possible. It adds a new block "Facebook Comments"
  which acts as a Facebook commenting widget. It has a simple configuration process, the entire process takes minutes to install and configure.

* For a full description of the module, visit the project page:
  https://www.drupal.org/project/facebook_comments_block

-- REQUIREMENTS --

* No special requirements.

-- INSTALLATION --

* Install as you would normally install a contributed Drupal module. See:
  https://www.drupal.org/documentation/install/modules-themes/modules-8
  for further information.

-- CONFIGURATION --

* Go to "admin" -> "structure" -> "block layout".
* You can place a facebook comment block in a region by selecting Place block.
* Under the "FACEBOOK COMMENTS BOX SETTINGS" you can configure the following:
  - Facebook Application ID: Optional.
  - Main domain: Optional: If you have more than one domain you can set the main domain
    for facebook comments box to use the same commenting widget across all other domains.
  - Language: Select the default language of the comments plugin.
  - Color scheme: Set the color schema of facebook comments box.
  - Order of comments: Set the order of comments.
  - Number of posts: Select how many posts you want to display by default.
  - Width: Set width of facebook comments box.

-- TROUBLESHOOTING --

* If the block did not appear:

  - Check if you have entered a correct FACEBOOK APP ID,
    leave it blank in case you don't have a Facebook app.

-- MAINTAINERS --

Current maintainers:
* Mohammad AlQanneh (mqanneh) - https://www.drupal.org/u/mqanneh
