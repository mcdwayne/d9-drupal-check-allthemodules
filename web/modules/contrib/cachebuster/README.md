# Cachebuster8r (Cache Buster for Drupal 8)

This is fairly simple module, that does a great great job.
It removes the query string from CSS files, by converting them all to external
libraries.

Now, you wouldn't want to do something like this on a production website,
but it does facilitate development a lot.

Without the query strings the auto-reload of CSS files on chromium/chrome
browsers works, without the need for 3rd party JavaScript tools.

To see how Chromium workspaces are configured please visit this page:
https://stackoverflow.com/questions/16631825/chrome-developer-tools-workspace-mappings

Developed by:
Bill Seremetis (bserem)
Panagiotis Moutsopoulos (vensires)
