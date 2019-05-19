<?php

namespace Drupal\field_orbit\Plugin\Field\FieldFormatter;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\image\Plugin\Field\FieldFormatter\ImageFormatter;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Url;

/**
 * Plugin implementation of the 'Orbit' formatter.
 *
 * @FieldFormatter(
 *   id = "orbit",
 *   label = @Translation("Zurb Orbit slider"),
 *   field_types = {
 *     "image"
 *   }
 * )
 */
class OrbitFormatter extends ImageFormatter {
  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'animInFromRight' => 'slide-in-right',
      'animOutToRight' => 'slide-out-right',
      'animInFromLeft' => 'slide-in-left',
      'animOutToLeft' => 'slide-out-left',
      'caption' => '',
      'caption_link' => '',
      'autoPlay' => TRUE,
      'timerDelay' => 5000,
      'infiniteWrap' => TRUE,
      'swipe' => TRUE,
      'pauseOnHover' => TRUE,
      'accessible' => TRUE,
      'bullets' => TRUE,
      'navButtons' => TRUE,
      'containerClass' => 'orbit-container',
      'slideClass' => 'orbit-slide',
      'boxOfBullets' => 'orbit-bullets',
      'nextClass' => 'orbit-next',
      'prevClass' => 'orbit-previous',
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    // Get image_style and image_link form elements from parent method.
    $element = parent::settingsForm($form, $form_state);

    $link_types = [
      'content' => $this->t('Content'),
      'file' => $this->t('File'),
    ];
    $captions = [
      'title' => $this->t('Title text'),
      'alt' => $this->t('Alt text'),
    ];

    $element['caption'] = [
      '#title' => $this->t('Caption'),
      '#type' => 'select',
      '#default_value' => $this->getSetting('caption'),
      '#empty_option' => $this->t('Nothing'),
      '#options' => $captions,
    ];
    $element['caption_link'] = [
      '#title' => $this->t('Link caption to'),
      '#type' => 'select',
      '#default_value' => $this->getSetting('caption_link'),
      '#empty_option' => $this->t('Nothing'),
      '#options' => $link_types,
      '#states' => [
        'invisible' => [
          ':input[name$="[settings_edit_form][settings][caption]"]' => ['value' => ''],
        ],
      ],
    ];
    $element['animInFromRight'] = [
      '#type' => 'select',
      '#title' => $this->t('animInFromRight'),
      '#default_value' => $this->getSetting('animInFromRight'),
      '#options' => $this->getAnimationInOptions(),
    ];
    $element['animOutToRight'] = [
      '#type' => 'select',
      '#title' => $this->t('animOutToRight'),
      '#default_value' => $this->getSetting('animOutToRight'),
      '#options' => $this->getAnimationOutOptions(),
    ];
    $element['animInFromLeft'] = [
      '#type' => 'select',
      '#title' => $this->t('animInFromLeft'),
      '#default_value' => $this->getSetting('animInFromLeft'),
      '#options' => $this->getAnimationInOptions(),
    ];
    $element['animOutToLeft'] = [
      '#type' => 'select',
      '#title' => $this->t('animOutToLeft'),
      '#default_value' => $this->getSetting('animOutToLeft'),
      '#options' => $this->getAnimationOutOptions(),
    ];
    $element['autoPlay'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Autoplay'),
      '#default_value' => $this->getSetting('autoPlay'),
      '#description' => $this->t('Allows Orbit to automatically animate on page load.'),
    ];
    $element['timerDelay'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Timer speed'),
      '#element_validate' => ['element_validate_integer_positive'],
      '#default_value' => $this->getSetting('timerDelay'),
      '#description' => $this->t('Amount of time, in ms, between slide transitions'),
    ];
    $element['infiniteWrap'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Infinite Wrap'),
      '#default_value' => $this->getSetting('infiniteWrap'),
      '#description' => $this->t('Allows Orbit to infinitely loop through the slides.'),
    ];
    $element['swipe'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Swipe'),
      '#default_value' => $this->getSetting('swipe'),
      '#description' => $this->t('Allows the Orbit slides to bind to swipe events for mobile.'),
    ];
    $element['pauseOnHover'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Pause on hover'),
      '#default_value' => $this->getSetting('pauseOnHover'),
      '#description' => $this->t('Pause slideshow when you hover on the slide.'),
    ];
    $element['accessible'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Keyboard events'),
      '#default_value' => $this->getSetting('accessible'),
      '#description' => $this->t('Allows Orbit to bind keyboard events to the slider.'),
    ];
    $element['bullets'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Bullets'),
      '#default_value' => $this->getSetting('bullets'),
      '#description' => $this->t('Show bullets.'),
    ];
    $element['navButtons'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Nav buttons'),
      '#default_value' => $this->getSetting('navButtons'),
      '#description' => $this->t('Show navigations buttons.'),
    ];
    $element['containerClass'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Container Class'),
      '#default_value' => $this->getSetting('containerClass'),
      '#description' => $this->t('Class applied to the container of Orbit'),
    ];
    $element['slideClass'] = [
      '#type' => 'textfield',
      '#title' => $this->t('slide class'),
      '#default_value' => $this->getSetting('slideClass'),
      '#description' => $this->t('Class applied to individual slides.'),
    ];
    $element['boxOfBullets'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Bullets class'),
      '#default_value' => $this->getSetting('boxOfBullets'),
      '#description' => $this->t('Class applied to the bullet container.'),
    ];
    $element['nextClass'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Next class'),
      '#default_value' => $this->getSetting('nextClass'),
      '#description' => $this->t('Class applied to the `next` navigation buton.'),
    ];
    $element['prevClass'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Prev class'),
      '#default_value' => $this->getSetting('prevClass'),
      '#description' => $this->t('Class applied to the `previous` navigation button.'),
    ];

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    // Get summary of image_style and image_link from parent method.
    $summary = parent::settingsSummary();

    $caption_types = [
      'title' => $this->t('Title text'),
      'alt' => $this->t('Alt text'),
    ];

    $link_types = [
      'content' => $this->t('Content'),
      'file' => $this->t('File'),
    ];

    // Display this setting only if there's a caption.
    $caption_types_settings = $this->getSetting('caption');
    if (isset($caption_types[$caption_types_settings])) {
      $caption_message = $this->t('Caption: @caption', ['@caption' => $caption_types[$caption_types_settings]]);
      $link_types_settings = $this->getSetting('caption_link');
      if (isset($link_types[$link_types_settings])) {
        $caption_message .= ' (' . $this->t('Link to: @link', ['@link' => $link_types[$link_types_settings]]) . ')';
      }
      $summary[] = $caption_message;
    }
    $summary[] = $this->t('animInFromLeft: @effect', ['@effect' => $this->getSetting('animInFromLeft')]);
    $summary[] = $this->t('animInFromRight: @effect', ['@effect' => $this->getSetting('animInFromRight')]);
    $summary[] = $this->t('animOutToLeft: @effect', ['@effect' => $this->getSetting('animOutToLeft')]);
    $summary[] = $this->t('animOutToRight: @effect', ['@effect' => $this->getSetting('animOutToRight')]);
    $summary[] = $this->t('Autoplay: @autoplay', ['@autoplay' => $this->getSetting('infiniteWrap') ? $this->t('yes') : $this->t('no')]);
    $summary[] = $this->t('Timer delay: @speedms', ['@speed' => $this->getSetting('timerDelay')]);
    $summary[] = $this->t('Infinite wrap: @wrap', ['@wrap' => $this->getSetting('infiniteWrap') ? $this->t('yes') : $this->t('no')]);
    $summary[] = $this->t('Swipe enabled: @swipe', ['@swipe' => $this->getSetting('swipe') ? $this->t('yes') : $this->t('no')]);
    $summary[] = $this->t('Pause on hover: @pause', ['@pause' => $this->getSetting('pauseOnHover') ? $this->t('yes') : $this->t('no')]);
    $summary[] = $this->t('Keyboard navigation: @accessible', ['@accessible' => $this->getSetting('accessible') ? $this->t('yes') : $this->t('no')]);
    $summary[] = $this->t('bullets: @bullets', ['@bullets' => $this->getSetting('bullets') ? $this->t('yes') : $this->t('no')]);
    $summary[] = $this->t('Navigation buttons: @nav', ['@nav' => $this->getSetting('navButtons') ? $this->t('yes') : $this->t('no')]);
    $summary[] = $this->t('Container class: @class', ['@class' => $this->getSetting('containerClass')]);
    $summary[] = $this->t('Slide class: @class', ['@class' => $this->getSetting('slideClass')]);
    $summary[] = $this->t('Bullets class: @class', ['@class' => $this->getSetting('boxOfBullets')]);
    $summary[] = $this->t('Next class: @class', ['@class' => $this->getSetting('nextClass')]);
    $summary[] = $this->t('Previous class: @class', ['@class' => $this->getSetting('nextClass')]);

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    // Get image html from parent method.
    $images = parent::viewElements($items, $langcode);

    static $orbit_count;
    $orbit_count = (is_int($orbit_count)) ? $orbit_count + 1 : 1;

    $files = $this->getEntitiesToView($items, $langcode);

    $elements = [];
    $entity = [];
    $links = [
      'image_link' => 'path',
      'caption_link' => 'caption_path',
    ];

    // Loop through required links (because image and
    // caption can have different links).
    foreach ($items as $delta => $item) {
      // Set Image caption.
      if ($this->getSetting('caption') != '') {
        $caption_settings = $this->getSetting('caption');
        if ($caption_settings == 'title') {
          $item_settings[$delta]['caption'] = $item->getValue()['title'];
        }
        elseif ($caption_settings == 'alt') {
          $item_settings[$delta]['caption'] = $item->getValue()['alt'];
        }
        $item->set('caption', $item_settings[$delta]['caption']);
      }
      // Set Image and Caption Link.
      foreach ($links as $setting => $path) {
        if ($this->getSetting($setting) != '') {
          switch ($this->getSetting($setting)) {
            case 'content':
              $entity = $item->getEntity();
              if (!$entity->isNew()) {
                $uri = $entity->urlInfo();
                $uri = !empty($uri) ? $uri : '';
                $item->set($path, $uri);
              }
              break;

            case 'file':
              foreach ($files as $file_delta => $file) {
                $image_uri = $file->getFileUri();
                $uri = Url::fromUri(file_create_url($image_uri));
                $uri = !empty($uri) ? $uri : '';
                $items[$file_delta]->set($path, $uri);
              }
              break;
          }
        }
      }
    }

    $defaults = $this->defaultSettings();

    if (count($items)) {
      // Only include non-default values to minimize html output.
      $options = [];
      foreach ($defaults as $key => $setting) {
        // Don't pass these to orbit.
        if ($key == 'caption_link' || $key == 'caption' || $key == 'image_style') {
          continue;
        }
        if ($this->getSetting($key) != $setting) {
          $options[$key] = $this->getSetting($key);
        }
      }

      $elements[] = [
        '#theme' => 'field_orbit',
        '#items' => $items,
        '#options' => $options,
        '#entity' => $entity,
        '#image' => $images,
        '#orbit_id' => $orbit_count,
      ];
    }

    return $elements;
  }

  /**
   * Array of animations out options.
   *
   * @return array
   *   Array of animations options.
   */
  protected function getAnimationOutOptions() {
    return [
      "Slide" => [
        "slide-out-down" => $this->t("slide-out-down"),
        "slide-out-left" => $this->t("slide-out-left"),
        "slide-out-up" => $this->t("slide-out-up"),
        "slide-out-right" => $this->t("slide-out-right"),
      ],
      "Fade" => [
        "fade-out" => $this->t("fade-out"),
      ],
      "Hinge" => [
        "hinge-out-from-top" => $this->t("hinge-out-from-top"),
        "hinge-out-from-right" => $this->t("hinge-out-from-right"),
        "hinge-out-from-bottom" => $this->t("hinge-out-from-bottom"),
        "hinge-out-from-left" => $this->t("hinge-out-from-left"),
        "hinge-out-from-middle-x" => $this->t("hinge-out-from-middle-x"),
        "hinge-out-from-middle-y" => $this->t("hinge-out-from-middle-y"),
      ],
      "Scale" => [
        "scale-out-up" => $this->t("scale-out-up"),
        "scale-out-down" => $this->t("scale-out-down"),
      ],
      "Spin" => [
        "spin-out" => $this->t("spin-out"),
        "spin-out-ccw" => $this->t("spin-out-ccw"),
      ],
    ];
  }

  /**
   * Array of animation in options.
   *
   * @return array
   *   Array of animation options.
   */
  protected function getAnimationInOptions() {
    return [
      "Slide" => [
        "slide-in-down" => $this->t("slide-in-down"),
        "slide-in-left" => $this->t("slide-in-left"),
        "slide-in-up" => $this->t("slide-in-up"),
        "slide-in-right" => $this->t("slide-in-right"),
      ],
      "Fade" => [
        "fade-in" => $this->t("fade-in"),
      ],
      "Hinge" => [
        "hinge-in-from-top" => $this->t("hinge-in-from-top"),
        "hinge-in-from-right" => $this->t("hinge-in-from-right"),
        "hinge-in-from-bottom" => $this->t("hinge-in-from-bottom"),
        "hinge-in-from-left" => $this->t("hinge-in-from-left"),
        "hinge-in-from-middle-x" => $this->t("hinge-in-from-middle-x"),
        "hinge-in-from-middle-y" => $this->t("hinge-in-from-middle-y"),
      ],
      "Scale" => [
        "scale-in-up" => $this->t("scale-in-up"),
        "scale-in-down" => $this->t("scale-in-down"),
      ],
      "Spin" => [
        "spin-in" => $this->t("spin-in"),
        "spin-in-ccw" => $this->t("spin-in-ccw"),
      ],
    ];
  }

}
