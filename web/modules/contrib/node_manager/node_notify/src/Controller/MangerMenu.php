<?php


namespace Drupal\node_notify\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\system\SystemManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

class MangerMenu extends ControllerBase {

  /**
   * System Manager Service.
   *
   * @var \Drupal\system\SystemManager
   */
  protected $systemManager;

  public function __construct(SystemManager $systemManager) {
    $this->systemManager = $systemManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('system.manager')
    );
  }

  /**
   * Provides a single block from the administration menu as a page.
   */
  public function nodeMenuBlockPage() {
    return $this->systemManager->getBlockContents();
  }

}