<?php

namespace Drupal\doccheck_basic;

use Drupal\Core\Language\LanguageManager;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\doccheck_basic\Form\SettingsForm;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Contains \Drupal\doccheck_basic\DoccheckBasicCommon.
 */
class DoccheckBasicCommon implements ContainerFactoryPluginInterface {
  use StringTranslationTrait;

  /**
   * The variable containing the conditions configuration.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  private $config;

  /**
   * The variable containing the logging.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactory
   */
  private $logger;

  /**
   * The variable containing the current user.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
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
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger
   *   The logger service.
   * @param \Drupal\Core\Config\ImmutableConfig $config
   *   The config service.
   * @param \Drupal\Core\Session\AccountProxyInterface $currentUser
   *   The current user service.
   * @param \Symfony\Component\HttpFoundation\Request $requestStack
   *   The request stack service.
   * @param \Drupal\Core\Language\LanguageManager $languageManager
   *   The language service.
   */
  public function __construct(LoggerChannelFactoryInterface $logger,
  ImmutableConfig $config,
  AccountProxyInterface $currentUser,
  Request $requestStack,
  LanguageManager $languageManager
  ) {
    $this->logger = $logger;
    $this->config = $config;
    $this->currentUser = $currentUser;
    $this->requestStack = $requestStack;
    $this->languageManager = $languageManager;
  }

  /**
   * Dependency injection create.
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static($container->get('logger.factory'),
    $container->get('config.factory'),
    $container->get('current_user'),
    $container->get('request_stack'),
    $container->get('language_manager'));
  }

  /**
   * Defines doccheck login for block and page.
   */
  public function doccheckBasicLogin($template) {
    \Drupal::service('page_cache_kill_switch')->trigger();
    if (strlen($this->config->get('dc_loginid')) < 1) {
      $err_msg = $this->t('DocCheck Login ID not set');
      $this->logger->get('doccheck_basic')->error($err_msg);
      \Drupal::messenger()->addMessage($err_msg, 'error');
      return [
        '#markup' => '',
        '#cache' => [
          'max-age' => 0,
        ],
      ];
    }

    if ($this->currentUser->getAccount()->isAnonymous()) {
      $this->requestStack->getSession()->set('forced', TRUE);
    }
    elseif ($template === 'page') {
      return [
        '#theme' => 'doccheck_basic',
        '#loggedin' => TRUE,
        '#dctemplate' => NULL,
        '#width' => NULL,
        '#height' => NULL,
        '#language' => NULL,
        '#loginid' => NULL,
        '#devmode' => NULL,
        '#cache' => [
          'max-age' => 0,
        ],
      ];

    }
    if ($template === 'page' && $this->config->get('dc_noderedirect') !== '') {
      $this->requestStack->getSession()->set('dc_page',
        $this->config->get('dc_noderedirect'));
    }
    else {
      $this->requestStack->getSession()->set('dc_page', $this->requestStack->getRequestUri());
    }

    return [
      '#theme' => 'doccheck_basic',
      '#loggedin' => FALSE,
      '#dctemplate' => ($this->config->get('dc_template') == SettingsForm::CUSTOM_TEMPLATE) ? ($this->config->get('dc_template_custom')) : ($this->config->get('dc_template')),
      '#width' => $this->templateSize($this->config->get('dc_template'), 'w'),
      '#height' => $this->templateSize($this->config->get('dc_template'), 'h'),
      '#language' => $this->languageManager->getCurrentLanguage()->getId(),
      '#loginid' => $this->config->get('dc_loginid'),
      '#devmode' => ($this->config->get('dc_devmode')) ?
      ('<a href="' . $this->requestStack->getSchemeAndHttpHost() . '/_dc_callback">' . $this->t('Development mode login') . '</a>') :
      (''),
      '#cache' => [
        'max-age' => 0,
      ],
    ];
  }

  /**
   * Returns width or height of doccheck template.
   */
  public function templateSize($type, $dimension) {
    $template_size = [
      's_red' => ['w' => 156, 'h' => 203],
      'm_red' => ['w' => 311, 'h' => 188],
      'l_red' => ['w' => 424, 'h' => 215],
      'xl_red' => ['w' => 467, 'h' => 231],
      'login_s' => ['w' => 156, 'h' => 203],
      'login_m' => ['w' => 311, 'h' => 195],
      'login_l' => ['w' => 424, 'h' => 215],
      'login_xl' => ['w' => 467, 'h' => 231],
      SettingsForm::CUSTOM_TEMPLATE => [
        'w' => $this->config->get('dc_template_custom_width'),
        'h' => $this->config->get('dc_template_custom_height'),
      ],
    ];
    return (isset($template_size[$type])) ?
      ($template_size[$type][$dimension]) : (FALSE);
  }

}
