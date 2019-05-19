<?php

namespace Drupal\social_connect\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a block for Social Connect.
 *
 * @Block(
 *   id = "social_connect_block",
 *   admin_label = @Translation("Social Connect"),
 *   category = @Translation("Social Connect")
 * )
 */
class SocialConnectBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The Current User object.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * Constructs a new SwitchUserBlock object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   Current user.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, AccountInterface $current_user) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->currentUser = $current_user;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
        $configuration, $plugin_id, $plugin_definition, $container->get('current_user')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function blockAccess(AccountInterface $account) {
    if ($account->isAnonymous()) {
      return AccessResult::allowed();
    }
    return AccessResult::forbidden();
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $configs = \Drupal::configFactory()->get('social_connect.settings');

    $global_settings = $configs->get('global');
    $connection_settings = $configs->get('connections');
    $items = [];
    foreach ($connection_settings as $source => $connection_setting) {
      if ($connection_setting['enable']) {
        $items[] = [
          'source' => $source,
          'button_text' => t($connection_setting['button_text']),
          'id' => 'sc-id-' . $source
        ];
      }
    }

    if (!empty($items)) {
      // Add js library and settings.
      $fb_settings = $connection_settings['facebook'];
      $google_settings = $connection_settings['google'];
      $output = [
        '#theme' => 'social_connect_block',
        '#items' => $items,
        '#attached' => [
          'library' => [
            'social_connect/libraries',
          ],
          'drupalSettings' => [
            'social_connect' => [
              'debug' => $global_settings['debug'],
              'redirect_to' => $global_settings['redirect_to'],
              'facebook' => [
                'app_id' => $fb_settings['app_id'],
                'api_version' => $fb_settings['api_version']
              ],
              'google' => [
                'client_id' => $google_settings['client_id']
              ]
            ],
          ],
        ],
        '#cache' => ['max-age' => 0],
      ];
      return $output;
    }
  }

}
