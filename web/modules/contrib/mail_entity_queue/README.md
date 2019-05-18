### SUMMARY
This module provides a queues based system to manage mails.

It treats every queue item as an entity and provides a default processor.

Processors can be provided from external modules and can be selected in each queue.

Each queue can be limited by the following:

- Number of items per cron run
- Delay between one item and the next
- Number of attemps per item (Pending to implement)

Note the following things:

- At the moment this module does not use the Drupal core Queue API.
- Queue items are created programmatically (there isn't a UI for this at the moment).

### INSTALLATION
Using composer:
```
composer require drupal/mail_entity_queue --sort-packages
```

### CONFIGURATION

You can add one or several Queues in Configuration > System > Mail entity queues (config/system/mail-entity-queue)
Mail queue items are managed from Structure > Mail queue items (admin/structure/mail-entity-queue). 
You can update/delete/process each item individually from there.

## USAGE

- First, create a queue from Configuration > System > Mail entity queues (config/system/mail-entity-queue)
- Using the queue machine name, load it programmatically and add items to it, for example:

```
$queue = \Drupal::entityTypeManager()->getStorage('mail_entity_queue')->load('my_queue');
$to = 'kimchi@example.com';
$params = [
    'subject' => 'My awesome email',
    'body' => ['Body of the email'],
    'headers' => [
        'From' => 'info@cambrico.net',
        'Sender' => 'info@cambrico.net'
    ],
];
$queue->addItem($to, $params);
```

- Manage the queues from Structure > Mail queue items (admin/structure/mail-entity-queue)


## ALTERNATIVE MODULES

- Queue Mail: https://www.drupal.org/project/queue_mail

### SPONSORS
- [Fundación UNICEF Comité Español](https://www.unicef.es)

### CONTACT
Developed and maintained by Cambrico (http://cambrico.net).

Get in touch with us for customizations and consultancy:
http://cambrico.net/contact

#### Current maintainers:
- Pedro Cambra [(pcambra)](http://drupal.org/user/122101)
- Manuel Egío [(facine)](http://drupal.org/user/1169056)
