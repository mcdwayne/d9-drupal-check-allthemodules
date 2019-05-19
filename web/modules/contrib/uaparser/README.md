# ua-parser module

Project page: https://drupal.org/project/uaparser

This module integrates Drupal 8 with **ua-parser**, an open-source and
community-driven, regexp-based user-agent parser.

For more information about ua-parser, visit [uaparser.org](http://www.uaparser.org/).


## Installing

The module requires [using Composer to manage Drupal site dependencies](https://www.drupal.org/node/2718229).
Once you have setup building your code base using composer, require the module
via

```
$ composer require drupal/uaparser
```

then enable the module as usual.


## Configuration

- Go to _Manage > Configuration > System > ua-parser_ and update the user-agent
  definition file to the most recent version by clicking on the 'Update now'
  button.
- Select the 'Enable automatic updates' tickbox if you want automatic updates of
  the file, and the frequency of the updates.
- The 'USER-AGENT LOOKUP' part of the form allows to parse any user-agent string
  and display its results. By default it displays the results of the current
  request's user-agent string, but any string can be copy/pasted in the text
  field, and results displayed clicking on the 'Lookup' button.


## Usage

The module by itself does not do much - it exposes a service that can be used to
parse user-agent strings and return meaningful results. All the work is done by
the [_ua-parser/uap-php_](https://github.com/ua-parser/uap-php) library that the
service integrates with.
The service however allows to cache user-agent strings and its results in a
Drupal cache bin, to speed up lookups.
Also, the service manages updates of the user-agent definition file from the
source [_ua-parser/uap-core_](https://github.com/ua-parser/uap-core) library.

### Basic code example:

```php
$parser = \Drupal::service('uaparser');
$parsed_ua = $parser->parse('Mozilla/5.0 (Windows NT 6.2; WOW64; rv:35.0) Gecko/20100101 Firefox/35.0');
return ['#markup' => $parsed_ua['client']->toString()];
```

will return
```
Firefox 35.0/Windows 8
```

The $parsed_ua variable returned by the ::parse() method is an associative array
containing the following keys:
- 'client' - an instance of the \UAParser\Result\Client class with all the data
  parsed from the user-agent string;
- 'time' - the time in milliseconds the user-agent string required to be parsed.


## Other modules

The [Browscap](https://www.drupal.org/project/browscap) module provides similar
functionality.
