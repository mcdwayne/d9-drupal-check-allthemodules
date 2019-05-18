Mailjet Module - Drupal 8.x.
===========================

Mailjet APIv3 module for Drupal 8.x.

This module for Drupal 8.x. provides complete control of Drupal Email 
settings with Mailjet and also adds specific Drupal Commerce email marketing 
functionality such as triggered marketing emails and marketing campaign revenue statistics.

The Mailjet Module for Drupal 8.x configures your default Drupal SMTP 
settings to use Mailjet's SMTP relay with enhanced deliverability and 
tracking. The module also provides the ability to synchronise your Drupal 
opt-in contacts and send bulk and targeted emails to them with real time 
statistics including opens, clicks, geography, average time to click, unsubs, etc. 

Mailjet is a powerful all-in-one email service provider used to get maximum 
insight and deliverability results from both  marketing and transactional 
emails. Our analytics tools and intelligent APIs give senders the best 
understanding of how to maximize benefits for each individual contact and 
campaign email after email. 

Requirements
------------
  * Libraries API (https://www.drupal.org/project/libraries)

Recommended modules
-------------------
  The following modules are not strictly required but it is nice to install them to get 
  the full capability of the Mailjet features.
  * Commerce (https://www.drupal.org/project/commerce)
  * Message (https://www.drupal.org/project/message)


Prerequisites
-------------

The Mailjet plugin relies on the PHPMailer v5.2.21 for sending emails.

To install PHPMailer via composer use: NOT SUPPORT YET! 


To install PHPMailer manually:
1) Get the PHPMailer v5.2.22 from GitHub here:
http://github.com/PHPMailer/PHPMailer/archive/v5.2.22.zip
2) Extract the archive and rename the folder "PHPMailer-5.2.22" to "phpmailer".
3) Upload the "phpmailer" folder to your server inside DRUPAL_ROOT/libraries/.
4) Verify that the file class.phpmailer.php is correctly located at this
path: DRUPAL_ROOT/libraries/phpmailer/class.phpmailer.php

Installation
------------

1. Download a last release of MAILJET beta version for Drupal version 8.x.
2. Upload the module  in DRUPAL_ROOT/modules/ directory ( and extract the module if is archived).
3. Log in as administrator in Drupal.
4. Enable the Mailjet settings module on the Home >  Administration > Extend page.
5. Fill in required settings on the Home > Administration > Configuration > System  Mailjet Settings > Mailjet API page.
6. You will be required to enter API key and Secret Key from your Mailjet account. If you do not have an account yet, please [create one](https://app.mailjet.com/signup?aff=drupal-8).

Configuration
-------------

1. The site can be set up to use the mailjet module as an email gateway, this
    can be easily configured, by clicking on the Settings tab => 
    your_site/admin/config/system/mailjet, and then selecting the checkbox on 
    the top, "Send emails through Mailjet", click "Save Settings" button on the 
    bottom of the page. 
    You can test that feature by sending a test email, just click the button on 
    the top of the page Send test email in Settings tab.
2. If you want to enable the Campaign feature, you should enable the 
    Mailjet Campaign module, you can do that from Home >  Administration > Extend page.
3.  Enabling the campaign sub module will create additional menu item in your
    administration menu, the new menu is called "Campaign" (/admin/config/system/mailjet/mailjet-panel/campaign). 
4. Clicking this menu item will display all the campaigns created by the administrator, 
    from this point you will be able to create new campaigns as well, 
    the same way you do that on mailjet.com.
5. If you want to create a campaign simply go to the campaign page => 
    your_site/admin/mailjet/campaign
    On the top right side of the page that will be presented there is a 
    button “Create a campaign”, clicking that button will lead you to a new 
    page presenting a form that needs to be full fill, 
    this is the first out of three steps of creating a new campaign. The 
    following fields are requiered - title of the campaign, language, and 
    contact list that you already created, select your edition mode and click
    “Save and continue”.
    In the next step you should enter the “Sender name”, choose template of 
    the email and write your email, 
    if you want you can add links inside the email body(the "TEXT" text area) 
    leading to your site and if a customer click on that link and purchase any product 
    from your site this order will be recorded in the ROI stats feature 
    where you can see how good is your conversion rate, click the “Done” 
    button on the bottom of 
    the email text area, and click “Continue”, in the next step which is the 
    last one you can choose 
    to send the email now or schedule it for later, click “Save and send”.
6.  If you enable the stats module 2 menu items will appear Dashboard where 
    you can see the results of the mail campaigns and the Mailjet ROI stats,
    clicking the ROI statsyou can see the actual conversion of your campaigns,
    this feature will display a view which will present the campaign name, 
    number of orders made by users who clicked on the link of your site in your email campaign.
7. My account menu item will redirect you to the mailjet logging page.
8. Upgrade menu link will redirect you to the pricing list of mailjet where 
    you can pick up a plan and upgrade your account.
9. The contacts menu item allows you to create lists that can be used for your campaigns.
    If you click on Contacts the list of all contact lists will be displayed on the top right 
    side of the screen a button for creating a new contact list is available => Create a contact list. 
    If you click on the button a short notification from mailjet with some terms will appear 
    if you click the OK button you will be redirected to the creation form of your contacts list. 
    Here you need to enter your list name, choose an import method => 
    Upload from CSV, Copy/Paste from Excel, Copy/Paste from TXT, CSV, RTF. 
    Upload your file and click Create. 
    On the next step you should choose the import type email or mailjet_list_view click create.
10. If you want to enable the trigger_examples sub-module you need to enable 
    the views_bulk_operations module and apply the following patch to it:  
    https://www.drupal.org/files/issues/views-vbo-patch-anon-users.patch

Author

## Changelog
= 8.x-2.3 =
*Fix mailjet composer issues
*Use Latest PHPMailer(6.0.7) not only v5.2.22
*Use phpmailer as external library or from drupal composer
*Change installation requirements
*Improve subscription url building and encode base64 encoded properties
*Convert contact properties values depends on type on confirmation
*Remove phpmailer from plugin
*Fix test mail
*Fix typos

= 8.x-2.2 =
* Add user agent to all API calls

= 8.x-2.1 =
* Fix synchronizations
* Fix subscription witget
* Fix ROI stats

= 8.x-2.0 =
* Remove depricated Global $language from stats module
* Enable user to sort subscription form parameters
* Add sort_fields field to the subscrioption form

= 8.x-1.3 =
* Fix Saving settings with "Send emails through Mailjet" unchecked overrides non-default Mail system
* Fix adding inline css and js with the subscription form

= 8.x-1.2 =
* Update the tracking parameter

= 8.x-1.1 =
* Fixed problem with breadcrumb navigation
* Fixed problem with uninstall of trigger examples
* Fixed admin URL link not working during module initial setup
