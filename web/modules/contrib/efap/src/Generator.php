<?php
/**
 * Created by PhpStorm.
 * User: bartv
 * Date: 07/05/2018
 * Time: 17:56
 */

namespace Drupal\efap;

use Drupal\Console\Core\Utils\StringConverter;
use Drupal\Console\Extension\Extension;
use Drupal\Console\Extension\Manager;

/**
 * Class Generator
 *
 * @package Drupal\efap
 */
class Generator extends \Drupal\Console\Core\Generator\Generator {

  /**
   * @var \Drupal\Console\Extension\Manager
   */
  protected $extensionManager;

  /**
   * @var \Drupal\Console\Core\Utils\StringConverter
   */
  protected $stringConverter;

  /**
   * Generator constructor.
   *
   * @param \Drupal\Console\Extension\Manager $extensionManager
   */
  public function __construct(
    Manager $extensionManager,
    StringConverter $stringConverter
  ) {
    $this->extensionManager = $extensionManager;
    $this->stringConverter = $stringConverter;
  }

  /**
   * @param \Drupal\Console\Extension\Extension $module
   * @param string $class
   * @param string $entity
   * @param string $bundle
   * @param string $id
   * @param string $label
   * @param string $description
   */
  public function generate(
    Extension $module,
    string $class,
    string $entityType,
    string $bundle,
    string $id,
    string $label,
    string $description,
    array $services
  ) {
    $path = $this->extensionManager
      ->getModule('efap')
      ->getTemplatePath();
    $this->addSkeletonDir($path);

    $folder = $this->stringConverter->underscoreToCamelCase($entityType);
    $folder = $this->stringConverter->anyCaseToUcFirst($folder);

    $path = strtr('@source/Plugin/ExtraField/@entity/@class.php', [
      '@source' => $module->getSourcePath(),
      '@entity' => $folder,
      '@class' => $class,
    ]);

    $parameters['module'] = $module->getName();
    $parameters['class'] = $class;
    $parameters['entityType'] = $entityType;
    $parameters['folder'] = $folder;
    $parameters['bundle'] = $bundle;
    $parameters['id'] = $id;
    $parameters['label'] = $label;
    $parameters['description'] = $description;
    $parameters['services'] = $services;

    $this->renderFile(
      'ExtraField.php.twig',
      $path,
      $parameters
    );
  }

}
