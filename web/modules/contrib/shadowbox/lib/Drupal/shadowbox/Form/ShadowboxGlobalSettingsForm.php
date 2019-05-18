<?php

/**
 * @file
 * Contains \Drupal\shadowbox\Form\ShadowboxGlobalSettingsForm.
 */

namespace Drupal\shadowbox\Form;

use Drupal\Core\Form\ConfigFormBase;

/**
 * Configure the settings for Shadowbox.
 */
class ShadowboxGlobalSettingsForm extends ConfigFormBase {

  /**
   * Validate a hex color value.
   *
   * @param $input
   *   The string to validate.
   *
   * @return
   *   TRUE if $input is a valid hex color value (e.g. 000 or 000000)
   */
  public function validateHexColor($input, $long = FALSE) {
    if ($long == TRUE) {
      return preg_match('!^[a-f0-9]{6}$!i', $input);
    }
    else {
      return preg_match('!^[a-f0-9]{3}([a-f0-9]{3})?$!i', $input);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'shadowbox_global_settings';
  }

  /**
   * Implements \Drupal\Core\Form\FormInterface::buildForm().
   */
  public function buildForm(array $form, array &$form_state) {
    $config = $this->config('shadowbox.settings');
    $config_list = $this->config('shadowbox.settings')->get();

    $form['#attached'] = array(
      'css' => array(
        drupal_get_path('module', 'shadowbox') . '/shadowbox_admin.css'
      ),
      'library' => array(
        'shadowbox/shadowbox',
      ),
    );

    if (!isset($config_list['shadowbox_enabled'])) {
      // Enable warning
      $form['shadowbox']['enable_test'] = array(
        '#prefix' => '<div class="messages warning">',
        '#markup' => t('Shadowbox is disabled.'),
        '#suffix' => '</div>',
      );
    }

    // Shadowbox test.
    $form['shadowbox']['test'] = array(
      '#prefix' => '<div id="shadowbox-settings-test">',
      '#markup' => l(t('Test Shadowbox with the current settings.'), drupal_get_path('module', 'image') . '/sample.png', array('attributes' => array('rel' => 'shadowbox'))),
      '#suffix' => '</div>',
    );

    // Global shadowbox settings.
    $form['shadowbox']['shadowbox_enabled'] = array(
      '#type' => 'checkbox',
      '#title' => t('Enable Shadowbox'),
      '#default_value' => $config->get('shadowbox_enabled'),
      '#description' => t('Check this box to enable Shadowbox for the entire site.'),
    );
    $form['shadowbox']['shadowbox_location'] = array(
      '#type' => 'textfield',
      '#title' => t('Shadowbox location'),
      '#default_value' => $config->get('shadowbox_location'),
      '#description' => t('Enter the location of the !download. It is recommended to use e.g. sites/all/libraries/shadowbox.', array('!download' => l(t('third party Shadowbox distribution'), 'http://www.shadowbox-js.com/download.html', array('absolute' => TRUE)))),
    );
    $form['shadowbox']['activation'] = array(
      '#type' => 'details',
      '#title' => t('Page specific activation settings'),
      '#collapsed' => TRUE,
    );

    $access = \Drupal::currentUser()->hasPermission('use PHP for settings');
    $activation = $config->get('shadowbox_activation_type');
    $pages = $config->get('shadowbox_pages');

    if ($activation == SHADOWBOX_ACTIVATION_PHP && !$access) {
      $form['shadowbox']['activation']['shadowbox_activation_type'] = array(
        '#type' => 'value',
        '#value' => SHADOWBOX_ACTIVATION_PHP,
      );
      $form['shadowbox']['activation']['shadowbox_pages'] = array(
        '#type' => 'value',
        '#value' => $pages,
      );
    }
    else {
      $options = array(
        SHADOWBOX_ACTIVATION_NOTLISTED => t('Enable on every page except the listed pages.'),
        SHADOWBOX_ACTIVATION_LISTED => t('Enable on only the listed pages.'),
      );
      $description = t("Specify pages by using their paths. Enter one path per line. The '*' character is a wildcard. Example paths are %blog for the blog page and %blog-wildcard for every personal blog. %front is the front page.", array('%blog' => 'blog', '%blog-wildcard' => 'blog/*', '%front' => '<front>'));

      if (\Drupal::moduleHandler()->moduleExists('php') && $access) {
        $options += array(SHADOWBOX_ACTIVATION_PHP => t('Enable if the following PHP code returns <code>TRUE</code> (PHP-mode, experts only).'));
        $title = t('Pages or PHP code');
        $description .= ' ' . t('If the PHP option is chosen, enter PHP code between %php. Note that executing incorrect PHP code can break your Drupal site.', array('%php' => '<?php ?>'));
      }
      else {
        $title = t('Pages');
      }

      $form['shadowbox']['activation']['shadowbox_activation_type'] = array(
        '#type' => 'radios',
        '#title' => t('Enable Shadowbox on specific pages'),
        '#options' => $options,
        '#default_value' => $activation,
      );
      $form['shadowbox']['activation']['shadowbox_pages'] = array(
        '#type' => 'textarea',
        '#title' => '<span class="element-invisible">' . $title . '</span>',
        '#default_value' => $pages,
        '#description' => $description,
      );
    }

    // Shadowbox options.
    $form['shadowbox']['shadowbox_options'] = array(
      '#type' => 'details',
      '#title' => t('Shadowbox options'),
      '#description' => t('Shadowbox is highly configurable, but can also be used with little to no configuration at all. The following options may be used to configure Shadowbox on a site-wide basis.'),
      '#collapsed' => TRUE,
    );

    // Shadowbox animation.
    $form['shadowbox']['shadowbox_options']['animation_settings'] = array(
      '#type' => 'details',
      '#title' => t('Animation'),
      '#collapsed' => TRUE,
    );
    $form['shadowbox']['shadowbox_options']['animation_settings']['shadowbox_animate'] = array(
      '#type' => 'checkbox',
      '#title' => t('Enable animation'),
      '#description' => t('Set this false to disable all fancy animations (except fades). This can improve the overall effect on computers with poor performance.'),
      '#default_value' => $config->get('shadowbox_animate'),
    );
    $form['shadowbox']['shadowbox_options']['animation_settings']['shadowbox_animate_fade'] = array(
      '#type' => 'checkbox',
      '#title' => t('Enable fading animations'),
      '#description' => t('Set this false to disable all fading animations.'),
      '#default_value' => $config->get('shadowbox_animate_fade'),
    );
    $form['shadowbox']['shadowbox_options']['animation_settings']['shadowbox_animation_sequence'] = array(
      '#type' => 'select',
      '#title' => t('Animation sequence'),
      '#multiple' => FALSE,
      '#description' => t('The animation sequence to use when resizing Shadowbox.'),
      '#options' => array(
        'wh' => t('Width then height'),
        'hw' => t('Height then width'),
        'sync' => t('Simultaneously'),
      ),
      '#default_value' => $config->get('shadowbox_animation_sequence'),
    );
    $form['shadowbox']['shadowbox_options']['animation_settings']['shadowbox_resize_duration'] = array(
      '#type' => 'textfield',
      '#title' => t('Resize duration'),
      '#description' => t('The duration (in seconds) of the resizing animations.'),
      '#size' => 5,
      '#maxlength' => 4,
      '#default_value' => $config->get('shadowbox_resize_duration'),
    );
    $form['shadowbox']['shadowbox_options']['animation_settings']['shadowbox_fade_duration'] = array(
      '#type' => 'textfield',
      '#title' => t('Fade duration'),
      '#description' => t('The duration (in seconds) of the overlay fade animation.'),
      '#size' => 5,
      '#maxlength' => 4,
      '#default_value' => $config->get('shadowbox_fade_duration'),
    );
    $form['shadowbox']['shadowbox_options']['animation_settings']['shadowbox_initial_height'] = array(
      '#type' => 'textfield',
      '#title' => t('Initial height'),
      '#description' => t('The height of Shadowbox (in pixels) when it first appears on the screen.'),
      '#size' => 5,
      '#maxlength' => 4,
      '#default_value' => $config->get('shadowbox_initial_height'),
    );
    $form['shadowbox']['shadowbox_options']['animation_settings']['shadowbox_initial_width'] = array(
      '#type' => 'textfield',
      '#title' => t('Initial width'),
      '#description' => t('The width of Shadowbox (in pixels) when it first appears on the screen.'),
      '#size' => 5,
      '#maxlength' => 4,
      '#default_value' => $config->get('shadowbox_initial_width'),
    );

    // Shadowbox overlay.
    $form['shadowbox']['shadowbox_options']['overlay'] = array(
      '#type' => 'details',
      '#title' => t('Overlay'),
      '#collapsed' => TRUE,
    );
    $form['shadowbox']['shadowbox_options']['overlay']['shadowbox_overlay_color'] = array(
      '#type' => 'textfield',
      '#title' => t('Overlay color'),
      '#description' => t('Select a hexadecimal color value for the overlay (e.g. 000 or 000000 for black).'),
      '#size' => 8,
      '#maxlength' => 7,
      '#default_value' => $config->get('shadowbox_overlay_color'),
      '#field_prefix' => '#',
    );
    $form['shadowbox']['shadowbox_options']['overlay']['shadowbox_overlay_opacity'] = array(
      '#type' => 'textfield',
      '#title' => t('Overlay opacity'),
      '#description' => t('The opacity of the overlay. Accepts values between 0 and 1. 0 is fully transparent, 1 is fully opaque.'),
      '#size' => 5,
      '#maxlength' => 4,
      '#default_value' => $config->get('shadowbox_overlay_opacity'),
    );
    $form['shadowbox']['shadowbox_options']['overlay']['shadowbox_viewport_padding'] = array(
      '#type' => 'textfield',
      '#title' => t('Viewport padding'),
      '#description' => t('The amount of padding (in pixels) to maintain around the edge of the browser window.'),
      '#size' => 4,
      '#maxlength' => 4,
      '#default_value' => $config->get('shadowbox_viewport_padding'),
    );

    // Shadowbox galleries.
    $form['shadowbox']['shadowbox_options']['gallery'] = array(
      '#type' => 'details',
      '#title' => t('Galleries'),
      '#collapsed' => TRUE,
    );
    $form['shadowbox']['shadowbox_options']['gallery']['shadowbox_display_nav'] = array(
      '#type' => 'checkbox',
      '#title' => t('Display gallery navigation'),
      '#description' => t('Enable display of the gallery navigation controls.'),
      '#default_value' => $config->get('shadowbox_display_nav'),
    );
    $form['shadowbox']['shadowbox_options']['gallery']['shadowbox_continuous_galleries'] = array(
      '#type' => 'checkbox',
      '#title' => t('Enable continuous galleries'),
      '#description' => t('By default, the galleries will not let a user go before the first image or after the last. Enabling this feature will let the user go directly to the first image in a gallery from the last one by selecting "Next".'),
      '#default_value' => $config->get('shadowbox_continuous_galleries'),
    );
    $form['shadowbox']['shadowbox_options']['gallery']['shadowbox_display_counter'] = array(
      '#type' => 'checkbox',
      '#title' => t('Enable counter'),
      '#description' => t('Enable display of the gallery counter. Counters are never displayed on elements that are not part of a gallery.'),
      '#default_value' => $config->get('shadowbox_display_counter'),
    );
    $form['shadowbox']['shadowbox_options']['gallery']['shadowbox_counter_type'] = array(
      '#type' => 'select',
      '#title' => t('Counter type'),
      '#multiple' => FALSE,
      '#description' => t('The mode to use for the gallery counter. May be either \'default\' or \'skip\'. The default counter is a simple \'1 of 5\' message. The skip counter displays a separate link to each piece in the gallery, enabling quick navigation in large galleries.'),
      '#options' => array(
        'default' => t('Default'),
        'skip' => t('Skip'),
      ),
      '#default_value' => $config->get('shadowbox_counter_type'),
    );
    $form['shadowbox']['shadowbox_options']['gallery']['shadowbox_counter_limit'] = array(
      '#type' => 'textfield',
      '#title' => t('Counter limit'),
      '#description' => t('Limits the number of counter links that will be displayed in a "skip" style counter. If the actual number of gallery elements is greater than this value, the counter will be restrained to the elements immediately preceding and following the current element.'),
      '#default_value' => $config->get('shadowbox_counter_limit'),
    );
    $form['shadowbox']['shadowbox_options']['gallery']['shadowbox_slideshow_delay'] = array(
      '#type' => 'textfield',
      '#title' => t('Slideshow delay'),
      '#description' => t('A delay (in seconds) to use for slideshows. If set to anything other than 0, this value determines an interval at which Shadowbox will automatically proceed to the next piece in the gallery.'),
      '#default_value' => $config->get('shadowbox_slideshow_delay'),
    );

    // Shadowbox movies.
    $form['shadowbox']['shadowbox_options']['movies'] = array(
      '#type' => 'details',
      '#title' => t('Movies'),
      '#collapsed' => TRUE,
    );
    $form['shadowbox']['shadowbox_options']['movies']['shadowbox_autoplay_movies'] = array(
      '#type' => 'checkbox',
      '#title' => t('Auto-play movies'),
      '#description' => t('Automatically play movies when they are loaded.'),
      '#default_value' => $config->get('shadowbox_autoplay_movies'),
    );
    $form['shadowbox']['shadowbox_options']['movies']['shadowbox_show_movie_controls'] = array(
      '#type' => 'checkbox',
      '#title' => t('Enable movie controls'),
      '#description' => t('Enable display of QuickTime and Windows Media player movie controls.'),
      '#default_value' => $config->get('shadowbox_show_movie_controls'),
    );
    $form['shadowbox']['shadowbox_options']['movies']['shadowbox_youtube_quality'] = array(
      '#type' => 'select',
      '#title' => t('Youtube Quality'),
      '#options' => array(
        'auto'    => t('automatic'),
        'small'   => '240p',
        'medium'  => '360p',
        'large'   => '480p',
        'hd720'   => '720p',
        'hd1080'  => '1080p',
      ),
      '#description' => t('Choose youtube preferred resolution.'),
      '#default_value' => $config->get('shadowbox_youtube_quality'),
    );

    // Shadowbox input controls.
    $form['shadowbox']['shadowbox_options']['input'] = array(
      '#type' => 'details',
      '#title' => t('Input controls'),
      '#collapsed' => TRUE,
    );
    $form['shadowbox']['shadowbox_options']['input']['shadowbox_overlay_listen'] = array(
      '#type' => 'checkbox',
      '#title' => t('Mouse click closes overlay'),
      '#description' => t('Enable listening for mouse clicks on the overlay that will close Shadowbox.'),
      '#default_value' => $config->get('shadowbox_overlay_listen'),
    );
    $form['shadowbox']['shadowbox_options']['input']['shadowbox_enable_keys'] = array(
      '#type' => 'checkbox',
      '#title' => t('Enable keys'),
      '#description' => t('Enable keyboard navigation of galleries.'),
      '#default_value' => $config->get('shadowbox_enable_keys'),
    );

    // Shadowbox media handling.
    $form['shadowbox']['shadowbox_options']['media_handling'] = array(
      '#type' => 'details',
      '#title' => t('Media handling'),
      '#collapsed' => TRUE,
    );
    $form['shadowbox']['shadowbox_options']['media_handling']['shadowbox_handle_oversize'] = array(
      '#type' => 'select',
      '#title' => t('Handle large images'),
      '#multiple' => FALSE,
      '#description' => t('The mode to use for handling images that are too large for the viewport. The "none" setting will not alter the image dimensions, though clipping may occur. Setting this to "resize" enables on-the-fly resizing of large content. In this mode, the height and width of large, resizable content will be adjusted so that it may still be viewed in its entirety while maintaining its original aspect ratio. The "drag" mode will display an oversized image at its original resolution, but will allow the user to drag it within the view to see portions that may be clipped.'),
      '#options' => array(
        'resize' => t('Resize'),
        'drag' => t('Drag'),
        'none' => t('None'),
      ),
      '#default_value' => $config->get('shadowbox_handle_oversize'),
    );
    $form['shadowbox']['shadowbox_options']['media_handling']['shadowbox_handle_unsupported'] = array(
      '#type' => 'select',
      '#title' => t('Handle unsupported'),
      '#multiple' => FALSE,
      '#description' => t('The mode to use for handling unsupported media. May be either <strong>link</strong> or <strong>remove</strong>. Media are unsupported when the browser plugin required to display the media properly is not installed. The link option will display a user-friendly error message with a link to a page where the needed plugin can be downloaded. The remove option will simply remove any unsupported gallery elements from the gallery before displaying it. With this option, if the element is not part of a gallery, the link will simply be followed.'),
      '#options' => array(
        'link' => t('Link'),
        'remove' => t('Remove'),
      ),
      '#default_value' => $config->get('shadowbox_handle_unsupported'),
    );

    // Shadowbox libraries.
    $form['shadowbox']['shadowbox_options']['libraries'] = array(
      '#type' => 'details',
      '#title' => t('Libraries'),
      '#collapsed' => TRUE,
    );
    $form['shadowbox']['shadowbox_options']['libraries']['shadowbox_use_sizzle'] = array(
      '#type' => 'checkbox',
      '#title' => t('Use Sizzle'),
      '#description' => t('Enable loading the Sizzle.js CSS selector library.'),
      '#default_value' => $config->get('shadowbox_use_sizzle'),
    );

    return parent::buildForm($form, $form_state);
  }

  /**
   * Implements \Drupal\Core\Form\FormInterface::validateForm().
   */
  public function validateForm(array &$form, array &$form_state) {
    parent::validateForm($form, $form_state);

    $color = $form_state['values']['shadowbox_overlay_color'];
    $opacity = $form_state['values']['shadowbox_overlay_opacity'];
    $slideshow_delay = $form_state['values']['shadowbox_slideshow_delay'];
    $resize_duration = $form_state['values']['shadowbox_resize_duration'];
    $fade_duration = $form_state['values']['shadowbox_fade_duration'];
    $viewport_padding = $form_state['values']['shadowbox_viewport_padding'];
    $initial_height = $form_state['values']['shadowbox_initial_height'];
    $initial_width = $form_state['values']['shadowbox_initial_width'];
    $counter_limit = $form_state['values']['shadowbox_counter_limit'];

    if (!is_numeric($resize_duration) || $resize_duration < 0 || $resize_duration > 10) {
      $this->setFormError('shadowbox_resize_duration', $form_state, $this->t('You must enter a number between 0 and 10.'));
    }

    if (!is_numeric($fade_duration) || $fade_duration < 0 || $fade_duration > 10) {
      $this->setFormError('shadowbox_fade_duration', $form_state, $this->t('You must enter a number between 0 and 10.'));
    }

    if (!is_numeric($initial_height)) {
      $this->setFormError('shadowbox_initial_height', $form_state, $this->t('You must enter a number.'));
    }
    else {
      $form_state['values']['shadowbox_initial_height'] = floor($initial_height);
    }

    if (!is_numeric($initial_width)) {
      $this->setFormError('shadowbox_initial_width', $form_state, $this->t('You must enter a number.'));
    }
    else {
      $form_state['values']['shadowbox_initial_width'] = floor($initial_width);
    }

    if (!$this->validateHexColor($color)) {
      $this->setFormError('shadowbox_overlay_color', $form_state, $this->t('You must enter a properly formed hex value (e.g. 000 or 000000 for black.)'));
    }

    if ($opacity == '' || (floor($opacity) != 0 && $opacity != 1)) {
      $this->setFormError('shadowbox_overlay_opacity', $form_state, $this->t('You must enter a decimal number between 0 and 1.'));
    }

    if (!is_numeric($viewport_padding) || $viewport_padding < 0 || $viewport_padding > 200) {
      $this->setFormError('shadowbox_viewport_padding', $form_state, $this->t('You must enter a number between 0 and 200.'));
    }
    else {
      $form_state['values']['shadowbox_viewport_padding'] = (int)$viewport_padding;
    }
  }

  /**
   * Implements \Drupal\Core\Form\FormInterface::submitForm().
   */
  function submitForm(array &$form, array &$form_state) {
    parent::submitForm($form, $form_state);

    $this->config('shadowbox.settings')
    ->set('shadowbox_location', $form_state['values']['shadowbox_location'])
    ->set('shadowbox_enabled', $form_state['values']['shadowbox_enabled'])
    ->set('shadowbox_activation_type', $form_state['values']['shadowbox_activation_type'])
    ->set('shadowbox_pages', $form_state['values']['shadowbox_pages'])
    ->set('shadowbox_animate', $form_state['values']['shadowbox_animate'])
    ->set('shadowbox_animate_fade', $form_state['values']['shadowbox_animate_fade'])
    ->set('shadowbox_animation_sequence', $form_state['values']['shadowbox_animation_sequence'])
    ->set('shadowbox_resize_duration', $form_state['values']['shadowbox_resize_duration'])
    ->set('shadowbox_fade_duration', $form_state['values']['shadowbox_fade_duration'])
    ->set('shadowbox_initial_height', $form_state['values']['shadowbox_initial_height'])
    ->set('shadowbox_initial_width', $form_state['values']['shadowbox_initial_width'])
    ->set('shadowbox_overlay_color', $form_state['values']['shadowbox_overlay_color'])
    ->set('shadowbox_overlay_opacity', $form_state['values']['shadowbox_overlay_opacity'])
    ->set('shadowbox_viewport_padding', $form_state['values']['shadowbox_viewport_padding'])
    ->set('shadowbox_display_nav', $form_state['values']['shadowbox_display_nav'])
    ->set('shadowbox_continuous_galleries', $form_state['values']['shadowbox_continuous_galleries'])
    ->set('shadowbox_display_counter', $form_state['values']['shadowbox_display_counter'])
    ->set('shadowbox_counter_type', $form_state['values']['shadowbox_counter_type'])
    ->set('shadowbox_counter_limit', $form_state['values']['shadowbox_counter_limit'])
    ->set('shadowbox_slideshow_delay', $form_state['values']['shadowbox_slideshow_delay'])
    ->set('shadowbox_autoplay_movies', $form_state['values']['shadowbox_autoplay_movies'])
    ->set('shadowbox_show_movie_controls', $form_state['values']['shadowbox_show_movie_controls'])
    ->set('shadowbox_youtube_quality', $form_state['values']['shadowbox_youtube_quality'])
    ->set('shadowbox_overlay_listen', $form_state['values']['shadowbox_overlay_listen'])
    ->set('shadowbox_enable_keys', $form_state['values']['shadowbox_enable_keys'])
    ->set('shadowbox_handle_oversize',  $form_state['values']['shadowbox_handle_oversize'])
    ->set('shadowbox_handle_unsupported', $form_state['values']['shadowbox_handle_unsupported'])
    ->set('shadowbox_use_sizzle', $form_state['values']['shadowbox_use_sizzle'])
    ->save();
  }
}