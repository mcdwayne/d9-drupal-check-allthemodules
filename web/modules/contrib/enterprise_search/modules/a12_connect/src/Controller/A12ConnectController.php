<?php
/**
 * @file
 * Contains Drupal\a12_connect\Controller\A12ConnectController.
 */

namespace Drupal\a12_connect\Controller;

use Drupal\Core\Controller\ControllerBase;


class A12ConnectController extends ControllerBase
{
    /**
     * Returns the connection settings for A12 Webservices
     */
    public function connectionSettings() {
        return array (
            '#type' => 'markup',
            '#markup' => $this->t('Hello World'),
        );
    }

}