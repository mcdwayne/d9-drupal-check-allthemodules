# Message Notify Slack integration

This module provides a simple Notifier plugin for the
[Message Notify](https://drupal.org/project/message_notify) module
allowing message notifications to be sent to [Slack](https://slack.com).

Currently that's all it provides; there isn't yet any integration for
[Message Subscribe](https://drupal.org/project/message_subscribe),
although interested parties may feel free to submit patches.

For now, the only way to use this is via the message_notify.sender
service as follows:

```
<?php
$message = \Drupal\message\Entity\Message::load($message_id);
$sender = \Drupal::service('message_notify.sender');
$sender->send($message, [], 'slack');
```

The above example loads a message object and sends it via the Slack
notification plugin with no options.  This default usage will cause the
message to be posted to the default channel and under the default
username as configured in the [Slack](https://drupal.org/project/slack)
module itself.

If you'd like to send to a different channel, or perhaps under a
different username, that's supported via the second argument:

```
<?php
$message = \Drupal\message\Entity\Message::load($message_id);
$sender = \Drupal::service('message_notify.sender');
$sender->send($message, [
  'channel' => '#myfavoriteroom',
  'username' => 'myusername',
];
```

The above example will result in user "myusername" posting the provided
message in the "#myfavoriteroom" channel.
