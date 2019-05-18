<?php

namespace Drupal\cloudwords\Form;

use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Url;

class CloudwordsProjectLanguageApproveForm extends ConfirmFormBase {

  /**
   * The ID of the project
   *
   * @var int
   */
  protected $project_id;

  /**
   * Language of translations in project.
   *
   * @var string
   */
  protected $language;

  /**
   * Language status of translations in project.
   *
   * @var string
   */
  protected $language_status;

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'cloudwords_project_language_approve_form';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to approve %lang?', ['%lang' => $this->language]);
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
    return $this->t('Approving a language tells your translation vendor the content has been accepted and does not need further work. This action cannot be undone.');
  }

  public function buildForm(array $form, \Drupal\Core\Form\FormStateInterface $form_state, \Drupal\cloudwords\CloudwordsDrupalProject $cloudwords_project = NULL, \Drupal\cloudwords\CloudwordsLanguage $cloudwords_language = NULL) {
    $this->language = $cloudwords_language->getDisplay();
    $this->project_id = $cloudwords_project->getId();

    $form_state->set(['cloudwords_project'], $cloudwords_project);
    $form_state->set(['cloudwords_language'], $cloudwords_language);

    return parent::buildForm($form, $form_state);
  }

  public function submitForm(array &$form, \Drupal\Core\Form\FormStateInterface $form_state) {
    $project = $form_state->get(['cloudwords_project']);
    $language = $form_state->get(['cloudwords_language']);

    cloudwords_get_api_client()->approve_project_language($project->getId(), $language->getLanguageCode());

    $project->approve($language);

    drupal_set_message($this->t('%name was successfully approved.', [
      '%name' => $project->getName()
      ]));

    $form_state->setRedirect('cloudwords.cloudwords_project_overview_form', ['cloudwords_project' => $project->getId()]);
  }

}
