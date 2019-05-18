<?php

namespace Drupal\purest_user\Plugin\rest\resource;

use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\Session\AccountInterface;
use Drupal\rest\ModifiedResourceResponse;
use Drupal\user\UserInterface;
use Drupal\user\Plugin\rest\resource\UserRegistrationResource;
use Drupal\purest\Plugin\rest\resource\EntityResourceValidationTrait;
use Drupal\purest_user\AccountValidationServiceInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Drupal\Core\Config\ConfigFactoryInterface;

/**
 * Provides a resource to get view modes by entity and bundle.
 *
 * @RestResource(
 *   id = "user_user_register_resource",
 *   label = @Translation("Purest User Register"),
 *   serialization_class = "Drupal\user\Entity\User",
 *   uri_paths = {
 *     "https://www.drupal.org/link-relations/create" = "/purest/user/register",
 *   }
 * )
 */
class RegisterResource extends UserRegistrationResource {

  use EntityResourceValidationTrait;

  /**
   * The current request.
   *
   * @var \Symfony\Component\HttpFoundation\Request
   */
  protected $currentRequest;

  /**
   * The reCAPTCHA response.
   *
   * @var string
   */
  protected $recaptchaResponse;

  /**
   * The account validation service.
   *
   * @var \Drupal\purest_user\AccountValidationServiceInterface
   */
  protected $accountValidationService;

  /**
   * Drupal\Core\Config\ConfigFactoryInterface definition.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Constructs a new UserRegistrationResource instance.
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
   * @param \Drupal\Core\Config\ImmutableConfig $user_settings
   *   A user settings config instance.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   * @param \Symfony\Component\HttpFoundation\Request $current_request
   *   The current request.
   * @param \Drupal\purest_user\AccountValidationServiceInterface $validation_service
   *   Account validation service.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    array $serializer_formats,
    LoggerInterface $logger,
    ImmutableConfig $user_settings,
    AccountInterface $current_user,
    Request $current_request,
    AccountValidationServiceInterface $validation_service,
    ConfigFactoryInterface $config_factory
  ) {
    parent::__construct(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $serializer_formats,
      $logger,
      $user_settings,
      $current_user
    );
    $this->currentRequest = $current_request;
    $this->recaptchaResponse = $this->currentRequest->query->get('recaptcha');
    $this->accountValidationService = $validation_service;
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
      $container->get('logger.factory')->get('rest'),
      $container->get('config.factory')->get('user.settings'),
      $container->get('current_user'),
      $container->get('request_stack')->getCurrentRequest(),
      $container->get('purest_user.validation'),
      $container->get('config.factory')
    );
  }

  /**
   * Responds to POST requests.
   *
   * @param \Drupal\user\UserInterface $account
   *   The user account.
   *
   * @return \Drupal\rest\ModifiedResourceResponse
   *   The HTTP response object.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\HttpException
   *   Throws exception expected.
   */
  public function post(UserInterface $account = NULL) {
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

    $this->ensureAccountCanRegister($account);

    $account->set('init', $account->getEmail());
    $account->addRole('rest_user');
    $account->block();

    $this->checkEditFieldAccess($account);

    // Make sure that the user entity is valid (email and name are valid).
    $this->validate($account);

    // Create the account.
    $account->save();

    // Attempt to send account validation email.
    $this->accountValidationService->sendVerificationEmail($account);

    return new ModifiedResourceResponse($account, 200);
  }

}
