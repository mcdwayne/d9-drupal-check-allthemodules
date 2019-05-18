
Mailchimp Newsletter Campaign
=============================

This module is developed for the purpose of creating and scheduling newsletter
campaigns dynamically on Mailchimp account using "Newsletter" content type 
fields as email content.It provides you a regular newsletter template with 
static header and footer.It uses version 3.0 of Mailchimp API library.


Dependencies
============

Mailchimp https://www.drupal.org/project/mailchimp (only core module is 
required to be installed,not the submodules)


Installation
============

Install "mailchimp_newsletter_campaign" module as usual by directing your 
browser to /admin/modules.


Configuration
=============

*No configuration of its own.You only need to configure the Mailchimp core 
 module.
*Direct your browser to admin/config/services/mailchimp to configure the core 
 Mailchimp module.
*Put in your MailChimp API key for your MailChimp account.For more info,read 
 out the Mailchimp README.txt file.


Usage
======

1)Installing the mailchimp_newsletter_campaign module brings a new content type 
  named as "Newsletter" .
  
2)Go to /node/add/newsletter and create the newsletter campaign.Fill the email
  title(subject), email content.

3)You are required to choose the mailchimp list from the advance settings.
  If API key was not configured correctly or no list has been created in 
  mailchimp account ,then "Save" node button will remain disabled.

4)When you save the node ,it will automatically save the campaign on your 
  mailchimp account.

5)You can even schedule your newsletter by providing date and time while 
  creating node.Timezone referred will be this - 
  https://us15.admin.mailchimp.com/account/details/. 

6)If the schedule is not in future,it will save that campaign on Mailchimp as 
  a draft only.You can then schedule it from your Mailchimp account directly.

7)Also you can customize the Email template as per your need by altering the 
  file /mailchimp_newsletter_campaign/templates/mailchimp.html.twig
