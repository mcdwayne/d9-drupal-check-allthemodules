# Mailjet API

## CONTENTS OF THIS FILE

  * Introduction
  * Requirements
  * Installation
  * Using the module
  * Author

## INTRODUCTION

This module provides integration with the Mailjet email service. It provide
a simple Mail Plugin which permit to send emails with a request http on the
Mailjet API webservice.

The module uses Mailjet Official SDK for sending emails with the send API v3.1.

This is not an official module sponsored by Mailjet. If you are looking for a
complete integration with Mailjet service, please checkout the official module
page.

Why this module ?
Where as I was looking for sending emails with the Mailjet Send API v3.1,
unfortunatly the official module doesn't provide such feature and is more
centered about all the services provided by MailJet (Campaign, Contact, etc.).
I was looking for a simple integration of the Mail Manager service with the
Mailjet API.


## REQUIREMENTS

This module requires the Mail system module.
This module requires the Mailjet APIv3 PHP SDK
You need a Mailjet account for using this module.

## INSTALLATION

Install the module with composer. This will download automatically the required
dependencies. composer require drupal/mailjet_api ^1.0

## USING THE MODULE

Configure your public and secret API key in the module administration settings
form.
Enable the few options availables as you need (cron, use theme key,
sandbox mode, embed image, etc).
Configure Mail system module to use the Mailjet API Mailer to format and/or
send emails. You can configure Mail System globally or for a specific module/key.
Test your integration with the testing form.

This module could play nice with local solutions managing newsletters and
subscriptions as the simplenews module, but if you wanted to use a webservice
to send emails. It could be use too for any transactional email, or any email
sent by your Drupal 8 site.

### AUTHOR

Flocon de toile
Website: https://www.flocondetoile.fr
Drupal: https://www.drupal.org/u/flocondetoile
