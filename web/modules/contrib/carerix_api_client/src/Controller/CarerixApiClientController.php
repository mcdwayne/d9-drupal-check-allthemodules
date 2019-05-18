<?php
/**
 * @file
 * Contains \Drupal\carerix_api_client\Controller\CarerixApiClientController.
 */

namespace Drupal\carerix_api_client\Controller;

/**
 * Provides route responses for the Carerix API Client module.
 */
class CarerixApiClientController {

    /**
     * A configuration page.
     *
     * @return array
     *   A simple renderable array.
     */
    public function config() {
        $element = array(
            '#type' => 'markup',
            '#markup' => 'Hello, world this configuration!',
        );

        return $element;
    }

    /**
     * A test page.
     *
     * @return array
     *   A simple renderable array.
     */
    public function test() {
        $output = 'Not loaded!';
        // Load the client.
        $client = carerix_api_client_load();
        if (is_object($client)) {
            $output = 'Loaded!';
        }

        $element = array(
            '#type' => 'markup',
            '#markup' => $output,
        );

        return $element;
    }

}
?>