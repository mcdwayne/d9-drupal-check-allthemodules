<?php

/**
 * @file
 * Contains \Drupal\hawk_auth_qr\Controller\HawkAuthQrController.
 */

namespace Drupal\hawk_auth_qr\Controller;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\hawk_auth\Controller\HawkAuthController;
use Drupal\hawk_auth\Entity\HawkCredentialInterface;
use Drupal\hawk_auth_qr\Response\HawkAuthQrImageResponse;
use Endroid\QrCode\QrCode;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Routing\Route;

/**
 * Controller for Hawk Auth QR, primary purpose is to generate and display
 * QR codes for individual credentials.
 */
class HawkAuthQrController extends ControllerBase {

  /**
   * Qr Code generator.
   *
   * @var QrCode
   */
  protected $qrCode;

  /**
   * Constructs the controller's object.
   *
   * @param QrCode $qr_code
   *   The library for generating QR Codes.
   */
  public function __construct(QrCode $qr_code) {
    $this->qrCode = $qr_code;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('hawk_auth_qr.qr')
    );
  }

  /**
   * Checks for access for viewing a user's hawk credential.
   *
   * {@inheritdoc}
   */
  public function accessView(Route $route, RouteMatchInterface $route_match, AccountInterface $account) {
    /** @var HawkCredentialInterface $credential */
    $credential = $route_match->getParameter('hawk_credential');

    return AccessResult::allowedIf(
      HawkAuthController::checkUserAccessForView($credential->getOwner(), $account)
    );
  }

  /**
   * Displays a QR Code's image.
   *
   * @param HawkCredentialInterface $hawk_credential
   *   The credential which's code is being displayed.
   *
   * @return HawkAuthQrImageResponse
   *   Response object containing the image.
   */
  public function view(HawkCredentialInterface $hawk_credential) {
    return new HawkAuthQrImageResponse($hawk_credential, $this->qrCode);
  }
}
