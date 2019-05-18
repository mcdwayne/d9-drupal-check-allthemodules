README.txt
==========

GovUK Notify is a scalable notifications platform for use by UK Government
departments and agencies.

GovUK Notify is a module to enable you to send emails from your Drupal
installation via the UK Government's GovUK Notify service
(https://www.notifications.service.gov.uk/)


INSTALLATION
------------

This module depends on the alphagov/notifications-php-client and
php-http/guzzle6-adapter. Depending on which strategy you're using to manage
your project dependencies you'll either need to:

Use Composer to install Drupal core and manage dependencies
- https://www.drupal.org/node/2718229

Modify composer.json in your Drupal's root directory
- https://www.drupal.org/node/2404989


CONFIGURATION
-------------

In order to use this module you'll need to have a Gov.UK Notify account, have
created an API key and have created a 'default' template.

1. Create a Gov.UK Notify account: 
https://www.notifications.service.gov.uk/register

2. Create an API Key:
Login to Gov.UK Notify.
Click on 'API Integration'
Click 'Create an API Key'
Give the Key a name and select the type of key
Note down the API Key you're given.

3. Create a 'Default' email template:
When sending messages through the Gov.UK Notify API Drupal can either use a
specific template, or a 'default' template which just contains one replacement
token for the subject and one for the entire message body. This provides some
flexibility as to whether to use Drupal's templating engine or Gov.UK's. Your
choice will be dependenent upon the solution you're trying to deliver.
Login to Gov.UK Notify
Click on 'Templates'
Click 'Create Template'
Select 'Email'
In subject just enter ((subject))
In message just enter ((message))
Save the Template and note the template ID.

4. Similarly for SMS messages, create a 'Default' SMS template:
Login to Gov.UK Notify
Click on 'Templates'
Click 'Create Template'
Select 'Text'
In message just enter ((message))
Save the Template and note the template ID.

5. Enable the govuk_notify module if you've not done so already, then 
Go to /admin/config/govuk_notify/settings to configure the module.

6. In the field 'API Key' enter your GovUK Notify API Key from step 2.

7. In the field 'Default template ID' enter the ID of your default template from
step 3.

8. In the field 'Default SMS template ID' enter the ID of your default SMS template from
step 4.

9. (optional). To test the system enter an email address in the 'Test email
address' field. When you click the 'Save configuration' button an email will be
sent to the email address that you've entered. NB If your account is still in
trial mode then you'll only be able to send emails to members of your team.

10. (optional). To test the system enter a phone number in the 'Test SMS
address' field. When you click the 'Save configuration' button an SMS will be
sent to the phone number that you've entered. NB If your account is still in
trial mode then you'll only be able to send SMS messages to members of your team.

11. (optional) If you want to send enable all system emails to be sent through
Notify ensure that then check the 'Use GOVUK Notify to send system emails'
field.

12. (optional) If you're using a Test (not Trial) API Key then you can simulate
temporary and permanent failures by checking either the "Always force a
temporary failure" or "Always force a permanent failure" - this will force the
module to use the email addresses listed at
https://www.notifications.service.gov.uk/integration_testing.
