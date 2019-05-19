<?php

namespace Drupal\tikitoki\FieldProcessor;

/**
 * Class LinkFieldProcessor.
 *
 * @package Drupal\tikitoki\FieldProcessor
 */
class LinkFieldProcessor extends BaseFieldProcessor {
  /**
   * Field destination ID.
   *
   * @var string
   */
  protected static $destinationId = 'externalLink';

  /**
   * {@inheritdoc}
   */
  /*public function getValue() {
    if ($this->field->getBaseId() == 'field') {
      return parent::getValue();
    }
    // Workaround for the "Content: Path" field.
    elseif ($this->field->getBaseId() == 'node_path') {
      return \Drupal::request()->getSchemeAndHttpHost()
        . '/' . $this->viewsRow->_entity->toUrl()->getInternalPath();
    }
    return '';
  }*/

}
