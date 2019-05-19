<?php

namespace Drupal\tag1quo\Adapter\Http;

use Drupal\tag1quo\Adapter\Core\Core;

/**
 * Class JsonResponse.
 *
 * @internal This class is subject to change.
 */
class JsonResponse extends Response {

  /**
   * @var array
   */
  protected $json;

  /**
   * @return array
   */
  public function getContent() {
    return $this->json;
  }

  /**
   * @return array
   */
  public function getJson() {
    return $this->json;
  }

  /**
   * {@inheritdoc}
   */
  public function getMessage() {
    return isset($this->json['message']) ? $this->json['message'] : '';
  }

  public function setContent($content) {
    $this->json = Core::create()->jsonDecode($content) ?: array();
    return parent::setContent($content);
  }

}
