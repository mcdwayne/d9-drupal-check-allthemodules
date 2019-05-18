<?php
/**
 * @file
 * Contains \Drupal\openlayers\Form\ControlForm.
 */

namespace Drupal\openlayers\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form controller for the content_entity_example entity edit forms.
 *
 * @ingroup content_entity_example
 */
class ControlOptionsForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    
    
    /* @var $entity \Drupal\openlayers\Entity\OpenLayersControl */
    $form = parent::buildForm($form, $form_state);
    return $form;
  }
  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    // Redirect to term list after save.
    $form_state->setRedirect('entity.openlayers.controloptions.collection');
    $entity = $this->getEntity();
    $entity->save();
  }
}
