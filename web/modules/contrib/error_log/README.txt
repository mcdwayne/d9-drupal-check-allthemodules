Error Log
=========

Once Drupal 8 bootstraps, it sends only a subset of errors to PHP's error log —
which could be, for example, an error log in Apache, or stderr on the command
line.

Error Log module adds the PHP error log as a Drupal logger implementation, so
errors will once again appear in the same log or console that they appeared in
before Drupal bootstrapped.

Please file bug reports and feature requests at:
https://www.drupal.org/project/error_log
