<?php

namespace Drupal\enhanced_user\Plugin\rest\resource;

use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\datetime\Plugin\Field\FieldType\DateTimeItem;
use Drupal\enhanced_user\Entity\Sex;
use Drupal\rest\ModifiedResourceResponse;
use Drupal\rest\Plugin\ResourceBase;
use Drupal\rest\ResourceResponse;
use Drupal\user\Entity\User;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * Provides a resource to get view modes by entity and bundle.
 *
 * @RestResource(
 *   id = "enhanced_user_user_profile",
 *   label = @Translation("Enhanced user profile"),
 *   uri_paths = {
 *     "canonical" = "/api/rest/enhanced-user/user-profile/{user}"
 *   }
 * )
 */
class UserProfile extends ResourceBase {

  /**
   * A current user instance.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * Constructs a new UserProfile object.
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
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    array $serializer_formats,
    LoggerInterface $logger,
    AccountProxyInterface $current_user) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $serializer_formats, $logger);

    $this->currentUser = $current_user;
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
      $container->get('logger.factory')->get('enhanced_user'),
      $container->get('current_user')
    );
  }

  /**
   * Responds to PATCH requests.
   *
   * @param User $user
   * @param $data
   * @return \Drupal\rest\ModifiedResourceResponse
   *   The HTTP response object.
   */
  public function patch(User $user, $data) {

    // You must to implement the logic of your REST Resource here.
    // Use current user after pass authentication to validate access.
    if (!$this->currentUser->hasPermission('access content')) {
      throw new AccessDeniedHttpException();
    }

    if ((int)$this->currentUser->id() !== (int)$user->id())
      throw new AccessDeniedHttpException(t('Only can change own profile.'));

    if (isset($data['nick_name']) && !empty($data['nick_name'])) {
      $user->set('nick_name', $data['nick_name']);
    }

    if (isset($data['mail']) && !empty($data['mail'])) {
      $user->set('mail', $data['mail']);
    }

    if (isset($data['birthday']) && !empty($data['birthday'])) {
      $date = new DrupalDateTime($data['birthday'], new \DateTimeZone(DateTimeItem::STORAGE_TIMEZONE));
      $user->set('birthday', $date->format(DateTimeItem::DATE_STORAGE_FORMAT));
    }

    if (isset($data['sex']) && !empty($data['sex'])) {
      $sex = Sex::load($data['sex']);
      $user->set('sex', $sex);
    }

    $user->save();

    return new ModifiedResourceResponse($user, 200);
  }

  /**
   * {@inheritdoc}
   */
  protected function getBaseRoute($canonical_path, $method) {
    $route = parent::getBaseRoute($canonical_path, $method);
    $parameters = $route->getOption('parameters') ?: [];
    $parameters['user']['type'] = 'entity:user';
    $route->setOption('parameters', $parameters);

    return $route;
  }
}
