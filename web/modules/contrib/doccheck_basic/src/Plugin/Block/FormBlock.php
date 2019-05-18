<?php

namespace Drupal\doccheck_basic\Plugin\Block;

use Drupal\Core\Language\LanguageManager;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\Block\BlockBase;
use Drupal\doccheck_basic\DoccheckBasicCommon;
use Symfony\Component\HttpFoundation\RequestStack;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a 'doccheck_basic' block.
 *
 * @Block(
 *   id = "doccheck_basic",
 *   admin_label = @Translation("DocCheck Basic"),
 *   category = @Translation("Login")
 * )
 */
class FormBlock extends BlockBase implements ContainerFactoryPluginInterface {
  /**
   * The variable containing the logging.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactory
   */
  private $logger;

  /**
   * The variable containing the conditions configuration.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  private $config;

  /**
   * The variable containing the current user.
   *
   * @var AccountProxy
   */
  protected $currentUser;

  /**
   * The variable containing the request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * The variable containing the language manager.
   *
   * @var \Drupal\Core\Language\LanguageManager
   */
  protected $languageManager;

  /**
   * Dependency injection through the constructor.
   *
   * @param array $configuration
   *   The block configuration.
   * @param string $plugin_id
   *   The plugin id.
   * @param mixed $plugin_definition
   *   The plugin definition.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger
   *   The logger service.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config
   *   The config service.
   * @param \Drupal\Core\Session\AccountProxyInterface $currentUser
   *   The current user service.
   * @param \Symfony\Component\HttpFoundation\RequestStack $requestStack
   *   The request stack service.
   * @param \Drupal\Core\Language\LanguageManager $languageManager
   *   The language service.
   */
  public function __construct(array $configuration,
  $plugin_id,
  $plugin_definition,
  LoggerChannelFactoryInterface $logger,
  ConfigFactoryInterface $config,
  AccountProxyInterface $currentUser,
  RequestStack $requestStack,
  LanguageManager $languageManager
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->logger = $logger;
    $this->config = $config->get('config.doccheck_basic');
    $this->currentUser = $currentUser;
    $this->requestStack = $requestStack->getCurrentRequest();
    $this->languageManager = $languageManager;
  }

  /**
   * Dependency injection create.
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static($configuration,
    $plugin_id,
    $plugin_definition,
    $container->get('logger.factory'),
    $container->get('config.factory'),
    $container->get('current_user'),
    $container->get('request_stack'),
    $container->get('language_manager'));
  }

  /**
   * Doccheck login block.
   */
  public function build() {
    $docCheckBasicCommon = new DoccheckBasicCommon($this->logger, $this->config, $this->currentUser, $this->requestStack, $this->languageManager);
    return $docCheckBasicCommon->doccheckBasicLogin('block');

  }

  /**
   * No caching for login block.
   */
  public function getCacheMaxAge() {
    \Drupal::service('page_cache_kill_switch')->trigger();
    return 0;
  }

}
