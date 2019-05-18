<?php

namespace Drupal\daemons\Generator;

use Drupal\Console\Core\Generator\Generator;
use Drupal\Console\Core\Generator\GeneratorInterface;
use Drupal\Console\Extension\Manager;

/**
 * Class DaemonGenerator.
 *
 * @package Drupal\Console\Generator
 */
class DaemonGenerator extends Generator implements GeneratorInterface {
  protected $extensionManager;

  /**
   * DaemonGenerator constructor.
   *
   * @param \Drupal\Console\Extension\Manager $extensionManager
   *   Console Manager object.
   */
  public function __construct(Manager $extensionManager) {
    $this->extensionManager = $extensionManager;
  }

  /**
   * {@inheritdoc}
   */
  public function generate(array $parameters) {
    $module = $parameters['module'];
    $class_name = $parameters['class_name'];

    $this->addSkeletonDir($this->extensionManager->getModule('daemons')->getTemplatePath());

    $this->renderFile(
      'daemon.plugin.php.twig',
      $this->extensionManager->getPluginPath($module, 'Daemons') . '/' . $class_name . '.php',
      $parameters
    );
  }

}
