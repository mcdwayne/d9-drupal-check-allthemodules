<?php

namespace Drupal\yac_referral\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\yac_referral\ReferralHandlers;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class ReferralData.
 *
 * @package Drupal\yac_referral\Controller
 * @group yac_referral
 */
class ReferralData extends ControllerBase {

  /**
   * A varaibale that will store the ReferralHandlers class.
   *
   * @var \Drupal\yac_referral\ReferralHandlers
   */
  protected $referralHandlers;

  /**
   * ReferralData constructor.
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
   * Calls the handlers function that will create user network table.
   *
   * @param string $user
   *   The user uid as a string.
   *
   * @return array
   *   A renderable array.
   */
  public function networkTable($user) {
    $uid = (int) $user;
    return $this->referralHandlers->networkTable($uid);
  }

}
