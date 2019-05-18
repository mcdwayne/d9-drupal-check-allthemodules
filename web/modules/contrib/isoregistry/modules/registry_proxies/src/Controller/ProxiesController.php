<?php
/**
 * @file
 * Contains \Drupal\registry_proxies\Controller\ProxiesController.
 */
namespace Drupal\registry_proxies\Controller;

use Symfony\Component\HttpFoundation\Response;
//use GuzzleHttp\Psr7\ServerRequest;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;

class ProxiesController {
  
  public function redirect($namespace, $oid, $version = null) {
    $response = new Response();
    //load Namespace
    $tids = \Drupal::entityQuery('taxonomy_term')
      ->condition('vid', "namespaces")
      ->condition('field_shortcut', $namespace)
      ->execute();
    $termID = null;
    foreach($tids as $key => $value) {
      $termID = $value;
    }
    //load namensraum
    $query = \Drupal::entityQuery('node')
      ->condition('type', 'namensraum')
      ->condition('status', 1)
      ->condition('field_namespace', $termID);
    $nids = $query->execute();
    $node = null;
    foreach ($nids as $nid) {
      $node = \Drupal\node\Entity\Node::load($nid);
    }
    $url = null;
    if($node != null) {
      //load URLs
      foreach ($node->get('field_id_resolver')->getValue() as $resolver) {
        $resolver = \Drupal\node\Entity\Node::load($resolver['target_id']);
        $regex = $resolver->get('field_id_muster')->getValue()[0]['value'];
        if(preg_match($regex,$oid)) {
          if($resolver->get('field_post_anfrage')->getValue()[0]['value'] === 'nein') {
            $url = str_replace('{OID}',$oid,$resolver->get('field_url_vorlage_')->getValue()[0]['value']);
            $url = str_replace('{Version}', $version, $url);
            $response->setStatusCode(Response::HTTP_OK);
            $response->setStatusCode(200);
            $response->headers->set('Refresh', '0; url='.$url);
            break;
          }
          else {
            $client = new Client();
            $url = $resolver->get('field_url_vorlage_')->getValue()[0]['value'];
            $type = $resolver->get('field_post_typ')->getValue()[0]['value'];
            $requestData = $resolver->get('field_post_anfragedaten')->getValue()[0]['value'];
            $requestData = str_replace('{OID}', $oid, $requestData);
            $requestData = str_replace('{Version}', $version, $requestData);
            $request = new Request('POST', $url, ['Content-Type' => $type], $requestData);
            $responseRequest = $client->send($request);
            $body  = $responseRequest->getBody();
            $size = $responseRequest->getBody()->getSize();
            $responseString = '';
            while (!$body->eof()) {
              $responseString = $responseString.$body->read($size);
            }
            $response->setStatusCode(Response::HTTP_OK);
            $response->setStatusCode(200);
            $response->headers->set('Content-Type', $type);
            $response->setContent($responseString);
            break;
          }
        }
      } 
    }
    if($node === null || $url === null || $responseString === '') {
      $response->setStatusCode(Response::HTTP_OK);
      $response->setStatusCode(404);
    }
    
    return $response;
  }
}