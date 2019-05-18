<?php

namespace Drupal\digitallocker_requester\Controller;

use Drupal\Core\Access\AccessResultAllowed;
use Drupal\Core\Access\AccessResultForbidden;
use Drupal\Core\Controller\ControllerBase;
use Exception;
use SimpleXMLElement;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Class FetchDocumentController.
 *
 * @package Drupal\digitallocker_requester\Controller
 */
class FetchDocumentController extends ControllerBase {

  /**
   * Checks if DigiLocker has returned sane data.
   *
   * @return \Drupal\Core\Access\AccessResultAllowed|\Drupal\Core\Access\AccessResultForbidden
   *   Whether the fetching of the document is allowed or not.
   */
  public function permit() {
    if (isset($_POST['data'])) {
      $data = json_decode($_POST['data']);
      if (isset($data->sharedTill) and
        isset($data->txn) and
        isset($data->uri)
      ) {
        return new AccessResultAllowed();
      }
    }
    return new AccessResultForbidden();
  }

  /**
   * Given details of the document, fetch it from digital locker & link to node.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   The file id after saving the document in the system.
   */
  public function fetchDocument() {

    $data = json_decode($_POST['data']);
    $time = \Drupal::time()->getRequestTime();
    $config = \Drupal::config('digitallocker_requester.settings');
    $client = \Drupal::httpClient();
    $issuerConfig = \Drupal::config('digitallocker_issuer.settings');

    $request = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8" standalone="yes"?><PullDocRequest xmlns:ns2="http://tempuri.org/" />');
    $request->addAttribute('ver', '1.0');
    $request->addAttribute('ts', date('d-m-Y h:i:s', $time));
    $request->addAttribute('txn', $data->txn);
    $request->addAttribute('orgId', $config->get('requester_id'));
    $request->addAttribute('appId', $issuerConfig->get('requester_id'));
    $request->addAttribute('keyhash', hash('sha256', $config->get('secret_key') . date('d-m-Y h:i:s', $time)));
    $request->DocDetails->URI = $data->uri;

    try {
      $response = $client->post($config->get('base_url') . '/public/requestor/api/pulldoc/1/xml', [
        'body' => $request->asXML(),
        'headers' => ['Content-Type' => 'application/xml'],
      ]);

      if ($response->getStatusCode() <> 200) {
        \Drupal::logger('digital locker')
          ->notice($response->status_message, []);
      }
      else {
        $contents = $response->getBody()->getContents();

        $response = new simpleXMLElement($contents);
        if (intval($response->ResponseStatus) != 1) {
          \Drupal::logger('digital locker')
            ->notice($response->ResponseStatus, []);
        }

        $document = base64_decode((string) $response->DocDetails->docContent[0]);

        $file = file_save_data($document, file_default_scheme() . '://' . $data->filename);
        $file->status = 0;

        return new JsonResponse(['fid' => $file->fid, 'did' => $data->docId]);
      }

    }
    catch (Exception $e) {
      \Drupal::logger('digital locker')->notice($e->getMessage(), []);
    }
    return new JsonResponse(['nothing' => 'nada']);
  }

}
