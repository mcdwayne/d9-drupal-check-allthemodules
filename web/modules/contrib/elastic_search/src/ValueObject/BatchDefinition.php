<?php
/**
 * Created by PhpStorm.
 * User: twhiston
 * Date: 08.05.17
 * Time: 21:47
 */

namespace Drupal\elastic_search\ValueObject;

use twhiston\twLib\Immutable\Immutable;

/**
 * Class BatchDefinition
 *
 * @package Drupal\elastic_search\ValueObject
 */
class BatchDefinition extends Immutable {

  /**
   * @var mixed[]
   */
  private $data;

  /**
   * BatchDefinition constructor.
   *
   * @param \mixed[]                                          $operations
   * @param string                                            $finished
   * @param \Drupal\Core\StringTranslation\TranslatableMarkup $title
   * @param \Drupal\Core\StringTranslation\TranslatableMarkup $initMessage
   * @param \Drupal\Core\StringTranslation\TranslatableMarkup $progressMessage
   * @param \Drupal\Core\StringTranslation\TranslatableMarkup $errorMessage
   */
  public function __construct(array $operations,
                              $finished,
                              \Drupal\Core\StringTranslation\TranslatableMarkup $title,
                              \Drupal\Core\StringTranslation\TranslatableMarkup $initMessage,
                              \Drupal\Core\StringTranslation\TranslatableMarkup $progressMessage,
                              \Drupal\Core\StringTranslation\TranslatableMarkup $errorMessage) {
    $this->data['operations'] = $operations;
    $this->data['finished'] = $finished;
    $this->data['title'] = $title;
    $this->data['init_message'] = $initMessage;
    $this->data['progress_message'] = $progressMessage;
    $this->data['error_message'] = $errorMessage;
    parent::__construct();
  }

  public function getDefinitionArray() {
    return $this->data;
  }

}