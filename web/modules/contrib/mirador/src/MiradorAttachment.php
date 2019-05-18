<?php

/**
 * @file
 * An implementation of PageAttachmentInterface for the mirador library.
 */

namespace Drupal\mirador;

use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Config\ConfigFactoryInterface;

/**
 * An implementation of PageAttachmentInterface for the mirador library.
 */
class MiradorAttachment implements ElementAttachmentInterface {

  /**
   * The service to determine if mirador should be activated.
   *
   * @var \Drupal\mirador\ActivationCheckInterface
   */
  protected $activation;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The mirdor settings.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $settings;

  /**
   * Create an instance of MiradorAttachment.
   */
  public function __construct(ActivationCheckInterface $activation, ModuleHandlerInterface $module_handler, ConfigFactoryInterface $config) {

    $this->activation = $activation;
    $this->moduleHandler = $module_handler;
    $this->settings = $config->get('mirador.settings');
  }

  /**
   * {@inheritdoc}
   */
  public function isApplicable() {

    return !drupal_installation_attempted() && $this->activation->isActive();
  }

  /**
   * {@inheritdoc}
   */
  public function attach(array &$page) {

    $page['#attached']['library'][] = 'mirador/mirador';
    $page['#attached']['library'][] = 'mirador/init';
  }

}
