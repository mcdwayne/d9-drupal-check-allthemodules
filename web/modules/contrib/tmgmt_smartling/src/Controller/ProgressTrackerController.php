<?php

namespace Drupal\tmgmt_smartling\Controller;

use Drupal\Core\Controller\ControllerBase;
use PHPUnit\Framework\Exception;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class ProgressTrackerController extends ControllerBase {

  public function callback(Request $request) {
    try {
      $smartling_provider_config = \Drupal::getContainer()
        ->get("tmgmt_smartling.smartling_config_manager")
        ->getConfigByProjectId($request->get("projectId"));

      $api_wrapper = \Drupal::getContainer()
        ->get("tmgmt_smartling.smartling_api_wrapper");

      $api_wrapper->setSettings($smartling_provider_config->get("settings"));

      $api_wrapper
        ->getApi("progress")
        ->deleteRecord(
          $request->get("spaceId"),
          $request->get("objectId"),
          $request->get("recordId")
        );
    }
    catch (Exception $e) {
      return new Response('Failed', 500);
    }

    return new Response('OK');
  }

}
