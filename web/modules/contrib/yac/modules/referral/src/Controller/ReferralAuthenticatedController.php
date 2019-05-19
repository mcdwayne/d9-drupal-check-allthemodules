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
class ReferralAuthenticatedController extends ControllerBase {

  /**
   * A varaibale that will store the ReferralHandlers class.
   *
   * @var \Drupal\yac_referral\ReferralHandlers
   */
  protected $referralHandlers;

  /**
   * ReferralAuthenticatedController constructor.
   *
   * @param Drupal\yac_referral\ReferralHandlers $validation
   *   Assign the ReferralHandlers class to protected variable.
   */
  public function __construct(ReferralHandlers $validation) {
    $this->referralHandlers = $validation;
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
   *   The code provided by the path.
   *
   * @return mixed
   *   The authorization to view the registration page.
   */
  public function access(string $affiliate_code) {
    /** @var bool $isValid */
    $isValid = $this->referralHandlers->validCode($affiliate_code);
    return $isValid ? AccessResult::allowed() : AccessResult::forbidden();
  }

  /**
   * Handles the registration of new customer for an authenticated user.
   *
   * @param string $affiliate_code
   *   The route parameter.
   *
   * @return array
   *   Render array.
   *
   * @throws \Drupal\Core\TypedData\Exception\ReadOnlyException
   */
  public function registration(string $affiliate_code) {
    /** @var \Drupal\Core\Session\AccountProxyInterface $accountProxy */
    $accountProxy = \Drupal::currentUser();
    return [
      '#method' => $this->referralHandlers->submitAffiliate($accountProxy, $affiliate_code),
    ];
  }

}
