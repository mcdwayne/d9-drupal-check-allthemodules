<?php

namespace Drupal\mobile_number\Plugin\rest\resource;

use Drupal\Core\Session\AccountProxyInterface;
use Drupal\mobile_number\MobileNumberUtilInterface;
use Drupal\rest\Plugin\ResourceBase;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * Request verification code resource.
 *
 * @RestResource(
 *   id = "request_verification_code",
 *   label = @Translation("Mobile number: request verification code"),
 *   uri_paths = {
 *     "canonical" = "/mobile-number/request-code/{number}",
 *   }
 * )
 */
class RequestVerificationCodeResource extends ResourceBase {

  /**
   * A current user instance.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * Mobile number util.
   *
   * @var \Drupal\mobile_number\MobileNumberUtilInterface
   */
  protected $util;

  /**
   * Constructs a Drupal\rest\Plugin\ResourceBase object.
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
   * @param \Drupal\mobile_number\MobileNumberUtilInterface $util
   *   Mobile number utility service.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    array $serializer_formats,
    LoggerInterface $logger,
    AccountProxyInterface $current_user,
    MobileNumberUtilInterface $util) {

    parent::__construct($configuration, $plugin_id, $plugin_definition, $serializer_formats, $logger);

    $this->currentUser = $current_user;
    $this->util = $util;
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
      $container->get('logger.factory')->get('mobile_number'),
      $container->get('current_user'),
      $container->get('mobile_number.util')
    );
  }

  /**
   * Responds send verification code POST request.
   *
   * @param string|null $number
   *   Callable mobile number.
   *
   * @return \Drupal\rest\ResourceResponse
   *   The HTTP response object.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\HttpException
   * @throws \Symfony\Component\HttpKernel\Exception\BadRequestHttpException
   * @throws MobileNumberException
   */
  public function get($number = NULL) {

    if (!$number) {
      throw new BadRequestHttpException('Mobile number not provided.');
    }
    $number = "+$number";

    $mobile_number = $this->util->testMobileNumber($number);

    if (!$this->util->checkFlood($mobile_number)) {
      throw new AccessDeniedHttpException('Too many verification attempts, please try again in a few hours.');
    }

    if (!$this->util->checkFlood($mobile_number, 'sms')) {
      throw new AccessDeniedHttpException('Too many verification code requests, please try again shortly..');
    }

    $message = MobileNumberUtilInterface::MOBILE_NUMBER_DEFAULT_SMS_MESSAGE;
    $code = $this->util->generateVerificationCode();
    $token = $this->util->sendVerification($mobile_number, $message, $code);

    if (!$token) {
      throw new HttpException(500, 'An error occurred while sending sms.');
    }

    $response = new Response(json_encode(['verification_token' => $token]));

    return $response;
  }

}
