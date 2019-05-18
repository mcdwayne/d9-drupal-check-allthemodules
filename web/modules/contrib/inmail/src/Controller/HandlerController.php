<?php

namespace Drupal\inmail\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\inmail\HandlerConfigInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\Core\Url;

/**
 * Route controller for message handlers.
 *
 * @ingroup handler
 */
class HandlerController extends ControllerBase {

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static($container->get('plugin.manager.inmail.handler'));
  }

  /**
   * Returns a title for the handler configuration edit page.
   */
  public function titleEdit(HandlerConfigInterface $inmail_handler) {
    return $this->t('Configure %label handler', array('%label' => $inmail_handler->label()));
  }

  /**
   * Enables a message handler.
   */
  public function enable(HandlerConfigInterface $inmail_handler) {
    $inmail_handler->enable()->save();
    return new RedirectResponse(Url::fromRoute('entity.inmail_handler.collection', [], ['absolute' => TRUE])->toString());
  }

  /**
   * Disables a message handler.
   */
  public function disable(HandlerConfigInterface $inmail_handler) {
    $inmail_handler->disable()->save();
    return new RedirectResponse(Url::fromRoute('entity.inmail_handler.collection', [], ['absolute' => TRUE])->toString());
  }

}
