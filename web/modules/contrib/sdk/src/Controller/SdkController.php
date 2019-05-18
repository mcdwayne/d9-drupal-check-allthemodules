<?php

namespace Drupal\sdk\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\sdk\SdkPluginManager;

/**
 * Class SdkController.
 */
class SdkController extends ControllerBase {

  /**
   * Instance of the "plugin.manager.sdk" service.
   *
   * @var SdkPluginManager
   */
  private $pluginManager;

  /**
   * SdkController constructor.
   *
   * @param \Drupal\sdk\SdkPluginManager $plugin_manager
   *   Instance of the "plugin.manager.sdk" service.
   */
  public function __construct(SdkPluginManager $plugin_manager) {
    $this->pluginManager = $plugin_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static($container->get('plugin.manager.sdk'));
  }

  /**
   * {@inheritdoc}
   */
  public function callback($sdk) {
    try {
      $redirect = $this->pluginManager->createInstance($sdk)->loginCallback();

      if ($redirect instanceof RedirectResponse) {
        return $redirect;
      }
    }
    catch (\Exception $e) {
      $this->getLogger('sdk')->notice($e->getMessage());
    }

    if (empty($_SESSION['destination'])) {
      $destination = $GLOBALS['base_url'];
    }
    else {
      $destination = $_SESSION['destination'];
      unset($_SESSION['destination']);
    }

    return new RedirectResponse($destination);
  }

}
