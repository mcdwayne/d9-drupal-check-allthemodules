<?php
/**
 * @file
 * Contains \Drupal\royalslider\Entity\RoyalSliderOptionSetEntity.
 */

namespace Drupal\royalslider\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\royalslider\RoyalSliderOptionSetInterface;

/**
 * Defines a RoyalSliderOptionSet configuration entity class.
 *
 * @ConfigEntityType(
 *   id = "royalslider_optionset",
 *   label = @Translation("RoyalSlider OptionSet"),
 *   handlers = {
 *     "list_builder" = "Drupal\royalslider\RoyalSliderOptionSetListBuilder",
 *     "form" = {
 *       "default" = "Drupal\royalslider\Form\RoyalSliderOptionSetForm",
 *       "add" = "Drupal\royalslider\Form\RoyalSliderOptionSetForm",
 *       "edit" = "Drupal\royalslider\Form\RoyalSliderOptionSetForm",
 *       "delete" = "Drupal\royalslider\Form\RoyalSliderOptionSetDeleteForm"
 *     }
 *   },
 *   config_prefix = "royalslider_optionset",
 *   admin_permission = "administer site configuration",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "name"
 *   },
 *   links = {
 *     "collection" = "entity.royalslider_optionset.collection",
 *     "edit-form" = "entity.royalslider_optionset.edit_form",
 *     "delete-form" = "entity.royalslider_optionset.delete_form"
 *   }
 * )
 */
class RoyalSliderOptionSetEntity extends ConfigEntityBase
  implements RoyalSliderOptionSetInterface {
  /**
   * The ID of the rs optionset.
   *
   * @var string
   */
  public $id;

  /**
   * The rs optionset name.
   *
   * @var string
   */
  public $name;

  /**
   * The following options are the general options for the slider.
   *
   * @see http://dimsemenov.com/plugins/royal-slider/documentation/#options
   */

  /**
   * Automatically updates slider height based on base width.
   *
   * @var bool
   */
  public $auto_scale_slider = FALSE;

  /**
   * Base slider width.
   * Slider will autocalculate the ratio based on these values.
   *
   * @var int
   */
  public $auto_scale_slider_width = 800;

  /**
   * Base slider height.
   *
   * @var int
   */
  public $auto_scale_slider_height = 400;

  /**
   * Scale mode for images.
   *
   * @var string
   */
  public $image_scale_mode = 'fit-if-smaller';

  /**
   * Aligns image to center of slide.
   *
   * @var bool
   */
  public $image_align_center = TRUE;

  /**
   * Distance between image and edge of slide (doesn't work with 'fill' scale mode).
   *
   * @var int
   */
  public $image_scale_padding = 4;

  /**
   * Navigation type, can be 'bullets', 'thumbnails', 'tabs' or 'none'
   *
   * @var string
   */
  public $control_navigation = 'bullets';

  /**
   * Direction arrows navigation.
   *
   * @var bool
   */
  public $arrows_nav = TRUE;

  /**
   * Auto hide arrows.
   *
   * @var bool
   */
  public $arrows_nav_auto_hide = TRUE;

  /**
   * Hides arrows completely on touch devices.
   *
   * @var bool
   */
  public $arrows_nav_auto_hide_on_touch = FALSE;

  /**
   * Adds base width to all images for better-looking loading.
   *
   * @var int
   */
  public $img_width = NULL;

  /**
   * Adds base height to all images for better-looking loading.
   *
   * @var int
   */
  public $img_height = NULL;

  /**
   * Spacing between slides in pixels.
   *
   * @var int
   */
  public $slides_spacing = 8;

  /**
   * Start slide index.
   *
   * @var int
   */
  public $start_slide_id = 0;

  /**
   * Makes slider to go from last slide to first.
   *
   * @var bool
   */
  public $loop = FALSE;

  /**
   * Makes slider to go from last slide to first with rewind. Overrides prev option.
   *
   * @var bool
   */
  public $loop_rewind = FALSE;

  /**
   * Randomizes all slides at start.
   *
   * @var bool
   */
  public $randomize_slides = FALSE;

  /**
   * Number of slides to preload on sides.
   *
   * @var int
   */
  public $num_images_to_preload = 4;

  /**
   * Enables spinning preloader.
   *
   * @var bool
   */
  public $use_preloader = TRUE;

  /**
   * Slides orientation: can be 'vertical' or 'horizontal'.
   *
   * @var string
   */
  public $slides_orientation = 'horizontal';

  /**
   * Transition type: 'move' or 'fade'.
   *
   * @var string
   */
  public $transition_type = 'move';

  /**
   * Slider transition speed, in ms.
   *
   * @var int
   */
  public $transition_speed = 600;

  /**
   * Easing function for simple transition.
   *
   * @var string
   */
  public $ease_in_out = 'easeInOutSine';

  /**
   * Easing function of animation after ending of the swipe gesture.
   *
   * @var string
   */
  public $ease_out = 'easeOutSine';

  /**
   * If set to TRUE adds arrows and fullscreen button inside rsOverflow container,
   * otherwise inside root slider container.
   *
   * @var bool
   */
  public $controls_inside = TRUE;

  /**
   * Navigates forward by clicking on slide.
   *
   * @var bool
   */
  public $navigate_by_click = TRUE;

  /**
   * Mouse drag navigation over slider.
   *
   * @var bool
   */
  public $slider_drag = TRUE;

  /**
   * Touch navigation of slider.
   *
   * @var bool
   */
  public $slider_touch = TRUE;

  /**
   * Navigate slider with keyboard left and right arrows.
   *
   * @var bool
   */
  public $keyboard_nav_enabled = FALSE;

  /**
   * Fades in slide after it's loaded.
   *
   * @var bool
   */
  public $fadein_loaded_slide = TRUE;

  /**
   * Allows usage of CSS3 transitions.
   *
   * @var bool
   */
  public $allow_css3 = TRUE;

  /**
   * Adds global caption element to slider.
   *
   * @var bool
   */
  public $global_caption = FALSE;

  /**
   * Adds rsActiveSlide class to current slide before transition.
   *
   * @var bool
   */
  public $add_active_class = FALSE;

  /**
   * Minimum distance in pixels to show next slide while dragging.
   *
   * @var int
   */
  public $min_slide_offset = 10;

  /**
   * Scales and animates height based on current slide.
   *
   * @var bool
   */
  public $auto_height = FALSE;

  /**
   * Overrides HTML of slides,
   * used for creating of slides from HTML that is not attached to DOM.
   *
   * @var string
   */
  public $slides = NULL;

  /**
   * The following options are part of the 'Thumbnails & tabs' settings.
   *
   * The properties are prefixed with thumbs.
   *
   * @see http://dimsemenov.com/plugins/royal-slider/documentation/#thumbnails
   */

  /**
   * Thumbnails mouse drag.
   *
   * @var bool
   */
  public $thumbs_drag = TRUE;

  /**
   * Thumbnails touch.
   *
   * @var bool
   */
  public $thumbs_touch = TRUE;

  /**
   * Thumbnail orientation: 'horizontal' or 'vertical'.
   *
   * @var string
   */
  public $thumbs_orientation = 'horizontal';

  /**
   * Thumbnails arrows.
   *
   * @var bool
   */
  public $thumbs_arrows = TRUE;

  /**
   * Spacing between thumbs.
   *
   * @var int
   */
  public $thumbs_spacing = 4;

  /**
   * Auto hide thumbnails arrows on hover.
   *
   * @var bool
   */
  public $thumbs_arrows_auto_hide = FALSE;

  /**
   * Automatically centers container with thumbs if there are small number of items.
   *
   * @var bool
   */
  public $thumbs_auto_center = TRUE;

  /**
   * Thumbnails transition speed.
   *
   * @var int
   */
  public $thumbs_transition_speed = 600;

  /**
   * Reduces size of main viewport area by thumbnails width or height,
   * use it when you set 100% width to slider.
   *
   * @var bool
   */
  public $thumbs_fit_in_viewport = TRUE;

  /**
   * Margin that equals thumbs spacing for first and last item.
   *
   * @var bool
   */
  public $thumbs_first_margin = TRUE;

  /**
   * Replaces default thumbnail arrow.
   * Variable accepts jQuery element $('This is left arrow') that will be used as arrow.
   * You have to add it to DOM manually.
   *
   * @var string
   */
  public $thumbs_arrow_left = NULL;

  /**
   * Replaces default thumbnail arrow.
   * Variable accepts jQuery element $('This is right arrow') that will be used as arrow.
   * You have to add it to DOM manually.
   *
   * @var string
   */
  public $thumbs_arrow_right = NULL;

  /**
   * Adds span element with class thumbIco to every thumbnail.
   *
   * @var bool
   */
  public $thumbs_append_span = FALSE;

  /**
   * The following options are part of the 'Fullscreen' settings.
   *
   * The properties are prefixed with fullscreen_.
   *
   * @see http://dimsemenov.com/plugins/royal-slider/documentation/#fullscreen
   */

  /**
   * Fullscreen functions enabled.
   *
   * @var bool
   */
  public $fullscreen_enabled = FALSE;

  /**
   * Force keyboard arrows nav in fullscreen.
   *
   * @var bool
   */
  public $fullscreen_keyboard_nav = TRUE;

  /**
   * Fullscreen button at top right.
   *
   * @var bool
   */
  public $fullscreen_button_fs = TRUE;

  /**
   * Native browser fullscreen.
   *
   * @var bool
   */
  public $fullscreen_native_fs = FALSE;

  /**
   * The following options are part of the 'Deep linking' settings.
   *
   * The properties are prefixed with deep_linking.
   *
   * @see http://dimsemenov.com/plugins/royal-slider/documentation/#deeplinking
   */

  /**
   * Linking to slides by appending #SLIDE_INDEX to url.
   *
   * @var bool
   */
  public $deep_linking_enabled = FALSE;

  /**
   * Automatically change URL after transition and listen for hash change.
   *
   * @var bool
   */
  public $deep_linking_change = FALSE;

  /**
   * Prefix that will be added to hash.
   *
   * @var string
   */
  public $deep_linking_prefix = '';

  /**
   * The following options are part of the 'Autoplay' settings.
   *
   * The properties are prefixed with autplay.
   *
   * @see http://dimsemenov.com/plugins/royal-slider/documentation/#autoplay
   */

  /**
   * Enable autoplay or not.
   *
   * @var bool
   */
  public $autoplay_enabled = FALSE;

  /**
   * Stop autoplay at first user action.
   *
   * @var bool
   */
  public $autoplay_stop_at_action = TRUE;

  /**
   * Pause autoplay on hover.
   *
   * @var bool
   */
  public $autoplay_pause_on_hover = TRUE;

  /**
   * Delay between items in ms.
   *
   * @var int
   */
  public $autoplay_delay = 3000;

  /**
   * The following options are part of the 'Video' settings.
   *
   * The properties are prefixed with video.
   *
   * @see http://dimsemenov.com/plugins/royal-slider/documentation/#video
   */

  /**
   * Auto hide arrows when video is playing.
   *
   * @var bool
   */
  public $video_auto_hide_arrows = TRUE;

  /**
   * Auto hide navigation when video is playing.
   *
   * @var bool
   */
  public $video_auto_hide_control_nav = FALSE;

  /**
   * Auto hide animated blocks when video is playing.
   *
   * @var bool
   */
  public $video_auto_hide_blocks = FALSE;

  /**
   * Youtube embed code. %id% is replaced by video id.
   *
   * @var string
   */
  public $video_youtube_code = '<iframe src="http://www.youtube.com/embed/%id%?rel=1&autoplay=1&showinfo=0" frameborder="no"></iframe>';

  /**
   * Vimeo embed code. %id% is replaced by video id.
   *
   * @var string
   */
  public $video_vimeo_code = '<iframe src="http://player.vimeo.com/video/%id%?byline=0&amp;portrait=0&amp;autoplay=1" frameborder="no" webkitAllowFullScreen mozallowfullscreen allowFullScreen></iframe>';

  /**
   * The following options are part of the 'Animated blocks' settings.
   *
   * The properties are prefixed with animated.
   *
   * @see http://dimsemenov.com/plugins/royal-slider/documentation/#animated-blocks
   */

  /**
   * Adds a fade effect when transitioning between slides.
   *
   * @var bool
   */
  public $animated_fade_effect = TRUE;

  /**
   * Move effect direction. Can be 'left', 'right', 'top', 'bottom' or 'none'.
   *
   * @var string
   */
  public $animated_move_effect = 'top';


  /**
   * Distance for move effect in pixels.
   *
   * @var int
   */
  public $animated_move_offset = 20;

  /**
   * Transition speed of block, in ms.
   *
   * @var int
   */
  public $animated_speed = 400;

  /**
   * Easing function of block animation.
   *
   * @var string
   */
  public $animated_easing = 'easeOutSine';

  /**
   * Delay between each block show up, in ms.
   *
   * @var int
   */
  public $animated_delay = 200;

  /**
   * The following options are part of the 'Visible-nearby' settings.
   *
   * The properties are prefixed with visible_nearby_.
   *
   * @see http://dimsemenov.com/plugins/royal-slider/documentation/#visible-nearby
   */

  /**
   * Enable visible-nearby.
   *
   * @var bool
   */
  public $visible_nearby_enabled = TRUE;

  /**
   * Ratio that determines area of center image.
   *
   * @var float
   */
  public $visible_nearby_center_area = 0.6;

  /**
   * Alignment of center image, if you set it to false center image will be aligned to left.
   *
   * @var bool
   */
  public $visible_nearby_center = TRUE;

  /**
   * Used for responsive design.
   * Changes centerArea value to breakpointCenterArea when width of slider is
   * less then value in this option. Set to 0 to disable. Should be number.
   *
   * @var int
   */
  public $visible_nearby_breakpoint = 0;

  /**
   * Same as centerArea option, just for breakpoint.
   *
   * @var float
   */
  public $visible_nearby_breakpoint_center_area = 0.8;


  /**
   * Build the javascript array from an optionset.
   */
  public function buildJsOptionSet() {
    $js_array = [
      'autoScaleSlider' => $this->auto_scale_slider,
      'autoScaleSliderWidth' => $this->auto_scale_slider_width,
      'autoScaleSliderHeight' => $this->auto_scale_slider_height,
      'loop' => $this->loop,
    ];

    // Remove the values that are set to the default value.
    foreach($js_array as $key => $value) {
      if ($this->getOptionDefault($key) == $value) {
        unset($js_array[$key]);
      }
    }
    return $js_array;
  }

  private function getOptionDefault($key) {
    $defaults = [
      // General.
      'manuallyInit' => (bool) FALSE,
      'loop' => (bool) FALSE,
      'loopRewind' => (bool) FALSE,
      'randomizeSlides' => (bool) FALSE,
      'usePreloader' => (bool) TRUE,
      'numImagesToPreload' => 4,
      'slidesOrientation' => 'horizontal',
      'globalCaption' => FALSE,
      // Full Screen.
      'fullscreen' => array(
        'enabled' => FALSE,
        'keyboardNav' => TRUE,
        'buttonFS' => TRUE,
        'nativeFS' => FALSE,
      ),
      // Navigation.
      'controlNavigation' => 'bullets',
      'controlsInside' => TRUE,
      'sliderDrag' => TRUE,
      'sliderTouch' => TRUE,
      'keyboardNavEnabled' => FALSE,
      'navigateByClick' => TRUE,
      'arrowsNav' => TRUE,
      'arrowsNavAutoHide' => TRUE,
      'arrowsNavHideOnTouch' => FALSE,
      // Thumbnails.
      'thumbs' => array(
        'drag' => TRUE,
        'touch' => TRUE,
        'orientation' => 'horizontal',
        'arrows' => TRUE,
        'spacing' => 4,
        'arrowsAutoHide' => FALSE,
        'autoCenter' => TRUE,
        'transitionSpeed' => 600,
        'fitInViewport' => TRUE,
        'firstMargin' => TRUE,
        'arrowLeft' => NULL,
        'arrowRight' => NULL,
        'appendSpan' => FALSE,
      ),
      // Transitions.
      'transitionType' => 'move',
      'transitionSpeed' => 600,
      'easeInOut' => 'easeInOutSine',
      'easeOut' => 'easeOutSine',
      'allowCSS3' => TRUE, // Note: that since only 3 easing methods are available
      // in CSS3 by default with RoyalSlider and we'd have to implement the rest
      // ourselves, it's probably best to set this to FALSE in most Option Sets.
      // However, in order to maintain compatibility with the RoyalSlider API
      // we're leaving this be.
      'addActiveClass' => FALSE,
      'fadeinLoadedSlide' => TRUE,
      // Dimensions.
      'autoScaleSlider' => FALSE,
      'autoScaleSliderWidth' => 800,
      'autoScaleSliderHeight' => 400,
      'autoHeight' => FALSE,
      'imageScaleMode' => 'fit-if-smaller',
      'imageScalePadding' => 4,
      'imageAlignCenter' => TRUE,
      'imgWidth' => NULL,
      'imgHeight' => NULL,
      'slidesSpacing' => 8,
      // Autoplay
      'autoplay' => array(
        'enabled' => FALSE,
        'stopAtAction' => TRUE,
        'pauseOnHover' => TRUE,
        'delay' => 300,
      ),
      // Visible Nearby
      'visibleNearby' => array(
        'enabled' => TRUE,
        'centerArea' => 0.6,
        'center' => TRUE,
        'navigateByCenterClick' => TRUE,
        'breakpoint' => 0,
        'breakpointCenterArea' => 0.8,
      ),
      // Deep linking
      'deeplinking' => array(
        'enabled' => FALSE,
        'change' => FALSE,
        'prefix' => '',
      ),
      // Video
      'video' => array(
        'autoHideArrows' => TRUE,
        'autoHideControlNav' => FALSE,
        'autoHideBlocks' => FALSE,
        'youTubeCode' => '<iframe src="http://www.youtube.com/embed/%id%?rel=1&autoplay=1&showinfo=0" frameborder="no"></iframe>',
        'vimeoCode' => '<iframe src="http://player.vimeo.com/video/%id%?byline=0&amp;portrait=0&amp;autoplay=1" frameborder="no" webkitAllowFullScreen mozallowfullscreen allowFullScreen></iframe>',
      ),
      // Drupal-specific.
      'drupalAutoSetSliderDimensions' => FALSE,
      'drupalAutoSetImageDimensions' => TRUE,
    ];

    return $defaults[$key];
  }

}