CONTENTS OF THIS FILE
---------------------

 * Introduction
 * Demo
 * Installation
 * Configuration
 * Other IRC Hooks
 * Design Decisions


INTRODUCTION
------------

Superslides Caption Slider is a Caption Slider integrated with views using two 
powerful libraries Superslides and Animate.css

Superslides is a full screen, hardware accelerated slider for jQuery.animate.css
is a bunch of cool, fun, and cross-browser animations can be applied on captions


DEMO
------------

http://burblex8gjpcnsbh.devcloud.acquia-sites.com/


INSTALLATION
------------

First you create new folder either in the sites/all/modules folder or just 
directly in the modules folder at the Drupal root. 
The good news is that you can move the folder even after, enabled. No more need 
to rebuild the registry. You can thanks the clever autoloading capability of D8

1.	Download Module and Enable it.

2.	Dowload superslides library from 'https://github.com/nicinabox/superslides'
and place it in libraries folder. 

3.	Rename the folder as 'superslides', So your file structure should look like 
this: [drupal_root]/libraries/superslides/dist/jquery.superslides.min.js.

4.	Dowload Animate css library from 'https://github.com/daneden/animate.css'
and place it in libraries folder. 

5.	Rename the folder as ‘animate’ , So your file structure should look like 
this: [drupal_root]/libraries/animate/animate.css.


Configuration
-------------

•	Add new view (block preferred) of fields
•	Choose style format as ‘superslides’
•	Add Image field. Title field, or body field.
•	You can choose options from style options like autoplay, authoplay duration,
field animation etc

Features
----------------
•	Easily Integrated through Views
•	Simple, easy to use interface.
•	Create FullScreen Responsive slideshows in seconds through views
•	Autoplay
•	Key Arrow Bullet Navigation
•	100+ caption effects/transitions
•	Multiple sliders in one page
•	Cross browser support
•	Browser Compatibility
•	IE6+
•	Chrome 3+
•	Firefox 2+
•	Safari 3.1+
•	Opera 10+
•	Mobile browsers(iOS, Android, Windows, Windows Surface and Mac are all 
supported)
