<?php

namespace Drupal\wrappers_delight\Generator;

use Drupal\Console\Core\Generator\Generator;
use Drupal\Console\Extension\Manager;
use Drupal\Core\Entity\EntityTypeManagerInterface;

class WrapperGeneratorBase extends Generator {

  /**
   * @var \Drupal\Console\Extension\Manager
   */
  protected $extensionManager;

  /**
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * WrapperGeneratorBase constructor.
   *
   * @param \Drupal\Console\Extension\Manager $extensionManager
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   */
  public function __construct(
    Manager $extensionManager,
    EntityTypeManagerInterface $entity_type_manager
  ) {
    $this->extensionManager = $extensionManager;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * @param string $class_name
   * @param array $use_classes
   */
  protected function ensureUseStatements($class_name, $use_classes) {
    $class = new \ReflectionClass($class_name);
    $class_contents = file_get_contents($class->getFileName());
    $use_statements = array_map(function($class) {
      return 'use ' . $class . ';';
    }, $use_classes);
    foreach ($use_statements as $use) {
      if (!strstr($class_contents, $use)) {
        $this->addToExistingClass($class_name, $use, 'use');
      }
    }
  }

  /**
   * @param string $class_name
   * @param array $traits
   */
  protected function ensureTraits($class_name, $traits) {
    $class = new \ReflectionClass($class_name);
    foreach ($traits as $trait) {
      if (!in_array($trait, $class->getTraitNames())) {
        $trait_obj = new \ReflectionClass($trait);
        $trait_shortname = $trait_obj->getShortName();
        $this->addToExistingClass($class_name, "  use $trait_shortname;", 'top');
      }
    }
    $this->ensureUseStatements($class_name, $traits);
  }

  /**
   * @param string $class_name
   * @param string $code
   * @param string $position
   */
  protected function addToExistingClass($class_name, $code, $position = 'bottom') {
    $trimmed_code = trim($code);
    if (!empty($trimmed_code)) {
      $class = new \ReflectionClass($class_name);
      $class_contents = file_get_contents($class->getFileName());
      if ($position == 'bottom') {
        $class_contents = preg_replace('/\}$/', rtrim($code) . "\n\n}\n", trim($class_contents));
      }
      elseif ($position == 'top') {
        $declaration_pattern = '/(class ' . $class->getShortName() . '.*?\{)/';
        $class_contents = preg_replace($declaration_pattern, "$1\n\n" . rtrim($code) . "\n", trim($class_contents));
      }
      elseif ($position == 'use') {
        $namespace_pattern = '/(namespace .*?;)/';
        $class_contents = preg_replace($namespace_pattern, "$1\n\n" . rtrim($code) . "\n", trim($class_contents));
        $class_contents = preg_replace('/(use .*?;)[\s\n]*?use/', "$1\nuse", $class_contents);
      }
      file_put_contents($class->getFileName(), $class_contents);
    }
  }

  /**
   * @param string $class_name
   *
   * @return string
   */
  protected function getClassFilename($class_name) {
    $class = new \ReflectionClass($class_name);
    return $class->getFileName();
  }

  /**
   * @param $entity_type
   *
   * @return \ReflectionClass
   */
  protected function getEntityClass($entity_type) {
    /** @var \Drupal\Core\Entity\EntityTypeInterface $entity_info */
    $entity_info = $this->entityTypeManager->getDefinition($entity_type);
    return new \ReflectionClass($entity_info->getClass());
  }

}
