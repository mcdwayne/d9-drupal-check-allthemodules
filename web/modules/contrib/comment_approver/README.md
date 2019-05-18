CONTENTS OF THIS FILE
---------------------

 * Introduction
 * Requirements
 * Installation
 * MODULE FEATURES
 * Configuration
 * Maintainers


INTRODUCTION
------------
On a busy website where administrator and moderators deal with high volume of
comments, to moderate each and every comment could be a daunting task. Sometimes
admins skip comment moderation but that could lead to high amount of spam, to
address that need this module was built. The idea behind the module is for
system to run some tests like profanity, sentiment analysis and for comment to
be automatically published / unpublished as per the rules configured.

REQUIREMENTS
------------

No special requirements


INSTALLATION
------------

Install as you would normally install a contributed Drupal module. See:
https://drupal.org/documentation/install/modules-themes/modules-8 for further
information.

MODULE FEATURES
---------------
 * System will perform various tests (as configured by admin) before deciding
   if a comment should be automatically published.

 * Some of the examples of tests include profanity test, sentiment test
   ( 3rd Party Integration with http://text-processing.com/docs/sentiment.html)

 * Admin can configure which tests will be run on comments of the website and
   select the resulting action based on pass / fail status of the test.

 * If configured as a comment approver and comment passes all the selected tests
   than the comment will be automatically approved otherwise the comment will
   remain unpublish and will go for manual approval from admin.

 * If configured as comment blocker and if a comment fails any test then it will
   be unpublished, and will go for manual review.

 * As a developer you can add new tests as per your Organisation and website
   needs, these tests are written in form of comment approver plugins.

CONFIGURATION
-------------

 * After installing, go to:
    admin/config/comment-approver/comment-approver-settings

 * Select which tests you want to perform on a comment before approving.Select
   the tests you want system to perform on comments for any comment to be
   approved automatically.

 * Select the mode of operation (disabled/comment approver/comment blocker).

 * Configure the settings related to individual plugins (tests) if available.


MAINTAINERS
-----------

Current maintainers:
 * Hemant Gupta (https://www.drupal.org/u/guptahemant)
