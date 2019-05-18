<?php

/**
 * @file
 * Contains \Drupal\nodeletter\NodeletterNotEnabledException.
 */

namespace Drupal\nodeletter;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\node\Entity\NodeType;
use Drupal\node\NodeTypeInterface;
use \Exception;


class NodeletterNotEnabledException extends Exception {

  use StringTranslationTrait;

  protected $nodeType;

  public function __construct($node_type, $message=NULL, $code=NULL, \Exception $previous=NULL) {
    if (is_string($node_type))
      $node_type = NodeType::load($node_type);
    else if ( ! $node_type instanceof NodeTypeInterface) {
      throw new \InvalidArgumentException();
    }
    /** @var NodeTypeInterface $node_type */
    $this->nodeType = $node_type;
    if (empty($message)) {
      $message = $this->t(
        "Nodeletter is not enabled for content type %type",
        ['%type' => $node_type->label()]
      );
    }
    parent::__construct($message, $code, $previous);
  }

  /**
   * @return \Drupal\Core\Entity\EntityInterface|\Drupal\node\NodeTypeInterface
   */
  public function getNodeType() {
    return $this->nodeType;
  }
}
