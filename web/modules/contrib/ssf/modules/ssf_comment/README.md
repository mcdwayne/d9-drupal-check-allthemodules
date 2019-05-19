#Statistical Spam Filter for Comments

##Description
Statistical Spam Filter for Comments (SSF for Comments) is a module for Drupal 8
that is distributed together with the module Statistical Spam Filter (SSF) as a 
submodule.

SSF is able to learn the difference between wanted (ham) and not wanted (spam) 
texts. After entering enough ham and spam texts SSF is able to predict whether 
a new text is ham or spam with considerable accuracy.

SSF for Comments uses SSF for blocking comment spam.

##Features
* Automatic recognition of spam and ham comments.
* Automatic delivery of comments to approve, unapproved and spam comments 
folders.
* Approval of false positives by admin.
* Rejection of false negatives by admin.
* Automatic re-learning of ham and spam when moving spam to approved comments 
and vice versa.
* Delivers undecided comments to the unapproved comments.
* Configurable thresholds for fine-tuning the undecided margin between ham and 
spam classification.

##Installation
Install as usual, see 
[Installing Drupal 8 Modules](https://www.drupal.org/docs/8/extending-drupal-8/
installing-drupal-8-modules) 
under [Extending Drupal 8](https://www.drupal.org/docs/8/extending-drupal-8) 
for further instructions.

##Configuration
SSF for Comments requires no configuration apart from enabling the module.  
It is possible to adjust to thresholds for classifying texts as ham and spam.

###More information
[Project page](https://www.drupal.org/project/ssf)  
[b8: statistical discussion](http://nasauber.de/opensource/b8/discussion/)
