# drupal-slaask

The <a href="http://slaask.com">Slaask</a> module integrates the Slaask chat JavaScript into your Drupal site.

## Requirements

This module requires no other modules.

## Installation

Clone the repo into the "modules" folder of your site.
<pre><code>git clone https://github.com/slaaskhq/drupal-slaask.git
cd drupal-slaask</code></pre>

See <https://drupal.org/documentation/install/modules-themes/modules-8> for further information.

## Configuration

* Configuration is  available on the administration page: **admin/config/slaask**
* Add your Slaask key in the widget id field and it will be injected into the file **js/slaask_init.js**
* Format: _slaask.init('*************');
