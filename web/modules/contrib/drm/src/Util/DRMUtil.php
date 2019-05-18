<?php

namespace Drupal\drm\Util;

use Drupal\Component\Utility\Xss;
use Drupal\Core\Entity\ContentEntityType;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;
use Drupal\field\Entity\FieldConfig;
use Drupal\views\Views;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class BusinessRulesUtil.
 *
 * @package Drupal\drm\Util
 */
class DRMUtil {

  use StringTranslationTrait;

  const BIGGER = '>';

  const BIGGER_OR_EQUALS = '>=';

  const SMALLER = '<';

  const SMALLER_OR_EQUALS = '<=';

  const EQUALS = '==';

  const DIFFERENT = '!=';

  const IS_EMPTY = 'empty';

  const CONTAINS = 'contains';

  const STARTS_WITH = 'starts_with';

  const ENDS_WITH = 'ends_with';

  
  /**
   * Drupal Container.
   *
   * @var \Symfony\Component\DependencyInjection\ContainerInterface
   */
  public $container;

  /**
   * The entity field manager.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  public $entityFieldManager;
  
  /**
   * The field type plugin manager.
   *
   * @var \Drupal\Core\Field\FieldTypePluginManagerInterface
   */
  public $fieldTypePluginManager;
  
  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  public $configFactory;

  /**
   * The currently active request object.
   *
   * @var \Symfony\Component\HttpFoundation\Request
   */
  public $request;


  /**
   * BusinessRulesUtil constructor.
   *
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *   The services container.
   */
  public function __construct(ContainerInterface $container) {

    $this->container              = $container;
    $this->entityFieldManager     = $container->get('entity_field.manager');
    $this->fieldTypePluginManager = $container->get('plugin.manager.field.field_type');
    $this->configFactory          = $container->get('config.factory');
    $this->request                = $container->get('request_stack')->getCurrentRequest();
  }
  
  

  /**
   * Helper function to return all editable fields from one bundle.
   *
   * @param string $entityType
   *   The entity type.
   * @param string $bundle
   *   The entity bundle.
   * @param array $field_types_ids
   *   Array of field types ids if you want to get specifics field types.
   *
   * @return array
   *   Array of fields ['type' => 'description']
   */
  public function getBundleEditableFields($entityType, $bundle, array $field_types_ids = []) {

    if (empty($entityType) || empty($bundle)) {
      return [];
    }

    $fields      = $this->entityFieldManager->getFieldDefinitions($entityType, $bundle);
    $field_types = $this->fieldTypePluginManager->getDefinitions();
    $options     = [];
    foreach ($fields as $field_name => $field_storage) {

      // Do not show: non-configurable field storages but title.
      $field_type = $field_storage->getType();
      if (($field_storage instanceof FieldConfig || ($field_storage instanceof BaseFieldDefinition && $field_name == 'title'))
      ) {
        if (count($field_types_ids) == 0 || in_array($field_type, $field_types_ids)) {
          $options[$field_name] = $this->t('@type: @field', [
            '@type'  => $field_types[$field_type]['label'],
            '@field' => $field_storage->getLabel() . " [$field_name]",
          ]);
        }
      }

    }
    asort($options);

    return $options;
  }

}
