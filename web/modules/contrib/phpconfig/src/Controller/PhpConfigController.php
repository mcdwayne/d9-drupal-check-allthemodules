<?php
/**
 * Created by PhpStorm.
 * User: bappasarkar
 * Date: 20/04/17
 * Time: 11:16 PM
 */

/**
 * @file
 * Contains \Drupal\phpconfig\Controller\PhpConfigController.
 */

namespace Drupal\phpconfig\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;
use Symfony\Component\HttpFoundation\JsonResponse;

class PhpConfigController extends ControllerBase {
    public function index() {
        $header = array('Item', 'Value', 'Status', 'Operations');
        $rows = array();

        $results = db_query("SELECT * FROM {phpconfig_items} ORDER BY item ASC");
        $output = [];
        // If we have results.
        if ($results) {
            while ($conf = $results->fetchObject()) {
                $rows[] = array(
                  $conf->item,
                  $conf->value,
                  ($conf->status == 1) ? 'Enabled' : 'Disabled',
                  \Drupal::l(t('Edit'), Url::fromUri('internal:/admin/config/development/phpconfig/'.$conf->configid.'/edit', array('attributes' => array('class' => 'button button-primary', 'style' => 'margin-bottom:10px;')))),
                );
            }
            // Prepare the list table.
            $output['list'] = array(
              '#type' => 'table',
              '#header' => $header,
              '#rows' => $rows,
              '#weight' => 1,
            );
        }
        // Add new config link.
        $output['add_new'] = array(
          '#type' => 'markup',
          '#markup' => \Drupal::l(t('Add new'), Url::fromUri('internal:/admin/config/development/phpconfig/add', array('attributes' => array('class' => 'button button-primary', 'style' => 'margin-bottom:10px;'))))
        );
        return $output;
    }

    public function test() {
        return new JsonResponse('success');
    }
}