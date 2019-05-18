<?php

namespace Drupal\revealjs\Plugin\views\style;

use Drupal\core\form\FormStateInterface;
use Drupal\views\Plugin\views\style\StylePluginBase;

/**
 * Style plugin for Reveal.js
 *
 * @ViewsStyle(
 *   id = "Revealjs",
 *   title = @Translation("Reveal.js"),
 *   help = @Translation("Render a slide"),
 *   theme = "views_view_revealjs",
 *   display_types = {"normal"}
 * )
 */

class Revealjs extends StylePluginBase {

  /**
   * Does the style plugin allows to use style plugins.
   *
   * @var bool
   */
  protected $usesRowPlugin = TRUE;

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
   $options = parent::defineOptions();

   $options['theme_slide'] = ['default' => 'none'];
   $options['controls'] = ['default' => TRUE];
   $options['controlsTutorial'] = ['default' => TRUE];
   $options['controlsLayout'] = ['default' => 'bottom-right'];
   $options['controlsBackArrows'] = ['default' => 'faded'];
   $options['progress'] = ['default' => TRUE];
   $options['defaultTiming'] = ['default' => 120];
   $options['slideNumber'] = ['default' => FALSE];
   $options['history'] = ['default' => FALSE];
   $options['keyboard'] = ['default' => TRUE];
   $options['overview'] = ['default' => TRUE];
   $options['center'] = ['default' => TRUE];
   $options['touch'] = ['default' => TRUE];
   $options['loop'] = ['default' => FALSE];
   $options['rtl'] = ['default' => FALSE];
   $options['shuffle'] = ['default' => FALSE];
   $options['fragments'] = ['default' => TRUE];
   $options['embedded'] = ['default' => FALSE];
   $options['help'] = ['default' => TRUE];
   $options['showNotes'] = ['default' => FALSE];
   $options['autoPlayMedia'] = ['default' => null];
   $options['autoSlide'] = ['default' => 0];
   $options['autoSlideStoppable'] = ['default' => TRUE];
   $options['mouseWheel'] = ['default' => FALSE];
   $options['hideAddressBar'] = ['default' => TRUE];
   $options['transition'] = ['default' => 'none'];
   $options['transitionSpeed'] = ['default' => 'default'];
   $options['backgroundTransition'] = ['default' => 'none'];
   $options['viewDistance'] = ['default' => 3];
   $options['parallaxBackgroundImage'] = ['default' => ''];
   $options['parallaxBackgroundSize'] = ['default' => ''];
   $options['height'] = ['default' => ''];
   $options['width'] = ['default' => ''];
   $options['margin'] = ['default' => NULL];
   $options['minScale'] = ['default' => NULL];
   $options['maxScale'] = ['default' => NULL];


   return $options;
  }

  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);

    $form['theme_slide'] = [
      '#type' => 'select',
      '#title' => $this->t('Theme presentation'),
      '#default_value' => $this->options['theme_slide'],
      '#description' => $this->t('Choose a theme for your presentation'),
      '#options'=> [
        'none' => $this->t('None'),
        'beige' => $this->t('Beige'),
        'blood' => $this->t('Blood'),
        'league' => $this->t('League'),
        'serif' => $this->t('Serif'),
        'simple' => $this->t('Simple'),
        'sky' => $this->t('Sky'),
        'white' => $this->t('White'),
      ],
    ];

    $form['controls'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Display controls'),
      '#description' => $this->t('Display controls arrows in the presentation'),
      '#default_value' => $this->options['controls'],
    ];

    $form['controlsTutorial'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Controls tutorial'),
      '#description' => $this->t('Help the user learn the controls by providing hints'),
      '#default_value' => $this->options['controlsTutorial'],
    ];

    $form['controlsLayout'] = [
      '#type' => 'select',
      '#title' => $this->t('Controls position'),
      '#description' => $this->t('Determines where controls appear'),
      '#default_value' => $this->options['controlsLayout'],
      '#options' => [
        'bottom-right' => $this->t('Bottom-right'),
        'edges' => $this->t('Edges'),
      ],
    ];

    $form['controlsBackArrows'] = [
      '#type' => 'select',
      '#title' => $this->t('Navigation arrows visibility'),
      '#description' => $this->t('Visibility rule for navigation arrows'),
      '#default_value' => $this->options['controlsBackArrows'],
      '#options' => [
        'faded' => $this->t('Faded'),
        'hidden' => $this->t('Hidden'),
        'visible' => $this->t('Visible'),
      ],
    ];

    $form['progress'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Progress bar'),
      '#description' => $this->t('Display a presentation progress bar'),
      '#default_value' => $this->options['progress'],
    ];

    $form['defaultTiming'] = [
      '#type' => 'number',
      '#title' => $this->t('Default timing per slide'),
      '#description' => $this->t('Specify the average time in seconds that you think you will spend. This is used to show a pacing timer in the speaker view'),
      '#default_value' => $this->options['defaultTiming'],
      '#field_suffix' => $this->t('ms'),
    ];

    $form['slideNumber'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Display page number'),
      '#description' => $this->t('Display the page number of the current slide'),
      '#default_value' => $this->options['slideNumber'],
    ];

    $form['history'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Browser history'),
      '#description' => $this->t('Push each slide change to the browser history'),
      '#default_value' => $this->options['history'],
    ];

    $form['keyboard'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Keyboard shortcuts'),
      '#description' => $this->t('Enable keyboard shortcuts for navigation'),
      '#default_value' => $this->options['keyboard'],
    ];

    $form['overview'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Overview mode'),
      '#description' => $this->t('Enable the slide overview mode'),
      '#default_value' => $this->options['overview'],
    ];

    $form['center'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Vertical center'),
      '#description' => $this->t('Vertical centering of slides'),
      '#default_value' => $this->options['center'],
    ];

    $form['touch'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Touch navigation'),
      '#description' => $this->t('Enables touch navigation on devices with touch input'),
      '#default_value' => $this->options['touch'],
    ];

    $form['loop'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Loop'),
      '#description' => $this->t('Loop the presentation'),
      '#default_value' => $this->options['loop'],
    ];

    $form['rtl'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Presentation direction'),
      '#description' => $this->t('Change the presentation direction to be RTL'),
      '#default_value' => $this->options['rtl'],
    ];

    $form['shuffle'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Random order slides'),
      '#description' => $this->t('Randomizes the order of slides each time the presentation loads'),
      '#default_value' => $this->options['shuffle'],
    ];

    $form['fragments'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Fragments'),
      '#description' => $this->t('Turns fragments on and off globally'),
      '#default_value' => $this->options['fragments'],
    ];

    $form['embedded'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Embedded mode'),
      '#description' => $this->t('Flags if the presentation is running in an embedded mode'),
      '#default_value' => $this->options['embedded'],
    ];

    $form['help'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Help overlay'),
      '#description' => $this->t('Flags if we should show a help overlay when the key is pressed'),
      '#default_value' => $this->options['help'],
    ];

    $form['showNotes'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Speaker notes visible'),
      '#description' => $this->t('Flags if speaker notes should be visible to all viewers'),
      '#default_value' => $this->options['showNotes'],
    ];

    $form['autoPlayMedia'] = [
      '#type' => 'select',
      '#title' => $this->t('Navigation arrows visibility'),
      '#description' => $this->t('Visibility rule for navigation arrows'),
      '#default_value' => $this->options['autoPlayMedia'],
      '#options' => [
        'null' => $this->t('Null: Media will autoplay if data-autoplay is present'),
        'true' => $this->t('True: All media will autoplay, regardless of individual setting'),
        'false' => $this->t('False: No media will autoplay, regardless of individual setting'),
      ],
    ];

    $form['autoSlide'] = [
      '#type' => 'number',
      '#title' => $this->t('Default timing per slide'),
      '#description' => $this->t('Number of milliseconds between automatically proceeding to the next slide, 
      disabled when set to 0, this value can be overwritten by using a data-autoslide attribute on your slides'),
      '#default_value' => $this->options['autoSlide'],
      '#field_suffix' => $this->t('ms'),
    ];

    $form['autoSlideStoppable'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Stopping auto-sliding'),
      '#description' => $this->t('Stop auto-sliding after user input'),
      '#default_value' => $this->options['autoSlideStoppable'],
    ];

    $form['mouseWheel'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Slide navigation mouse Wheel'),
      '#description' => $this->t('Enable slide navigation via mouse wheel'),
      '#default_value' => $this->options['mouseWheel'],
    ];

    $form['hideAddressBar'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Hide adresse bar mobile devices'),
      '#description' => $this->t('Hides the address bar on mobile devices'),
      '#default_value' => $this->options['hideAddressBar'],
    ];

    $form['transition'] = [
      '#type' => 'select',
      '#title' => $this->t('Translation style'),
      '#description' => $this->t('Transition style'),
      '#default_value' => $this->options['transition'],
      '#options' => [
        'none' => $this->t('None'),
        'fade' => $this->t('Fade'),
        'slide' => $this->t('Slide'),
        'convex' => $this->t('Convex'),
        'concave' => $this->t('Concave'),
        'zoom' => $this->t('Zoom'),
      ],
    ];

    $form['transitionSpeed'] = [
      '#type' => 'select',
      '#title' => $this->t('Translation speed'),
      '#description' => $this->t('Transition speed'),
      '#default_value' => $this->options['transitionSpeed'],
      '#options' => [
        'default' => $this->t('Default'),
        'fast' => $this->t('Fast'),
        'slow' => $this->t('Slow'),
      ],
    ];

    $form['backgroundTransition'] = [
      '#type' => 'select',
      '#title' => $this->t('Background translation'),
      '#description' => $this->t('Background translation'),
      '#default_value' => $this->options['backgroundTransition'],
      '#options' => [
        'none' => $this->t('None'),
        'fade' => $this->t('Fade'),
        'slide' => $this->t('Slide'),
        'convex' => $this->t('Convex'),
        'concave' => $this->t('Concave'),
        'zoom' => $this->t('Zoom'),
      ],
    ];

    $form['viewDistance'] = [
      '#type' => 'number',
      '#title' => $this->t('Slides away from current'),
      '#description' => $this->t('Number of slides away from the current that are visible'),
      '#default_value' => $this->options['viewDistance'],
    ];

    $form['parallaxBackgroundImage'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Parallax Background Image'),
      '#description' => $this->t('Set a background image e.g: https://domain.com/nameimg.jpg'),
      '#default_value' => $this->options['parallaxBackgroundImage'],
    ];

    $form['parallaxBackgroundSize'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Parallax Background size'),
      '#description' => $this->t('Set a specific height and width (not required) Syntax: 2100px 900px)'),
      '#default_value' => $this->options['parallaxBackgroundSize'],
    ];

    $form['height'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Height custom'),
      '#description' => $this->t('Set a specific height for slides (you can apply a percentage), empty for default value'),
      '#default_value' => $this->options['height'],
    ];

    $form['width'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Width custom'),
      '#description' => $this->t('Set a specific width for slides (you can apply a percentage), empty for default value'),
      '#default_value' => $this->options['width'],
    ];

    $form['margin'] = [
      '#type' => 'number',
      '#title' => $this->t('Margin custom'),
      '#description' => $this->t('Set a specific margin for slides, empty for default value'),
      '#default_value' => $this->options['margin'],
      '#step' => 0.1,
      '#min' => 0,
    ];

    $form['minScale'] = [
      '#type' => 'number',
      '#title' => $this->t('Minimum Scale'),
      '#description' => $this->t('Set the minimum scale, empty for default value'),
      '#default_value' => $this->options['minScale'],
      '#step' => 0.1,
      '#min' => 0,
    ];

    $form['maxScale'] = [
      '#type' => 'number',
      '#title' => $this->t('Maximum Scale'),
      '#description' => $this->t('Set the maximum scale, empty for default value'),
      '#default_value' => $this->options['maxScale'],
      '#step' => 0.1,
      '#min' => 0,
    ];

  }
}
