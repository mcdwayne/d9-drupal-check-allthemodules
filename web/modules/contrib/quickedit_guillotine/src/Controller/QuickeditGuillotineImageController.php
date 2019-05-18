<?php

namespace Drupal\quickedit_guillotine\Controller;

use Drupal\Core\Entity\EntityInterface;
use Drupal\image\Controller\QuickEditImageController;
use Drupal\media_entity\Entity\Media;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Controller for re-rendering entities.
 */
class QuickeditGuillotineImageController extends QuickEditImageController {

  /**
   * Returns JSON representing the new file upload, or validation errors.
   *
   * @param string $entity_type
   *    Entity type.
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity of which an image field is being rendered.
   * @param string $field_name
   *   The name of the (image) field that is being rendered.
   * @param string $langcode
   *   The language code of the field that is being rendered.
   * @param string $view_mode_id
   *   The view mode of the field that is being rendered.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *    The JSON response.
   */
  public function render($entity_type, EntityInterface $entity, $field_name, $langcode, $view_mode_id) {
    $entity_view_mode_ids = array_keys(
      $this->entityManager()->getViewModes($entity->getEntityTypeId())
    );
    if (in_array($view_mode_id, $entity_view_mode_ids, TRUE)) {
      $output = $entity->$field_name->view($view_mode_id);
    }
    else {
      $mode_id_parts = explode('-', $view_mode_id, 2);
      $module = reset($mode_id_parts);
      $args = [$entity, $field_name, $view_mode_id, $langcode];
      $output = $this->moduleHandler()->invoke(
        $module, 'quickedit_render_field', $args
      );
    }
    if ($entity instanceof  Media) {
      $entity->automaticallySetThumbnail();
      $entity->save();
    }

    $data = [
      'html' => $this->renderer->renderRoot($output),
    ];
    return new JsonResponse($data);
  }

}
