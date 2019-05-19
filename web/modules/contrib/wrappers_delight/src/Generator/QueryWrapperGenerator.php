<?php

namespace Drupal\wrappers_delight\Generator;


class QueryWrapperGenerator extends WrapperGeneratorBase {

  /**
   * @param string $entity_type
   * @param string $class_name
   * @param string $extension
   * @param array $methods
   *
   * @return bool
   */
  public function generate($entity_type, $class_name, $entity_wrapper_class, $extension, $methods) {
    $template = 'class.query.entity.php.twig';
    $entity_class = $this->getEntityClass($entity_type);
    $class_short_name = preg_replace('/.*\\\\(.*)?$/', "$1", $class_name);
    if (!empty($entity_class)) {
      $parameters = [
        'namespace' => 'Drupal\\' . $extension . '\\Plugin\\WrappersDelight\\' . $entity_class->getShortName(),
        'entity_type' => $entity_type,
        'class_name' => $class_name,
        'class_name_short' => $class_short_name,
        'entity_wrapper_class' => $entity_wrapper_class,
        'entity_class' => $entity_class->getName(),
        'entity_class_short' => $entity_class->getShortName(),
        'methods' => trim(implode("\n", $methods)),
      ];
      $this->renderer->setSkeletonDirs([$this->extensionManager->getModule('wrappers_delight')->getPath() . '/templates']);
      $filepath = $this->extensionManager->getModule($extension)->getPath() .
        '/src/Plugin/WrappersDelight/' . $entity_class->getShortName() . '/' . $class_short_name . '.php';
      return $this->renderFile($template, $filepath, $parameters);
    }
  }

  /**
   * @param string $entity_type
   * @param string $class_name
   * @param string $extension
   * @param array $methods
   */
  public function update($entity_type, $class_name, $entity_wrapper_class, $extension, $methods) {
    $this->ensureUseStatements($class_name,[
      'Drupal\wrappers_delight\Annotation\WrappersDelight',
      'Drupal\wrappers_delight\Annotation\WrappersDelightMethod',
    ]);
    $this->addToExistingClass($class_name, implode("\n", $methods), 'bottom');
    $this->fileQueue->addFile($this->getClassFilename($class_name));
  }
  
}
