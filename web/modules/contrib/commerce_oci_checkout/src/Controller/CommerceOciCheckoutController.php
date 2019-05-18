<?php

namespace Drupal\commerce_oci_checkout\Controller;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Flood\FloodInterface;
use Drupal\user\UserAuthInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Attribute\AttributeBag;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * Returns responses for Commerce OCI Checkout routes.
 */
class CommerceOciCheckoutController extends ControllerBase {

  const HOOK_URL_ATTRIBUTE_NAME = 'oci_checkout_hook_url';

  /**
   * Entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * Attribute bag.
   *
   * @var \Symfony\Component\HttpFoundation\Session\Attribute\AttributeBag
   */
  protected $attributeBag;

  /**
   * The flood service.
   *
   * @var \Drupal\Core\Flood\FloodInterface
   */
  protected $flood;

  /**
   * Constructs the controller object.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, ModuleHandlerInterface $module_handler, ConfigFactoryInterface $config_factory, AttributeBag $attribute_bag, UserAuthInterface $user_auth, FloodInterface $flood) {
    $this->entityTypeManager = $entity_type_manager;
    $this->moduleHandler = $module_handler;
    $this->configFactory = $config_factory;
    $this->attributeBag = $attribute_bag;
    $this->userAuth = $user_auth;
    $this->flood = $flood;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('module_handler'),
      $container->get('config.factory'),
      $container->get('session.attribute_bag'),
      $container->get('user.auth'),
      $container->get('flood')
    );
  }

  /**
   * Start session.
   */
  public function ociStart(Request $request) {
    // Clear all of the things we know we will end up using.
    $this->attributeBag->clear();
    $variables = [];
    $fields = [
      'hook_url',
      'username',
      'password',
    ];
    foreach ($fields as $field) {
      $upper = strtoupper($field);
      if ($request->get($field)) {
        $variables[$field] = $request->get($field);
      }
      if ($request->get($upper)) {
        $variables[$field] = $request->get($upper);
      }
    }
    $has_empty = FALSE;
    foreach ($fields as $field) {
      if (empty($variables[$field])) {
        $has_empty = TRUE;
      }
    }
    if ($has_empty) {
      throw new AccessDeniedHttpException('You have to supply HOOK_URL, USERNAME and PASSWORD');
    }
    $flood_config = $this->config('user.flood');
    if (!$this->flood->isAllowed('user.failed_login_ip', $flood_config->get('ip_limit'), $flood_config->get('ip_window'))) {
      throw new AccessDeniedHttpException('You have to supply HOOK_URL, USERNAME and PASSWORD');
    }
    // See if we can find the user by this email.
    if (!$accounts = $this->entityTypeManager->getStorage('user')
      ->loadByProperties([
        'mail' => $variables['username'],
      ])) {
      throw new AccessDeniedHttpException('No user found with those credentials');
    }
    /** @var \Drupal\Core\Session\AccountInterface $account */
    $account = reset($accounts);
    // @todo: Check if user is blocked.
    // Try to authenticate.
    if (!$uid = $this->userAuth->authenticate($account->getAccountName(), $variables['password'])) {
      throw new AccessDeniedHttpException('Wrong username/pass combination');
    }
    // Store the hook url.
    $this->attributeBag->set(self::HOOK_URL_ATTRIBUTE_NAME, $variables['hook_url']);
    // @todo: Use something with proper dependency injection.
    user_login_finalize($account);
    return new RedirectResponse('/');
  }

}
