Social Timeline module:
------------------------
Maintainers:
  Rob Lee (http://drupal.org/user/893454)
Requires - Drupal 8


Overview:
--------
The Social Timeline module lets you retrieve status/posts/videos/images
from different social networks in a timeline format from the newest to
the oldest.


Features:
---------
* Get status/posts/videos/ images from differents accounts in the same
  social network
* Twitter, Facebook Page, Youtube, Delicious, Flickr, Dribbble, Digg,
  Pinterest, Tumblr, Instagram, Google+, Lastfm
* Retrieve Youtube videos using search keywords.
* Retrieve tweets using a hashtag.
* Different display styles.
* Limit the number of Feeds to retrieve.
* Add multiple custom feeds
* Show/Hide Social Icons.
* Social Filter Support.
* Cross Browser Support.
* Simple to Customize.
* Full Documentation.
* Demo examples included.


Installation:
-------------
1. Download and unpack the Social Timeline module directory in your
   modules folder (this will usually be "modules" or "modules/contrib").
2. You need to purchase the jQuery Social Timeline plugin ($6, I did not create
   or sell it):
   http://codecanyon.net/item/jquery-social-timeline/2390758?sso?WT.ac=cate...
3. Unpack it to the social timeline module folder
   "e.g., modules/contrib/social_timeline/library.
4. Go to "Extend" in the Drupal admin and enable the module.


Configuration:
--------------
On the "Block layout" page there will be a new Social Timeline option under
"Content". This is where you can add new Social Timeline feeds and configure
them. Make sure to fill out the credentials below if using said services.


Set Twitter Credentials:
------------------------
1. Add a new Twitter application
2. Fill in Name, Description, Website, and Callback URL (don't leave any blank)
   with anything you want
3. Agree to rules, fill out captcha, and submit your application
4. Click the button "Create my access token" and then go to the OAuth tab.
5. Copy the Consumer key, Consumer secret, Access token and Access token secret
   into the files in the libraries folder:
   twitter_oauth/user_timeline.php and twitter_oauth/search.php


Set Facebook Credentials:
-------------------------
1. Add a new facebook application
2. Create a new App and fill the form
3. Copy the App ID and App Secret into the files in the libraries folder:
   facebook_auth/facebook_page.php


Set Instagram Credentials:
--------------------------
1. Add a new Instagram application
2. You will find the client ID in http://instagram.com/developer/clients/manage/
3. Copy the Client ID into the files in the libraries folder:
   instagram_auth/instagram.php and instagram_auth/instagram_hash.php


Set YouTube Credentials:
------------------------
1. Create/Select a project in the Google Console
2. In the API & Auth link on the sidebar make sure YouTube Data API is on.
3. In the Credentials link on the sidebar create a new key and select
   Browser key type.
4. Copy the API key into the files in the libraries folder:
   youtube_auth/youtube.php


Usage:
------
The Social Timeline module provdes blocks to use on various pages/contexts.
Go to "Structure" -> "Block layout" and look under Content for Social Timeline.
Once you click on the link it will bring up a configuration form for you to
input your settings. You cannot have more than one timeline on the same page.
