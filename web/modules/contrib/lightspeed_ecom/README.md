# Lightspeed eCom

## About

This module integrates the
[Lightspeed eCom platform](https://www.lightspeedhq.com/ecommerce/) with
your Drupal site.

This project contains the following modules:

* Lightspeed eCom (`lightspeed_ecom`): Low level integration with the
  [Lightspeed eCom API](http://developers.lightspeedhq.com/ecom/). This
  module allow site managers to configure shops and their API
  credentials. A service (`lightspeed.ecom.client_factory`) is provided
  for developers to retrieve API client for each defined shop. A client
  to the `default` shop is also provide as a service
  (`lightspeed.ecom.client`). These services are used by other modules
  in this project and by third party modules to access the Lightspeed
  eCom API.

## Usage

You can configure shop at `admin/config/services/lightspeed-ecom`.
 
### Webhooks

Webhooks support is provided as
[Events](https://api.drupal.org/api/drupal/core!core.api.php/group/events/8.1.x). 

Here are the steps to register a webhook subscriber:

 - Define a service in your module, tagged with 'event_subscriber' (see
   [Services](https://api.drupal.org/api/drupal/core%21core.api.php/group/container/8.1.x)
   for instructions).
 - Define a class for your subscriber service that implements
   `\Symfony\Component\EventDispatcher\EventSubscriberInterface`.
 - In your class, the `getSubscribedEvents` method returns a list of the
   webhook events this class is subscribed to, and which methods on the
   class should be called for each one. Example:
   
  ```
  public function getSubscribedEvents() {
    // Subscribe to customers events
    $events[WebhookEvent::CUSTOMERS_CREATED][] = array('onCreate');
    $events[WebhookEvent::CUSTOMERS_UPDATE][] = array('onUpdate');
    $events[WebhookEvent::CUSTOMERS_CREATED][] = array('onDelete');
    return $events;
  }
  ```

  - Write the methods that respond to the events; each one receives a
    webhook event object provided. In the above example, you would need
    to write `onCreate(WebhookEvent $event)`,
    `onUpdate(WebhookEvent $event)` and `onDelete(WebhookEvent $event)`
    methods.
   
   - Webhooks are automatically registered with Lightspeed eCom on
     module installation (and removed when module is uninstalled).


## TODO

* Catalog sync: Automatically import your catalog(s) as entities in your
  Drupal site.
