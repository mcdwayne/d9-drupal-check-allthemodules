<?php

namespace Drupal\xero\Plugin\Field\FieldWidget;

use Drupal\xero\Plugin\Field\FieldType\XeroReference;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\TypedData\TypedDataManager;
use Drupal\Component\Utility\SafeMarkup;
use Drupal\Component\Utility\Random;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a simple textfield for entering GUID.
 *
 * @FieldWidget(
 *   id = "xero_textfield",
 *   label = @Translation("Xero textfield"),
 *   field_types = {
 *     "xero_reference"
 *   },
 *   multiple_values = TRUE
 * )
 *
 * @internal
 */
class XeroTextfieldWidget extends WidgetBase implements ContainerFactoryPluginInterface {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return array(
      'xero_type' => array(),
    );
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
      $container->get('typed_data_manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, array $third_party_settings, TypedDataManager $typed_data_manager) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $third_party_settings);

    $this->fieldDefinition = $field_definition;
    $this->settings = $settings;
    $this->thirdPartySettings = $third_party_settings;
    $this->typedDataManager = $typed_data_manager;
    $this->randomGenerator = new Random();
  }

  /**
   * Get the Xero data type definition.
   *
   * @param $type
   *   The Xero type setting provided by this widget.
   * @return \Drupal\Core\TypedData\DataDefinitionInterface $definition
   *   The Xero data type definition or FALSE.
   */
  protected function getXeroDefinition($type) {
    $types = XeroReference::getTypes();

    if (!in_array($type, $types)) {
      return FALSE;
    }

    try {
      $definition = $this->typedDataManager->getDefinition($type);
    }
    catch (PluginNotFoundException $e) {
      $definition = FALSE;
    }

    return $definition;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $settings = array();
    $type_options = array();

    $types = $this->getSetting('xero_type');
    foreach ($types as $type_name) {
      $definition = $this->getXeroDefinition($type_name);

      if ($definition) {
        $type_options[$type_name] = $definition['label'];
      }
    }

    if (empty($type_options)) {
      return $settings;
    }

    $settings[] = t('Xero types: @types', array('@types' => implode(', ', $type_options)));
    return $settings;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $options = $this->getTypeOptions();

    $element['xero_type'] = array(
      '#type' => 'select',
      '#title' => t('Xero Type'),
      '#description' => t('Select the Xero data type to use for this form.'),
      '#options' => $options,
      '#multiple' => TRUE,
      '#default_value' => $this->getSetting('xero_type'),
    );

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $required = $element['#required'];
    $options = $this->getTypeOptions($this->getSetting('xero_type'));

    $element += array(
      '#type' => 'container',
      'type' => array(
        '#type' => 'select',
        '#title' => t('Xero type'),
        '#description' => t('Select the Xero data type to associate.'),
        '#options' => $options,
        '#default_value' => isset($items[$delta]->type) ? $items[$delta]->type : NULL,
      ),
      'guid' => array(
        '#type' => 'textfield',
        '#title' => t('GUID'),
        '#description' => t('Provide the globally-unique identifier for the Xero item.'),
        '#default_value' => isset($items[$delta]->guid) ? $items[$delta]->guid : NULL,
        '#maxlength' => 38,
        '#placeholder' => $this->getGUIDPlaceholder(),
        '#attributes' => array('class' => array('text-full')),
        '#size' => 60,
      ),
      'label' => array(
        '#type' => 'textfield',
        '#title' => t('Description'),
        '#description' => t('Describe the reference to the Xero item'),
        '#default_value' => isset($items[$delta]->label) ? $items[$delta]->label : NULL,
        '#maxlength' => 255,
        '#attributes' => array('class' => array('text-full')),
        '#size' => 60,
      ),
    );

    return $element;
  }

  /**
   * Get the xero type options.
   *
   * @param $available
   *   (Optional) Xero types to restrict to.
   * @return array
   *   An array of options for a select list.
   */
  protected function getTypeOptions(array $available = array()) {
    $options = array();

    $types = XeroReference::getTypes();

    foreach ($types as $type_name) {
      if (empty($available) || (!empty($available) && in_array($type_name, $available))) {
        $definition = $this->getXeroDefinition($type_name);

        if ($definition) {
          $options[$type_name] = $definition['label'];
        }
      }
    }

    return $options;
  }

  /**
   * Provide a random GUID to act as a HTML placeholder attribute.
   *
   * @return string
   *   Random GUID for use as placeholder attribute.
   */
  protected function getGUIDPlaceholder() {
    $hash = strtolower(hash('ripemd128', md5($this->randomGenerator->string(100))));
    $guid = substr($hash, 0, 8) . '-' . substr($hash, 8, 4) . '-' . substr($hash, 12, 4);
    $guid .= '-' . substr($hash, 16, 4) . '-' . substr($hash, 20, 12);

    return $guid;
  }

}
