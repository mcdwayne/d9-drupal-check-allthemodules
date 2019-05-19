OVERVIEW
--------

This module does not do anything directly. All it contains is tests that:
- Verify that the user interface text that is used in the User Guide and the
  steps for the tasks are present and work.
- Generate screen capture images
- Generate database dumps and files directories that you can use to clone the
  demo site that the User Guide builds, as it would be at the end of each
  chapter.


EXCLUSIONS
----------

A list of images that could not be automated, and therefore need to be generated
manually, can be found in the source/en/images/README.txt file in the main
User Guide project directory.

There are also some pages in the User Guide that are difficult to test using
this automated test framework, because they involve steps on drupal.org. There
may be a test added for them later, but for now, these pages should be manually
tested periodically, to make sure the text in the User Guide for task steps
matches the current drupal.org site:

- extend-manual-install
- extend-module-find
- extend-module-install
- extend-theme-find
- extend-theme-install
- install-prepare
- security-update-module
- security-update-theme


RUNNING TESTS ON DRUPALCI
-------------------------

If you are a maintainer of the User Guide Tests project, you can run the tests
on the DrupalCI infrastructure by following these steps:

1. Log in to Drupal.org and go to the User Guide Tests project page:
   https://www.drupal.org/project/user_guide_tests

2. Click "Automated testing".

3. Find the latest branch of the project, such as 8.x-6.x-dev, and click
   "Add test / retest" under that branch.

4. Choose these values and click "Save & queue":
   Environment: PHP 7.2 & MySQL 5.5
   Core: Stable release
   Schedule: Run once

5. When the test finishes, you can find the screenshot and backup files
   as follows, starting from the "Automated testing" page:

   First, click through to the test result, then "View results on dispatcher".
   In the "Test result" section (you may have to expand it), click on a
   particular test link and look at the "HTML output was generated" section.
   It should have lines saying where the backups and screenshot files are
   located, like "BACKUPS going to...". The directories will end in something
   like "test46426370/backups" and "test46426370/screenshots".

   These directories can be found in the "Build artifacts" section of the
   dispatch results page, under
   artifacts / run_tests.js / simpletest_html

UPDATING IMAGES AND BACKUPS
---------------------------

You can copy the .gz files in the backup directories into the "backups"
directory under this directory, in the subdirectory for the appropriate
language, and commit them to Git.

The screenshot images will need to be cropped. You can crop them in place by
running the cropimages.sh script in this directory. You will need to have the
ImageMagick command "convert" available on your computer. To run it, cd to the
directory with the uncropped images, and run the script. After cropping, images
go into the ../source/LL/images directory in the User Guide project, where LL is
the language code.


SETTING UP TO RUN TESTS MANUALLY
--------------------------------

You can run the tests on your own local system using Drupal Core's PHPUnit test
framework from the command line. This section details how to set up the tools;
some steps may need to be repeated if you update software on your local
computer. Here are the steps:

1. Install the Chrome or Chromium browser, if you do not already have it
   installed.

2. Install a local test Drupal site, running the version of Drupal you want to
   generate screen shots for (Drupal 8.0.2, 8.1.0, etc. -- make sure it is the
   latest actual release, not a development branch, for purposes of screenshots.

3. Apply patches for core issue(s):

   https://www.drupal.org/project/drupal/issues/2905295
   At this time, the issue has no official patch. But it can be gotten around
   by applying this work-around patch:
   https://www.drupal.org/files/issues/2019-02-26/2905295-work-around.patch

   https://www.drupal.org/project/drupal/issues/3037729
   Apply this patch file:
   https://www.drupal.org/files/issues/2019-03-05/3037729.patch

4. Copy the entire User Guide Tests project directory into the top-level
   'modules' directory of your local Drupal site. (Alternatively, if your
   operating system supports it, you can instead make a symbolic link.)

5. This module requires one PHP library that is not available to Composer (it
   is on GitHub but not in the "Packagist" package repository). So, it has
   to be downloaded manually, until the developer registers it -- see issue
   https://github.com/backupmigrate/backup_migrate_core/issues/3

   To do this, run the following commands from your Drupal root directory:

   composer require psr/log dev-master
   composer config repositories.bm vcs https://github.com/backupmigrate/backup_migrate_core
   composer require backupmigrate/core

6. At the top level of your Drupal site, enter the following command:

   composer require drupal/user_guide_tests

   If you do not have Composer
   installed, see https://getcomposer.org/

7. The previous step should have downloaded the Mayo theme and Admin Toolbar
   modules. Edit their info.yml files by adding the lines:
     version: 8.x-1.25
     project: admin_toolbar
     datestamp: 1542915184
   to modules/contrib/admin_toolbar/admin_toolbar.info.yml
   and:
     version: 8.x-1.2
     project: mayo
     datestamp: 1459203843
   to themes/contrib/mayo/mayo.info.yml

   You'll also need to remove any other "version" lines in both of those
   info.yml files (there might be one that says "version: VERSION").

8. Follow the steps in core/tests/README.md to set up the testing environment,
   including the parts that are specific to running tests that use
   chromedriver and WebDriverTestBase.


RUNNING THE TESTS MANUALLY
--------------------------

Once you are set up for testing, you can run the tests for a particular language
as follows:

1. Start chromedriver and keep it running:

   chromedriver --port=4444


2. Find the test file for the language you want to run. The tests for each
   language are in the tests/src/FunctionalJavascript directory under this
   directory.

3. Optionally, edit the test file to make it run just a subset of the
   screenshots and tests, and to enable/disable the saving of database/file
   backups. To do this, find the member variable $notRunList in the test file,
   change its name to $runList, and change 'skip' to another value for each
   section you want to run. The values are documented in the base class
   UserGuideDemoTestBase.php.

   You may also want to override the $doCrop member variable from the
   ScreenshotTestBase base class. If it is set to TRUE, the test will crop
   images automatically. The default is FALSE because cropping doesn't work
   in many PHP environments. You can run the test called DrupalScreenshotTest
   to see if the cropping process works.

4. Run the test for the language of interest with a command like this, from the
   core directory under your Drupal root:

   sudo -u www-data ../vendor/bin/phpunit -v -c /path/to/phpunit.xml \
   ../modules/user_guide_tests/tests/src/FunctionalJavascript/UserGuideDemoTestEn.php

   If your web user is something other than www-data, modify the command
   appropriately, and change the language near the end of the command if
   necessary. You will also need to change the path to your phpunit.xml file.

5. Assuming the test run succeeds, you should see some output that tells you
   where the backups and screenshot files have been stored.


RESTORING BACKUPS
-----------------

This Git repository contains file and database backups from running the tests
for each language, for each chapter. You can use them to set up a site that
contains the output of the User Guide steps, at the end of each chapter. Find
backups in subdirectory "backups" under this directory, organized by language
and then by chapter.

Note that if you restore a database backup, the database table prefix has been
set to 'generic_simpletest_prefix'. You can either set your Drupal site to use
this database prefix in your settings.php file, or you could do a search/replace
on the database backup file before you import it.

The file backups contain the contents of the sites/default/files directory.


MAINTAINER/DEVELOPER NOTES
--------------------------

Making a test class for a new language:
- Extend the base UserGuideDemoTestBase class, and name it UserGuideDemoTestLL,
  where LL is the language code.
- Override the $demoInput member variable, translating the input into the
  target language. Note that most of the text should not contain
  ' characters, as this will result in an error when generating the screen
  shots. If you have a spreadsheet with the array keys for $demoInput in
  column A, and the translated text in column C, you can use this formula
  to generate the array in row 2 (and then copy to the other rows):
     =IF(A2 <> "","'"&A2&"' => """&C2&""",","")
  Then copy this column of output into the $demoInput array in your
  new class.
- Add PO files to the translations directory (see README.txt file there for
  instructions).

Troubleshooting failing tests:
- These tests are based on WebDriverTestBase, which uses Chromedriver to
  automatically drive a Chrome or Chromium browser.
- If you are having problems at a particular line of a test, you can insert
  the following line just above it to stop the test and do things manually in
  the browser (including looking at the dblog report, etc.):
    $this->stopTheTestForDebug();
  Once you are done, close the browser window. The test will abort.
- The browser in tests is sensitive to scrolling -- if it has scrolled down too
  far, it may not find form fields to fill in, text in asserts, etc. You can
  insert
    $this->scrollWindowUp();
  in a test to scroll the window back to the top and avoid this problem.
- The browser has JavaScript and Ajax capabilities, so it should behave mostly
  like what you see in your own browser on a Drupal site. But if you trigger
  an Ajax behavior, you need to wait for it to finish before going on with the
  next step in the test -- if you don't, you may have weird-looking test failure
  messages having something to do with Curl calls. To wait for Ajax to finish,
  insert the following line in the test:
    $this->assertSession()->assertWaitOnAjaxRequest();
