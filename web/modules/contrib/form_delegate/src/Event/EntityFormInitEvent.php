<?php

namespace Drupal\form_delegate\Event;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Entity\Entity\EntityFormDisplay;
use Drupal\Core\Entity\EntityFormInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\form_delegate\Exception\EntityFormModesNotSupportedException;
use Symfony\Component\EventDispatcher\Event;

/**
 * Event dispatched when entity form is initiating.
 *
 * @package Drupal\form_delegate\Event
 */
class EntityFormInitEvent extends Event {

  use StringTranslationTrait;

  /**
   * The event name.
   */
  const EVENT = 'entity_form.init';

  /**
   * The entity form being initialized.
   *
   * @var \Drupal\Core\Entity\EntityFormInterface
   */
  protected $entityForm;

  /**
   * The form state for the entity form being initialized.
   *
   * @var \Drupal\Core\Form\FormStateInterface
   */
  protected $formState;

  /**
   * EntityFormInitEvent constructor.
   *
   * @param \Drupal\Core\Entity\EntityFormInterface $entityForm
   *   The initializing form.
   * @param \Drupal\Core\Form\FormStateInterface $formState
   *   The initializing form state of the form.
   */
  public function __construct(EntityFormInterface $entityForm, FormStateInterface $formState) {
    $this->entityForm = $entityForm;
    $this->formState = $formState;
  }

  /**
   * Gets the form object.
   *
   * @return \Drupal\Core\Entity\EntityFormInterface
   *   The form object.
   */
  public function getForm() {
    return $this->entityForm;
  }

  /**
   * Gets the form state object.
   *
   * @return \Drupal\Core\Form\FormStateInterface
   *   The form state object.
   */
  public function getFormState() {
    return $this->formState;
  }

  /**
   * Helper method for subscribers to set the form display mode.
   *
   * @param string $displayId
   *   The display mode ID.
   *
   * @throws \Drupal\form_delegate\Exception\EntityFormModesNotSupportedException
   */
  public function setFormDisplay($displayId) {
    $form = $this->getForm();
    /** @var \Drupal\Core\Entity\FieldableEntityInterface $entity */
    $entity = $form->getEntity();

    // Only fieldable entities have form modes.
    if (!$form instanceof ContentEntityForm) {
      throw new EntityFormModesNotSupportedException(sprintf('Only fieldable entities
       have form modes. Tried to set form mode for entity of type %s', $entity->getEntityTypeId()));
    }

    $display = EntityFormDisplay::collectRenderDisplay($entity, $displayId);
    $form->setFormDisplay($display, $this->getFormState());
  }

}
