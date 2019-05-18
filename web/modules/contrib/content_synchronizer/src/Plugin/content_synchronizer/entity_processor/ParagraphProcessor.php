<?php

namespace Drupal\content_synchronizer\Plugin\content_synchronizer\entity_processor;

use Drupal\content_synchronizer\Processors\Entity\EntityProcessorBase;
use Drupal\Core\Entity\EntityInterface;
use Drupal\paragraphs\Entity\Paragraph;

/**
 * Plugin implementation of the 'accordion' formatter.
 *
 * @EntityProcessor(
 *   id = "content_synchronizer_paragraph_processor",
 *   entityType = "paragraph"
 * )
 */
class ParagraphProcessor extends EntityProcessorBase {

  /**
   * {@inheritdoc}
   */
  public function getEntityToImport(array $data, EntityInterface $existingEntity = NULL) {
    if (is_null($existingEntity)) {
      $existingEntity = Paragraph::create(['type' => $this->getDefaultLanguageData($data)['type']]);
    }

    return parent::getEntityToImport($data, $existingEntity);
  }

}
