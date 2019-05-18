<?php

namespace Drupal\consent\Controller;

use Drupal\consent\ConsentFactoryInterface;
use Drupal\consent\Storage\ConsentStorageInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Zend\Diactoros\Response\JsonResponse;

/**
 * Class ConsentSubmitController.
 */
class ConsentSubmitController implements ContainerInjectionInterface {

  /**
   * The consent factory.
   *
   * @var \Drupal\consent\ConsentFactoryInterface
   */
  protected $factory;

  /**
   * The consent storage.
   *
   * @var \Drupal\consent\Storage\ConsentStorageInterface
   */
  protected $storage;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * The logger channel factory.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactoryInterface
   */
  protected $loggerFactory;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('consent.factory'),
      $container->get('consent.storage'),
      $container->get('current_user'),
      $container->get('logger.factory')
    );
  }

  /**
   * ConsentSubmitController constructor.
   *
   * @param \Drupal\consent\ConsentFactoryInterface $factory
   *   The consent factory.
   * @param \Drupal\consent\Storage\ConsentStorageInterface $storage
   *   The consent storage.
   * @param \Drupal\Core\Session\AccountProxyInterface $current_user
   *   The current user.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger_factory
   *   The logger channel factory.
   */
  public function __construct(ConsentFactoryInterface $factory, ConsentStorageInterface $storage, AccountProxyInterface $current_user, LoggerChannelFactoryInterface $logger_factory) {
    $this->factory = $factory;
    $this->storage = $storage;
    $this->currentUser = $current_user;
    $this->loggerFactory = $logger_factory;
  }

  /**
   * Consent submit controller callback.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\HttpException
   */
  public function submit(Request $request) {
    if (!('POST' === $request->getMethod())) {
      throw new HttpException(405, 'HTTP Method not allowed.', NULL, ['Allow' => 'POST']);
    }
    $category = $request->request->get('c');
    if (empty($category)) {
      throw new HttpException(400, 'Missing "category" parameter.');
    }
    $consent = $this->factory->create()
      ->setTimestamp(time())
      ->setTimezone(drupal_get_user_timezone())
      ->setCategory($category)
      ->setClientIp($request->getClientIp())
      ->setDomain($request->getHttpHost())
      ->setUserId($this->currentUser->id());
    $success = FALSE;
    try {
      $this->storage->save($consent);
      $success = TRUE;
    }
    catch (\Exception $e) {
      $this->loggerFactory->get('consent')->error('Failed to save consent. Exception message: ' . $e->getMessage() . ' // Exception code: ' . $e->getCode());
    }
    return new JsonResponse($success);
  }

}
