<?php

namespace Drupal\external_data_source\Controller;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Controller\ControllerBase;

/**
 * Class AccessController.
 *
 * @package Drupal\external_data_source\Controller
 */
class AccessController extends ControllerBase
{

    /**
     * Checks access.
     *
     * @author Amine Cherif <maccherif2001@gmail.com>
     * Prevent the external use of autocomplete so only a connected user can use
     *   it
     * @return \Drupal\Core\Access\AccessResultInterface
     *   The access result.
     */
    public function access()
    {
        return AccessResult::allowedIf(!\Drupal::currentUser()->isAnonymous());
    }

}
