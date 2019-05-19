<?php

namespace Drupal\tikitoki\FieldProcessor;

use Drupal\views\Plugin\views\field\FieldHandlerInterface;
use Drupal\views\ResultRow;

/**
 * Class BaseFieldProcessor.
 *
 * @package Drupal\tikitoki\FieldProcessor
 */
class BaseFieldProcessor implements FieldProcessorInterface {
  /**
   * Field destination ID.
   *
   * @var string
   */
  protected static $destinationId = '';
  /**
   * Entity field object.
   *
   * @var \Drupal\views\Plugin\views\field\FieldHandlerInterface
   */
  protected $field;
  /**
   * Views result row object.
   *
   * @var \Drupal\views\ResultRow
   */
  protected $viewsRow;

  /**
   * DateFieldProcessorBase constructor.
   *
   * @param \Drupal\views\Plugin\views\field\FieldHandlerInterface $field
   *   Entity field object.
   * @param \Drupal\views\ResultRow $row
   *   Views result row object.
   */
  public function __construct(FieldHandlerInterface $field, ResultRow $row) {
    $this->field    = $field;
    $this->viewsRow = $row;
  }

  /**
   * {@inheritdoc}
   */
  public static function getDestinationId() {
    return (string) static::$destinationId;
  }

  /**
   * {@inheritdoc}
   */
  public function getValue() {
    return $this->field->advancedRender($this->viewsRow);
  }

}
