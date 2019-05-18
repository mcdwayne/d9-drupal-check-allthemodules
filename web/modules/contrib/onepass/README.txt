OnePass for Drupal
---------------------

CONTENTS OF THIS FILE
---------------------
 * About 1Pass
 * Basic usage
 * Requirements
 * Recommended modules
 * Installation
 * Configuration
 * Feed
 * Test and Live environments
 * Using 1Pass to control access to your content
 * Maintainers

About 1Pass
---------------------
1Pass is a platform for single-article sales.

When 1Pass users see an article for sale behind the 1Pass button on a
participating web site, they click it and the article appears.
1Pass takes care of all the accounting behind the scenes.

This module makes that possible in the following ways:

 - It populates the button embed code
 - It provides a shortcode to add to posts where the 1Pass embed should appear
 - It provides a virtual display suite field which will automatically truncate
     the posts in case the editor doesn't enter the shortcode
 - It provides a built in Atom feed to make your content available to 1Pass

It also provides:

 - The option to use 1Pass to restrict content
 -- This option can be set when creating the content type
 --- Can be changed for each node

To use the module, you need:

 * 1Pass API credentials

To get these, enter your email at 1pass.me.

Basic usage
---------------------
This assumes that you already have a paywall or some other means of
restricting content. If you want to use 1Pass for this,
see _Using 1Pass to control your content_, below.

Requirements
---------------------
 * Views

Recommended modules
---------------------
 * Display suite

Installation
---------------------
Install the module under sites/all/modules/contrib
Enable the module.

Configuration
---------------------
There, you'll need to fill in your 1Pass publishable key and secret key
under : admin/config/content/onepass

Enabling a post for 1Pass :
 1) You have to edit a content type and configure it for being enabled
      with 1Pass integration
 2) You may configure the virtual display suite field for automatically
      truncate 1Pass content each time at the same place
 3) You can then choose to activate or not each content for being 1Pass
      able and so use the shortcode in the content

Feed
---------------------
The feed will update every time you update your website. 1Pass will
automatically discover any new content or updates to your existing content.
The feed is managed by the view : onepass_atoms which is restricted to be
viewed by 1Pass servers only.

Test and Live environments
---------------------
If you're a developer fine-tuning your 1Pass integration on a staging server,
use Dev API host to have 1Pass communicate with the 1Pass test server.
This setting is configured on admin/config/content/onepass

Using 1Pass to control access to your content
---------------------
If your content is currently free-to-air, you can use the 1Pass module to
control access. Enable the '1Pass paywall' box on the settings page and
on any content type.

Once this setting is checked, you can lock up a piece by adding the `[1pass]`
shortcode into the body copy, and ticking the 1Pass checkbox for that piece.
This will result in the post content being truncated at that point and
the 1Pass button being injected.

Maintainers
---------------------
This project has been sponsored by :
 * onepass : https://1pass.me/
