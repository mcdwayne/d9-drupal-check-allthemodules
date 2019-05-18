<?php

namespace Drupal\dea\Plugin\Field\FieldWidget;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Annotation\Translation;
use Drupal\Core\Field\Annotation\FieldWidget;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Widget for assigning entity operations.
 *
 * @FieldWidget(
 *   id = "entity_operation",
 *   label = @Translation("Entity Operation"),
 *   field_types = {
 *    "entity_operation"
 *   }
 * )
 */
class EntityOperationFieldWidget extends WidgetBase {
  /**
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  protected $types;

  /**
   * @var \Drupal\dea\EntityOperationManager
   */
  protected $operations;

  /**
   * @var \Drupal\Core\Entity\EntityTypeBundleInfo
   */
  protected $bundles;

  /**
   * {@inheritdoc}
   */
  public function __construct($plugin_id, $plugin_definition, \Drupal\Core\Field\FieldDefinitionInterface $field_definition, array $settings, array $third_party_settings) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $third_party_settings);

    $this->types = \Drupal::getContainer()->get('entity_type.manager');
    $this->operations = \Drupal::getContainer()->get('dea.entity.operation');
    $this->bundles = \Drupal::getContainer()->get('entity_type.bundle.info');
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $wrapper_id = 'entity-operation-widget-' . $items->getName() . '-' .  $delta;
    $path = $element['#field_parents'] + [$items->getName(), $delta];
    $entity_type = $form_state->getValue($path + [count($path) => 'entity_type'], $items[$delta]->entity_type);

    return $element + [
      'grant' => [
        '#type' => 'container',
        '#attributes' => [
          'id' => $wrapper_id,
          'class' => ['form--inline', 'clearfix'],
        ],
        'entity_type' => [
          '#parents' => $element['#field_parents'] + [$items->getFieldDefinition()->getName(), $delta, 'entity_type'],
          '#type' => 'select',
          '#label' => $this->t('Entity type'),
          '#default_value' => $entity_type,
          '#options' => $this->typeOptions(),
          '#empty_option' => $this->t('Choose entity type'),
          '#ajax' => [
            'wrapper' => $wrapper_id,
            'callback' => [get_class($this), 'selectBundleAjax'],
          ],
        ],
        'bundle' => [
          '#parents' => $element['#field_parents'] + [$items->getFieldDefinition()->getName(), $delta, 'bundle'],
          '#access' => (bool) $entity_type,
          '#type' => 'select',
          '#label' => $this->t('Bundle'),
          '#default_value' => $form_state->getValue($path + [count($path) => 'bundle'], $items[$delta]->bundle),
          '#options' => $entity_type ? $this->bundleOptions($entity_type): [],
        ],
        'operation' => [
          '#parents' => $element['#field_parents'] + [$items->getFieldDefinition()->getName(), $delta, 'operation'],
          '#access' => (bool) $entity_type,
          '#type' => 'select',
          '#label' => $this->t('Operation'),
          '#default_value' => $form_state->getValue($path + [count($path) => 'operation'], $items[$delta]->operation),
          '#options' => $entity_type ? $this->operationOptions($entity_type): [],
        ],
      ],
    ];
  }

  public static function selectBundleAjax(array $form, FormStateInterface $form_state) {
    $parents = $form_state->getTriggeringElement()['#array_parents'];
    return NestedArray::getValue($form, array_splice($parents, 0,  -1));
  }

  protected function typeOptions() {
    $options = [];
    foreach ($this->types->getDefinitions() as $def) {
      if (count($this->operations->operations($def->id())) > 0) {
        $options[$def->id()] = $def->getLabel();
      }
    }
    return $options;
  }

  protected function bundleOptions($entity_type) {
    $options = [];
    foreach ($this->bundles->getBundleInfo($entity_type) as $bundle => $info) {
      $options[$bundle] = $info['label'];
    }
    return $options;
  }

  protected function operationOptions($entity_type) {
    return $this->operations->operations($entity_type);
  }
}