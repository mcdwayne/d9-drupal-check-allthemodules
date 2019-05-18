
Animations Drupal Module
================================================================================

The animations module consist of a set of cool cross-browser animations based on CSS/JS. You can easily add multiple predefined animations to your site, using CSS selectors in the module settings page.

Great for emphasis, home pages, sliders, and general just-add-water-awesomeness.

Installation & Usage Example
Download and install the module, use "drush animations-libraries" or download the libraries manually -> see requirements at the bottom.
To add the desired animation to all your "H1" tags as an example go to /admin/config/animations/config
Choose your desired effects and add one selector per line to apply the effect and save.
Selector Examples

CSS selector	Description
input, textarea	Use all single line text fields and textareas on site.
.your-form-class *	Use all text fields in given form class.
#your-form-id *	Use all text fields in given form id.
#your-form-id *:not(textarea)	Use all single line text fields but not textareas in given form id.
#your-form-id input:not(input[type=password])	Use all single line text fields but not password text fields in given form id.


For more advanced users: You can do a whole bunch of other stuff with animate.css when you combine it with jQuery or add your own CSS rules. Dynamically add animations using jQuery with ease:

$('#yourElement').addClass('animated bounceOutLeft');

List of animations
typewriter
bounce
flash
pulse
rubberBand
shake
headShake
swing
tada
wobble
jello
bounceIn
bounceInDown
bounceInLeft
bounceInRight
bounceInUp
bounceOut
bounceOutDown
bounceOutLeft
bounceOutRight
bounceOutUp
fadeIn
fadeInDown
fadeInDownBig
fadeInLeft
fadeInLeftBig
fadeInRight
fadeInRightBig
fadeInUp
fadeInUpBig
fadeOut
fadeOutDown
fadeOutDownBig
fadeOutLeft
fadeOutLeftBig
fadeOutRight
fadeOutRightBig
fadeOutUp
fadeOutUpBig
flipInX
flipInY
flipOutX
flipOutY
lightSpeedIn
lightSpeedOut
rotateIn
rotateInDownLeft
rotateInDownRight
rotateInUpLeft
rotateInUpRight
rotateOut
rotateOutDownLeft
rotateOutDownRight
rotateOutUpLeft
rotateOutUpRight
hinge
rollIn
rollOut
zoomIn
zoomInDown
zoomInLeft
zoomInRight
zoomInUp
zoomOut
zoomOutDown
zoomOutLeft
zoomOutRight
zoomOutUp
slideInDown
slideInLeft
slideInRight
slideInUp
slideOutDown
slideOutLeft
slideOutRight
slideOutUp

Add your effect? Propose it in the issue queue or write a patch :)

All the cool kids use Typed.js
https://slack.com
https://envato.com
https://productmap.co/
...

Dependencies
The module currently integrates the following libraries
Animate.css and typed.js - use "drush animations-libraries" after enabling the module to download all the libraries or download them manually.

Download, extract and rename the folders to have the correct paths:

[DRUPAL_ROOT]/libraries/animate/animate.css (downloaded from https://github.com/daneden/animate.css version 3.5.2 and higher)
[DRUPAL_ROOT]/libraries/typed/js/typed.js (downloaded from https://github.com/mattboldt/typed.js/ version 1.1.4 and higher)