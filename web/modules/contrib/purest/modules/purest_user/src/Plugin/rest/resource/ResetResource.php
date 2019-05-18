<?php

namespace Drupal\purest_user\Plugin\rest\resource;

use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\rest\ModifiedResourceResponse;
use Drupal\rest\Plugin\ResourceBase;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpFoundation\Request;
use Drupal\purest_user\PasswordChangeTokenServiceInterface;
use Drupal\Core\Config\ConfigFactoryInterface;

/**
 * Provides a resource to change a user password using a token.
 *
 * @RestResource(
 *   id = "purest_user_reset_resource",
 *   label = @Translation("Purest Password Reset Resource"),
 *   uri_paths = {
 *     "canonical" = "/purest/user/reset"
 *   }
 * )
 */
class ResetResource extends ResourceBase {

  /**
   * A current user instance.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * EntityTypeManagerInterface.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The reCAPTCHA response.
   *
   * @var bool
   */
  protected $recaptchaResponse;

  /**
   * PasswordChangeTokenServiceInterface.
   *
   * @var \Drupal\purest\PasswordChangeTokenService
   */
  protected $passwordChangeService;

  /**
   * Config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Constructs a new UserActivationRestResource object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param array $serializer_formats
   *   The available serialization formats.
   * @param \Psr\Log\LoggerInterface $logger
   *   A logger instance.
   * @param \Drupal\Core\Session\AccountProxyInterface $current_user
   *   A current user instance.
   * @param \Drupal\purest_user\PasswordChangeTokenServiceInterface $password_change
   *   Password change service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Symfony\Component\HttpFoundation\Request $current_request
   *   The current request.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
  $plugin_definition,
    array $serializer_formats,
    LoggerInterface $logger,
    AccountProxyInterface $current_user,
    PasswordChangeTokenServiceInterface $password_change,
    EntityTypeManagerInterface $entity_type_manager,
    Request $current_request,
    ConfigFactoryInterface $config_factory) {

    parent::__construct(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $serializer_formats,
      $logger
    );

    $this->currentUser = $current_user;
    $this->entityTypeManager = $entity_type_manager;
    $this->currentRequest = $current_request;
    $this->recaptchaResponse = $this->currentRequest
      ->query->get('recaptcha');
    $this->passwordChangeService = $password_change;
    $this->configFactory = $config_factory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->getParameter('serializer.formats'),
      $container->get('logger.factory')->get('purest_user'),
      $container->get('current_user'),
      $container->get('purest_user.reset'),
      $container->get('entity_type.manager'),
      $container->get('request_stack')->getCurrentRequest(),
      $container->get('config.factory')
    );
  }

  /**
   * Responds to PATCH requests.
   *
   * @param array $data
   *   The request parameters.
   *
   * @return \Drupal\rest\ModifiedResourceResponse
   *   The HTTP response object.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\HttpException
   *   Throws exception expected.
   */
  public function patch(array $data) {
    if (
        (!isset($data['id']) || empty($data['id'])) ||
        (!isset($data['token']) || empty($data['token'])) ||
        (!isset($data['timestamp']) || empty($data['timestamp'])) ||
        (!isset($data['password']) || empty($data['password']))
      ) {
      throw new BadRequestHttpException(t('ID, token, timestamp and password must be provided.'));
    }

    if (!is_string($data['password']) || strlen($data['password']) < 6) {
      throw new BadRequestHttpException(t('The password must be at least 6 characters.'));
    }

    // If Purest Recaptcha module is enabled check if it should
    // be used on this resource.
    if (\Drupal::moduleHandler()->moduleExists('purest_recaptcha')) {
      $purest_user_config = $this->configFactory->get('purest_user.settings');
      $resources_recaptcha = $purest_user_config->get('resources_recaptcha');
      $use_recaptcha = $resources_recaptcha['register'];

      if ($use_recaptcha) {
        $recaptcha_service = \Drupal::service('purest_recaptcha.recaptcha');

        if (!is_string($this->recaptchaResponse)) {
          throw new BadRequestHttpException(t('reCAPTCHA query string must be present.'));
        }

        $recaptcha_valid = $recaptcha_service->validate($this->recaptchaResponse);

        if (!$recaptcha_valid) {
          throw new BadRequestHttpException(t('reCAPTCHA validation failed.'));
        }
      }
    }

    $user_storage = $this->entityTypeManager->getStorage('user');
    $account = $user_storage->load($data['id']);

    if (!$account) {
      throw new BadRequestHttpException(t('User does not exist.'));
    }

    // Blocked accounts cannot request a new password.
    if (!$account->isActive()) {
      throw new BadRequestHttpException(t('Password reset link cannot be issued for this account.'));
    }

    $changed = $this->passwordChangeService->changePassword(
      $account,
      $data['token'],
      $data['timestamp'],
      $data['password']
    );

    if (!$changed) {
      throw new BadRequestHttpException(t('Password could not be changed. Please request a new token.'));
    }

    $this->logger->notice(t('Password changed for user @username with email address @email'),
    [
      '@username' => $account->get('name')->value,
      '@email' => $account->get('mail')->value,
    ]);

    $mailed = $this->passwordChangeService
      ->sendPasswordChangedConfirmationEmail($account);

    if (!$mailed) {
      // Log failed emails as actual user might not have requested change.
      $this->logger->notice(t('Email could not be delivered after password change for user @username with email address @email'),
      [
        '@username' => $account->get('name')->value,
        '@email' => $account->get('mail')->value,
      ]);
    }

    return new ModifiedResourceResponse([
      'message' => t('Your password was successfully changed. You may now sign in.'),
    ], 200);
  }

}
