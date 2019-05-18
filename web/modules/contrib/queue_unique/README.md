# Queue Unique
Did you ever wanted a queue that only accepts unique items? This module provides a way of doing that. If you try to insert a duplicated item in the queue, the item is ignored.

```php
// $data can be anything.
$data = array('Lorem', 'ipsum');

$queue_name = 'your_queue_name';
$queue = \Drupal::service('queue')->get($queue_name);
$queue->createItem($data);
// This will insert a duplicate, and will return FALSE.
if ($queue->createItem($data) === FALSE) {
  // The item was a duplicate, respond appropriately.
}
```

## Usage
In order for your queue to use the Queue Unique you need to update your `settings.php` file:

```php
$settings['queue_service_your_queue_name'] = 'queue_unique.database';
```
