<?php

namespace Drupal\commerce_inventory\Plugin\views\field;

use Drupal\commerce_inventory\InventoryAdjustmentTypeManager;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Field\FieldTypePluginManagerInterface;
use Drupal\Core\Field\FormatterPluginManager;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\views\Plugin\views\field\EntityField;
use Drupal\views\ResultRow;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * A handler to provide a field that is completely custom by the administrator.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("commerce_inventory_adjustment_type_label")
 */
class CommerceInventoryAdjustmentTypeLabel extends EntityField {

  /**
   * The inventory adjustment type plugin manager.
   *
   * @var \Drupal\commerce_inventory\InventoryAdjustmentTypeManager
   */
  protected $inventoryAdjustmentTypeManager;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityManagerInterface $entity_manager, FormatterPluginManager $formatter_plugin_manager, FieldTypePluginManagerInterface $field_type_plugin_manager, LanguageManagerInterface $language_manager, RendererInterface $renderer, InventoryAdjustmentTypeManager $adjustment_type_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $entity_manager, $formatter_plugin_manager, $field_type_plugin_manager, $language_manager, $renderer);

    $this->inventoryAdjustmentTypeManager = $adjustment_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity.manager'),
      $container->get('plugin.manager.field.formatter'),
      $container->get('plugin.manager.field.field_type'),
      $container->get('language_manager'),
      $container->get('renderer'),
      $container->get('plugin.manager.commerce_inventory_adjustment_type')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getItems(ResultRow $values) {
    $items = parent::getItems($values);
    foreach ($items as &$item) {
      if ($definition = $this->inventoryAdjustmentTypeManager->getDefinition($item['raw']->get('value')->getValue(), FALSE)) {
        $label_select = $this->options['label_select'];
        if (!array_key_exists($label_select, $definition)) {
          $label_select = 'label';
        }
        $item['rendered']['#context']['value'] = $definition[$label_select];
      }
    }
    return $items;
  }

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['label_select']['default'] = 'label';
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);

    $form['label_select'] = [
      '#title' => $this->t('Label selection'),
      '#description' => $this->t('Choose label version to use.'),
      '#type' => 'select',
      '#options' => [
        'label' => $this->t('Default'),
        'label_verb' => $this->t('Verb'),
        'label_preposition' => $this->t('Preposition'),
        'label_related_preposition' => $this->t('Related Preposition'),
      ],
      '#default_value' => $this->options['label_select'],
    ];
  }

}
