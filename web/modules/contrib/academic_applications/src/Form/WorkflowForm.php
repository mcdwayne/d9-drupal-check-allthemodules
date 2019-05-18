<?php

namespace Drupal\academic_applications\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Entity\Query\QueryFactory;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class WorkflowForm.
 *
 * @package Drupal\academic_applications\Form
 */
class WorkflowForm extends EntityForm {

  /**
   * An entity query factory.
   *
   * @var \Drupal\Core\Entity\Query\QueryFactory
   */
  protected $entityQueryFactory;

  /**
   * Construct the WorkflowForm.
   *
   * @param \Drupal\Core\Entity\Query\QueryFactory $query_factory
   *   An entity query factory.
   */
  public function __construct(QueryFactory $query_factory) {
    $this->entityQueryFactory = $query_factory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static($container->get('entity.query'));
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    $workflow = $this->entity;
    $webform_settings_url = $this->getUrlGenerator()->generateFromRoute('entity.webform.collection');
    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $workflow->label(),
      '#description' => $this->t("Label for the workflow."),
      '#required' => TRUE,
    ];

    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $workflow->id(),
      '#machine_name' => [
        'exists' => '\Drupal\academic_applications\Entity\Workflow::load',
      ],
      '#disabled' => !$workflow->isNew(),
    ];

    $form['application'] = [
      '#type' => 'select',
      '#title' => $this->t('Application form'),
      '#options' => $this->webFormSelectOptions(),
      '#default_value' => !$workflow->isNew() ? $workflow->getApplication() : NULL,
      '#description' => $this->t('The application <a href=":url">Webform</a>.', [':url' => $webform_settings_url]),
      '#required' => TRUE,
    ];

    $form['upload'] = [
      '#type' => 'select',
      '#title' => $this->t('File upload form'),
      '#options' => $this->webFormSelectOptions(),
      '#default_value' => !$workflow->isNew() ? $workflow->getUpload() : NULL,
      '#description' => $this->t('The file upload <a href=":url">Webform</a>.', [':url' => $webform_settings_url]),
      '#required' => TRUE,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
    $this->validateApplicationElement($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $workflow = $this->entity;
    $status = $workflow->save();

    switch ($status) {
      case SAVED_NEW:
        drupal_set_message($this->t('Created the %label workflow.', [
          '%label' => $workflow->label(),
        ]));
        break;

      default:
        drupal_set_message($this->t('Saved the %label workflow.', [
          '%label' => $workflow->label(),
        ]));
    }
    $form_state->setRedirectUrl($workflow->urlInfo('collection'));
  }

  /**
   * Builds a select array of configured Web forms.
   *
   * @return array
   *   Webform titles, keyed by form ID.
   */
  protected function webFormSelectOptions() {
    $options = [];
    foreach ($this->configFactory()->listAll('webform.webform.') as $webform_config_name) {
      $webform_config = $this->configFactory()->get($webform_config_name);
      $options[$webform_config_name] = $webform_config->get('title');
    }

    return $options;
  }

  /**
   * Validates the application form element.
   *
   * @param array $form
   *   The form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   */
  protected function validateApplicationElement(array &$form, FormStateInterface $form_state) {
    // Ensure this entity's application does not exist with a different ID,
    // regardless of whether it's new or updated.
    $matching_entities = $this->entityQueryFactory->get('academic_applications_workflow')
      ->condition('application', $this->entity->getApplication())
      ->execute();
    $matched_entity = reset($matching_entities);
    if (!empty($matched_entity) && ($matched_entity != $this->entity->id()) && $matched_entity != $this->entity->getOriginalId()) {
      $form_state->setError($form['application'], $this->t('This application form has been used in the "@workflow" workflow.', ['@workflow' => $matched_entity]));
    }
  }

}
