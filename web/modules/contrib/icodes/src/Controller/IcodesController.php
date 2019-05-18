<?php

namespace Drupal\icodes\Controller;

use Symfony\Component\HttpFoundation\Request;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Access\AccessResult;

/**
 * Controller routines for imce routes.
 */
class IcodesController extends ControllerBase
{

    /**
     * Processes merchants feed url
     */
    public function processMerchantsFeed()
    {

        $data = \Drupal::service('icodes.process_merchants')->processFeed();
        return $data;
    }

    /**
     * Processes vouchers feed url
     */
    public function processVouchersFeed()
    {

        $data = \Drupal::service('icodes.process_vouchers')->processFeed();
        return $data;
    }

    /**
     * Processes offers feed url
     */
    public function processOffersFeed()
    {

        $data = \Drupal::service('icodes.process_offers')->processFeed();
        return $data;
    }

    /**
     * Processes cleanup routine
     */
    public function processCleanupRoutine()
    {

        $data = \Drupal::service('icodes.process_cleanup')->processCleanup();
        return $data;
    }
}