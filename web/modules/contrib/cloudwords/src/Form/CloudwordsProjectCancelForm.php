<?php

namespace Drupal\cloudwords\Form;

use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;
use Drupal\Core\Url;

class CloudwordsProjectCancelForm extends ConfirmFormBase {

  /**
   * The ID of the project
   *
   * @var int
   */
  protected $project_id;

  /**
   * The name of the project
   *
   * @var int
   */
  protected $project_name;

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'cloudwords_project_cancel_form';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to cancel %project?', ['%project' => $this->project_name]);
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    //@todo change to proper cancel url
    return Url::fromUri('internal:/admin/cloudwords/projects/' . $this->project_id);
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription () {
    return $this->t('Canceling this project will release the content attached to it and you will not be able to import any translated content. This will not cancel the project in Cloudwords.');
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, \Drupal\Core\Form\FormStateInterface $form_state, \Drupal\cloudwords\CloudwordsDrupalProject $cloudwords_project = null) {
    $form_state->set(['cloudwords_project'], $cloudwords_project);

    $this->project_id = $cloudwords_project->getId();
    $this->project_name = $cloudwords_project->getName();

    return parent::buildForm($form, $form_state);
  }

  public function submitForm(array &$form, \Drupal\Core\Form\FormStateInterface $form_state) {
    $project = $form_state->get(['cloudwords_project']);
    $project->cancel();
    $form_state->setRedirect('cloudwords.cloudwords');
    drupal_set_message($this->t('%name was canceled successfully.', [
      '%name' => $this->project_name
      ]));
  }
}
