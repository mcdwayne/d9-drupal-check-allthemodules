<?php

namespace Drupal\box_clone\Entity;

use Drupal\Core\Entity\EntityFormBuilder;
use Drupal\Core\Form\FormState;
use Drupal\Core\Entity\EntityInterface;

/**
 * Builds entity forms.
 */
class BoxCloneEntityFormBuilder extends EntityFormBuilder {

  /**
   * {@inheritdoc}
   */
  public function getForm(EntityInterface $original_entity, $operation = 'default', array $form_state_additions = []) {

    /** @var \Drupal\box\Entity\BoxInterface $new_box */
    $new_box = $original_entity->createDuplicate();

    // Check for paragraph fields which need to be duplicated as well.
    foreach ($new_box->getTranslationLanguages() as $langcode => $language) {
      $translated_box = $new_box->getTranslation($langcode);

      foreach ($translated_box->getFieldDefinitions() as $field_definition) {
        $field_storage_definition = $field_definition->getFieldStorageDefinition();
        $field_settings = $field_storage_definition->getSettings();
        $field_name = $field_storage_definition->getName();
        if (isset($field_settings['target_type']) && $field_settings['target_type'] == "paragraph") {

          // Each paragraph entity will be duplicated.
          if (!$translated_box->get($field_name)->isEmpty()) {
            foreach ($translated_box->get($field_name) as $value) {
              if ($value->entity) {
                $value->entity = $value->entity->createDuplicate();
                /** @var \Drupal\Core\Field\FieldDefinitionInterface $field_definition */
                foreach ($value->entity->getFieldDefinitions() as $field_definition) {
                  /** @var \Drupal\Core\Field\FieldStorageDefinitionInterface $field_storage_definition */
                  $field_storage_definition = $field_definition->getFieldStorageDefinition();
                  $pfield_settings = $field_storage_definition->getSettings();
                  $pfield_name = $field_storage_definition->getName();
                  \Drupal::moduleHandler()->alter('cloned_box_paragraph_field', $value->entity, $pfield_name, $pfield_settings);
                }
              }
            }
          }
        }
        \Drupal::moduleHandler()->alter('cloned_box', $translated_box, $field_name, $field_settings);
      }
      $translated_box->setTitle(box_clone_get_default_title($translated_box, $langcode));
    }

    // Get the form object for the entity defined in entity definition.
    $form_object = $this->entityManager->getFormObject($new_box->getEntityTypeId(), $operation);

    // Assign the form's entity to our duplicate!
    $form_object->setEntity($new_box);

    $form_state = (new FormState())->setFormState($form_state_additions);
    return $this->formBuilder->buildForm($form_object, $form_state);
  }

}
