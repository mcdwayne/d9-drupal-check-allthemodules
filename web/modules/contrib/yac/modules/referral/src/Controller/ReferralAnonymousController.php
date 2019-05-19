<?php

namespace Drupal\yac_referral\Controller;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Controller\ControllerBase;
use Drupal\yac_referral\ReferralHandlers;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class ReferralAuthenticatedController.
 *
 * @package Drupal\yac_referral\Controller
 * @group yac_referral
 */
class ReferralAnonymousController extends ControllerBase {

  /**
   * A varaibale that will store the ReferralHandlers class.
   *
   * @var \Drupal\yac_referral\ReferralHandlers
   */
  protected $referralHandlers;

  /**
   * A varaibale that will store the EntityManager class.
   *
   * @var \Drupal\Core\Entity\EntityManager
   */
  protected $entityManager;

  /**
   * ReferralAuthenticatedController constructor.
   *
   * @param \Drupal\yac_referral\ReferralHandlers $referral_handlers
   *   The ReferralHandlers class.
   */
  public function __construct(ReferralHandlers $referral_handlers) {
    $this->referralHandlers = $referral_handlers;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get("yac_referral.validation")
    );
  }

  /**
   * Handles the access checking for the registration route.
   *
   * The check is performed against the affiliate code provided as route
   * parameter. When an invalid code is found it forbids the access and
   * redirects the browser to 403 page.
   *
   * @param string $affiliate_code
   *   The affiliate code passed by the route.
   *
   * @return mixed
   *   Calls to allowed() or forbidden() methods in AccessResult class.
   */
  public function access(string $affiliate_code) {
    /** @var bool $isValid */
    $isValid = $this->referralHandlers->validCode($affiliate_code);
    return $isValid ? AccessResult::allowed() : AccessResult::forbidden();
  }

  /**
   * Handles the registration of new customer for an anonymous user.
   *
   * @param string $affiliate_code
   *   The route parameter.
   *
   * @return array
   *   Render array.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   */
  public function registration(string $affiliate_code) {
    return [
      '#markup' => $this->referralHandlers->submitAnonymous($affiliate_code),
    ];
  }

}
