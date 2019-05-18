<?php
namespace Drupal\dea_request\Form;

use Drupal\Core\Entity\ContentEntityDeleteForm;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\dea\SolutionDiscovery;

class DeleteForm extends ContentEntityDeleteForm {
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

  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->revoke($form_state->getValue('revoke'), $this->entity);
    parent::submitForm($form, $form_state);
    $form_state->setRedirect('dea_request.list');
  }
}
