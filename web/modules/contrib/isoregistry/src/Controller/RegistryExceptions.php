<?php
namespace Drupal\isoregistry\Controller;

/**
  * Klasse über die Fehlermeldungen produziert werden können, die über Drupal hinaus gehen.
  *
  * 
  *
  * @author AndyLicht
  */

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;

class RegistryExceptions {
  
  private $exception;
  
  /**
   * Constructor um eine Exception-Objekt zu erzeugen.
   * 
   * @param string $exception übergibt eine Zeichenkette an das Objekt und setzt das Attribut $exception.
   * 
   * @return void
   */
  function __construct($exception) {
    $this->exception = $exception;
  }
  
  /**
   * Fehlermeldung als XML ausgeben.
   * 
   * Greift auf das Objektattribut $exception zu.
   * 
   * @return string $exception
   */
  public function getXMLException() {
    $error = '<?xml version="1.0"?><error>'.$this->exception.'</error>';
    $response = new Response();
    $response->setContent($error);
    $response->setStatusCode(Response::HTTP_OK);
    $response->headers->set('Content-Type', 'application/xml');
    return $response;
  }
  
  public function getDefaultException() {
    global $base_url;
    drupal_set_message($this->exception, 'error');
    $response = new RedirectResponse($base_url);
    return $response;
  }
}
