<?php

namespace Drupal\drop_down_login\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Cache\Cache;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Session\AccountProxy;
use Drupal\Core\Routing\UrlGeneratorInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Block\BlockManagerInterface;

/**
 * Provides a 'Drop Down Login' block.
 *
 * @Block(
 *   id = "drop_down_login_block",
 *   admin_label = @Translation("Drop Down Login")
 * )
 */
class DropDownLoginBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The instantiated account.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $account;

  /**
   * The url generator.
   *
   * @var \Drupal\Core\Routing\UrlGeneratorInterface
   */
  protected $urlGenerator;

  /**
   * The config_factory variable.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The Plugin Block Manager.
   *
   * @var \Drupal\Core\Block\BlockManagerInterface
   */
  protected $blockManager;

  /**
   * Constructs a new BlockContentBlock.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Session\AccountProxy $account
   *   The account service.
   * @param \Drupal\Core\Routing\UrlGeneratorInterface $urlGenerator
   *   The URL generator.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The configuration factory.
   * @param \Drupal\Core\Block\BlockManagerInterface $block_manager
   *   The Plugin Block Manager.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, AccountProxy $account, UrlGeneratorInterface $urlGenerator, ConfigFactoryInterface $configFactory, BlockManagerInterface $block_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->account       = $account;
    $this->urlGenerator  = $urlGenerator;
    $this->configFactory = $configFactory;
    $this->blockManager  = $block_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('current_user'),
      $container->get('url_generator'),
      $container->get('config.factory'),
      $container->get('plugin.manager.block')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    // Checking User anonymous then open block.
    $user = $this->account->isAnonymous();

    // For anonymous user.
    if ($user) {
      // User Login Block.
      $customblock = $this->blockManager->createInstance('user_login_block', []);
      $login_form = $customblock->build();
      $login_url = $this->urlGenerator->generateFromRoute('user.page');
      $login_link_text = $this->t('Login');

      $output = [
        '#attached' => [
          'library' => [
            'drop_down_login/drop_down_login_setting',
          ],
        ],
        '#theme' => 'drop_down_login',
        '#login_form' => $login_form,
        '#login_url' => $login_url,
        '#login_link_text' => $login_link_text,
      ];
    }
    // For Authenticate user.
    else {
      // Get Configuration.
      $settings = $this->configFactory->getEditable('drop_down_login.admin.settings');
      $myAccount = $settings->get('drop_down_login_want_myaccount');
      if (!empty($myAccount['drop_down_login_want_myaccount']) && isset($myAccount['drop_down_login_want_myaccount'])) {
        $myAccountLinkConfig = $settings->get('drop_down_login_myaccount_links');
        $myaccount_url = $this->urlGenerator->generateFromRoute('user.page');
        $logout_url = $this->urlGenerator->generateFromRoute('user.logout');
        $logout_link_text = $this->t('Log Out');
        $myaccount_text = $this->t('My Account');
        $myAccountlink = $myAccountLinkConfig['table'];
        $output = [
          '#attached' => [
            'library' => [
              'drop_down_login/drop_down_login_setting',
            ],
          ],
          '#theme' => 'drop_down_myaccount',
          '#myaccount_links' => $myAccountlink,
          '#myaccount_url' => $myaccount_url,
          '#myaccount_link_text' => $myaccount_text,
          '#name' => $this->account->getUsername(),
          '#logout_url' => $logout_url,
          '#logout_link_text' => $logout_link_text,
        ];
      }
      else {
        $logout_url = $this->urlGenerator->generateFromRoute('user.logout');
        $logout_link_text = $this->t('Log Out');
        $output = [
          '#attached' => [
            'library' => [
              'drop_down_login/drop_down_login_setting',
            ],
          ],
          '#theme' => 'drop_down_logout',
          '#logout_url' => $logout_url,
          '#logout_link_text' => $logout_link_text,
        ];
      }
    }
    return $output;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheTags() {
    return Cache::mergeTags(parent::getCacheTags(), array('user:' . $this->account->id()));
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts() {
    return Cache::mergeContexts(parent::getCacheContexts(), ['user']);
  }

}
