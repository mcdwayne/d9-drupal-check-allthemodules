<?php

namespace Drupal\usajobs_integration\Plugin\Block;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Block\BlockBase;
use Drupal\usajobs_integration\RequestData;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a 'USAJobs' block.
 *
 * @Block(
 *   id = "usajobs_integration_block",
 *   admin_label = @Translation("USAJobs Job Listings"),
 * )
 */
class UsajobsIntegrationBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The RequestData service.
   *
   * @var \Drupal\usajobs_integration\RequestData
   */
  protected $requestData;

  /**
   * Constructs a new UsajobsIntegrationBlock instance.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\usajobs_integration\RequestData $request_data
   *   The usajobs_integration RequestData service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, RequestData $request_data) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->requestData = $request_data;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('usajobs_integration.request.data')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function blockAccess(AccountInterface $account) {
    return AccessResult::allowedIfHasPermission($account, 'access content');
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    return array(
      '#theme' => 'usajobs_integration_block',
      '#jobs' => $this->requestData->getJobListings(),
    );

  }

}
