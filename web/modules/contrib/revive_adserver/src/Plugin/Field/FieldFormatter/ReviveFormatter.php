<?php

namespace Drupal\revive_adserver\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\revive_adserver\InvocationMethodServiceManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a default Revive adserver formatter.
 *
 * @FieldFormatter(
 *   id = "revive_adserver_zone",
 *   label = @Translation("Revive Adserver"),
 *   field_types = {
 *     "revive_adserver_zone"
 *   }
 * )
 */
class ReviveFormatter extends FormatterBase implements ContainerFactoryPluginInterface {

  /**
   * The Invocation Method Manager.
   *
   * @var \Drupal\revive_adserver\InvocationMethodServiceManager
   */
  protected $invocationMethodManager;

  /**
   * Constructs a new DisqusFormatter.
   *
   * @param string $plugin_id
   *   The plugin_id for the formatter.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The definition of the field to which the formatter is associated.
   * @param array $settings
   *   The formatter settings.
   * @param string $label
   *   The formatter label display setting.
   * @param string $view_mode
   *   The view mode.
   * @param \Drupal\revive_adserver\InvocationMethodServiceManager $invocationMethodServiceManager
   *   The Invocation Method Manager.
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, $label, $view_mode, InvocationMethodServiceManager $invocationMethodServiceManager) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $label, $view_mode, []);
    $this->invocationMethodManager = $invocationMethodServiceManager;
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
      $configuration['label'],
      $configuration['view_mode'],
      $container->get('plugin.manager.revive_adserver.invocation_method_service')
    );
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return ['invocation_method' => ''] + parent::defaultSettings();
  }

  /**
   * @inheritdoc
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $invocation_method = $this->getSetting('invocation_method');
    // Fallback to async javascript method, if no method was set.
    if (empty($invocation_method)) {
      $invocation_method = 'async_javascript';
    }
    
    $element['invocation_method'] = [
      '#type' => 'select',
      '#title' => $this->t('Invocation method'),
      '#description' => $this->t('Banner invocation method. How will the ads be displayed.'),
      '#default_value' => $invocation_method,
      '#options' => $this->invocationMethodManager->getInvocationMethodOptionList(),
      '#required' => TRUE,
    ];
    return $element;
  }

  /**
   * @inheritdoc
   */
  public function settingsSummary() {
    $invocation_method = $this->getSetting('invocation_method');
    $invocation_method_options = $this->invocationMethodManager->getInvocationMethodOptionList();
    $summary = [];
    if (!empty($invocation_method)) {
      if ($this->getFieldSetting('invocation_method_per_entity')) {
        $summary[] = $this->t('Fallback Invocation method: @invocation_method', ['@invocation_method' => $invocation_method_options[$this->getSetting('invocation_method')]]);
      }
      else {
        $summary[] = $this->t('Invocation method: @invocation_method', ['@invocation_method' => $invocation_method_options[$this->getSetting('invocation_method')]]);
      }
    }
    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $element = [];
    $method = $this->getSetting('invocation_method');

    foreach ($items as $delta => $item) {
      // Overwrite with the fields invocation method, if available.
      $values = $item->getValue();
      if ($this->getFieldSetting('invocation_method_per_entity') && !empty($values['invocation_method'])) {
        $method = $values['invocation_method'];
      }
      $invocationMethod = $this->invocationMethodManager->loadInvocationMethodFromInput($method);
      $invocationMethod->setZoneId($values['zone_id']);
      $invocationMethod->prepare();
      if ($invocationMethod) {
        $element[$delta] = $invocationMethod->render();
      }
    }

    return $element;
  }

}
