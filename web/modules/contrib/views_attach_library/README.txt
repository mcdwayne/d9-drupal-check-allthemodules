Overview:
-------------------------------

The library in views module is designed to attach JS and CSS library in views,
by providing just library name.


Installation and configuration:
-------------------------------

Installation is as simple as copying the module into your 'modules' contrib
directory, then enabling the module.

To add library in view follow below steps
1) Create or edit view
2) Find Attach Library section
3) Click on add library or edit library 
4) Add or edit library name in textfield.

########## default view section ######### 

For example there is a custom module called "abc" and this module has
library called "abc-js" and this library has 2 js(abc1.js , abc2.js)
and 1 css(abc.css) file.

so add "portal_abc/abc-js" in textfield which is nothing but just library name,
where "abc" is module or theme name and "abc-js" is library name.
so this view will include 2 js(abc1.js , abc2.js) and 1 css(abc.css) file.

########## default view section end ########

#########  View display section ############

If view has block or page display where you want add another library ,
so just go to that block or page display and click on add library or edit library,
then add or edit library name in textfield.

For example there is a custom module called xyz and this module has
library called xyz-js and this library has 2 js(xyz1.js , xyz2.js)
and 1 css(xyz.css) file. 

so add "xyz/xyz-js" in textfield which is nothing but just library name.
where "xyz" is module or theme name and "xyz-js" is library name.
so this view display will include 2 js(xyz1.js , xyz2.js) and 1 css(xyz.css) file.

########## View display section end ######## 


For a full description visit project page:
https://www.drupal.org/project/library_in_views

Bug reports, feature suggestions and latest developments:
http://drupal.org/project/issues/library_in_views


---REQUIREMENTS---

*View & view UI
 

MAINTAINERS
-----------
Current maintainers:
* Hardik Patel - https://www.drupal.org/user/3316709/
* Yogesh Chougule - https://www.drupal.org/user/724666/
* Rahul Lamkhade - https://www.drupal.org/user/2718915/


