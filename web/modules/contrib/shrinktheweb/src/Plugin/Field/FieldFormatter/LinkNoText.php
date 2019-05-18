<?php

namespace Drupal\shrinktheweb\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * @FieldFormatter(
 *  id = "shrinktheweb_link_no_text",
 *  label = @Translation("[ShrinkTheWeb] No link text"),
 *  field_types = {"link"}
 * )
 */
class LinkNoText extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return array(
      'custom_width' => '',
      'full_length' => '',
      'max_height' => '',
      'native_resolution' => '',
      'widescreen_resolution_y' => '',
      'delay' => '',
      'quality' => ''
    ) + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $elements = parent::settingsForm($form, $form_state);

    $elements['custom_width'] = array(
      '#type' => 'number',
      '#title' => t('Custom Width'),
      '#field_suffix' => t('px'),
      '#default_value' => $this->getSetting('custom_width'),
      '#min' => 1,
      '#description' => t('enter your custom image width, this will override default size '),
      '#size' => 4,
      '#maxlength' => 4,
    );
    $elements['full_length'] = array(
      '#type' => 'select',
      '#title' => $this->t('Full-Length capture'),
      '#default_value' => $this->getSetting('full_length'),
      '#options' => array(
        '1' => $this->t('Enabled'),
        '0' => $this->t('Disabled'),
        '' => $this->t('Not set'),
      ),
    );
    $elements['max_height'] = array(
      '#type' => 'number',
      '#title' => t('Max height'),
      '#field_suffix' => t('px'),
      '#default_value' => $this->getSetting('max_height'),
      '#min' => 1,
      '#description' => t('use if you want to set maxheight for fullsize capture'),
      '#size' => 10,
      '#maxlength' => 10,
    );
    $elements['native_resolution'] = array(
      '#type' => 'number',
      '#title' => t('Native resolution'),
      '#field_suffix' => t('px'),
      '#default_value' => $this->getSetting('native_resolution'),
      '#min' => 1,
      '#description' => t('i.e. 640 for 640x480'),
      '#size' => 4,
      '#maxlength' => 4,
    );
    $elements['widescreen_resolution_y'] = array(
      '#type' => 'number',
      '#title' => t('Widescreen resolution Y'),
      '#field_suffix' => t('px'),
      '#default_value' => $this->getSetting('widescreen_resolution_y'),
      '#min' => 1,
      '#description' => t('i.e. 900 for 1440x900 if 1440 is set for Native resolution'),
      '#size' => 4,
      '#maxlength' => 4,
    );
    $elements['delay'] = array(
      '#type' => 'number',
      '#title' => t('Delay After Load'),
      '#field_suffix' => t('seconds'),
      '#default_value' => $this->getSetting('delay'),
      '#min' => 1,
      '#description' => t('max. 45'),
      '#size' => 2,
      '#maxlength' => 2,
    );
    $elements['quality'] = array(
      '#type' => 'number',
      '#title' => t('Quality'),
      '#field_suffix' => t('%'),
      '#default_value' => $this->getSetting('quality'),
      '#min' => 1,
      '#description' => t('0 .. 100 '),
      '#size' => 3,
      '#maxlength' => 3,
    );

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = array();

    $settings = $this->getSettings();

    if (!empty($settings['custom_width'])) {
      $summary[] = t('Custom Width is @custom_width px', array('@custom_width' => $settings['custom_width']));
    }
    else {
      $summary[] = t('Custom Width is not set');
    }

    if (!empty($settings['full_length'])) {
      if ($settings['full_length'] == 1)
        $summary[] = t('Full-Length capture enabled');
      else
        $summary[] = t('Full-Length capture disabled');
    }
    else {
      $summary[] = t('Full-Length is not set');
    }

    if (!empty($settings['max_height'])) {
      $summary[] = t('Max height is @max_height px', array('@max_height' => $settings['max_height']));
    }
    else {
      $summary[] = t('Max height is not set');
    }

    if (!empty($settings['native_resolution'])) {
      $summary[] = t('Native resolution is @native_resolution px', array('@native_resolution' => $settings['native_resolution']));
    }
    else {
      $summary[] = t('Native resolution is not set');
    }

    if (!empty($settings['widescreen_resolution_y'])) {
      $summary[] = t('Widescreen resolution Y is @widescreen_resolution_y px', array('@widescreen_resolution_y' => $settings['widescreen_resolution_y']));
    }
    else {
      $summary[] = t('Widescreen resolution Y is not set');
    }

    if (!empty($settings['delay'])) {
      $summary[] = t('Delay is @delay seconds', array('@delay' => $settings['delay']));
    }
    else {
      $summary[] = t('Delay is not set');
    }

    if (!empty($settings['quality'])) {
      $summary[] = t('Quality is @quality %', array('@quality' => $settings['quality']));
    }
    else {
      $summary[] = t('Quality is not set');
    }

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements (FieldItemListInterface $items, $langcode) {
    $settings = $this->getSettings();

    $elements = array();

    foreach ($items as $delta => $item) {

      $url = $item->uri;
      $elements[$delta] = array(
        '#theme' => 'shrinktheweb_image',
        '#url' => $url,
        '#image_path' => '',
      );

      if (!empty($settings['custom_width']))
        $elements[$delta]['#custom_width'] = $settings['custom_width'];

      if (!empty($settings['full_length']))
        $elements[$delta]['#full_length'] = $settings['full_length'];

      if (!empty($settings['max_height']))
        $elements[$delta]['#max_height'] = $settings['max_height'];

      if (!empty($settings['native_resolution']))
        $elements[$delta]['#native_resolution'] = $settings['native_resolution'];

      if (!empty($settings['widescreen_resolution_y']))
        $elements[$delta]['#widescreen_resolution_y'] = $settings['widescreen_resolution_y'];

      if (!empty($settings['delay']))
        $elements[$delta]['#delay'] = $settings['delay'];

      if (!empty($settings['quality']))
        $elements[$delta]['#quality'] = $settings['quality'];
    }

    return $elements;
  }

}
