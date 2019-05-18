Cached Computed Field
---------------------

This module is intended for storing computationally expensive data in normal
field storage and updating the data on a regular basis during cron runs. This
allows to do any processor intensive or time consuming calculations offline
and update the database asynchronously.

A typical use case is to store the result of external REST API calls in field
storage and refreshing these at night when there are few visitors on the site.

Having the data stored in the database in normal field storage has the
advantage that it integrates seamlessly with the rest of Drupal, and the values
can even be exposed in the editor UI for temporary adjustments (e.g. for
adjusting warehouse stock levels on the fly, until the nightly cron run resets
the data).


Usage
-----

This is an API module that is intended for developers. In order to update the
field data an event subscriber needs to be implemented that listens to the 
`RefreshExpiredFieldEventInterface::EVENT_NAME` event.

First you create your fields normally. The module supplies different fields
for the most common data types: boolean, integer, float, string etc. These are
based on the standard core field types and share the same formatters and
widgets. When configuring the fields you can set up the cache lifetime for
each individual field instance, ranging from 1 minute to one week. A cron job
will then check if this period has passed, and fire an event when needed.

Second step is to implement the event listener. You are passed the metadata
about the entity and field that triggered the event. A base class is supplied
that will make it easy to implement this, you basically just need to calculate
or fetch your data, and feed it to the `updateFieldValue()` method. An example:


```
<?php

namespace Drupal\my_project\EventSubscriber;

use Drupal\cached_computed_field\Event\RefreshExpiredFieldEventInterface;
use Drupal\cached_computed_field\EventSubscriber\RefreshExpiredFieldSubscriberBase;
use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use GuzzleHttp\Client;

/**
 * Event subscriber that updates the stock count from the vendor inventory API.
 */
class UpdateStockCount extends RefreshExpiredFieldSubscriberBase {

  /**
   * The Guzzle HTTP client.
   *
   * @var \GuzzleHttp\Client
   */
  protected $httpClient;

  /**
   * Constructs a new RefreshVisitCountEventSubscriber object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   * @param \Drupal\Component\Datetime\TimeInterface $time
   *   The system time service.
   * @param \GuzzleHttp\Client $httpClient
   *   The Guzzle HTTP client.
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager, TimeInterface $time, Client $httpClient) {
    parent::__construct($entityTypeManager, $time);
    $this->httpClient = $httpClient;
  }

  /**
   * {@inheritdoc}
   */
  public function refreshExpiredField(RefreshExpiredFieldEventInterface $event) {
    // Check if we are updating the 'inventory' field on products.
    if ($event->getEntityTypeId() !== 'product' || $event->getFieldName() !== 'field_inventory') {
      return;
    }

    // Only refresh the field if it has actually expired.
    if (!$this->fieldNeedsRefresh($event)) {
      return;
    }

    // Retrieve the inventory data for our product from our vendor's API.
    $sku = $this->getEntity($event)->get('sku')->getValue();
    $options = ['query' => ['sku' => $sku]];
    $result = $this->httpClient->get('https://example.com/api/inventory', $options);

    // Only refresh the data if the API call doesn't return an error code.
    if ($result->getStatusCode() == 200) {
      $data = json_decode($result->getBody());
      $this->updateFieldValue($event, $data->stock_count);
    }
  }

}
```
