<?php

namespace Drupal\address_dawa\Plugin\Field\FieldWidget;

use Drupal\Component\Utility\Crypt;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\address_dawa\AddressDawaInterface;
use Drupal\address_dawa\Plugin\Validation\Constraint\AddressDawaConstraint;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the 'address_dawa' widget.
 *
 * @FieldWidget(
 *   id = "address_dawa",
 *   label = @Translation("Address DAWA"),
 *   field_types = {
 *     "address_dawa"
 *   },
 * )
 */
class AddressDawaWidget extends WidgetBase implements ContainerFactoryPluginInterface {

  /**
   * AddressDawa service.
   *
   * @var \Drupal\address_dawa\AddressDawaInterface
   */
  protected $addressDawa;

  /**
   * {@inheritdoc}
   */
  public function __construct(
    $plugin_id,
    $plugin_definition,
    FieldDefinitionInterface $field_definition,
    array $settings,
    array $third_party_settings,
    AddressDawaInterface $address_dawa
  ) {
    $this->addressDawa = $address_dawa;
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $third_party_settings);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $plugin_id,
      $plugin_definition,
      $configuration['field_definition'],
      $configuration['settings'],
      $configuration['third_party_settings'],
      $container->get('address_dawa.address_dawa')
    );
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return array(
      'size' => 60,
      'placeholder' => '',
    ) + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $element['size'] = array(
      '#type' => 'number',
      '#title' => $this->t('Size of dawa address textfield'),
      '#default_value' => $this->getSetting('size'),
      '#required' => TRUE,
      '#min' => 1,
    );
    $element['placeholder'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Placeholder'),
      '#default_value' => $this->getSetting('placeholder'),
      '#description' => $this->t('Text that will be shown inside the DAWA address field until a value is entered. This hint is usually a sample value or a brief description of the expected format.'),
    );
    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = array();

    $summary[] = $this->t('DAWA address field size: @size', array('@size' => $this->getSetting('size')));
    $placeholder = $this->getSetting('placeholder');
    if (!empty($placeholder)) {
      $summary[] = $this->t('Placeholder: @placeholder', array('@placeholder' => $placeholder));
    }

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element['address'] = $element + array(
      '#type' => 'textfield',
      '#default_value' => isset($items[$delta]->value) ? $items[$delta]->value : NULL,
      '#size' => $this->getSetting('size'),
      '#placeholder' => $this->getSetting('placeholder'),
      '#autocomplete_route_name' => $this->getFieldSetting('address_type') == 'adresse' ? 'fetch.dawa.adresse' : 'fetch.dawa.adgangsadresse',
      '#maxlength' => 255,
    );

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function massageFormValues(array $values, array $form, FormStateInterface $form_state) {
    foreach ($values as &$value) {
      if (empty($value['address'])) {
        continue;
      }
      $options = [
        'q' => $value['address'],
      ];
      $address_type = $this->getFieldSetting('address_type');
      $result = $this->addressDawa->fetchAddress($options, $address_type);
      if (empty($result) && !$this->getFieldSetting('allow_non_danish_address')) {
        // Address can not be found from DAWA.
        $value += [
          'type' => AddressDawaConstraint::ADDRESS_CAN_NOT_BE_FOUND['error_code'],
          'value' => $value['address'],
        ];
        continue;
      }

      // Enable ADDRESS_MULTIPLE_LOCATION constraint if configured to do so.
      if (count($result) > 1 && !$this->getFieldSetting('allow_non_unique_address')) {
        // Query results multiple addresses from DAWA.
        $value += [
          'type' => AddressDawaConstraint::ADDRESS_MULTIPLE_LOCATION['error_code'],
          'value' => $value['address'],
        ];
        continue;
      }

      if (!empty($result)) {
        $coordinate = $address_type == 'adresse' ? $result[0]->adgangsadresse->adgangspunkt->koordinater : $result[0]->adgangspunkt->koordinater;
        $value += [
          'type' => $address_type,
          'id' => $result[0]->id,
          'status' => (int) $result[0]->status,
          'value' => $value['address'],
          'lat' => (float) $coordinate[1],
          'lng' => (float) $coordinate[0],
          'data' => (array) $result[0],
        ];
      }
      else {
        $value += [
          'type' => $address_type,
          'id' => 'non_dawa_' . Crypt::hashBase64($value['address']),
          'status' => 1,
          'value' => $value['address'],
          'lat' => 1,
          'lng' => 1,
          'data' => [$value['address']],
        ];
      }

    }
    return $values;
  }

}
