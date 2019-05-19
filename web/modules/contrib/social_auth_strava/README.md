Social Auth Strava allows users to register and login to your Drupal site with 
their Strava account. It is based on Social Auth and Social API projects.

This module adds the path user/login/strava which redirects the user to Strava 
for authentication.

After Strava has returned the user to your site, the module compares the email 
address provided by Strava to the site's users. If your site already has an 
account with the same email address, user is logged in. If not, a new user 
account is created.

Login process can be initiated from the "Strava" button in the Social Auth 
block. Alternatively, site builders can place (and theme) a link to 
user/login/strava wherever on the site.