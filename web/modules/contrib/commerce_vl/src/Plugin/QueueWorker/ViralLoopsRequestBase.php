<?php

namespace Drupal\commerce_vl\Plugin\QueueWorker;

use Drupal\commerce_vl\ViralLoopsIntegratorInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Queue\QueueWorkerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class ViralLoopsRequestBase.
 *
 * @package Drupal\commerce_klaviyo\Plugin\QueueWorker
 */
abstract class ViralLoopsRequestBase extends QueueWorkerBase implements ContainerFactoryPluginInterface {

  /**
   * Drupal\commerce_vl\ViralLoopsIntegratorInterface definition.
   *
   * @var \Drupal\commerce_vl\ViralLoopsIntegratorInterface
   */
  protected $viralLoopsIntegrator;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, ViralLoopsIntegratorInterface $commerce_vl_integrator) {
    $this->viralLoopsIntegrator = $commerce_vl_integrator;
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('commerce_vl.integrator')
    );
  }

  /**
   * Return needed method name.
   *
   * @return string
   *   ViralLoopsIntegrator method name.
   */
  abstract protected function getMethodName();

  /**
   * {@inheritdoc}
   */
  public function processItem($data) {
    if (!empty($data['args'])) {
      call_user_func_array(
        [
          $this->viralLoopsIntegrator,
          $this->getMethodName(),
        ],
        $data['args']);
    }
  }

}
