This module integrates the SweetAlert as an AjaxCommand

Third Party Dependencies

- SweetAlert (https://github.com/t4t5/sweetalert)

Installation

- Download the SweetAlert library and unzip it to /libraries/sweetalert
- Enable the SweetAlert module
- Clear all cache, the status report page should indicate both libraries are included successfully

The libraries path is in your Drupal root, outside of /core. Do not place it in /core.

Usage

In a custom theme or module, you can trigger a Sweet Alert within your javascript by invoking it directly and providing it whatever options you want to use:

<code>
swal({
 title: "Welcome!",
 text: "Thank you for registering! Your new account is ready.",
 type: "success",
 confirmButtonText: "Cool, lets go!"
});
</code>

This module also defines a custom AJAX command, so you can use SweetAlert in an AjaxResponse:

<code>
use Drupal\sweetalert\Ajax\SweetAlertCommand;

// controller or form code...

$options = SweetAlertCommand::defaultOptions();
$options['title'] = $this->t('Welcome!');
$options['text'] = $this->t('Thank you for registering! Your new account is ready.');
$options['type'] = 'success';
$options['confirmButtonText'] = $this->t('Cool, lets go!)';

$response = new AjaxResponse();
$response->addCommand(new SweetAlertCommand($options));
return $response;
</code>

SweetAlertCommand::defaultOptions() returns an array of default options you can override. Debug and inspect them or visit the documentation page to see your full list of options.

You can do this from either the #ajax property of a form item (the callback can return an AjaxResponse), or you can create routes in your module that return an AjaxResponse object.

You will also need to attach the libraries to your theme or module. You will likely want it attached in hook_preprocess_page:

<code>
$variables['#attached']['library'][] = 'sweetalert/command';
</code>

See all the Sweet Alert options available to you at the demo page: https://github.com/t4t5/sweetalert

Themes

SweetAlert comes with three themes on top of its base theme, Facebook, Google, and Twitter. You can add on any of these at the settings form at admin/config/user-interface/sweetalert. In your code, you need to attach the library.
Here is one way you could do that:

<code>
$config = \Drupal::config('sweetalert.settings');

$theme = $config->get('theme');

if (!empty($theme)) {
  $variables['#attached']['library'][] = 'sweetalert/theme.' . $theme;
}
</code>

If you have added your own (for example, in your theme) - define the library in your libraries.yml file - and then that code becomes:

<code>
$variables['#attached']['library'][] = 'themename/cssname'
</code>