#Statistical Spam Filter

##Description
Statistical Spam Filter (SSF) is a module for Drupal 8 that is able to learn 
the difference between wanted (ham) and not wanted (spam) texts. After entering 
enough ham and spam texts SSF is able to predict whether a new text 
is ham or spam with considerable accuracy.

SSF is based upon 
[b8 - A statistical ("bayesian") spam filter implemented in PHP](
http://nasauber.de/opensource/b8/) by Tobias Leupold.

b8 has been designed to classify forum posts, weblog comments or guestbook 
entries, not emails. For this reason, it uses a slightly different technique 
than most of the other statistical spam filters out there.

This module offers a service for other modules to use. A submodule for blocking 
comment spam and maintaining comment ham and spam comments is part of this 
distribution.

##Features
* Learn ham and spam texts by entering them via the learn($text, $category) 
function.
* Unlearn ham and spam texts by entering them via the unlearn($text, $category) 
function.
* Classify text as ham or spam with the classift($text) function.

##Installation
Install as usual, see 
[Installing Drupal 8 Modules](https://www.drupal.org/docs/8/extending-drupal-8/
installing-drupal-8-modules) 
under [Extending Drupal 8](https://www.drupal.org/docs/8/extending-drupal-8) 
for further instructions.

##Configuration
SSF requires no configuration apart from enabling the module.

###More information
[Project page](https://www.drupal.org/project/ssf)  
[b8: statistical discussion](http://nasauber.de/opensource/b8/discussion/)  
b8_readme.md included in this distribution
