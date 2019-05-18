<?php

namespace Drupal\messagebird\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\telephone\Plugin\Field\FieldFormatter\TelephoneLinkFormatter;

/**
 * Plugin implementation of the 'telephone_link' formatter.
 *
 * @FieldFormatter(
 *   id = "messagebird_link",
 *   label = @Translation("Telephone link"),
 *   field_types = {
 *     "messagebird"
 *   }
 * )
 */
class MessageBirdLinkFormatter extends TelephoneLinkFormatter {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return array(
      'format' => 'international',
    ) + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $elements['format'] = array(
      '#type' => 'select',
      '#title' => $this->t('Display value as'),
      '#default_value' => $this->getSetting('format'),
      '#options' => array(
        'number' => $this->t('Number'),
        'value' => $this->t('e164'),
        'international' => $this->t('International'),
        'national' => $this->t('National'),
        'rfc3966' => $this->t('rfc3966'),
        'title' => $this->t('Custom title'),
      ),
      '#description' => $this->t('The link itself will be formatted as rfc3966.'),
    );

    $elements = array_merge($elements, parent::settingsForm($form, $form_state));

    $elements['title']['#title'] = t('Title to replace telephone number display');
    $elements['title']['#states'] = array(
      'visible' => array(
        'select[name$="][settings_edit_form][settings][format]"]' => array('value' => 'title'),
      ),
    );

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = array();
    $settings = $this->getSettings();

    $formats = array(
      'number' => $this->t('Number'),
      'value' => $this->t('e164'),
      'international' => $this->t('International'),
      'national' => $this->t('National'),
      'rfc3966' => $this->t('rfc3966'),
    );

    if ($settings['format'] == 'text') {
      $summary[] = $this->t('Link using text: @title', array('@title' => $settings['title']));
    }
    else {
      $summary[] = $this->t('Link using format: @format', array('@format' => $formats[$settings['format']]));
    }

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $element = array();
    $title_setting = $this->getSetting('title');
    $format_setting = $this->getSetting('format');

    foreach ($items as $delta => $item) {
      $title = $format_setting == 'text' ? $title_setting : $item->$format_setting;

      // Render each element as link.
      $element[$delta] = array(
        '#type' => 'link',
        '#title' => $title,
        '#url' => Url::fromUri($item->rfc3966),
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
