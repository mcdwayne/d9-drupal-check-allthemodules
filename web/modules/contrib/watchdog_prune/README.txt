Watchdog Prune
========================
Original Idea:
Richard Peacock - richard@peacocksoftware.com

Maintainer:
Vishwa Chikate - vishwa.chikate@gmail.com


This module will allow you to selectively delete watchdog entries based on
criteria, like age.

Drupal normally only deletes watchdog entries after 1,000 to 1,000,000 entries.

Instead, you can use this module to say "Delete entries older than 2 years", for example.
This allows your site to guarantee that it retains watchdog entries for a certain time,
regardless of how many entries are accumulated.  However, it also lets you still remove
entries from the table eventually, so as not to have a watchdog table which is too large.

This module is ideal for sites where entries should be kept for a certain amount of time,
and for the purpose of generating reports based on user activity, without the worry that
entries might start getting deleted by Drupal before a report can be run.
