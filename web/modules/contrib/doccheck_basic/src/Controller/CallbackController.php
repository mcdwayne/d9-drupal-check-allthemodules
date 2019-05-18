<?php

namespace Drupal\doccheck_basic\Controller;

use Drupal\Core\Language\LanguageManager;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\RequestStack;
use Drupal\doccheck_basic\DoccheckBasicCommon;

/**
 * DocCheck Basic Callback Controller.
 */
class CallbackController extends ControllerBase {

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
   * The variable containing the user manager.
   *
   * @var \Drupal\Core\Entity\UserStorageInterface
   */
  private $userManager;

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
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config
   *   The config service.
   * @param \Drupal\Core\Session\AccountProxyInterface $currentUser
   *   The current user service.
   * @param \Drupal\Core\Entity\EntityTypeManager $entityTypeManager
   *   The entity service.
   * @param \Symfony\Component\HttpFoundation\RequestStack $requestStack
   *   The request stack service.
   * @param \Drupal\Core\Language\LanguageManager $languageManager
   *   The language service.
   */
  public function __construct(LoggerChannelFactoryInterface $logger,
  ConfigFactoryInterface $config,
  AccountProxyInterface $currentUser,
  EntityTypeManager $entityTypeManager,
  RequestStack $requestStack,
  LanguageManager $languageManager
  ) {
    $this->logger = $logger;
    $this->config = $config->get('config.doccheck_basic');
    $this->currentUser = $currentUser;
    $this->userManager = $entityTypeManager->getStorage('user');
    $this->requestStack = $requestStack->getCurrentRequest();
    $this->languageManager = $languageManager;
  }

  /**
   * Dependency injection create.
   */
  public static function create(ContainerInterface $container) {
    return new static($container->get('logger.factory'),
    $container->get('config.factory'),
    $container->get('current_user'),
    $container->get('entity_type.manager'),
    $container->get('request_stack'),
    $container->get('language_manager'));
  }

  /**
   * Doccheck callback page, redirects to requested URL.
   */
  public function callbackPage() {
    \Drupal::service('page_cache_kill_switch')->trigger();

    $redirect_page = $this->requestStack->getSession()->get('dc_page');
    $this->requestStack->getSession()->remove('dc_page');

    if (strlen($redirect_page) == 0) {
      $err_msg = $this->t("No cookie found. DocCheck Basic login cookie error.");
      return self::errorMsg($err_msg);
    }
    if (strlen($this->config->get('dc_user')) < 1) {
      $err_msg = $this->t('DocCheck login user not set.');
      return self::errorMsg($err_msg);
    }
    if ($this->currentUser->getAccount()->isAnonymous()) {
      $dc_user = $this->userManager->load($this->config->get('dc_user'));
      if ($dc_user === FALSE) {
        $err_msg = $this->t('DocCheck login username not valid or not selected.');
        return self::errorMsg($err_msg);
      }
      if ($dc_user->hasRole('administrator')) {
        $err_msg = $this->t('DocCheck login user has administrator role.');
        return self::errorMsg($err_msg);
      }
      user_login_finalize($dc_user);
    }

    $response = new RedirectResponse($redirect_page);
    $response->send();
    return $response;
  }

  /**
   * Defines login page.
   */
  public function loginPage() {
    $docCheckBasicCommon = new DoccheckBasicCommon($this->logger, $this->config, $this->currentUser, $this->requestStack, $this->languageManager);
    return $docCheckBasicCommon->doccheckBasicLogin('page');
  }

  /**
   * Generate error message.
   */
  public function errorMsg($err_msg) {
    $this->logger->get('doccheck_basic')->error($err_msg);
    \Drupal::messenger()->addMessage($err_msg, 'error');
    return [
      '#type' => 'markup',
      '#markup' => '',
      '#cache' => [
        'max-age' => 0,
      ],
    ];
  }

}
