Queue mail
==========

Drush
-----
Drush has his own command to process specific queue: `drush queue-run QUEUE_NAME`

So you can use command `drush queue-run queue_mail --time-limit=15` to run queue_mail worker.

__Note__: you have to use time-limit option with "drush queue-run queue_mail" because "Queue processing time" setting
doesn't work in this case. If system can't send mails it adds them back to queue. Without time-limit option this 
process won't be finished.
