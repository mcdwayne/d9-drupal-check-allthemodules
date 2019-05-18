<?php

namespace Drupal\inmail\Controller;

use Drupal\Component\Plugin\PluginManagerInterface;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Datetime\DateFormatter;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\inmail\Entity\DelivererConfig;
use Drupal\inmail\Plugin\inmail\Deliverer\FetcherInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\Core\Url;

/**
 * Route controller for mail deliverers.
 *
 * @ingroup deliverer
 */
class DelivererController extends ControllerBase implements ContainerInjectionInterface {

  /**
   * The injected deliverer plugin manager.
   *
   * @var \Drupal\Component\Plugin\PluginManagerInterface
   */
  protected $delivererManager;

  /**
   * {@inheritdoc}
   */
  public function __construct(PluginManagerInterface $deliverer_manager) {
    $this->delivererManager = $deliverer_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.inmail.deliverer')
    );
  }

  /**
   * Returns a title for the deliverer configuration edit page.
   */
  public function titleEdit(DelivererConfig $inmail_deliverer) {
    return $this->t('Configure deliverer %label', array('%label' => $inmail_deliverer->label()));
  }

  /**
   * Enables a mail deliverer.
   */
  public function enable(DelivererConfig $inmail_deliverer) {
    $inmail_deliverer->enable()->save();
    return new RedirectResponse(Url::fromRoute('entity.inmail_deliverer.collection', [], ['absolute' => TRUE])->toString());
  }

  /**
   * Disables a mail deliverer.
   */
  public function disable(DelivererConfig $inmail_deliverer) {
    $inmail_deliverer->disable()->save();
    return new RedirectResponse(Url::fromRoute('entity.inmail_deliverer.collection', [], ['absolute' => TRUE])->toString());
  }

}
