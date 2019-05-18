<?php

namespace Drupal\navigation_blocks\Plugin\Block;

use Drupal\Core\Entity\EntityInterface;

/**
 * Provides a 'EntityCanonicalBackButton' block.
 *
 * @Block(
 *  id = "entity_canonical_back_button",
 *  deriver = "Drupal\navigation_blocks\Plugin\Deriver\EntityCanonicalBackButtonDeriver"
 * )
 */
class EntityCanonicalBackButton extends EntityBackButtonBase {

  /**
   * {@inheritdoc}
   *
   * @throws \Drupal\Core\Entity\EntityMalformedException
   */
  public function build(): array {
    if ($this->backButtonManager->isCanonicalPath()) {
      return [];
    }

    return parent::build();
  }

  /**
   * {@inheritdoc}
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   */
  protected function getEntity(): EntityInterface {
    return $this->getContextValue('entity');
  }

}
