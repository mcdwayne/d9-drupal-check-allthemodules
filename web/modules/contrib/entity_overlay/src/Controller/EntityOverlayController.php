<?php

namespace Drupal\entity_overlay\Controller;

use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\OpenDialogCommand;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Url;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Class EntityOverlayController.
 */
class EntityOverlayController extends ControllerBase {

  /**
   * Fetch a loaded entity for a type in a view mode and wrap it in a Dialog.
   *
   * @param string $method
   *   Method.
   * @param string $entity_type_id
   *   Entity type.
   * @param int $entity_id
   *   Entity id.
   * @param string $view_mode
   *   View mode.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse||\Symfony\Component\HttpFoundation\Response
   *   Redirect or ajax response.
   */
  public function getEntity($method, $entity_type_id, $entity_id, $view_mode) {
    // If nojs is the method redirect the user.
    $redirect = $method === 'nojs';

    // Javascript is ok.
    if (!$redirect) {
      try {
        $entity = $this->entityTypeManager()->getStorage($entity_type_id)->load($entity_id);
        $view_builder = $this->entityTypeManager()->getViewBuilder($entity_type_id);
        // Get the render array of this entity in the specified view mode.
        $view = $view_builder->view($entity, $view_mode);
        // Prepare the render array.
        $build = [
          '#type' => 'container',
          '#attributes' => [
            'id' => 'entity-overlay__container',
            'class' => 'entity-overlay__container--' . $entity_type_id . '-' . $entity_id,
          ],
          'entity' => $view,
        ];

        $response = new AjaxResponse();
        $content = \Drupal::service('renderer')->renderRoot($build);
        // Attach the library necessary for using the OpenDialogCommand
        // and set the attachments for this Ajax response.
        $build['#attached']['library'][] = 'core/drupal.dialog.ajax';
        $response->setAttachments($build['#attached']);
        // @todo set dialog options
        $options = [];
        // @todo review this section for label translation:
        // this is a workaround because in some situations,
        // the interface language of the referenced entity
        // could be different from the interface language.
        if ($entity instanceof ContentEntityInterface && $entity->isTranslatable()) {
          $languageId = \Drupal::service('language_manager')->getCurrentLanguage()->getId();
          if ($entity->hasTranslation($languageId)) {
            $entity = $entity->getTranslation($languageId);
          }
        }
        $response->addCommand(new OpenDialogCommand('#entity-overlay__container', $entity->label(), $content, $options));
      }
      catch (InvalidPluginDefinitionException $exception) {
        print $exception->getMessage();
      }
    }
    else {
      // Javascript is not used, redirect to the entity.
      $response = new RedirectResponse(Url::fromRoute("entity.{$entity_type_id}.canonical", [$entity_type_id => $entity_id])->toString(), 302);
    }

    return $response;
  }

}
