<?php

namespace Drupal\jquery_minicolors\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Field\Plugin\Field\FieldWidget\StringTextfieldWidget;

/**
 * Plugin implementation of the 'jquery_minicolors_widget' widget.
 *
 * @FieldWidget(
 *   id = "jquery_minicolors_widget",
 *   label = @Translation("Jquery minicolors widget"),
 *   field_types = {
 *     "string"
 *   }
 * )
 */
class JqueryMinicolorsWidget extends StringTextfieldWidget {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return array(
      'size' => 25,
      'placeholder' => '',
      'control' => 'hue',
      'format' => 'hex',
      'opacity' => 0,
      'swatches' => '',
      'position' => 'bottom left',
      'theme' => 'default',
      'animation_speed' => 50,
      'animation_easing' => 'swing',
      'change_delay' => 0,
      'letter_case' => 'lowercase',
      'show_speed' => 100,
      'hide_speed' => 100,
      'keywords' => '',
      'inline' => 0,
    ) + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $elements = parent::settingsForm($form, $form_state);

    $control = [
      'hue' => $this->t('Hue'),
      'brightness' => $this->t('Brightness'),
      'saturation' => $this->t('Saturation'),
      'wheel' => $this->t('Wheel'),
    ];
    $elements['control'] = array(
      '#type' => 'select',
      '#title' => $this->t('Control'),
      '#options' => $control,
      '#default_value' => $this->getSetting('control'),
      '#description' => $this->t('Determines the type of control.'),
    );

    $format = [
      'hex' => $this->t('Hexadecimal'),
      'rgb' => $this->t('RGB notation'),
    ];
    $elements['format'] = array(
      '#type' => 'select',
      '#title' => $this->t('Format'),
      '#options' => $format,
      '#default_value' => $this->getSetting('format'),
      '#description' => $this->t('The format miniColors should use.'),
    );

    $elements['opacity'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Opacity'),
      '#empty' => 0,
      '#return_value' => 1,
      '#default_value' => $this->getSetting('opacity'),
      '#description' => $this->t('Check this option to enable the opacity slider.'),
    );

    $elements['swatches'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Swatches'),
      '#default_value' => $this->getSetting('swatches'),
      '#description' => $this->t('A list separated by pipe of colors, in either rgb(a) or hex format, that will show up under the main color grid. There can be only up to 7 colors.'),
    );

    $position = [
      'bottom left' => $this->t('Bottom left'),
      'bottom right' => $this->t('Bottom right'),
      'top left' => $this->t('Top left'),
      'top right' => $this->t('Top right'),
    ];
    $elements['position'] = array(
      '#type' => 'select',
      '#title' => $this->t('Position'),
      '#options' => $position,
      '#default_value' => $this->getSetting('position'),
      '#description' => $this->t('Sets the position of the dropdown.'),
    );

    $theme = [
      'default' => $this->t('Default'),
      'bootstrap' => $this->t('Bootstrap'),
    ];
    $elements['theme'] = array(
      '#type' => 'select',
      '#title' => $this->t('Theme'),
      '#options' => $theme,
      '#default_value' => $this->getSetting('theme'),
      '#description' => $this->t('jQuery minicolors library provide support for bootstrap theme. Select the theme you want to use for prefixing the selectors.'),
    );

    $elements['inline'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Inline'),
      '#empty' => 0,
      '#return_value' => 1,
      '#default_value' => $this->getSetting('inline'),
      '#description' => $this->t('Check this option to force the color picker to appear inline.'),
    );

    $elements['animation_speed'] = array(
      '#type' => 'number',
      '#title' => $this->t('Animation Speed'),
      '#default_value' => $this->getSetting('animation_speed'),
      '#description' => $this->t('The animation speed, in milliseconds, of the sliders when the user taps or clicks a new color. Set to 0 for no animation.'),
    );

    $elements['animation_easing'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Animation Easing'),
      '#default_value' => $this->getSetting('animation_easing'),
      '#description' => $this->t('The easing to use when animating the sliders.'),
    );

    $elements['change_delay'] = array(
      '#type' => 'number',
      '#title' => $this->t('Change Delay'),
      '#default_value' => $this->getSetting('change_delay'),
      '#description' => $this->t('The time, in milliseconds, to defer the change event from firing while the user makes a selection. This is useful for preventing the change event from firing frequently as the user drags the color picker around.'),
    );

    $letter_case = [
      'uppercase' => $this->t('Uppercase'),
      'lowercase' => $this->t('Lowercase'),
    ];
    $elements['letter_case'] = array(
      '#type' => 'select',
      '#title' => $this->t('Letter Case'),
      '#options' => $letter_case,
      '#default_value' => $this->getSetting('letter_case'),
      '#description' => $this->t('Determines the letter case of the hex code value.'),
    );

    $elements['show_speed'] = array(
      '#type' => 'number',
      '#title' => $this->t('Show speed'),
      '#default_value' => $this->getSetting('show_speed'),
      '#description' => $this->t('The speed, in milliseconds, at which to show the color picker.'),
    );

    $elements['hide_speed'] = array(
      '#type' => 'number',
      '#title' => $this->t('Hide speed'),
      '#default_value' => $this->getSetting('hide_speed'),
      '#description' => $this->t('The speed, in milliseconds, at which to hide the color picker.'),
    );

    $elements['keywords'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Keywords'),
      '#default_value' => $this->getSetting('keywords'),
      '#description' => $this->t('A comma-separated list of keywords that the control should accept (e.g. inherit, transparent, initial).'),
    );

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];

    $summary[] = t('Textfield size: @size', array('@size' => $this->getSetting('size')));
    if (!empty($this->getSetting('placeholder'))) {
      $summary[] = t('Placeholder: @placeholder', array('@placeholder' => $this->getSetting('placeholder')));
    }
    $summary[] = t('Control: @control', array('@control' => $this->getSetting('control')));
    $summary[] = t('Format: @format', array('@format' => $this->getSetting('format')));
    $summary[] = t('Opacity: @opacity', array('@opacity' => $this->getSetting('opacity') ? 'true' : 'false'));
    if (!empty($this->getSetting('swatches'))) {
      $summary[] = t('Swatches: @swatches', array('@swatches' => $this->getSetting('swatches')));
    }

    $summary[] = t('Position: @position', array('@position' => $this->getSetting('position')));
    $summary[] = t('Theme: @theme', array('@theme' => $this->getSetting('theme')));

    if (!empty($this->getSetting('animation_speed'))) {
      $summary[] = t('Animation speed: @animation_speed', array('@animation_speed' => $this->getSetting('animation_speed')));
    }
    if (!empty($this->getSetting('animation_easing'))) {
      $summary[] = t('Animation easing: @animation_easing', array('@animation_easing' => $this->getSetting('animation_easing')));
    }
    $summary[] = t('Change delay: @change_delay', array('@change_delay' => $this->getSetting('change_delay')));

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element['value'] = $element + array(
      '#type' => 'textfield',
      '#default_value' => isset($items[$delta]->value) ? $items[$delta]->value : NULL,
      '#size' => $this->getSetting('size'),
      '#placeholder' => $this->getSetting('placeholder'),
      '#maxlength' => $this->getFieldSetting('max_length'),
      '#attributes' => [
        'class' => ['mini-colors'],
        'data-control' => $this->getSetting('control'),
        'data-format' => $this->getSetting('format'),
        'data-opacity' => $this->getSetting('opacity'),
        'data-position' => $this->getSetting('position'),
        'data-theme' => $this->getSetting('theme'),
        'data-letter-case' => $this->getSetting('letter_case'),
        'data-change-delay' => $this->getSetting('change_delay'),
        'data-show-speed' => $this->getSetting('show_speed'),
        'data-hide-speed' => $this->getSetting('hide_speed'),
      ],
    );

    if ($this->getSetting('swatches')) {
      // Remove all spaces.
      $swatches = preg_replace('/\s+/', '', $this->getSetting('swatches'));
      $element['value']['#attributes']['data-swatches'] = $swatches;
    }

    if ($this->getSetting('keywords')) {
      // Remove all spaces.
      $keywords = preg_replace('/\s+/', '', $this->getSetting('keywords'));
      $element['value']['#attributes']['data-keywords'] = $keywords;
    }

    if ($this->getSetting('animation_speed')) {
      $element['value']['#attributes']['data-animation-speed'] = $this->getSetting('animation_speed');
    }

    if ($this->getSetting('animation_easing')) {
      $element['value']['#attributes']['data-animation-easing'] = $this->getSetting('animation_easing');
    }

    if ($this->getSetting('inline')) {
      $element['value']['#attributes']['data-inline'] = $this->getSetting('inline');
    }

    if ($this->getSetting('size')) {
      $element['value']['#attributes']['data-size'] = $this->getSetting('size');
    }

    $element['#attached']['library'][] = 'jquery_minicolors/jquery_minicolors';

    return $element;
  }

}
