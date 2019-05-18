<?php

/**
 * @file
 * Contains \Drupal\Core\Field\Plugin\Field\FieldWidget\EntityReferenceAutocompleteWidget.
 */

namespace Drupal\pe_assignment_answer\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\user\EntityOwnerInterface;
use Symfony\Component\Validator\ConstraintViolationInterface;

/**
 * Plugin implementation of the 'entity_reference_markup' widget.
 *
 * @FieldWidget(
 *   id = "entity_reference_markup",
 *   label = @Translation("Markup"),
 *   description = @Translation("A markup field with no input."),
 *   field_types = {
 *     "entity_reference"
 *   }
 * )
 */
class EntityReferenceMarkupWidget extends WidgetBase {

    /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $entity = $items->getEntity();
    $referenced_entities = $items->referencedEntities();

//kint($entity); kint($referenced_entities);

//    $element += array(
//      '#type' => 'markup',
//      '#markup' => 'xxxxxxxx',
//    );

    $element += array(
      '#type' => 'entity_autocomplete',
      '#target_type' => $this->getFieldSetting('target_type'),
      '#selection_handler' => $this->getFieldSetting('handler'),
      '#selection_settings' => $this->getFieldSetting('handler_settings'),
      // Entity reference field items are handling validation themselves via
      // the 'ValidReference' constraint.
      '#validate_reference' => FALSE,
      '#maxlength' => 1024,
      '#default_value' => isset($referenced_entities[$delta]) ? $referenced_entities[$delta] : NULL,
      '#size' => $this->getSetting('size'),
      '#placeholder' => $this->getSetting('placeholder'),
    );

//    if ($this->getSelectionHandlerSetting('auto_create')) {
//      $element['#autocreate'] = array(
//        'bundle' => $this->getAutocreateBundle(),
//        'uid' => ($entity instanceof EntityOwnerInterface) ? $entity->getOwnerId() : \Drupal::currentUser()->id()
//      );
//    }

    return array('target_id' => $element);
  }

}
