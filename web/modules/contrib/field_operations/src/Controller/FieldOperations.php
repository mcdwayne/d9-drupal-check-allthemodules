<?php

namespace Drupal\field_operations\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Render\Markup;
use Drupal\Core\Url;
use Drupal\field_ui\FieldUI;
use Drupal\Core\Field\FieldTypePluginManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a class to build a listing of fields to edit, delete, storage.
 *
 * @see \Drupal\field\Entity\Field
 * @see field_ui_entity_info()
 */
class FieldOperations extends ControllerBase {

  /**
   * The field type plugin manager.
   *
   * @var \Drupal\Core\Field\FieldTypePluginManagerInterface
   */
  protected $fieldTypePluginManager;

  /**
   * Constructs a new FieldStorageAddForm object.
   *
   * @param \Drupal\Core\Field\FieldTypePluginManagerInterface $field_type_plugin_manager
   *   The field type plugin manager.
   */
  public function __construct(FieldTypePluginManagerInterface $field_type_plugin_manager) {
    $this->fieldTypePluginManager = $field_type_plugin_manager;
    $this->fieldTypes = $this->fieldTypePluginManager->getDefinitions();
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
        $container->get('plugin.manager.field.field_type'), $container->get('entity_type.bundle.info')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow() {
    global $base_url;
    $data['filters'] = array(
      '#type' => 'container',
      '#attributes' => array(
        'class' => array('table-filter', 'js-show'),
      ),
      '#weight' => -1,
    );
    $data['filters']['search_field'] = array(
      '#type' => 'search',
      '#title' => t('Enter a part of the field name'),
      '#title_display' => 'invisible',
      '#size' => 30,
      '#placeholder' => t('Filter by field name'),
      '#attributes' => array(
        'class' => array('table-filter-text'),
        'data-table' => '#field-operations',
        'autocomplete' => 'off',
      ),
    );
    $data['#attached']['library'][] = 'field_operations/field_operations_js';
    // Headers.
    $header['id'] = array(
      'data' => $this->t('Field name'),
      'class' => array(RESPONSIVE_PRIORITY_LOW),
    );
    $header['type'] = array(
      'data' => $this->t('Field type'),
      'class' => array(RESPONSIVE_PRIORITY_MEDIUM),
    );
    $header['usage'] = $this->t('Used in');
    $header['operations'] = $this->t('Operations');
    // Get All fields that are in use.
    $fields = \Drupal::entityManager()->getStorage('field_config')->loadByProperties(
        array(
          'deleted' => FALSE,
          'status' => 1,
        )
    );
    $bundle_info = \Drupal::service("entity_type.bundle.info")->getAllBundleInfo();
    $rows = [];
    foreach ($fields as $field_config) {
      // Get storage definition of field.
      $field_storage = $field_config->getFieldStorageDefinition();
      // Get firld type.
      $field_type = $this->fieldTypes[$field_storage->getType()];
      $row = [];
      $usage = [];
      // Get bundles and build rows.
      foreach ($field_storage->getBundles() as $bundle) {
        // If locked then disable.
        if ($field_storage->isLocked()) {
          $row['class'] = array('menu-disabled');
          $row['data']['id'] = $this->t('@field_name (Locked)', array('@field_name' => $field_storage->getName()));
        }
        else {
          $row['data']['id'] = $field_storage->getName();
        }
        $row['data']['type'] = $this->t('@type (module: @module)', array('@type' => $field_type['label'], '@module' => $field_type['provider']));
        $entity_type_id = $field_storage->getTargetEntityTypeId();
        $route_info = FieldUI::getOverviewRouteInfo($entity_type_id, $bundle);
        if ($route_info = FieldUI::getOverviewRouteInfo($entity_type_id, $bundle)) {
          // Generate usage and url's for edit, delete , storage.
          $usage = $this->t('%name', ['%name' => $bundle_info[$entity_type_id][$bundle]['label']]);
          $delete_url = $base_url . '' . $route_info->toString() . '/' . $field_config->id() . '/delete';
          $edit_url = $base_url . '' . $route_info->toString() . '/' . $field_config->id();
          $storage_url = $base_url . '' . $route_info->toString() . '/' . $field_config->id() . '/storage';
          $url_delete = \Drupal::l('Delete', Url::fromUri($delete_url));
          $url_edit = \Drupal::l('Edit', Url::fromUri($edit_url));
          $url_storage = \Drupal::l('Storage Settings', Url::fromUri($storage_url));
          $operations = $url_edit . '<br>' . $url_storage . '<br>' . $url_delete;
          $row['data']['usage'] = $usage;
          $row['data']['operations'] = Markup::create($operations);
        }
        $rows[] = $row['data'];
      }
    }
    // Construct field list table.
    $data['data'] = [
      '#theme' => 'table',
      '#header' => $header,
      '#rows' => $rows,
      '#attributes' => array(
        'id' => 'field-operations',
      ),
    ];
    $data['#cache'] = [
      'max-age' => 0
    ];
    return $data;
  }

}
