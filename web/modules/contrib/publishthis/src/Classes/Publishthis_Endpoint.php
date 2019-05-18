<?php

/**
 * This handles the input and response for the Publishing Endpoints from
 * the PublishThis platform
 * Current Actions
 * 1 - Verify
 * 2 - Publish
 */
namespace Drupal\publishthis\Classes;
use \Drupal\publishthis\Classes\Publishthis_API;
use \Drupal\publishthis\Classes\Publishthis_Publish;
use Symfony\Component\HttpFoundation\JsonResponse;

class Publishthis_Endpoint {
  private $obj_api;
  private $obj_publish;

  function __construct() {
    $this->obj_api     = new Publishthis_API();
    $this->obj_publish = new Publishthis_Publish();
  }

  /**
   * Escape sprecial characters
   */
  function escapeJsonString($value) { // list from www.json.org: (\b backspace, \f formfeed)
    $escapers = ["\\", "/", "\"", "\n", "\r", "\t", "\x08", "\x0c"];
    $replacements = [
      "\\\\",
      "\\/",
      "\\\"",
      "\\n",
      "\\r",
      "\\t",
      "\\f",
      "\\b"
    ];
    $result = str_replace($escapers, $replacements, $value);
    $escapers = ['\":\"', '\",\"', '{\"', '\"}'];
    $replacements = ['":"', '","', '{"', '"}'];
    $result = str_replace($escapers, $replacements, $result);
    return $result;
  }

  /**
   * Returns json response with failed status
   */
  function sendFailure($message = NULL) {
    $obj = [];
    $obj['success'] = FALSE;
    $obj['errorMessage'] = $this->escapeJsonString($message);
    return $obj;
  }

  /**
   * Returns json response with succeess status
   */
  function sendSuccess($message = NULL, $postId = NULL) {
    $obj = [];
    $obj['success'] = TRUE;
    if(isset($postId)) {
      $obj['publishedId'] = $postId;
    }
    $obj['errorMessage'] = NULL;
    return $obj;
  }

  /**
   * Verify endpoint action
   */
  private function actionVerify() {
    //first check to make sure we have our api token
    $apiToken = $this->obj_api->GetToken('pt_api_token');

    if (empty($apiToken)) {

      $message = [
      'message' => 'Verify Plugin Endpoint, Asked to verify our install at: ' . date("Y-m-d H:i:s") . ' failed because api token is not filled out',
      'status'  => 'error',
      ];
      $this->obj_api->LogMessage($message, '1');

      return $this->sendFailure('No API Key Entered');
    }

    //then, make a easy call to our api that should return our basic info.
    $apiResponse = $this->obj_api->get_client_info();

    if (empty($apiResponse)) {
      $message = [
      'message' => 'Verify Plugin Endpoint, Asked to verify our install at: ' . date("Y-m-d H:i:s") . ' failed because api token is not valid',
      'status'  => 'error',
      ];
      
      $this->obj_api->LogMessage($message, '1');

      return $this->sendFailure('API Key Entered is not Valid');
    }

    //if we got here, then it is a valid api token, and the plugin is installed.
    $message = [
      'message' => 'Verify Plugin Endpoint, Asked to verify our install at: ' . date("Y-m-d H:i:s"),
      'status'  => 'info',
    ];

    $this->obj_api->LogMessage($message, '2');

    return $this->sendSuccess();
  }

  /**
   * Publish endpoint action
   * @param integer $feedId
   */
  private function actionPublish($feedId) {

    if(empty($feedId)) {
      $this->sendFailure('Empty feed id');
      return;
    }

    $arrFeeds   = [];
    $arrFeeds[] = $feedId;

    //ok, now go try and publish the feed passed in
    try {
      $status = $this->obj_publish->publish_specific_feeds($arrFeeds);
    }
    catch (Exception $ex) {
      //looks like there was an internal error in publish, we will need to send a failure.
      //no need to log here, as our internal methods have all ready logged it
      return $this->sendFailure($ex->getMessage());
    }
    $message = NULL;
    return $this->sendSuccess($message, $status);
  }

  /**
   * Process request main function
   */
  function process_request() {
    try {
      $bodyContent = file_get_contents('php://input');

      $message = [
        'message' => 'Endpoint Request, ' .$bodyContent,
        'status'  => 'info',
      ];
      
      $this->obj_api->LogMessage($message, '2');

      $arrEndPoint = json_decode($bodyContent, TRUE);
      $action = $arrEndPoint["action"];

      switch ($action) {
        case "verify":
          $result = $this->actionVerify();
          return $result;
          break;
        case "publish":
          $postId = intval($arrEndPoint["postId"], 10);
          $result = $this->actionPublish($postId);
          return $result;
          break;
        default:
          $result = $this->sendFailure("Empty or bad request made to endpoint");
          return $result;
          break;
      }

    }
    catch (Exception $ex) {
      //we will log this to the pt logger, but we always need to send back a failure if this occurs
      $this->sendFailure($ex->getMessage());
    }
  }
}

