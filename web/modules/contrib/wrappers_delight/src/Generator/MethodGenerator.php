<?php

namespace Drupal\wrappers_delight\Generator;

use Drupal\Console\Core\Generator\Generator;
use Drupal\Console\Extension\Manager;
use Drupal\wrappers_delight\Annotation\WrappersDelight;
use Drupal\wrappers_delight\Annotation\WrappersDelightMethod;
use Drupal\wrappers_delight\WrappersDelightPluginManager;

class MethodGenerator extends Generator {

  /**
   * @var \Drupal\Console\Extension\Manager
   */
  protected $extensionManager;

  /**
   * @var \Drupal\wrappers_delight\WrappersDelightPluginManager
   */
  protected $pluginManager;

  /**
   * PermissionGenerator constructor.
   *
   * @param \Drupal\Console\Extension\Manager $extensionManager
   * @param \Drupal\wrappers_delight\WrappersDelightPluginManager $pluginManager
   */
  public function __construct(
    Manager $extensionManager,
    WrappersDelightPluginManager $pluginManager
  ) {
    $this->extensionManager = $extensionManager;
    $this->pluginManager = $pluginManager;
  }

  /**
   * @param \Drupal\field\Entity\FieldConfig $field_config
   * @param string $type
   * @param array $existing_methods
   * 
   * @return string
   */
  public function generate($field_config, $type, $existing_methods) {
    switch ($type) {
      case WrappersDelightMethod::GETTER:
        $template = 'fields/getter.php.twig';
        break;
        
      case WrappersDelightMethod::SETTER:
        $template = 'fields/setter.php.twig';
        break;
        
      case WrappersDelightMethod::CONDITION:
        $template = 'fields/condition.php.twig';
        break;

      case WrappersDelightMethod::SORT:
        $template = 'fields/sort.php.twig';
        break;
        
      case WrappersDelightMethod::EXISTS:
        $template = 'fields/exists.php.twig';
        break;
        
      case WrappersDelightMethod::NOT_EXISTS:
        $template = 'fields/not_exists.php.twig';
        break;
        
    }
    if (!empty($template) && !($type == WrappersDelightMethod::SETTER && $this->isReadOnly($field_config))) {
      $method_name = $this->getMethodName($field_config->getName(), $type, $existing_methods);
      $item_wrapper_class = $this->pluginManager->getFieldWrapperClass($field_config->getType());
      $list_wrapper_class = $this->pluginManager->getFieldListWrapperClass($field_config->getType());
      if (!empty($method_name)) {
        $parameters = [
          'field_name' => $field_config->getName(),
          'field_type' => $field_config->getType(),
          'method_name' => $method_name,
          'item_class' => $item_wrapper_class,
          'list_class' => $list_wrapper_class,
          'single_value' => ($field_config->getFieldStorageDefinition()->getCardinality() == 1)
        ];
        $this->renderer->setSkeletonDirs([$this->extensionManager->getModule('wrappers_delight')->getPath() . '/templates']);
        return $this->renderer->render($template, $parameters);
      }
    }
    return '';
  }

  /**
   * @param \Drupal\field\Entity\FieldConfig $field_config
   *
   * @return bool
   */
  public function isReadOnly($field_config) {
    return ($field_config->isReadOnly() || $field_config->isComputed());
  }

  /**
   * @param string $field_name
   * @param string $type
   * @param array $existing_methods
   */
  public function getMethodName($field_name, $type, $existing_methods) {
    $name = [];
    switch ($type) {
      case WrappersDelightMethod::GETTER:
        $name[] = 'get';
        break;

      case WrappersDelightMethod::SETTER:
        $name[] = 'set';
        break;

      case WrappersDelightMethod::CONDITION:
        $name[] = 'by';
        break;

      case WrappersDelightMethod::SORT:
        $name[] = 'sortBy';
        break;
        
      case WrappersDelightMethod::EXISTS:
        $name[] = 'whereExists';
        break;
        
      case WrappersDelightMethod::NOT_EXISTS:
        $name[] = 'whereNotExists';
        break;
    }
    // Try field name without "field_" at beginning.
    $name[] = $this->camelize(preg_replace('/^field_/', '', $field_name));
    if (!empty($existing_methods[implode('', $name)])) {
      // Method name already exists, try it with the field at beginning
      array_pop($name);
      $name[] = $this->camelize($field_name);
      
      if (!empty($existing_methods[implode('', $name)])) {
        // Method name with field also exists, try it with the word "Wrapped" at the end.
        array_pop($name);
        $name[] = $this->camelize(preg_replace('/^field_/', '', $field_name)) . 'Wrapped';
        if (!empty($existing_methods[implode('', $name)])) {
          unset($name[1]);
        }
      }
    }
    
    if (count($name) > 1) {
      return implode('', $name);
    }
    return NULL;
  }

  /**
   * @param string $name
   *
   * @return string
   */
  private function camelize($name) {
    return implode('', array_map('ucfirst', explode('_', $name)));
  }
  
}
