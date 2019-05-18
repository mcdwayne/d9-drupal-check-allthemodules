<?php

namespace Drupal\beautytips_manager\Form;

use Drupal\Core\Form\FormInterface;
use Drupal\Core\Form\FormStateInterface;

class CustomStylesEditForm implements FormInterface {

  protected $style;

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'beautytips_manager_custom_styles_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $id = NULL) {
    if (!is_null($id)) {
      $this->style = beautytips_manager_get_custom_style($id);
      $style_map = beautytips_manager_style_mapping();
      $style_options = $style_map['options'];
      $css_style_options = $style_map['css_options'];
    }

    $form = [];
    $form['name'] = [
      '#type' => 'textfield',
      '#title' => t('Style Name'),
      '#description' => t('It must contain only alphanumeric characters and underscores.'),
      '#default_value' => isset($this->style->name) ? $this->style->name : '',
    ];
    // TODO: Add this into mapping
    $style_info = [
      'fill' => t('background color (string - html color)'),
      'strokeWidth' => t('width of border (integer)'),
      'strokeStyle' => t('color of border (string - html color)'),
      'width' => t('width of popup (number with px or em)'),
      'padding' => t('space between content and border (number with px em)'),
      'cornerRadius' => t('Controls roundness of corners (integer)'),
      'spikeGirth' => t('thickness of spike (integer)'),
      'spikeLength' => t('length of spike (integer)'),
      'shadowBlur' => t('Size of popup shadow (integer)'),
      'shadowColor' => t('Color of popup shadow (string - html color)'),
    ];
    $form['custom_styles'] = [
      '#type' => 'fieldset',
      '#title' => t('Custom Style Options'),
      '#description' => t('<div id="beautytips-popup-changes"><div id="beauty-click-text"><p></p></div></div>'),
      '#attributes' => ['class' => ['bt-custom-styles']],
      '#tree' => TRUE,
    ];
    foreach ($style_info as $option => $description) {
      $form['custom_styles'][$option] = [
        '#title' => $option,
        '#description' => $description,
        '#type' => 'textfield',
        '#default_value' => (isset($style_options) && isset($this->style->{$style_options[$option]}) && !is_null($this->style->{$style_options[$option]})) ? $this->style->{$style_options[$option]} : '',
      ];
    }
    $form['custom_styles']['shadow'] = [
      '#title' => 'shadow',
      '#description' => t('Whether or not the popup has a shadow'),
      '#type' => 'radios',
      '#options' => [
        'default' => t('Default'),
        'shadow' => t('Shadow On'),
        'no_shadow' => t('Shadow Off'),
      ],
      '#attributes' => ['class' => ['beautytips-options-shadow']],
      '#default_value' => isset($this->style->shadow) ? $this->style->shadow : 'default',
    ];
    $form['custom_styles']['cssClass'] = [
      '#title' => 'cssClass',
      '#description' => t('The class that will be applied to the box wrapper div (of the TIP)'),
      '#type' => 'textfield',
      '#default_value' => isset($this->style->css_class) ? $this->style->css_class : '',
    ];
    $css_style_info = ['color', 'fontFamily', 'fontWeight', 'fontSize'];
    $form['custom_styles']['css-styles'] = [
      '#type' => 'fieldset',
      '#title' => t('Font Styling'),
      '#description' => t('Enter css options for changing the font style'),
      '#attributes' => ['class' => ['beautytips-css-styling']],
      '#collapsible' => FALSE,
    ];
    foreach ($css_style_info as $option) {
      $form['custom_styles']['css-styles'][$option] = [
        '#title' => $option,
        '#type' => 'textfield',
        '#default_value' => (isset($css_style_options) && isset($this->style->{$css_style_options[$option]}) && !is_null($this->style->{$css_style_options[$option]})) ? $this->style->{$css_style_options[$option]} : '',
      ];
    }

    beautytips_add_beautytips($form);
    $form['#attached']['library'][] = 'beautytips_manager/colorpicker';
    $form['#attached']['library'][] = 'beautytips/beautytips.bt-custom-style';
    $form['save'] = [
      '#type' => 'submit',
      '#value' => t('Save'),
    ];
    return $form;
  }

  public function validateForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    if (empty($values['name'])) {
      $form_state->setErrorByName('name', t('You must name this custom style.'));
    }
    if (preg_match('/[^a-zA-Z0-9_]/', $values['name'])) {
      $form_state->setErrorByName('name', t('Style name must be alphanumeric or underscores only.'));
    }

    $integer_fields = [
      'strokeWidth' => 'style',
      'cornerRadius' => 'style',
      'spikeGirth' => 'style',
      'spikeLength' => 'style',
      'shadowBlur' => 'style',
    ];
    $pixel_fields = [
      'width' => 'style',
      'padding' => 'style',
      'fontSize' => 'css',
    ];

    // Validate fields that expect a number
    foreach ($integer_fields as $name => $type) {
      $value = $type == 'css' ? $values['custom_styles']['css-styles'][$name] : $values['custom_styles'][$name];
      if ($value) {
        if (!ctype_digit($value)) {
          $error_element = $type == 'css' ? 'custom_styles][css-styles][' . $name : 'custom_styles][' . $name;
          $form_state->setErrorByName($error_element, t('You need to enter an integer value for <em>@name</em>', ['@name' => $name]));
        }
      }
    }

    // Validate fields that expect a number and unit
    foreach ($pixel_fields as $name => $type) {
      $value = $type == 'css' ? $values['custom_styles']['css-styles'][$name] : $values['custom_styles'][$name];
      if ($value) {
        $unit = substr($value, -2, 2);
        $value = str_replace(['px', ' ', 'em'], '', $value);
        if (!is_numeric($value) || (!$value && $value != 0) || !in_array($unit, [
            'px',
            'em',
          ])) {
          $error_element = $type == 'css' ? 'custom_styles][css-styles][' . $name : 'custom_styles][' . $name;
          $form_state->setErrorByName($error_element, t('You need to enter a numeric value for <em>@name</em>, followed by <em>px</em> or <em>em</em>', ['@name' => $name]));
        }
      }
    }
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    $style = new \stdClass;
    $style->name = $form_state->getValue('name');
    $mapping = beautytips_manager_style_mapping();
    foreach ($form_state->getValue('custom_styles') as $custom_style => $value) {
      if (!is_array($value)) {
        if (isset($mapping['options'][$custom_style])) {
          $style->{$mapping['options'][$custom_style]} = $value;
        }
      }
      else {
        if ($custom_style == 'css-styles') {
          foreach ($value as $css_style => $css_value) {
            if (isset($mapping['css_options'][$css_style])) {
              $style->{$mapping['css_options'][$css_style]} = $css_value;
            }
          }
        }
      }
    }
    if (!is_null($this->style)) {
      $style->id = $this->style->id;
    }
    beautytips_manager_save_custom_style($style);
    \Drupal::cache()->delete('beautytips:beautytips-styles');
    $form_state->setRedirect('beautytips_manager.customStyles');
  }
}
