<?php

namespace Drupal\digitallocker_issuer;

use Drupal\Core\Entity\EntityInterface;
use Drupal\node\Entity\NodeType;
use SimpleXMLElement;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * Class PushApi.
 *
 * @package Drupal\digitallocker_issuer
 */
class PushApi {

  /**
   * Publish a Single Certificate.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The node whose certificate is to be pushed.
   */
  public static function publishSingleCertificate(EntityInterface $node, $action) {
    /* @var \Drupal\Core\Field\FieldItemList $aadhar_field */

    $node_type = NodeType::load($node->bundle());

    if (!$node_type->getThirdPartySetting('digitallocker_issuer', 'enabled', FALSE)
      or !$node_type->getThirdPartySetting('digitallocker_issuer', 'auto_publish', FALSE)
    ) {
      return;
    }

    $config = \Drupal::config('digitallocker_issuer.settings');
    $requesterConfig = \Drupal::config('digitallocker_requester.settings');

    $timestamp = \Drupal::time()->getRequestTime();
    $client = \Drupal::httpClient();
    $path = \Drupal::service('path.alias_manager')
      ->getAliasByPath('/node/' . $node->id());

    $field_aadhaar = $node_type->getThirdPartySetting('digitallocker_issuer', 'field_aadhaar');
    $field_aadhaar = $node->{$field_aadhaar}->getValue();
    $field_valid = $node_type->getThirdPartySetting('digitallocker_issuer', 'field_validity');
    $field_valid = $node->{$field_valid}->getValue();

    $request = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><PushUriRequest xmlns:ns2="http://tempuri.org/" />');
    $request->addAttribute('ver', '1.0');
    $request->addAttribute('ts', date('d-m-Y h:i:s', $timestamp));
    $request->addAttribute('txn', $node->id());
    $request->addAttribute('orgId', $config->get('issuer_id'));
    $request->addAttribute('keyhash', hash('sha256', $config->get('api_key') . date('d-m-Y h:i:s', $timestamp)));

    $request->UriDetails->Uri = ltrim($path, '/');
    $request->UriDetails->DocId = self::mask_id($node->id());
    $request->UriDetails->Aadhaar = $field_aadhaar[0]['value'];
    $request->UriDetails->DocType = $node_type->getThirdPartySetting('digitallocker_issuer', 'doctype');
    $request->UriDetails->DocName = $node_type->label();
    $request->UriDetails->IssuedOn = date('d/m/Y', $node->getCreatedTime());
    $request->UriDetails->ValidFrom = date('d/m/Y', $node->getCreatedTime());
    $request->UriDetails->ValidTo = date('d/m/Y', strtotime($field_valid[0]['value']));
    $request->UriDetails->Timestamp = date('d/m/Y h:i:s A', $timestamp);
    $request->UriDetails->Action = $action;

    $response = $client->post($requesterConfig->get('base_url') . '/public/issuer/api/issuedoc/1/xml', [
      'body' => $request->asXML(),
      'headers' => ['Content-Type' => 'application/xml'],
    ]);

    if ($response->getStatusCode() <> 200) {
      \Drupal::logger('digital locker')->notice($response->getReasonPhrase());
    }

    $response = new simpleXMLElement($response->getBody()->getContents());
    if (!intval($response->ResponseStatus)) {
      drupal_set_message($node->getTitle() . ' : ' . strip_tags(strval($response->ResponseMessage)), 'error');
    }
  }

  /**
   * Publish Multiple Certificates.
   *
   * @param array $nids
   *   The node ids of the certificates to be published.
   */
  public static function publishMultipleCertificates(array $nids, $action) {
    /* @var \Drupal\node\Entity\Node $node */

    $config = \Drupal::config('digitallocker_issuer.settings');
    $client = \Drupal::httpClient();
    $time = \Drupal::time()->getRequestTime();
    $nodes = \Drupal::entityManager()->getStorage('node')->loadMultiple($nids);
    $csv = [];

    foreach ($nodes as $node) {

      $node_type = NodeType::load($node->bundle());

      $field_aadhaar = $node_type->getThirdPartySetting('digitallocker_issuer', 'field_aadhaar');
      $field_aadhaar = $node->{$field_aadhaar}->getValue();
      $field_valid = $node_type->getThirdPartySetting('digitallocker_issuer', 'field_validity');
      $field_valid = $node->{$field_valid}->getValue();

      $path = \Drupal::service('path.alias_manager')
        ->getAliasByPath('/node/' . $node->id());

      foreach ($field_aadhaar as $aadhaar) {
        $csv[] = implode(",", [
          $aadhaar['value'],
          ltrim($path, '/'),
          $node_type->getThirdPartySetting('digitallocker_issuer', 'doctype'),
          $node_type->label(),
          self::mask_id($node->nid),
          date('d/m/Y', $node->getCreatedTime()),
          date('d/m/Y', $node->getCreatedTime()),
          date('d/m/Y', strtotime($field_valid[0]['value'])),
          date('d/m/Y h:i:s A', $time),
          $action,
        ]);
      }
    }

    $request = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><PushCSVRequest xmlns:ns2="http://tempuri.org/" />');
    $request->addAttribute('ver', '1.0');
    $request->addAttribute('ts', date('d-m-Y h:i:s', $time));
    $request->addAttribute('txn', $time);
    $request->addAttribute('orgId', $config->get('issuer_id'));
    $request->addAttribute('keyhash', hash('sha256', $config->get('api_key') . date('d-m-Y h:i:s', $time)));
    $request->DocDetails->DocContent = base64_encode(implode("\n", $csv));
    $request->DocDetails->FileName = 'Certificates -' . date('Y-m-d-h-i-s', $time) . '.csv';

    $response = $client->request('POST', $config->get('base_url') . '/public/issuer/api/csvupload/1/xml', [
      'headers' => [
        'Content-Type' => 'application/xml',
      ],
    ]);
    $client->send($request->asXML());

    try {
      $response = new simpleXMLElement($response->data);
      if ((integer) $response->ResponseStatus) {
        drupal_set_message(\Drupal::translation()
          ->formatPlural(count($nids), 'Published 1 certificate.', 'Published @count certificates.'));
      }
      else {
        drupal_set_message((string) $response->ResponseMessage, 'error');
      }
    } catch (HttpException $e) {
      drupal_set_message($e->getMessage(), 'error');
    }
  }

  /**
   * @param $id
   *
   * @return string
   */
  private static function mask_id($id) {
    list($id, $mask) = [strval($id), NULL];
    for ($i = 0; $i < strlen($id); $i++) {
      $mask .= chr(97 + $id[$i]);
    }
    return $mask;
  }

}
