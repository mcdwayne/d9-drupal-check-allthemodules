<?php

namespace Drupal\roundabout\Plugin\views\style;

use Drupal\core\form\FormStateInterface;
use Drupal\views\Plugin\views\style\StylePluginBase;

/**
 * Provide an accordion style plugin for Views.
 *
 * @ingroup views_style_plugins
 *
 * @ViewsStyle(
 *   id = "roundabout",
 *   title = @Translation("Roundabout"),
 *   help = @Translation("Uses the jQuery Roundabout plugin to create a carousel like animation of the view results."),
 *   theme = "views_view_roundabout",
 *   display_types = { "normal" },
 * )
 */
class Roundabout extends StylePluginBase {

  /**
   * {@inheritdoc}
   */
  protected $usesRowPlugin = TRUE;

  /**
   * {@inheritdoc}
   */
  protected $usesRowClass = TRUE;

  /**
   * {@inheritdoc}
   */
  protected $usesFields = TRUE;

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['bearing'] = ['default' => 0.0];
    $options['tilt'] = ['default' => 0.0];
    $options['minZ'] = ['default' => 100];
    $options['maxZ'] = ['default' => 280];
    $options['minOpacity'] = ['default' => 0.4];
    $options['maxOpacity'] = ['default' => 1.0];
    $options['minScale'] = ['default' => 0.4];
    $options['maxScale'] = ['default' => 1.0];
    $options['duration'] = ['default' => 600];
    $options['btnNext'] = ['default' => '#roundabout-next'];
    $options['btnNextCallback'] = ['default' => NULL];
    $options['btnPrev'] = ['default' => '#roundabout-prev'];
    $options['btnPrevCallback'] = ['default' => NULL];
    $options['btnToggleAutoplay'] = ['default' => NULL];
    $options['btnStartAutoplay'] = ['default' => NULL];
    $options['btnStopAutoplay'] = ['default' => NULL];
    $options['easing'] = ['default' => 'swing'];
    $options['clickToFocus'] = ['default' => TRUE];
    $options['clickToFocusCallback'] = ['default' => NULL];
    $options['focusBearing'] = ['default' => 0.0];
    $options['shape'] = ['default' => 'lazySusan'];
    $options['debug'] = ['default' => 0];
    $options['childSelector'] = ['default' => 'div.views-row'];
    $options['startingChild'] = ['default' => 0];
    $options['reflect'] = ['default' => FALSE];
    $options['floatComparisonThreshold'] = ['default' => 0.001];
    $options['autoplay'] = ['default' => FALSE];
    $options['autoplayInitialDelay'] = ['default' => 0];
    $options['autoplayDuration'] = ['default' => 1000];
    $options['autoplayPauseOnHover'] = ['default' => FALSE];
    $options['enableDrag'] = ['default' => FALSE];
    $options['dropEasing'] = ['default' => 'swing'];
    $options['css'] = ['default' => '1'];

    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);

    $easingOptions = [
      'none' => t('None'),
      'slide' => t('Slide'),
      'swing' => t('Swing'),
      'linear' => t('Linear'),
      'bounceslide' => t('Bounceslide'),
      'easeInQuart' => t('easeInQuart'),
      'easeOutQuart' => t('easeOutQuart'),
      'easeInOutQuart' => t('easeInOutQuart'),
      'easeInExpo' => t('easeInExpo'),
      'easeOutExpo' => t('easeOutExpo'),
      'easeInOutExpo' => t('easeInOutExpo'),
      'easeInBack' => t('easeInBack'),
      'easeOutBack' => t('easeOutBack'),
      'easeInOutBack' => t('easeInOutBack'),
      'easeInQuad' => t('easeInQuad'),
      'easeOutQuad' => t('easeOutQuad'),
      'easeInOutQuad' => t('easeInOutQuad'),
      'easeInQuint' => t('easeInQuint'),
      'easeOutQuint' => t('easeOutQuint'),
      'easeInOutQuint' => t('easeInOutQuint'),
      'easeInCirc' => t('easeInCirc'),
      'easeOutCirc' => t('easeOutCirc'),
      'easeInOutCirc' => t('easeInOutCirc'),
      'easeInBounce' => t('easeInBounce'),
      'easeOutBounce' => t('easeOutBounce'),
      'easeInOutBounce' => t('easeInOutBounce'),
      'easeInCubic' => t('easeInCubic'),
      'easeOutCubic' => t('easeOutCubic'),
      'easeInOutCubic' => t('easeInOutCubic'),
      'easeInSine' => t('easeInSine'),
      'easeOutSine' => t('easeOutSine'),
      'easeInOutSine' => t('easeInOutSine'),
      'easeInElastic' => t('easeInElastic'),
      'easeOutElastic' => t('easeOutElastic'),
      'easeInOutElastic' => t('easeInOutElastic'),
    ];

    $form['duration'] = [
      '#type' => 'textfield',
      '#title' => t('Duration'),
      '#default_value' => $this->options['duration'],
      '#description' => t('The length of time Roundabout will take to move from one child element being in focus to another (when an animation is triggered). This value acts as the default for Roundabout, but each animation action can be given a custom duration for that animation.'),
    ];
    $form['btnNext'] = [
      '#type' => 'textfield',
      '#title' => t('Next Button'),
      '#default_value' => $this->options['btnNext'],
      '#description' => t('A jQuery selector of page elements that, when clicked, will trigger the Roundabout to animate to the next moving element.'),
    ];
    $form['btnPrev'] = [
      '#type' => 'textfield',
      '#title' => t('Prev Button'),
      '#default_value' => $this->options['btnPrev'],
      '#description' => t('A jQuery selector of page elements that, when clicked, will trigger the Roundabout to animate to the previous moving element.'),
    ];
    $form['easing'] = [
      '#type' => 'select',
      '#title' => t('Animation style'),
      '#default_value' => $this->options['easing'],
      '#description' => t("Select the animation to use for transitions."),
      '#options' => $easingOptions,
    ];
    $form['childSelector'] = [
      '#type' => 'textfield',
      '#title' => t('Child Selector'),
      '#default_value' => $this->options['childSelector'],
      '#description' => t('A jQuery selector of child elements within the elements Roundabout is called upon that will become the moving elements within Roundabout. By default, Roundabout works on unordered lists, but it can be changed to work with any nested set of child elements.'),
    ];
    $form['reflect'] = [
      '#type' => 'checkbox',
      '#title' => t('Reflect'),
      '#default_value' => $this->options['reflect'],
      '#description' => t('When true, reverses the direction in which Roundabout will operate. By default, next animations will rotate moving elements in a clockwise direction and previous animations will be counterclockwise. Using reflect will flip the two.'),
    ];
    $form['autoplay'] = [
      '#type' => 'checkbox',
      '#title' => t('Autoplay'),
      '#default_value' => $this->options['autoplay'],
      '#description' => t('When true, Roundabout will automatically advance the moving elements to the next child at a regular interval (settable as autoplayDuration).'),
    ];
    $form['autoplayInitialDelay'] = [
      '#type' => 'textfield',
      '#title' => t('Autoplay Initial Delay'),
      '#default_value' => $this->options['autoplayInitialDelay'],
      '#description' => t('The length of time (in milliseconds) to delay the start of Roundabout’s configured autoplay option. This only works with setting autoplay to true, and only on the first start of autoplay.'),
    ];
    $form['autoplayDuration'] = [
      '#type' => 'textfield',
      '#title' => t('Autoplay Duration'),
      '#default_value' => $this->options['autoplayDuration'],
      '#description' => t('The length of time (in milliseconds) between animation triggers when a Roundabout’s autoplay is playing.'),
    ];
    $form['autoplayPauseOnHover'] = [
      '#type' => 'checkbox',
      '#title' => t('Autoplay Pause On Hover'),
      '#default_value' => $this->options['autoplayPauseOnHover'],
      '#description' => t('When true, Roundabout will pause autoplay when the user moves the cursor over the Roundabout container.'),
    ];
  }

}
