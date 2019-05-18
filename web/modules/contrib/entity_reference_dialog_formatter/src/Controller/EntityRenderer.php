<?php

namespace Drupal\entity_reference_dialog_formatter\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Language\LanguageInterface;

class EntityRenderer extends ControllerBase {
  /**
   * Render a given entity with the given view mode.
   *
   * @param EntityInterface $entity
   *   The entity being forwarded.
   * @param string $view_mode
   *   The view mode to use, with "full" being the default value.
   *
   * @return array
   *   The render array for the entity.
   */
  public function render(EntityInterface $entity, $view_mode = 'full') {
    $viewBuilder = \Drupal::entityTypeManager()->getViewBuilder($entity->getEntityTypeId());
    $langcode = \Drupal::languageManager()->getCurrentLanguage(LanguageInterface::TYPE_CONTENT)->getId();
    return $viewBuilder->view($entity, $view_mode, $langcode);
  }

  /**
   * The _title_callback for the modal.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity.
   *
   * @return string
   *   The page title.
   */
  public function title(EntityInterface $entity, $view_mode = 'full') {
    /** @var EntityRepositoryInterface $entityRepository */
    $entityRepository = \Drupal::service('entity.repository');
    $langcode = \Drupal::languageManager()->getCurrentLanguage(LanguageInterface::TYPE_CONTENT)->getId();
    $entity = $entityRepository->getTranslationFromContext($entity, $langcode);
    $title = $entity->label();

    if (\is_string($title) && $entity->language() !== $langcode) {
      $title = $this->t($title);
    }

    return $title;
  }
}
