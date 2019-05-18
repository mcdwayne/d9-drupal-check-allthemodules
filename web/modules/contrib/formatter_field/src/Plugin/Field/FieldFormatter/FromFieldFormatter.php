<?php

namespace Drupal\formatter_field\Plugin\Field\FieldFormatter;

use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Field\FormatterPluginManager;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the 'formatter_field' formatter.
 *
 * @FieldFormatter(
 *   id = "formatter_field_from",
 *   label = @Translation("Formatter from field"),
 *   field_types = {}
 * )
 *
 * @see formatter_field_field_formatter_info_alter()
 */
class FromFieldFormatter extends FormatterBase implements ContainerFactoryPluginInterface {

  /**
   * Formatter plugin manager.
   *
   * @var \Drupal\Core\Field\FormatterPluginManager
   */
  protected $formatterManager;

  /**
   * Entity field manager.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $fieldManager;

  /**
   * Constructs a new ModerationStateWidget object.
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
   * @param array $third_party_settings
   *   Any third party settings.
   * @param \Drupal\Core\Field\FormatterPluginManager $formatter_manager
   *   Formatter plugin manager.
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $field_manager
   *   Entity field manager.
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, $label, $view_mode, array $third_party_settings, FormatterPluginManager $formatter_manager, EntityFieldManagerInterface $field_manager) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $label, $view_mode, $third_party_settings);
    $this->formatterManager = $formatter_manager;
    $this->fieldManager = $field_manager;
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
      $configuration['third_party_settings'],
      $container->get('plugin.manager.field.formatter'),
      $container->get('entity_field.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function prepareView(array $entities_items) {
    if ($formatter = $this->getFormatter($entities_items[0])) {
      $formatter->prepareView($entities_items);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    if ($formatter = $this->getFormatter($items)) {
      return $formatter->viewElements($items, $langcode);
    }
    return [];
  }

  /**
   * Creates an appropriate formatter for the field.
   *
   * @param \Drupal\Core\Field\FieldItemListInterface $items
   *   The field values to be rendered.
   *
   * @return \Drupal\Core\Field\FormatterInterface|null|void
   *   A formatter object or null when plugin is not found.
   */
  protected function getFormatter(FieldItemListInterface $items) {

    $definitions = $this->fieldManager->getFieldDefinitions(
      $this->fieldDefinition->getTargetEntityTypeId(),
      $this->fieldDefinition->getTargetBundle()
    );

    $target_field_name = $this->fieldDefinition->getName();
    $target_definition = $definitions[$target_field_name];

    $formatter_field = NULL;
    foreach ($definitions as $field_name => $definition) {
      if ($definition->getType() == 'formatter_field_formatter' && $definition->getSetting('field') == $target_field_name) {
        $formatter_field = $field_name;
      }
    }

    if (!$formatter_field) {
      $message = $this->t(
        'Could not find appropriate formatter field to render %field_label.',
        ['%field_label' => $target_definition->getLabel()]
      );
      drupal_set_message($message, 'warning');
      return;
    }

    $type = NULL;
    $settings = [];
    $field_items = $items->getEntity()->get($formatter_field);
    if (isset($field_items[0])) {
      $formatter_data = $field_items[0]->getValue();
      $type = empty($formatter_data['type']) ? '' : $formatter_data['type'];
      $settings = empty($formatter_data['settings']) ? [] : unserialize($formatter_data['settings']);
    }

    if (!$type) {
      return;
    }

    $options = [
      'field_definition' => $target_definition,
      'configuration' => [
        'type' => $type,
        'settings' => $settings,
        'label' => '',
        'weight' => 0,
      ],
      'view_mode' => '_custom',
    ];
    $formatter = $this->formatterManager->getInstance($options);

    return $formatter;
  }

}
