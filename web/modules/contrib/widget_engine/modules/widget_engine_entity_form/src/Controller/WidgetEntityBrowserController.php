<?php

namespace Drupal\widget_engine_entity_form\Controller;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\CloseDialogCommand;
use Drupal\Core\Ajax\OpenDialogCommand;
use Drupal\Core\Ajax\PrependCommand;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Form\FormState;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\TypedData\TranslatableInterface;
use Drupal\Core\Render\Element\StatusMessages;
use Drupal\entity_browser\Ajax\ValueUpdatedCommand;
use Symfony\Component\HttpFoundation\Request;
use Drupal\widget_engine_entity_form\Ajax\WidgetPreviewRebuildCommand;

/**
 * Returns responses for entity browser routes.
 */
class WidgetEntityBrowserController extends ControllerBase {

  /**
   * Return an Ajax dialog command for editing a referenced entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   An entity being edited.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The currently processing request.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   An Ajax response with a command for opening or closing the dialog
   *   containing the edit form.
   */
  public function entityBrowserEdit(EntityInterface $entity, Request $request) {
    // Build the entity edit form.
    $form_object = $this->entityTypeManager()->getFormObject($entity->getEntityTypeId(), 'edit');

    $form_state = (new FormState())
      ->setFormObject($form_object)
      ->disableRedirect();
    // Prepare an entity translation in the parent form language.
    if ($entity instanceof TranslatableInterface && $entity->isTranslatable()) {
      $langcode = $request->query->get('langcode');
      if (is_string($langcode)) {
        try {
          if (!$entity->hasTranslation($langcode)) {
            // Get the selected translation of the entity.
            $entity_langcode = $entity->language()->getId();
            $source = $form_state->get(['content_translation', 'source']);
            $source_langcode = $source ? $source->getId() : $entity_langcode;
            $entity = $entity->getTranslation($source_langcode);
            // The entity has no content translation source field if
            // no entity field is translatable, even if the host is.
            if ($entity->hasField('content_translation_source')) {
              // Initialise the translation with source language values.
              $entity->addTranslation($langcode, $entity->toArray());
              $translation = $entity->getTranslation($langcode);
              $manager = \Drupal::service('content_translation.manager');
              $manager->getTranslationMetadata($translation)->setSource($entity->language()->getId());
            }
          }
          $form_state->set('langcode', $langcode);
        }
        catch (\InvalidArgumentException $e) {
          // It could be that the parent form language is inappropriate for
          // translations. Do not change form langcode in this case. Let it
          // fallback to the default behavior - edit entity in its original
          // language.
        }
      }
    }
    $form_object->setEntity($entity);
    // Building the form also submits.
    $form = $this->formBuilder()->buildForm($form_object, $form_state);

    // Return a response, depending on whether it's successfully submitted.
    if (!$form_state->isExecuted()) {
      // Return the form as a modal dialog.
      $form['#attached']['library'][] = 'core/drupal.dialog.ajax';
      $title = $this->t('Edit entity @entity', ['@entity' => $entity->label()]);
      // Build options array.
      $options = ['modal' => TRUE];
      if (!$form_state->getErrors()) {
        $options += [
          'width' => 'auto',
          'height' => 'auto',
          'maxWidth' => '',
          'maxHeight' => '',
          'fluid' => 1,
          'autoResize' => 0,
          'resizable' => 0,
        ];
      }
      $response = AjaxResponse::create()->addCommand(new OpenDialogCommand('#' . $entity->getEntityTypeId() . '-' . $entity->id() . '-edit-dialog', $title, $form, $options));
      if ($form_state->getErrors()) {
        $response->addCommand(
          new PrependCommand('#' . $entity->getEntityTypeId() . '-' . $entity->id() . '-edit-dialog', StatusMessages::renderMessages('error'))
        );
      }
      return $response;
    }
    else {
      // Return command for closing the modal.
      $response = AjaxResponse::create()->addCommand(new CloseDialogCommand('#' . $entity->getEntityTypeId() . '-' . $entity->id() . '-edit-dialog'));
      // Also refresh the widget if "details_id" is provided.
      $details_id = $request->query->get('details_id');
      if (!empty($details_id)) {
        $response->addCommand(new ValueUpdatedCommand($details_id));
      }

      // Rebuild preview image.
      if ($entity->getEntityTypeId() == 'widget') {
        $response->addCommand(new WidgetPreviewRebuildCommand($entity->id()));
      }

      return $response;
    }
  }

}

