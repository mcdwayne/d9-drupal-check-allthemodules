
CONTENTS OF THIS FILE
---------------------

 * About Access Filter
 * Features
 * Configration
 * Todo

ABOUT ACCESS FILTER
--------------------

This module provides access control with paths/URIs and IP addresses.


FEATURES
--------------------

Filter:
  * Condition:
    The conditions to execute filter.
  * Rule:
    Rule to deny/allow access.
  * Response:
    Select response code, customize response body.


CONFIGRATION
--------------------

Getting started:
  * Open /admin/config/people/access_filter to list filters.
  * Click "Add filter" to create new filter.
  * Add below line to conditions
    - { type: path, path: '/' }
  * Add below line to rules (replace address with yours)
    - { type: ip, action: deny, address: '127.0.0.1' }
  * Open front page, your access will be denied.


Conditions:
  * type: path
    Targets Drupal path.
    - path: Drupal path.
    - regex: Use regex.

  * type: uri
    Targets request URI that contains query parameters.
    - uri: Request URI.
    - regex: Use regex.

  * type: session
    Targets session value ($_SESSION).
    - key: Session key.
    - value: Value to compare.
    - regex: Use regex.

  * type: cookie
    Targets cookie value ($_COOKIE).
    - key: Cookie key.
    - value: Value to compare.
    - regex: Use regex.

  * type: env
    Targets server environment value ($_SERVER).
    - key: Environment key.
    - value: Value to compare.
    - regex: Use regex.

  * type: and
    Join conditions with AND.
    - conditions: Conditions to join.

  * type: or
    Join conditions with OR.
    - conditions: Conditions to join.

All conditions can be negated by specifying "negate: 1".

Rules:
  * type: ip
    Deny/allow using IP address.
    - ip: IP address.

See add/edit filter page to see samples.

You can also add custom condition/rule by implementing plugins.
See source code in Plugin/AccessFilter/Condition, Plugin/AccessFilter/Rule.



More settings:
  * Disabling module
    If you make a mistake setting filters, you can add below line to
    your settings.php to disable access control of this module.
    >--
    // Disable Access Filter access control.
    $settings['access_filter_disabled'] = TRUE;
    --<

TODO
--------------------

* Add condition or rule using current user.
* Add condition using protocol/domain.
