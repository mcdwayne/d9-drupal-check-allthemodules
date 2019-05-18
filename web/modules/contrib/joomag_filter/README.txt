INTRODUCTION
------------

This input filter simplifies the embedding of Joomag publications in Drupal 
nodes. Simply copy the Joomla embed code provided by Joomag and paste it into
your post between brackets.

INSTALLATION
------------

1. Copy/upload the joomag_filter module to the modules directory
   of your Drupal 8 installation.

2. Enable the 'Joomag Filter' module in 'Extend', (admin/modules).

3. Visit the 'Configuration > [Content authoring] Text formats and editors'
   (admin/config/content/formats). Click "configure" next to the 'Full HTML' 
   input format.

4. Enable (check) the Joomag filter in the list of "Enabled filters".

5. Under "Filter processing order", drag the Joomag filter to the bottom (if not already there).

6. Save the configuration.
   
HOW-TO
------
1. Sign-in at joomnag.com.

2. In joomag.com, go to "MY ACCOUNT" > "MY MAGAZINES" and select a magazine 
from the list.

3. Click the "Embed" button of your issue.

4. Go to the "Joomla" tab and use the corresponding code. For example,
   [joomag autoFit=true title=my-mag 
   magazineId=090606500142208920 backgroundColor=ffffff toolbar=878787,100 ]

5. Click "Save" or "Preview" and you will see the Joomag content inside an 
   IFRAME.
   
See [this Joomag KB](http://goo.gl/DKoQOH) article for more information.

AUTHOR/MAINTAINER
-----------------

-- Mariano Barcia
https://www.drupal.org/u/mariano.barcia
