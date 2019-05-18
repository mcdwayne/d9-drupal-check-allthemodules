<?php

namespace Drupal\dream_fields\Controller;

use Drupal\Component\Plugin\PluginManagerInterface;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Url;
use Drupal\dream_fields\DreamFieldsPermissions;
use Drupal\field_ui\FieldUI;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class AddField.
 *
 * @package Drupal\dream_fields\Form
 */
class AddField extends ControllerBase {

  /**
   * The dream fields plugin manager.
   *
   * @var \Drupal\Component\Plugin\PluginManagerInterface
   */
  protected $dreamFieldsPluginManager;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Render a list of fields that can be selected.
   *
   * @return array
   *   The page output.
   */
  public function addField($entity_type_id, $bundle) {
    $fields = [];
    $entity_type = $this->entityTypeManager->getDefinition($entity_type_id);
    foreach ($this->getAllowedDefinitions() as $id => $definition) {
      $preview_provider = !empty($definition['preview_provider']) ? $definition['preview_provider'] : $definition['provider'];
      $fields[] = [
        '#type' => 'dream_field',
        '#title' => $definition['label'],
        '#description' => $definition['description'],
        '#image' => !empty($definition['preview']) ? Url::fromUri('base:' . drupal_get_path('module', $preview_provider) . '/' . $definition['preview'])->toString() : NULL,
        '#link' => Url::fromRoute('dream_fields.configure_field_' . $entity_type_id, [
          'field_type' => $id,
        ] + FieldUI::getRouteBundleParameter($entity_type, $bundle)),
      ];
    }

    return [
      '#type' => 'container',
      '#attributes' => [
        'class' => ['dream-fields'],
      ],
      'children' => $fields,
    ];
  }

  /**
   * The title callback for the page.
   */
  public function title($bundle) {
    return $this->t('Add field to %title', ['%title' => $bundle]);
  }

  /**
   * Get the definitions allowed by the permissions.
   */
  protected function getAllowedDefinitions() {
    $all_definitions = $this->dreamFieldsPluginManager->getDefinitions();
    if ($this->currentUser()->hasPermission('use all dream fields')) {
      return $all_definitions;
    }
    return array_filter($all_definitions, function($definition) {
      return $this->currentUser()->hasPermission(DreamFieldsPermissions::permissionName($definition['id']));
    });
  }

  /**
   * {@inheritdoc}
   */
  public function __construct(PluginManagerInterface $dream_fields_plugin_manager, EntityTypeManagerInterface $entity_type_manager) {
    $this->dreamFieldsPluginManager = $dream_fields_plugin_manager;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.dream_fields'),
      $container->get('entity_type.manager')
    );
  }

}
