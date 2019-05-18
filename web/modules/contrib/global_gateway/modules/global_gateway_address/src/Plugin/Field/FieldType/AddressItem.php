<?php

namespace Drupal\global_gateway_address\Plugin\Field\FieldType;

use Drupal\address\Plugin\Field\FieldType\AddressItem as AddressItemOrigin;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class CountryItem.
 *
 * @FieldType(
 *   id = "address",
 *   label = @Translation("Address"),
 *   description = @Translation("An entity field containing a postal address"),
 *   category = @Translation("Address"),
 *   default_widget = "address_default",
 *   default_formatter = "address_default"
 * )
 *
 * @package Drupal\global_gateway_address\Plugin\Field\FieldType
 */
class AddressItem extends AddressItemOrigin {
  use PreselectSaveTrait;

  /**
   * {@inheritdoc}
   */
  public function fieldSettingsForm(array $form, FormStateInterface $form_state) {
    $element['preselect_user_region_enabled'] = [
      '#type'          => 'checkbox',
      '#title'         => $this->t('Preselect country to Global Gateway region'),
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
