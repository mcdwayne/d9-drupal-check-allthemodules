Drupal 8 Commerce Abandoned Carts
=================================
This module will automatically send email messages to customers who have
abandoned their Drupal Commerce carts.

On each cron run, the module finds Drupal Commerce carts that have been
abandoned (using configurable settings) and that the customer has gone to the
point in the checkout process to have entered their email address. Then the
module will send an email message to the customer(s) to remind them that they
have not finished their order, or ask if they had an issue during checkout.

Email messages are fully customizable. Message limits and other options are
configurable in the module settings.

REQUIREMENTS:

- Mime Mail

INSTRUCTIONS:

- Install and enable module.
- Visit the configuration page at: admin/commerce/config/abandonded_carts
- Disable TEST mode to actually start sending mails to customers.
- To customize the email message template, simply copy the
  commerce_abandoned_carts_email.html.twig file from the module's templates
  directory into your site's default theme directory, clear the site caches and
  modify as needed.

NOTES:

- The module has TEST mode enable by default. Be sure to test functionality
  thoroughly first before turning off TEST mode and sending actual emails to
  customers.
- Some modules may override this module's 'from name' and 'from email' setting.
  For example, the Drupal Mandrill Module will override these settings with the
  settings entered into it's configuration. See the docs or issues for any mail
  handling modules that you may be using if you're experiencing issues with the
  email header values.
