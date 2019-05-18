<?php

namespace Drupal\experian_validation\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\experian_validation\Services\ExperianValidationService;
use Drupal\Component\Serialization\Json;
use Drupal\Core\State\StateInterface;

/**
 * Plugin implementation of the 'experian_validation_telephone' widget.
 *
 * @FieldWidget(
 *   id = "experian_validation_telephone",
 *   module = "experian_validation",
 *   label = @Translation("Experian Telephone"),
 *   field_types = {
 *     "experian_validation_rgb",
 *     "telephone"
 *   }
 * )
 */
class ExperianTelephoneWidget extends WidgetBase implements ContainerFactoryPluginInterface {
  /**
   * The state keyvalue collection.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;
  /**
   * The Experian service variable.
   *
   * @var \Drupal\experian_validation\Services\ExperianValidationService
   */
  protected $experianValidation;

  /**
   * Constructs Field object.
   *
   * @param string $plugin_id
   *   The plugin_id for the formatter.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The definition of the field to which the formatter is associated.
   * @param array $settings
   *   The formatter settings.
   * @param array $third_party_settings
   *   Third party settings.
   * @param \Drupal\Core\State\StateInterface $state
   *   State Key/Value Object.
   * @param \Drupal\experian_validation\Services\ExperianValidationService $experianValidation
   *   Experian Service Object.
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, array $third_party_settings, StateInterface $state, ExperianValidationService $experianValidation) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $third_party_settings);
    $this->state = $state;
    $this->experianValidation = $experianValidation;
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
      $container->get('state'),
      $container->get('experian_validation.service')
    );
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'placeholder' => '',
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $element['placeholder'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Placeholder'),
      '#default_value' => $this->getSetting('placeholder'),
      '#description' => $this->t('Text that will be shown inside the field until a value is entered. This hint is usually a sample value or a brief description of the expected format.'),
    ];
    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];

    $placeholder = $this->getSetting('placeholder');
    if (!empty($placeholder)) {
      $summary[] = $this->t('Placeholder: @placeholder', ['@placeholder' => $placeholder]);
    }
    else {
      $summary[] = $this->t('No placeholder');
    }

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element['value'] = $element + [
      '#type' => 'tel',
      '#default_value' => isset($items[$delta]->value) ? $items[$delta]->value : NULL,
      '#placeholder' => $this->getSetting('placeholder'),
      '#element_validate' => [[$this, 'validate']],
    ];
    return $element;
  }

  /**
   * Validate the color text field.
   */
  public function validate($element, FormStateInterface $form_state) {
    $phoneNumber = $element['#value'];
    if (strlen($phoneNumber) == 0) {
      $form_state->setValueForElement($element, '');
      return;
    }
    $countryCode = $this->state->get('expPhoneCountry');
    $response = $this->experianValidation->validatePhone($phoneNumber, $countryCode);
    $phoneStatus = Json::decode($response->getBody()->getContents(), TRUE);
    if (isset($phoneStatus['Certainty']) && strtolower($phoneStatus['Certainty']) != 'verified') {
      $form_state->setError($element, $this->t("Entered phone number is not valid."));
    }
  }

}
