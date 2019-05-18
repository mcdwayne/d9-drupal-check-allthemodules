INTRODUCTION
------------

This module allows for the queuing the creation of private messages and
threads from the Private Message module.

It provides a PrivateMessageThreadQueuer service that adds private message
requests to the queue, and a PrivateMessageThreadQueue queue worker that
processes the queue items.


DEPENDENCIES
------------

- Private Message (https://www.drupal.org/project/private_message)


USAGE
-----

Within your module, load the PrivateMessageThreadQueuer service from the
container using `\Drupal::get('private_message_queue.thread_queuer')` or via
dependency injection into your own service class.

Once you have the service you can call the `queue()` method providing an array
of recipient users, the body text for the message, and the message owner
(usually the current user), to queue the message. A new thread will be created
for the recipients if one exists, otherwise the message will be added to the
existing thread.

The queue will be processed as part of cron running.


AUTHORS
-------

- Oliver Davies (https://www.drupal.org/u/opdavies)
