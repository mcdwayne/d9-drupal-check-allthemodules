# Contact message REST

This module allows you to send Drupal contact form messages through REST.

## Background

By default in Drupal 8 core you can create contact forms, and let website 
visitors fill in those forms. The submissions will be sent via mail to the 
defined recipients. However, it's not possible to send messages through the REST
web services provided by Drupal 8 core. This module aims to fill that gap.

## Installation

- Enable the contact_message_rest module
- Enable the "contact_message" REST resource. It is recommended to use the Rest 
  UI module (https://www.drupal.org/project/restui) to do so:
  - Go to /admin/config/services/rest
  - Click "Enable" next to the "Contact message" resource (with path: 
  /contact_message/{entity})
  - Decide which formats to support and which authentication providers to allow.
- Make sure to set up your permissions correctly:
  - Go to /admin/people/permissions and set permission for 
  "Access POST on Contact message resource". 
  - Be careful when giving anonymous users access to this permission. 
  - Preferably you would only allow access to your applications consuming the 
  REST services, by letting them log in as Drupal user, authenticate via OAuth 
  (https://www.drupal.org/project/oauth), have a whitelisted IP 
  (https://www.drupal.org/project/ip_consumer_auth), or other means of 
  protecting your REST resources.
  
## Sending messages via REST

- Make sure you send the correct headers, such as "Content-type" and 
  "X-CSRF-Token".
- Send a POST request with a body containing the contact message fields in the
  specified format. In case of JSON, this would be for example:
  
```
{
    "contact_form":[{"target_id":"feedback"}],
    "name":[{"value":"John Doe"}],
    "mail":[{"value":"john.doe@example.com"}],
    "subject":[{"value":"REST contact form"}],
    "message":[{"value":"REST message body"}]
}
```
