# Rules API POST data

This module adds a Rules action for posting content to a remote API.

The base of this module was borrowed from the "Rules HTTP Client" project.
See: https://www.drupal.org/project/rules_http_client

Along with a  simple Rules plugin, some extra configs are included for
adding an API Transaction content type. 

Behind the scene, "drupal_http_request" (curl) is used to
make the HTTP calls, Rules handles the UI to setting you authentication
and header data.

Look for a new Rules action "API POST" within the data section of Rules actions.

Open Source Historic Shout Out & Credits:
Thanks to all those who have helped along the way.

Rules HTTP Client was originally created by Mitchell Tannenbaum (mitchell) and
co-maintained by Stuart Clark (Deciphered) and Benjamin Melançon
(mlncn). The Drupal 8 porting was done by Ajay Nimbolkar (ajayNimbolkar).


Required modules
--------------------------------------------------------------------------------

* Rules - http://drupal.org/project/rules
