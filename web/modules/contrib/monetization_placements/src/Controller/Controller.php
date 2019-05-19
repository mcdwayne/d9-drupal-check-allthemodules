<?php

namespace Drupal\monetization_placements\Controller;

use Drupal\Core\Controller\ControllerBase;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Drupal\Core\Database\Database;

class Controller extends ControllerBase {

    /**
     * Creates a Demo page to view the ZAN Placement
     */
    public function monetizationPlacementsDemo(Request $request) {
        return array(
            '#theme' => 'zan__demo_page__theme',
        );
    }

}