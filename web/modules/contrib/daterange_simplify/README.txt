Provides a formatter to simplify daterange field output.

This formatter wraps https://github.com/flack/ranger to provide a simplified set of date output
options outside of the standard Drupal date format configuration. These options are enumerated 
by the PHP IntlDateFormatter builtin: 

http://php.net/manual/en/class.intldateformatter.php

See https://github.com/flack/ranger/blob/master/README.md for more details. For non 'en' locales,
the PHP extension php-intl is required.