<?php

namespace Drupal\global_gateway_country\Plugin\Field\FieldType;

use Drupal\Core\Form\FormStateInterface;
use Drupal\country\Plugin\Field\FieldType\CountryItem as CountryItemOrigin;

/**
 * Class CountryItem.
 *
 * @FieldType(
 *   id = "country",
 *   label = @Translation("Country"),
 *   description = @Translation("Stores the ISO-2 name of a country."),
 *   default_widget = "country_default",
 *   default_formatter = "country_default"
 * )
 *
 * @package Drupal\global_gateway_country\Plugin\Field\FieldType
 */
class CountryItem extends CountryItemOrigin {

  /**
   * {@inheritdoc}
   */
  public function fieldSettingsForm(array $form, FormStateInterface $form_state) {
    return [
      'preselect_user_region_enabled' => [
        '#type'          => 'checkbox',
        '#title'         => $this->t('Preselect to Global Gateway region'),
        '#default_value' => $this->getSettings()['preselect_user_region_enabled'] == '1',
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultFieldSettings() {
    return [
      'preselect_user_region_enabled' => 0,
    ] + parent::defaultFieldSettings();
  }

  /**
   * Custom submit handler for correctly save the custom field settings option.
   *
   * @param array &$form
   *   Form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form state object.
   */
  public static function submitPreselectSetting(array &$form, FormStateInterface $form_state) {
    $value = $form_state->getValue(['default_value_input', 'preselect_user_region_enabled']);
    /** @var \Drupal\field\Entity\FieldConfig $field_config */
    $field_config = \Drupal::routeMatch()->getParameters()->get('field_config');
    $field_config->setSetting('preselect_user_region_enabled', $value)->save();
  }

}
