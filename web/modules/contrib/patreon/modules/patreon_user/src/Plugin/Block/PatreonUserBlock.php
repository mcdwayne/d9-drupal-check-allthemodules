<?php

namespace Drupal\patreon_user\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\patreon_user\PatreonUserService;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;

/**
 * Provides a 'PatreonUserBlock' block.
 *
 * @Block(
 *  id = "patreon_user_block",
 *  admin_label = @Translation("Patreon user block"),
 * )
 */
class PatreonUserBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * Drupal\patreon_user\PatreonUserService definition.
   *
   * @var \Drupal\patreon_user\PatreonUserService
   */
  protected $patreonUserApi;

  /**
   * Constructs a new PatreonUserBlock object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param string $plugin_definition
   *   The plugin implementation definition.
   */
  public function __construct(
        array $configuration,
        $plugin_id,
        $plugin_definition,
        PatreonUserService $patreon_user_api
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->patreonUserApi = $patreon_user_api;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('patreon_user.api')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function blockAccess(AccountInterface $account) {
    if ($account->isAnonymous()) {
      return AccessResult::allowed()
        ->addCacheContexts(['user.roles:anonymous']);
    }
    return AccessResult::forbidden();
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $config = \Drupal::config('patreon.settings');
    $key = $config->get('patreon_client_id');
    $url = $this->patreonUserApi->authoriseAccount($key, FALSE);
    $build = [];
    $build['patreon_user_block'] = [
      '#title' => $this->t('Login via Patreon'),
      '#type' => 'link',
      '#url' => $url,
    ];

    return $build;
  }

}
