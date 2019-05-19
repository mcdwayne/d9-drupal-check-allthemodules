<?php

namespace Drupal\sapi\Plugin\Statistics\ActionHandler;

use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\sapi\ActionHandlerBase;
use Drupal\sapi\ActionHandlerInterface;
use Drupal\sapi\ActionTypeInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * @ActionHandler (
 *  id = "action_logger",
 *  label = "Log any received actions"
 * )
 *
 * Testing action handler, which only logs the ->describe() string of every
 * received plugin, so that you can check the Drupal logs to see if actions
 * are being sent properly.
 *
 */
class ActionLogger extends ActionHandlerBase implements ActionHandlerInterface, ContainerFactoryPluginInterface {

  /**
   * Logger used to track handling
   *
   * @protected \Drupal\Core\Logger\LoggerChannelInterface $logger
   */
  protected $logger;

  /**
   * {@inheritdoc}
   *
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $loggerFactory
   *   a logger factory, which will be used to generate the logger
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, LoggerChannelFactoryInterface $loggerFactory) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    // don't keep the whole factory, just the one logger
    $this->logger = $loggerFactory->get('sapi');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,

      $container->get('logger.factory')
    );
  }


  /**
   * (@inheritdoc)
   */
  function process(ActionTypeInterface $action) {
    // just throw the action at the log for now.
    $this->logger->info($action->describe());
  }

}
