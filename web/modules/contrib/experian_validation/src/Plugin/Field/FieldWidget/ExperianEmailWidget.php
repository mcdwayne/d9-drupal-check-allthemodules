<?php

namespace Drupal\experian_validation\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element\Email;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\experian_validation\Services\ExperianValidationService;
use Drupal\Component\Serialization\Json;

/**
 * Plugin implementation of the 'experian_validation_email' widget.
 *
 * @FieldWidget(
 *   id = "experian_validation_email",
 *   module = "experian_validation",
 *   label = @Translation("Experian Email"),
 *   field_types = {
 *     "experian_validation_rgb",
 *     "email"
 *   }
 * )
 */
class ExperianEmailWidget extends WidgetBase implements ContainerFactoryPluginInterface {

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
   * @param \Drupal\experian_validation\Services\ExperianValidationService $experianValidation
   *   Experian Service Object.
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, array $third_party_settings, ExperianValidationService $experianValidation) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $third_party_settings);
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
      $container->get('experian_validation.service')
    );
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'size' => 60,
      'placeholder' => '',
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $element['size'] = [
      '#type' => 'number',
      '#title' => $this->t('Textfield size'),
      '#default_value' => $this->getSetting('size'),
      '#required' => TRUE,
      '#min' => 1,
    ];
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
    $summary[] = $this->t('Textfield size: @size', ['@size' => $this->getSetting('size')]);

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element['value'] = $element + [
      '#type' => 'email',
      '#default_value' => isset($items[$delta]->value) ? $items[$delta]->value : NULL,
      '#placeholder' => $this->getSetting('placeholder'),
      '#size' => $this->getSetting('size'),
      '#maxlength' => Email::EMAIL_MAX_LENGTH,
      '#element_validate' => [[$this, 'validate']],
    ];
    return $element;
  }

  /**
   * Validate the color text field.
   */
  public function validate($element, FormStateInterface $form_state) {
    $emailAddress = $element['#value'];
    if (strlen($emailAddress) == 0) {
      $form_state->setValueForElement($element, '');
      return;
    }
    $response = $this->experianValidation->validateEmail($emailAddress);
    $emailStatus = Json::decode($response->getBody()->getContents(), TRUE);
    if (isset($emailStatus['Certainty']) && strtolower($emailStatus['Certainty']) != 'verified') {
      $form_state->setError($element, $this->t("Entered email address is not verified or not reachable."));
    }
  }

}
