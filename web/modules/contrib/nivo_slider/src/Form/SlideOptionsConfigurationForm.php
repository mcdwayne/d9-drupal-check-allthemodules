<?php

namespace Drupal\nivo_slider\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\image\Entity\ImageStyle;
use Drupal\node\Entity\NodeType;
use Drupal\Core\Url;
use Drupal\likebtn\LikebtnInterface;

class SlideOptionsConfigurationForm extends ConfigFormBase {

  protected function getEditableConfigNames() {
    return [
      'nivo_slider.settings'
    ];
  }

  public function getFormId() {
    return 'slide_configuration';
  }

  public function buildForm(array $form, FormStateInterface $form_state) {
    $themes = \Drupal::moduleHandler()->invokeAll('nivo_slider_theme_info');

    // Allow theme information to be altered.
    \Drupal::moduleHandler()->alter('nivo_slider_theme_info', $themes);

    $config = $this->config('nivo_slider.settings');
    $available_themes = [];

    foreach ($themes as $theme => $properties) {
      $available_themes[$theme] = $properties['name'];
    }

    // Theme.
    $form['nivo_slider_theme'] = [
      '#type' => 'select',
      '#title' => $this->t('Theme'),
      '#options' => $available_themes,
      '#default_value' => $config->get('options.theme'),
      '#description' => $this->t('Select a slider theme. The slider theme determines the overall appearance of the slider.'),
    ];

    // Display image style settings if the image module is available.
    if (\Drupal::moduleHandler()->moduleExists('image') == TRUE) {
      // Create a list of the currently available image styles.
      $image_styles = ImageStyle::loadMultiple();
      $available_themes = [];

      foreach ($image_styles as $image_style) {
        $available_image_styles[$image_style->id()] = $image_style->label();
      }

      // Image Style.
      $form['nivo_slider_image_style'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Use image styles to generate slider images'),
        '#default_value' => $config->get('options.image_style'),
        '#description' => $this->t('Easily modify, scale, crop and apply various effects to slider images using <a href="@image-styles">Image styles</a>.', ['@image-styles' => Url::fromRoute('entity.image_style.collection')]),
      ];
      $form['nivo_slider_image_style_slide'] = [
        '#type' => 'select',
        '#title' => $this->t('Slide image style'),
        '#options' => $available_image_styles,
        '#default_value' => $config->get('options.image_style_slide'),
        '#description' => $this->t('Select an image style to apply to slide images.'),
        '#states' => [
          'visible' => [
            ':input[name="nivo_slider_image_style"]' => ['checked' => TRUE],
          ],
        ],
      ];
      $form['nivo_slider_image_style_thumb'] = [
        '#type' => 'select',
        '#title' => $this->t('Tumbnail image style'),
        '#options' => $available_image_styles,
        '#default_value' => $config->get('options.image_style_thumb'),
        '#description' => $this->t('Select an image style to apply to slide thumbnail images.'),
        '#states' => [
          'visible' => [
            ':input[name="nivo_slider_image_style"]' => ['checked' => TRUE],
          ],
        ],
      ];
    }

    // General.
    $form['general'] = [
      '#type' => 'details',
      '#title' => $this->t('General'),
      '#collapsed' => TRUE,
    ];
    $form['general']['nivo_slider_random_start'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Use a random starting slide'),
      '#default_value' => $config->get('options.random_start'),
      '#description' => $this->t('Randomly select a slide to begin the slideshow with.'),
    ];
    $form['general']['nivo_slider_start_slide'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Starting slide'),
      '#maxlength' => 3,
      '#size' => 3,
      '#default_value' => $config->get('options.start_slide'),
      '#description' => $this->t('Specify which slide the slideshow begins with.'),
      '#field_prefix' => $this->t('Slide #'),
      '#states' => [
        'visible' => [
          ':input[name="nivo_slider_random_start"]' => ['checked' => FALSE],
        ],
      ],
    ];

    // Effects.
    $form['effects'] = [
      '#type' => 'details',
      '#title' => $this->t('Effects'),
      '#collapsed' => TRUE,
    ];
    $form['effects']['nivo_slider_effect'] = [
      '#type' => 'select',
      '#title' => $this->t('Effect'),
      '#options' => [
        'sliceDown' => $this->t('Slice Down'),
        'sliceDownLeft' => $this->t('Slice Down Left'),
        'sliceUp' => $this->t('Slice Up'),
        'sliceUpLeft' => $this->t('Slice Up Left'),
        'sliceUpDown' => $this->t('Slice Up Down'),
        'sliceUpDownLeft' => $this->t('Slice Up Down Left'),
        'fold' => $this->t('Fold'),
        'fade' => $this->t('Fade'),
        'random' => $this->t('Random'),
        'slideInRight' => $this->t('Slide In Right'),
        'slideInLeft' => $this->t('Slide in Left'),
        'boxRandom' => $this->t('Box Random'),
        'boxRain' => $this->t('Box Rain'),
        'boxRainReverse' => $this->t('Box Rain Reverse'),
        'boxRainGrow' => $this->t('Box Rain Grow'),
        'boxRainGrowReverse' => $this->t('Box Rain Grow Reverse'),
      ],
      '#default_value' => $config->get('options.effect'),
      '#description' => $this->t('Select an effect. The chosen effect will be used to transition between slides.'),
    ];
    $form['effects']['effect_properties'] = [
      '#type' => 'details',
      '#title' => $this->t('Effect properties'),
      '#collapsed' => TRUE,
    ];
    $form['effects']['effect_properties']['nivo_slider_slices'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Slices'),
      '#maxlength' => 3,
      '#size' => 3,
      '#default_value' => $config->get('options.slices'),
      '#description' => $this->t('Used for slice animations.'),
      '#field_suffix' => $this->t('Slices'),
      '#states' => [
        'visible' => [
          [':input[name="nivo_slider_effect"]' => ['value' => 'sliceDown']],
          [':input[name="nivo_slider_effect"]' => ['value' => 'sliceDownLeft']],
          [':input[name="nivo_slider_effect"]' => ['value' => 'sliceUp']],
          [':input[name="nivo_slider_effect"]' => ['value' => 'sliceUpLeft']],
          [':input[name="nivo_slider_effect"]' => ['value' => 'sliceUpDown']],
          [':input[name="nivo_slider_effect"]' => ['value' => 'sliceUpDownLeft']],
          [':input[name="nivo_slider_effect"]' => ['value' => 'fold']],
          [':input[name="nivo_slider_effect"]' => ['value' => 'fade']],
          [':input[name="nivo_slider_effect"]' => ['value' => 'random']],
          [':input[name="nivo_slider_effect"]' => ['value' => 'slideInRight']],
          [':input[name="nivo_slider_effect"]' => ['value' => 'slideInLeft']],
        ],
      ],
    ];
    $form['effects']['effect_properties']['nivo_slider_box_columns'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Box columns'),
      '#maxlength' => 3,
      '#size' => 3,
      '#default_value' => $config->get('options.box_columns'),
      '#description' => $this->t('Used for box animations.'),
      '#field_suffix' => $this->t('Columns'),
      '#states' => [
        'visible' => [
          [':input[name="nivo_slider_effect"]' => ['value' => 'boxRandom']],
          [':input[name="nivo_slider_effect"]' => ['value' => 'boxRain']],
          [':input[name="nivo_slider_effect"]' => ['value' => 'boxRainReverse']],
          [':input[name="nivo_slider_effect"]' => ['value' => 'boxRainGrow']],
          [':input[name="nivo_slider_effect"]' => ['value' => 'boxRainGrowReverse']],
        ],
      ],
    ];
    $form['effects']['effect_properties']['nivo_slider_box_rows'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Box rows'),
      '#maxlength' => 3,
      '#size' => 3,
      '#default_value' => $config->get('options.box_rows'),
      '#description' => $this->t('Used for box animations.'),
      '#field_suffix' => $this->t('Rows'),
      '#states' => [
        'visible' => [
          [':input[name="nivo_slider_effect"]' => ['value' => 'boxRandom']],
          [':input[name="nivo_slider_effect"]' => ['value' => 'boxRain']],
          [':input[name="nivo_slider_effect"]' => ['value' => 'boxRainReverse']],
          [':input[name="nivo_slider_effect"]' => ['value' => 'boxRainGrow']],
          [':input[name="nivo_slider_effect"]' => ['value' => 'boxRainGrowReverse']],
        ],
      ],
    ];
    $form['effects']['nivo_slider_animation_speed'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Animation speed'),
      '#maxlength' => 6,
      '#size' => 6,
      '#default_value' => $config->get('options.animation_speed'),
      '#description' => $this->t('Enter a time in milliseconds. The animation speed determines how long it takes to transition from one slide to the next.'),
      '#field_suffix' => $this->t('ms'),
    ];
    $form['effects']['nivo_slider_pause_on_hover'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Pause animation when hovering over the slide'),
      '#default_value' => $config->get('options.pause_on_hover'),
      '#description' => $this->t('Disable slide transitions while the user is hovering over the slide.'),
    ];

    // Navigation.
    $form['navigation'] = [
      '#type' => 'details',
      '#title' => $this->t('Navigation'),
      '#collapsed' => TRUE,
    ];
    $form['navigation']['nivo_slider_directional_navigation'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Display directional navigation'),
      '#default_value' => $config->get('options.directional_navigation'),
      '#description' => $this->t('Display navigation buttons to go back to the previous slide and move forward to the next slide.'),
    ];
    $form['navigation']['nivo_slider_next_text'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Next button text'),
      '#default_value' => $config->get('options.next_text'),
      '#states' => [
        'visible' => [
          ':input[name="nivo_slider_directional_navigation"]' => ['checked' => TRUE],
        ],
      ],
    ];
    $form['navigation']['nivo_slider_previous_text'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Previous button text'),
      '#default_value' => $config->get('options.previous_text'),
      '#states' => [
        'visible' => [
          ':input[name="nivo_slider_directional_navigation"]' => ['checked' => TRUE],
        ],
      ],
    ];
    $form['navigation']['nivo_slider_control_navigation'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Display control navigation'),
      '#default_value' => $config->get('options.control_navigation'),
      '#description' => $this->t('Display navigation buttons to select an individual slide.'),
    ];
    $form['navigation']['nivo_slider_control_nav_thumbs'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Use thumbnails for control navigation'),
      '#default_value' => $config->get('options.nav_thumbs'),
      '#description' => $this->t('Use thumbnails of each slide as control navigation buttons. In order for thumbnails to be displayed, a compatible theme is required.'),
      '#states' => [
        'visible' => [
          ':input[name="nivo_slider_control_navigation"]' => ['checked' => TRUE],
        ],
      ],
    ];
    $form['navigation']['nivo_slider_manual_advance'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Disable automatic slide transitions'),
      '#default_value' => $config->get('options.manual_advance'),
      '#description' => $this->t('For the use to manually navigate between slides.'),
    ];
    $form['navigation']['nivo_slider_pause_time'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Pause time'),
      '#maxlength' => 6,
      '#size' => 6,
      '#default_value' => $config->get('options.pause_time'),
      '#description' => $this->t('Enter a time in milliseconds. The pause time determines how long each slide is displayed before transitioning to the next slide.'),
      '#field_suffix' => $this->t('ms'),
      '#states' => [
        'visible' => [
          ':input[name="nivo_slider_manual_advance"]' => ['checked' => FALSE],
        ],
      ],
    ];

    return parent::buildForm($form, $form_state);
  }

  public function validateForm(array &$form, FormStateInterface $form_state) {
    // TODO: Implement validateForm() method.
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $config = $this->config('nivo_slider.settings');

    $config->set('options.theme', $values['nivo_slider_theme'])
      ->set('options.image_style', $values['nivo_slider_image_style'])
      ->set('options.image_style_slide', $values['nivo_slider_image_style_slide'])
      ->set('options.image_style_thumb', $values['nivo_slider_image_style_thumb'])
      ->set('options.random_start', $values['nivo_slider_random_start'])
      ->set('options.start_slide', $values['nivo_slider_start_slide'])
      ->set('options.effect', $values['nivo_slider_effect'])
      ->set('options.slices', $values['nivo_slider_slices'])
      ->set('options.box_columns', $values['nivo_slider_box_columns'])
      ->set('options.box_rows', $values['nivo_slider_box_rows'])
      ->set('options.animation_speed', $values['nivo_slider_animation_speed'])
      ->set('options.pause_on_hover', $values['nivo_slider_pause_on_hover'])
      ->set('options.directional_navigation', $values['nivo_slider_directional_navigation'])
      ->set('options.next_text', $values['nivo_slider_next_text'])
      ->set('options.previous_text', $values['nivo_slider_previous_text'])
      ->set('options.control_navigation', $values['nivo_slider_control_navigation'])
      ->set('options.control_nav_thumbs', $values['nivo_slider_control_nav_thumbs'])
      ->set('options.manual_advance', $values['nivo_slider_manual_advance'])
      ->set('options.pause_time', $values['nivo_slider_pause_time'])
      ->save();

    parent::submitForm($form, $form_state);
  }
}
