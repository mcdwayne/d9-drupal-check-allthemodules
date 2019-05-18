<?php

namespace Drupal\global_gateway_address\Plugin\Field\FieldType;

use Drupal\address\Plugin\Field\FieldType\CountryItem as CountryItemOrigin;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class CountryItem.
 *
 * @FieldType(
 *   id = "address_country",
 *   label = @Translation("Country"),
 *   description = @Translation("An entity field containing a country"),
 *   category = @Translation("Address"),
 *   default_widget = "address_country_default",
 *   default_formatter = "address_country_default"
 * )
 *
 * @package Drupal\global_gateway_address\Plugin\Field\FieldType
 */
class CountryItem extends CountryItemOrigin {
  use PreselectSaveTrait;

  /**
   * {@inheritdoc}
   */
  public function fieldSettingsForm(array $form, FormStateInterface $form_state) {
    $element['preselect_user_region_enabled'] = [
      '#type'          => 'checkbox',
      '#title'         => $this->t('Preselect to Global Gateway region'),
      '#default_value' => $this->getSettings()['preselect_user_region_enabled'] == '1',
    ];
    return $element + parent::fieldSettingsForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultFieldSettings() {
    return ['preselect_user_region_enabled' => 0]
      + parent::defaultFieldSettings();
  }

}
