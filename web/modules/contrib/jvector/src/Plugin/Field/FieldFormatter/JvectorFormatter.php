<?php

/**
 * @file
 * Contains \Drupal\telephone\Plugin\field\formatter\TelephoneLinkFormatter.
 */

namespace Drupal\jvector\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Plugin implementation of the 'telephone_link' formatter.
 *
 * @FieldFormatter(
 *   id = "jvector_link",
 *   label = @Translation("Jvector"),
 *   field_types = {
 *     "list_integer",
 *     "list_float",
 *     "list_string"
 *   }
 * )
 */
class JvectorFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return array(
      'title' => '',
    ) + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $elements = array();
    $entity_type = 'jvector';
    $options = array();
    $jvectors = entity_load_multiple($entity_type);
    //@todo move this to an entity function?
    foreach ($jvectors AS $jvector_id => $jvector) {
      if (is_array($jvector->colorconfig)) {
        $colorconfigs = $jvector->colorconfig;
        if (!empty($colorconfigs)) {
          foreach ($jvector->colorconfig AS $colorconfig_id => $colorconfig) {
            //@todo Color config selection goes in here
          }
        };
      }
      $options[$jvector_id] = $jvector->label . " -> " . t('Default');
    }

    $elements['jvector'] = array(
      '#type' => 'select',
      '#title' => t('Jvector'),
      '#default_value' => $this->getSetting('jvector', 'jvector'),
      '#options' => $options,
      '#empty_option' => t('- None -'),
      '#description' => t('Choose a Jvector and a configuration scheme to replace the original select field.'),
      '#required' => TRUE,
    );


    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = array();
    $settings = $this->getSettings();

    if (!empty($settings['jvector']) && $jvector = entity_load('jvector', $settings['jvector'])) {
      $summary[] = t('Using Jvector: Link using text: @title', array('@title' => $jvector->label()));
    }
    else {
      $summary[] = t('No valid jvector selected, or Jvector not found.');
    }

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items) {
    $element = array();
    $title_setting = $this->getSetting('title');

    foreach ($items as $delta => $item) {
      // Render each element as link.
      $element[$delta] = array(
        '#type' => 'link',
        // Use custom title if available, otherwise use the telephone number
        // itself as title.
        '#title' => $title_setting ?: $item->value,
        // Prepend 'tel:' to the telephone number.
        '#url' => Url::fromUri('tel:' . rawurlencode(preg_replace('/\s+/', '', $item->value))),
        '#options' => array('external' => TRUE),
      );

      if (!empty($item->_attributes)) {
        $element[$delta]['#options'] += array('attributes' => array());
        $element[$delta]['#options']['attributes'] += $item->_attributes;
        // Unset field item attributes since they have been included in the
        // formatter output and should not be rendered in the field template.
        unset($item->_attributes);
      }
    }

    return $element;
  }
}
