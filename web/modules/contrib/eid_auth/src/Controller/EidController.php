<?php

namespace Drupal\eid_auth\Controller;

use BitWeb\IdServices\Authentication\MobileID\AuthenticationService;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\RedirectCommand;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Path\PathValidatorInterface;
use Drupal\Core\Url;
use Drupal\Core\PageCache\ResponsePolicy\KillSwitch;
use Drupal\eid_auth\Authentication\EidAuthentication;
use Drupal\user\PrivateTempStoreFactory;
use Sk\SmartId\Api\AuthenticationResponseValidator;
use Sk\SmartId\Api\Data\CertificateLevelCode;
use Sk\SmartId\Api\Data\NationalIdentity;
use Sk\SmartId\Client;
use Sk\SmartId\Exception\SmartIdException;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Class EidController.
 *
 * @package Drupal\eid_auth\Controller
 */
class EidController extends ControllerBase {

  /**
   * Path validator service.
   *
   * @var \Drupal\Core\Path\PathValidator
   */
  protected $pathValidator;

  /**
   * Logger service.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * KillSwitch service.
   *
   * @var \Drupal\Core\PageCache\ResponsePolicy\KillSwitch
   */
  protected $killSwitch;

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
   * EidController constructor.
   *
   * @param \Drupal\Core\Path\PathValidatorInterface $path_validator
   *   Path validator service.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger
   *   Logger service.
   * @param \Drupal\Core\PageCache\ResponsePolicy\KillSwitch $kill_switch
   *   KillSwitch service.
   * @param \Drupal\user\PrivateTempStoreFactory $temp_store_factory
   *   Private temp store factory object.
   */
  public function __construct(
    PathValidatorInterface $path_validator,
    LoggerChannelFactoryInterface $logger,
    KillSwitch $kill_switch,
    PrivateTempStoreFactory $temp_store_factory) {
    $this->pathValidator = $path_validator;
    $this->logger = $logger->get('eid_auth');
    $this->killSwitch = $kill_switch;
    $this->tempStoreFactory = $temp_store_factory;
    $this->store = $this->tempStoreFactory->get('eid_auth.smart_id');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('path.validator'),
      $container->get('logger.factory'),
      $container->get('page_cache_kill_switch'),
      $container->get('user.private_tempstore')
    );
  }

  /**
   * The main login page.
   *
   * @return \Symfony\Component\HttpFoundation\Response|array
   *   Redirect to user page if already logged in or
   *   authentication form render array.
   */
  public function login() {
    $user = $this->currentUser();

    if ($user->isAuthenticated()) {
      // Redirect user to user.page.
      return $this->redirect('user.page');
    }

    $eid_form = $this->formBuilder()->getForm('Drupal\eid_auth\Form\EidLoginForm');

    return [
      '#theme' => 'eid_auth_login_content',
      '#forms' => [
        'eid_auth_form' => $eid_form,
      ],
    ];
  }

  /**
   * ID-Card authentication.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   Redirect user to page depending on authentication result.
   */
  public function idCard() {
    $this->killSwitch->trigger();

    if (!EidAuthentication::isSuccessful()) {
      drupal_set_message($this->t('Authentication failed'), 'error');
      return $this->redirect('eid_auth.eid_controller_login');
    }
    else {
      EidAuthentication::login();
      return new RedirectResponse($this->getLoginRedirect()->toString());
    }
  }

  /**
   * Mobile-ID authentication check.
   *
   * @param string $session
   *   Mobile-ID service authentication session ID.
   * @param string $personal_id_code
   *   User personal ID code.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   Redirect user.
   *
   * @throws \BitWeb\IdServices\Exception\ServiceException
   */
  public function mobileIdCheck($session, $personal_id_code) {
    $config = $this->config('eid_auth.settings');
    $commands = new AjaxResponse();
    $service = new AuthenticationService();
    $service->setWsdl($config->get('mobile_id_wsdl'));
    $service->initSoap();
    $tries = 0;

    try {
      while ($response = $service->getMobileAuthenticateStatus($session, FALSE)) {
        $tries++;
        $status = $response->getStatus();

        if (in_array($status, ['OUTSTANDING_TRANSACTION', 'REQUEST_OK']) && $tries < 10) {
          $this->logger->notice('Mobile-id status: @message', ['@message' => $status]);
          sleep(5);
          continue;
        }
        elseif ($status == 'USER_AUTHENTICATED') {
          /* @var $user \Drupal\user\UserInterface */
          $user = EidAuthentication::findUserByPersonalIdCode($personal_id_code);

          if ($user) {
            user_login_finalize($user);
            $commands->addCommand(new RedirectCommand($this->getLoginRedirect()->toString()));
          }
          else {
            $message = $this->t('Login failed: user not found!');
            $this->logger->error('Mobile-id error: @message', ['@message' => $message]);
          }
        }
        elseif ($status == 'EXPIRED_TRANSACTION' || $tries == 10) {
          $message = $this->t('Mobile-ID request timeout!');
          $this->logger->error('Mobile-id error: @message', ['@message' => $message]);
        }
        else {
          $message = $this->t('Request failed with error code: @code', ['@code' => $response->getStatus()]);
        }

        break;
      }
    }
    catch (\Exception $e) {
      $message = $this->t('Request failed: @code', ['@code' => $e->getMessage()]);
      $this->logger->error('Mobile-id error: @message', ['@message' => $e->getMessage()]);
    }

    if (isset($message)) {
      drupal_set_message($message, 'error');

      $commands->addCommand(new RedirectCommand($this->getLoginRedirect()->toString()));
    }

    return $commands;
  }

  /**
   * Smart-ID authentication status check.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   Redirect response.
   */
  public function smartIdCheck() {
    $commands = new AjaxResponse();
    $config = $config = $this->config('eid_auth.settings');
    $client = new Client();
    $client->setRelyingPartyUUID($config->get('smart_id_relying_party_uuid'))
      ->setRelyingPartyName($config->get('smart_id_relying_party_name'))
      ->setHostUrl($config->get('smart_id_host_url'));

    // Consists of country and personal identity code.
    $identity = new NationalIdentity('EE', $this->store->get('personal_id_code'));

    $authenticationHash = $this->store->get('auth_hash');
    // Clean up.
    $this->store->delete('auth_hash');

    try {
      $authenticationResponse = $client->authentication()
        ->createAuthentication()
        ->withNationalIdentity($identity)
        ->withAuthenticationHash($authenticationHash)
        ->withCertificateLevel(CertificateLevelCode::QUALIFIED)
        ->authenticate();
    }
    catch (SmartIdException $e) {
      $this->logger->error('Smart-id exception: @type', ['@type' => get_class($e)]);
    }

    if (isset($authenticationResponse)) {
      $resource_location = NULL;

      if ($config->get('smart_id_resource_location')) {
        $resource_location = $config->get('smart_id_resource_location');
      }

      $authenticationResponseValidator = new AuthenticationResponseValidator($resource_location);
      $authenticationResult = $authenticationResponseValidator->validate($authenticationResponse);

      // Authentication validity result.
      $isValid = $authenticationResult->isValid();

      if ($isValid) {
        $auth_identity = $authenticationResult->getAuthenticationIdentity();

        $extracted_id = EidAuthentication::smartIdextractUserPersonalIdCode($auth_identity->getIdentityCode());

        /* @var $user \Drupal\user\UserInterface */
        $user = EidAuthentication::findUserByPersonalIdCode($extracted_id);

        if ($user) {
          user_login_finalize($user);
          $commands->addCommand(new RedirectCommand($this->getLoginRedirect()->toString()));
        }
        else {
          $message = $this->t('Login failed: user not found!');
          $this->logger->error('Smart-id error: @message', ['@message' => $message]);
        }
      }
      else {
        $errors = $authenticationResult->getErrors();
        $this->logger->error('Smart-id status: @message', ['@message' => $errors[0]]);
      }

    }
    else {
      drupal_set_message($this->t('Smart-ID login failed!'), 'error');
      $this->logger->error('Smart-id status: Authentication response not set!');
    }

    return $commands;
  }

  /**
   * Determine login redirect.
   *
   * @return \Drupal\Core\Url
   *   Url object.
   */
  private function getLoginRedirect() {
    $config = $this->config('eid_auth.settings');
    $redirect_path = $config->get('login_redirect');

    if (empty($redirect_path)) {
      $url = Url::fromRoute('<front>');
    }
    else {
      $url = $this->pathValidator->getUrlIfValidWithoutAccessCheck($redirect_path);

      if (!$url) {
        $url = Url::fromRoute('<front>');
      }
    }

    return $url;
  }

}
