<?php

namespace Drupal\dea_request\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\dea\SolutionDiscovery;
use Drupal\dea_request\Entity\AccessRequest;

class DenyForm extends ContentEntityForm {
  use RevokeFormTrait;
  public function __construct(
    EntityManagerInterface $entity_manager,
    SolutionDiscovery $solution_discovery
  ) {
    parent::__construct($entity_manager);
    $this->solutionDiscovery = $solution_discovery;
  }


  public function buildForm(array $form, FormStateInterface $form_state) {
    $builder = \Drupal::entityTypeManager()->getViewBuilder('dea_request');
    $form['entity'] = $builder->view($this->entity, 'summary');
    
    $form = parent::buildForm($form, $form_state);
    $form['revoke'] = $this->revokeOptions($this->entity);
    return $form;
  }
  
  /**
   * @inheritDoc
   */
  protected function copyFormValuesToEntity(EntityInterface $entity, array $form, FormStateInterface $form_state) {
    parent::copyFormValuesToEntity($entity, $form, $form_state);
    $entity->status = AccessRequest::DENIED;
  }

  /**
   * @inheritDoc
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);
    $form_state->setRedirect('dea_request.list');
    $this->revoke($form_state->getValue('revoke'), $this->entity);
    drupal_set_message($this->t('%user\'s request to %operation %label has been rejected.', [
      '%user' => $this->entity->uid->entity->label(),
      '%operation' => $this->entity->operation->value,
      '%label' => $this->entity->getTarget()->label(),
    ]));
  }


}
