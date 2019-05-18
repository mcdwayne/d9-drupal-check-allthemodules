<?php

namespace Drupal\navigation_blocks\Plugin\Block;

use Drupal\Core\Entity\EntityInterface;

/**
 * Base class for entity buttons.
 *
 * @package Drupal\navigation_blocks\Plugin\Block
 */
abstract class EntityBackButtonBase extends BackButton {

  /**
   * {@inheritdoc}
   *
   * @throws \Drupal\Core\Entity\EntityMalformedException
   */
  public function build(): array {
    $build = $this->backButtonManager->getPreferredLink($this->getPreferredBackPaths(), $this->useJavascript());
    if (!empty($build)) {
      return $build;
    }

    $entity = $this->getEntity();
    if ($entity) {
      $link = $entity->toLink()->toRenderable();
      $this->backButtonManager->addLinkAttributes($link);
      return $link;
    }

    return $this->backButtonManager->getLink($this->getLinkUrl(), $this->getLinkText());
  }

  /**
   * Get the for this block.
   *
   * @return \Drupal\Core\Entity\EntityInterface
   *   The entity.
   */
  abstract protected function getEntity(): EntityInterface;

}
