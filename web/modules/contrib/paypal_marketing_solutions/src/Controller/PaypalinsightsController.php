<?php

/**
 * @file
 * Contains \Drupal\paypal_marketing_solutions\Controller\PaypalinsightsController.
 */

namespace Drupal\paypal_marketing_solutions\Controller;

use Drupal\Core\Controller\ControllerBase;

class PaypalinsightsController extends ControllerBase {

    protected function getEditableConfigNames() {
        return [
            'paypal_marketing_solutions.paypalinsights_id',
        ];
    }

    public function content() {
        return [
            '#theme' => 'paypal_admin',
            '#markup' => '',
            '#attached' => [
                'library' => [
                    'paypal_marketing_solutions/paypal_marketing_solutions_act',
                ],
                'drupalSettings' => [
                    'paypal_marketing_solutions' => [
                        'path' => '/admin/config/services/paypal-marketing-solutions/getcontainerid/'
                    ]
                ],
            ],
        ];
    }

    public function getContainerId($containerid = NULL) {
        $config = \Drupal::service('config.factory')->getEditable('paypal_marketing_solutions.settings');
        if (!empty($containerid) && empty($config->get('id'))) {
            $config->set('id', $containerid)->save();
        }

        return [
            '#markup' => '',
        ];
    }
}
