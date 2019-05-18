This module makes the iGrowl library available for use from within Drupal, enabling you to create iGrowl alerts
from either your own custom javascript code or by using an included AJAX command function to spawn an alert.

Dependencies

- Animate.css (https://github.com/daneden/animate.css)
- iGrowl (http://catc.github.io/iGrowl)

Installation

- Download the animate.css zip file and unzip to /libraries/animate/animate.min.css
- Download the iGrowl zip file and unzip to /libraries/igrowl
- Enable the iGrowl module
- Clear all cache, the status report page should indicate both libraries are included successfully

The libraries path is in your Drupal root, outside of /core. Do not place it in /core.

Usage

In a custom theme or module, you can spawn an iGrowl alert within your javascript by invoking it directly and providing it whatever options you want to use:

<code>
$.iGrowl({
 message: "Your message here",
})
</code>

This module also defines a custom AJAX command, so you can leverage iGrowl from an AJAX response like so:

<code>
use Drupal\igrowl\Ajax\GrowlCommand;

// controller or form code...

$response = new AjaxResponse();

$options = GrowlCommand::defaultOptions();
$options['title'] = 'Excellent!';
$options['message'] = 'We have added ' . $foo . ' ' . $bar .' to your order!';
$options['type'] = 'success';
$options['icon'] = 'feather-circle-check';

$response->addCommand(new GrowlCommand($options));
return $response;
</code>

GrowlCommand::defaultOptions() returns an array of overrideable iGrowl options, like type, icon, message, title and more.

You can do this from either the #ajax property of a form item (the callback can return an AjaxResponse), or you can create routes in your module. The router.yml may look like this as a basic example:

<code>
mymodule.growl:
  path: '/mymodule/growl'
  defaults:
    _controller: '\Drupal\mymodule\Controller\MyModuleController::growl'
  requirements:
    _permission: 'access content'
</code>

And the corresponding controller:

<code>
namespace Drupal\mymodule\Controller;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Controller\ControllerBase;
use Drupal\igrowl\Ajax\GrowlCommand;

class MyModuleController extends ControllerBase {
  public function growl() {
    $options = GrowlCommand::defaultOptions();
    $options['message'] = 'This is a message.';
    $options['title'] = 'Hello';
    $options['icon'] = 'feather-check';
    $options['type'] = 'success';

    $command = new GrowlCommand($options);

    $response = new AjaxResponse();
    $response->addCommand($command);
    return $response;
  }
}
</code>

Depending on your use case, you may have specific growls, or you may make a flexible generic one. It's up to you.

You will also need to attach the libraries to your theme or module. You will likely want it attached in hook_preprocess_page:

<code>
$variables['#attached']['library'][] = 'igrowl/command';
$variables['#attached']['library'][] = igrowl/icons.feather';
</code>

The second library is the icon set. You have 4 options to pick from:

- icons.feather
- icons.lineicons
- icons.steadysets
- icons.vicons

You can preview all option and icon types on the iGrowl demo page at http://catc.github.io/iGrowl.