<?php

namespace Drupal\img_annotator\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\system\SystemManager;

/**
 * Lists admin menu links at admin/config/img_annotator.
 */
class AdminConfigPage extends ControllerBase {
  protected $systemManager;

  public function __construct(SystemManager $systemManager) {
    $this->systemManager = $systemManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static($container->get('system.manager'));
  }

  /**
   * Provides a single block from the administration menu as a page.
   */
  public function systemAdminMenuBlockPage() {
    return $this->systemManager->getBlockContents();
  }

}
