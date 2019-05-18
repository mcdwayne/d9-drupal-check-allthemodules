<?php

namespace Drupal\amp;

use Drupal\views\Plugin\views\style\StylePluginBase;

/**
 * AMP Form trait.
 *
 * Form elements used on many different AMP components.
 */
trait AmpFormTrait {

  /**
   * All settings.
   *
   * @return array
   *   All components settings provided by this trait.
   */
  public function allSettings() {
    return [
      'layout' => $this->t('Layout'),
      'width' => $this->t('Width'),
      'height' => $this->t('Height'),
      'autoplay' => $this->t('Autoplay'),
      'controls' => $this->t('Controls'),
      'loop' => $this->t('Loop'),
    ];
  }

  /**
   * Update the summary.
   *
   * Update the field formatter summary for all settings provided by this trait.
   */
  public function addToSummary($summary) {
    $settings = $this->allSettings();
    foreach ($settings as $setting => $label) {
      $value = $this->getSetting($setting);
      if (isset($value)) {
        if ($setting == 'width') {
          if ($this->widthError($this->getSetting('width'), $this->getSetting('layout'))) {
            $value = $this->t('INVALID!');
          }
        }
        if ($setting == 'height') {
          if ($this->heightError($this->getSetting('height'), $this->getSetting('layout'))) {
            $value = $this->t('INVALID!');
          }
        }
        if (!empty($value)) {
          $summary[] = $label . $this->t(': :value', [':value' => $value]);
        }
      }
    }
    return $summary;
  }

  /**
   * The #states layout selector.
   *
   * @return string
   *   A selector that can be used with #states to adjust options based on the
   *   selected layout.
   */
  public function layoutSelector() {
    // The layout selector varies depending on whether this is a views style
    // plugin or a field formatter plugin.
    if ($this instanceof StylePluginBase) {
      return ':input[name="style_options[settings][layout]"]';
    }
    else {
      return ':input[name="fields[' . $this->fieldDefinition->getName() . '][settings_edit_form][settings][layout]"]';
    }
  }

  /**
   * AMP Layouts.
   *
   * @return array
   *   The names of all possible AMP layouts, formatted as an option list.
   */
  public function allLayouts() {
    $layouts = [
      'responsive' => 'responsive',
      'fill' => 'fill',
      'fixed' => 'fixed',
      'fixed-height' => 'fixed-height',
      'flex-item' => 'flex-item',
      'intrinsic' => 'intrinsic',
      'nodisplay' => 'nodisplay',
      'container' => 'container',
    ];
    return $layouts;
  }

  /**
   * LibraryDescription.
   *
   * @return array
   *   Links to information about the AMP components used by the parent class.
   */
  public function libraryDescription() {
    $ampService = \Drupal::service('amp.utilities');
    return $ampService->libraryDescription($this->getLibraries());
  }

  /**
   * The layout form element.
   *
   * @return array
   *   A form element.
   */
  public function layoutElement() {
    $info_url = 'https://www.ampproject.org/docs/guides/responsive/control_layout.html';
    $element = [
      '#title' => t('AMP Layout'),
      '#type' => 'select',
      '#default_value' => $this->getSetting('layout'),
      '#empty_option' => t('- None -'),
      '#options' => $this->getLayouts(),
      '#description' => $this->t('<a href=":url" target="_blank">AMP Layout Information</a>', [':url' => $info_url]),
    ];
    return $element;
  }

  /**
   * The width form element.
   *
   * @return array
   *   A form element.
   */
  public function widthElement() {
    $element = [
      '#type' => 'number',
      '#title' => t('Width'),
      '#size' => 10,
      '#default_value' => $this->getSetting('width'),
      '#description' => $this->t('Width of the item in pixels, not percent. With the responsive layout you can set the aspect ratio instead, i.e. width: 16, height: 9.'),
      '#states' => ['visible' => [
        [$this->layoutSelector() => ['value' => 'fixed']],
        [$this->layoutSelector() => ['value' => 'intrinsic']],
        [$this->layoutSelector() => ['value' => 'responsive']],
      ]],
    ];
    return $element;
  }

  /**
   * Limit the width based on the layout.
   *
   * @param integer $width
   *   The potential value.
   *
   * @return string
   *   Either the value or 'auto' if the value is not applicable.
   *
   * @see https://www.ampproject.org/docs/design/responsive/control_layout#the-layout-attribute
   */
  public function validWidth($width, $layout) {
    if (empty($width)) {
      return 'auto';
    }
    switch ($layout) {
      case 'fixed':
      case 'intrinsic':
      case 'responsive':
        return $width;
      default:
        return 'auto';
    }
  }

  /**
   * See if selected width is invalid based on the selected layout.
   *
   * @param integer $width
   *   The setting value.
   *
   * @return bool
   *   Either TRUE or FALSE.
   */
  public function widthError($width, $layout) {
    // If the selected layout expects a numeric value and the current value is
    // empty, the width is invalid.
    return empty($width) && $this->validWidth(1, $layout) == 1;
  }

  /**
   * The height form element.
   *
   * @return array
   *   A form element.
   */
  public function heightElement() {
    $element = [
      '#type' => 'number',
      '#title' => t('Height'),
      '#size' => 10,
      '#default_value' => $this->getSetting('height'),
      '#description' => $this->t('Width of the item in pixels, not percent. With the responsive layout you can set the aspect ratio instead, i.e. width: 16, height: 9.'),
      '#states' => ['visible' => [
        [$this->layoutSelector() => ['value' => 'fixed']],
        [$this->layoutSelector() => ['value' => 'fixed-height']],
        [$this->layoutSelector() => ['value' => 'intrinsic']],
        [$this->layoutSelector() => ['value' => 'responsive']],
      ]],
    ];
    return $element;
  }

  /**
   * Limit the height based on the layout.
   *
   * @param integer $height
   *   The potential value.
   *
   * @return string
   *   Either the value or 'auto' if the value is not applicable.
   *
   * @see https://www.ampproject.org/docs/design/responsive/control_layout#the-layout-attribute
   */
  public function validHeight($height, $layout) {
    if (empty($height)) {
      return 'auto';
    }
    switch ($layout) {
      case 'fixed':
      case 'fixed-height':
      case 'intrinsic':
      case 'responsive':
        return $height;
      default:
        return 'auto';
    }
  }

  /**
   * See if selected height is invalid based on the selected layout.
   *
   * @param integer $height
   *   The setting value.
   *
   * @return bool
   *   Either TRUE or FALSE.
   */
  public function heightError($height, $layout) {
    // If the selected layout expects a numeric value and the current value is
    // empty, the height is invalid.
    return empty($height) && $this->validHeight(1, $layout) == 1;
  }

  /**
   * The autoplay form element.
   *
   * @return array
   *   A form element.
   */
  public function autoplayElement() {
    $element = [
      '#title' => $this->t('Autoplay'),
      '#type' => 'checkbox',
      '#description' => $this->t('Autoplay the videos for users without the "never autoplay videos" permission. Roles with this permission will bypass this setting.'),
      '#default_value' => $this->getSetting('autoplay'),
    ];
    return $element;
  }

  /**
   * The autoplay setting, taking into account user permissions.
   */
  public function validAutoplay() {
    return $this->currentUser->hasPermission('never autoplay videos') ? FALSE : $this->getSetting('autoplay');
  }
  /**
   * The controls form element.
   *
   * @return array
   *   A form element.
   */
  public function controlsElement() {
    $element = [
      '#title' => $this->t('Controls'),
      '#type' => 'checkbox',
      '#description' => $this->t('Display video controls to users.'),
      '#default_value' => $this->getSetting('controls'),
    ];
    return $element;
  }

  /**
   * The loop form element.
   *
   * @return array
   *   A form element.
   */
  public function loopElement() {
    $element = [
      '#title' => $this->t('Loop'),
      '#type' => 'checkbox',
      '#description' => $this->t('Loop the video back to the beginning when it completes.'),
      '#default_value' => $this->getSetting('loop'),
    ];
    return $element;
  }

}
