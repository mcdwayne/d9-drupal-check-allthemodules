<?php

namespace Drupal\smartid_auth\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Routing\UrlGeneratorInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Session\SessionManagerInterface;
use Drupal\user\PrivateTempStoreFactory;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class SmartidLoginForm.
 *
 * @package Drupal\smartid_auth\Form
 */
class SmartidLoginForm extends FormBase {

  /**
   * Logger service.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * TempStoreFactory service.
   *
   * @var \Drupal\user\PrivateTempStoreFactory
   */
  protected $tempStoreFactory;

  /**
   * PrivateTempStore object.
   *
   * @var \Drupal\user\PrivateTempStore
   */
  protected $store;

  /**
   * SessionManager service.
   *
   * @var \Drupal\Core\Session\SessionManagerInterface
   */
  protected $sessionManager;

  /**
   * Current User object.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * SmartidLoginForm constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   ConfigFactory service.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger
   *   Logger service.
   * @param \Drupal\Core\Routing\UrlGeneratorInterface $url_generator
   *   Url generator service.
   * @param \Drupal\user\PrivateTempStoreFactory $temp_store_factory
   *   Private temp store factory object.
   * @param \Drupal\Core\Session\SessionManagerInterface $session_manager
   *   Session manager service.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   Current user account.
   */
  public function __construct(ConfigFactoryInterface $config_factory,
                              LoggerChannelFactoryInterface $logger,
                              UrlGeneratorInterface $url_generator,
                              PrivateTempStoreFactory $temp_store_factory,
                              SessionManagerInterface $session_manager,
                              AccountInterface $current_user) {
    $this->setConfigFactory($config_factory);
    $this->setLoggerFactory($logger);
    $this->setUrlGenerator($url_generator);

    $this->tempStoreFactory = $temp_store_factory;
    $this->sessionManager = $session_manager;
    $this->currentUser = $current_user;

    $this->store = $this->tempStoreFactory->get('smartid_auth.smart_id');
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'smartid_auth_login_form';
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('logger.factory'),
      $container->get('url_generator'),
      $container->get('user.private_tempstore'),
      $container->get('session_manager'),
      $container->get('current_user')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['#attached']['library'][] = 'smartid_auth/login';

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    // Nothing to see here.
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Nothing to see here.
  }

}
