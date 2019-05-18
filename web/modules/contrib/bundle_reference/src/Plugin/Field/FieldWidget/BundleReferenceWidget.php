<?php

namespace Drupal\bundle_reference\Plugin\Field\FieldWidget;

use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Entity\ContentEntityTypeInterface;
use Drupal\Component\Utility\NestedArray;
use Drupal\Component\Utility\Html;

/**
 * Plugin implementation of the 'bundle_reference_widget' widget.
 *
 * @FieldWidget(
 *   id = "bundle_reference_widget",
 *   module = "bundle_reference",
 *   label = @Translation("Bundle reference"),
 *   field_types = {
 *     "bundle_reference"
 *   }
 * )
 */
class BundleReferenceWidget extends WidgetBase implements ContainerFactoryPluginInterface {

  /**
   * Bundle info service.
   *
   * @var \Drupal\Core\Entity\EntityTypeBundleInfoInterface
   */
  protected $bundleInfo;

  /**
   * Entity typoe manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * {@inheritdoc}
   */
  public function __construct(
    $plugin_id,
    $plugin_definition,
    FieldDefinitionInterface $field_definition,
    array $settings,
    array $third_party_settings,
    EntityTypeBundleInfoInterface $bundleInfo,
    EntityTypeManagerInterface $entityTypeManager
  ) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $third_party_settings);
    $this->bundleInfo = $bundleInfo;
    $this->entityTypeManager = $entityTypeManager;
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
      $container->get('entity_type.bundle.info'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $type_options = [
      '' => $this->t('-- Select entity type --'),
    ];

    $referencable_bundles = $this->fieldDefinition->getSetting('referencable_bundles');
    if (!empty($referencable_bundles)) {
      $referencable = [];
      foreach ($referencable_bundles as $item) {
        list($entity_type, $bundle) = explode(':', $item);
        $referencable[$entity_type][$bundle] = TRUE;
      }
    }

    foreach ($this->entityTypeManager->getDefinitions() as $definition) {
      if ($definition instanceof ContentEntityTypeInterface && (empty($referencable) || isset($referencable[$definition->id()]))) {
        $type_options[$definition->id()] = $definition->getLabel();
      }
    }

    $element['#type'] = 'container';
    $html_id = Html::cleanCssIdentifier($this->fieldDefinition->getName() . '-bundle-' . $delta);

    $current_entity_type = isset($items[$delta]->entity_type) ? $items[$delta]->entity_type : NULL;
    $element['entity_type'] = [
      '#type' => 'select',
      '#title' => $this->t('Entity type'),
      '#default_value' => $current_entity_type,
      '#options' => $type_options,
      '#ajax' => [
        'callback' => [get_called_class(), 'ajaxCallback'],
        'wrapper' => $html_id,
      ],
    ];

    $trigger = $form_state->getTriggeringElement();
    if ($trigger && isset($trigger['#ajax']['wrapper']) && $trigger['#ajax']['wrapper'] === $html_id && !empty($trigger['#value'])) {
      $current_entity_type = $trigger['#value'];
    }
    else {
      $parents = $element['#field_parents'];
      $parents[] = $this->fieldDefinition->getName();
      $parents[] = $delta;
      $parents[] = 'entity_type';
      if ($entity_type_id = $form_state->getValue($parents)) {
        $current_entity_type = $entity_type_id;
      }
    }

    if ($current_entity_type) {
      $element['bundle'] = [
        '#type' => 'select',
        '#title' => $this->t('Bundle'),
        '#options' => [],
        '#default_value' => isset($items[$delta]->bundle) ? $items[$delta]->bundle : NULL,
      ];
      foreach ($this->bundleInfo->getBundleInfo($current_entity_type) as $bundle_id => $data) {
        if (empty($referencable) || isset($referencable[$current_entity_type][$bundle_id])) {
          $element['bundle']['#options'][$bundle_id] = $data['label'];
        }
      }
    }
    $element['bundle']['#prefix'] = '<div id="' . $html_id . '">';
    $element['bundle']['#suffix'] = '</div>';

    return $element;
  }

  /**
   * Rebuild AJAX callback.
   */
  public static function ajaxCallback($form, $form_state) {
    $trigger = $form_state->getTriggeringElement();
    $parents = array_slice($trigger['#array_parents'], 0, -1, TRUE);
    $parents[] = 'bundle';
    $element = NestedArray::getValue($form, $parents);
    return $element;
  }

}
