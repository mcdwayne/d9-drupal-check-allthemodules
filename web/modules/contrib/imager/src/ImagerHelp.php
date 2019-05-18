<?php

namespace Drupal\imager;

/**
 * Class ImagerHelp.
 *
 * @package Drupal\imager
 */
class ImagerHelp {

  /**
   * Define help page html using heredoc.
   *
   * @return string
   *   Html for the help text.
   */
  static public function content() {

    $path = '/' . drupal_get_path('module', 'imager') . '/icons';

    $output = <<<EOT
<h3><strong>Overview</strong></h3>

<p>The Imager module works with the media_entity and media_entity_image module. &nbsp;
It implements a Field Formatter that displays media entity images using Drupal image styles.&nbsp; When a user left clicks on an image a popup appears with the image displayed in full resolution.&nbsp;  Using the mouse the user can zoom in and out and pan around the image.  In addition they can edit the image.&nbsp; Edits include rotation, cropping, changing the brightness/contrast and the hue/saturation/lightness.&nbsp; An edited image can then be stored back to Drupal overwriting an existing media entity image or by creating a new media entity.</p>

<h3>Configuration</h3>
<p>Configure Imager by <a href="/admin/config/media/imager">clicking here</a>.</p>
<h3><strong>User Instructions</strong></h3>

<ul>
	<li>Click on an image to display it full resolution in the&nbsp;imager viewer/editor.</li>
	<li>To close the imager viewer press ESCAPE, or 'X' or double click in the imager viewer.</li>
	<li>Hold down the left mouse button and drag to pan around the image.</li>
	<li>Use the mouse thumbwheel to zoom in and out. &nbsp;Or hold down the SHIFT key and left mouse button and then drag the mouse to zoom.<br />&nbsp;</li>
	<li>Press the left or right arrow keys to view the previous or next image.</li>
	<li>Press the 'R' key to reset the image and clear all edits.</li>
	<li>Press the 'F' key to toggle full screen mode.<br />&nbsp;</li>
	<li><img alt="" src="{$path}/close.png" /> Close the Viewer popup.</li>
	<li><img alt="" src="{$path}/left_arrow.png" />&nbsp;<img alt="" src="{$path}/right_arrow.png" /> Select the previous or next image from the source page - You can also press the left and right arrow keys.<br />
	&nbsp;</li>
	<li><img alt="" src="{$path}/newtab.png" /> View image in a new tab - Useful for printing the displayed image.</li>
	<li><img alt="" src="{$path}/information.png" /> View information about this image.</li>
	<li><img alt="" src="{$path}/slideshow.png" /> View images in a slideshow.</li>
	<li><img alt="" src="{$path}/fullscreen.png" />&nbsp;Toggle fullscreen mode.&nbsp; You can also press the 'F' key</li>
	<li><img alt="" src="{$path}/zoomin.png" />&nbsp;<img alt="" src="{$path}/zoomout.png" /> Zoom in and out.<br />
	&nbsp;</li>
	<li><img alt="" src="{$path}/frame.png" /> Click on the frame to select an area to crop. Click on the <img alt="" src="{$path}/scissors.png" /> scissors to complete the crop.</li>
	<li><img alt="" src="{$path}/color_wheel.png" /> Change the Hue/Saturation/Lightness.</li>
	<li><img alt="" src="{$path}/contrast.png" /> Change the Brightness/Contrast.</li>
	<li><img alt="" src="{$path}/rotate-left.png" />&nbsp;<img alt="" src="{$path}/rotate-right.png" /> Rotate the image right or left 90 degrees.</li>
	<li><img alt="" src="{$path}/reset.png" />&nbsp;Reset image back the the original. &nbsp;All edits, cropping, panning and zooming are cleared. &nbsp;<br />
	&nbsp;</li>
	<li><img alt="" src="{$path}/db_add.png" /> Save the edited image back to Drupal.</li>
	<li><img alt="" src="{$path}/download.png" /> Download the image to your local PC.</li>
	<li><img alt="" src="{$path}/configure.png" /> Configure local settings for the Imager Viewer</li>
	<li><img alt="" src="{$path}/help.png" /> Go to the Imager help page - /admin/help/imager<br />&nbsp;</li>
	<li><img alt="" src="{$path}/bug.png" /> View the status of the viewer - used for debugging.</li>
</ul>

<h3><strong>Future Plans</strong></h3>

<ul>
	<li>Make it mobile - remove the left button sidebar and replace with buttons that overlay the image and become visible on hover.&nbsp; When a user clicks a button a group of options appear the user can select further from.</li>
	<li>Allow users to edit media entity fields in-place.</li>
	<li>Implement a hook or an event to notify calling page images have been changed so they can update the original thumbnail</li>
	<li>Allow users to delete an image through the viewer popup.</li>
	<li>Provide button to popup a map showing locations of all images found on the source screen.&nbsp; Clicking on an image map marker changes the currently displayed image.</li>
	<li>Preload images in slideshow mode and save them so they do not have to be loaded every time.</li>
	<li>Add annotations - users can add simple text, arrows and simple geometric shapes to an image.</li>
</ul>

<h3><strong>Help Needed</strong></h3>

<p>I plan to make this into a contrib module on D.O.&nbsp; Since this is the first module I have submitted it has to go through the Project Application Process.&nbsp;Any help I can get with this process would be greatly appreciated.</p>

<h3><strong>Details</strong></h3>

<p>Lines of JavaScript - 3800<br />
Lines of PHP - 1500</p>

<p>The Imager module uses the HTML5 canvas to manipulate images.&nbsp; The only JavaScript libraries it uses are jQuery&nbsp;and the jQuery.imgareaselect plugin.</p>

<p>All editing of images is done with JavaScript in the browser.&nbsp; AJAX is used to load new images and save edited images back to Drupal.</p>

<p>The Imager dialog allows users to view, delete, and edit images for all images on a page without ever loading a new page.</p>
EOT;

    return $output;
  }

}
