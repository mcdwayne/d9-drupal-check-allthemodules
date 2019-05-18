Chrome Push Notification
===================

Chrome Push Notification that will help to send the notification in the
chrome browser of the desktop and mobile both.

INSTRUCTIONS:
--------------

1. Site should be have the SSL certificate because chrome notification
wants the site in the HTTPS format. This module will not work for HTTP site.
2. Enable the module chrome_push_notification.
3. Write the Google APP ID in the manifest.json.
4. Copy below two files to the root of the drupal because these are the
mandatory files
	a.) js/service-worker.js
	b.) js/manifest.json
5. Put the Google sendor Id in the manifest.json file.


CONFIGURATION:
--------------
Chrome Push Notification module have below configuration setting page

1. Using this URL you have to save the Google APP ID and Google API key.
	/admin/config/services/chrome_push_notification/configuration
2. Using the below URL you can send the notification to every user
who have enabled the chrome push notification in browser for your site.
	/admin/config/services/chrome_push_notification/send_message
3. Using the Below URL user can check the list of the User who have enabled
chrome notification.
	/admin/config/services/chrome_push_notification/user_list
