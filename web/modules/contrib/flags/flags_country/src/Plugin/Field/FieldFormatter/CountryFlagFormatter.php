<?php

namespace Drupal\flags_country\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Template\Attribute;
use Drupal\country\Plugin\Field\FieldFormatter\CountryDefaultFormatter;

/**
 * Plugin implementation of the 'country' formatter.
 *
 * @FieldFormatter(
 *   id = "country_flag",
 *   label = @Translation("Country with flag"),
 *   field_types = {
 *     "country"
 *   }
 * )
 */
class CountryFlagFormatter extends CountryDefaultFormatter {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    $settings = array();

    // Fall back to field settings by default.
    $settings['flag_display'] = 'flag-before';

    return $settings;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $form = parent::settingsForm($form, $form_state);

    $form['flag_display'] = array(
      '#type' => 'select',
      '#title' => $this->t('Output format'),
      '#default_value' => $this->getSetting('flag_display'),
      '#options' => $this->getOutputFormats(),
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = parent::settingsSummary();

    $format = $this->getSetting('flag_display');
    $formats = $this->getOutputFormats();

    $summary[] = $formats[$format];

    return $summary;
  }

  /**
   * Gets available view formats.
   *
   * @return string[]
   */
  protected function getOutputFormats() {
    return array(
      'flag-before' => $this->t('Flag before country name'),
      'flag-after' => $this->t('Flag after country name'),
      'flag-instead' => $this->t('Replace country name with flag'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $countries = \Drupal::service('country.field.manager')->getSelectableCountries($this->fieldDefinition);
    $elements  = parent::viewElements($items, $langcode);

    $format = $this->getSetting('flag_display');
    $attributes = new Attribute(array('class' => array($format)));

    foreach ($items as $delta => $item) {
      if (isset($countries[$item->value])) {
        unset($elements[$delta]['#markup']);
        if ('flag-instead' != $format) {
          $elements[$delta]['country'] = array('#markup' => $countries[$item->value]);
        }

        $elements[$delta]['flag'] = array(
          '#theme' => 'flags',
          '#code' => strtolower($item->value),
          '#attributes' => clone $attributes,
          '#source' => 'country',
        );
      }

      $elements[$delta]['#prefix'] = '<div class="field__flags__item">';
      $elements[$delta]['#suffix'] = '</div>';
    }

    return $elements;
  }

}
