<?php

namespace Drupal\simply_signups\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Component\Datetime\TimeInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Implements a simply signups template add form.
 */
class SimplySignupsTemplatesAddForm extends FormBase {

  protected $time;

  /**
   * Implements __construct function.
   */
  public function __construct(TimeInterface $time_interface) {
    $this->time = $time_interface;
  }

  /**
   * Implements create function.
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('datetime.time')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'simply_signups_templates_add_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['#attached']['library'][] = 'simply_signups/styles';
    $form['#attributes'] = [
      'class' => ['simply-signups-template-add-form', 'simply-signups-form'],
    ];
    $form['title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Title'),
      '#maxlength' => 64,
      '#required' => TRUE,
    ];
    $form['actions'] = [
      '#type' => 'actions',
    ];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save template'),
      '#attributes' => [
        'class' => [
          'button--primary',
          'btn-primary',
        ],
      ],
    ];
    $form['actions']['cancel'] = [
      '#type' => 'submit',
      '#value'  => 'Cancel',
      '#attributes' => [
        'title' => $this->t('Return to templates'),
        'class' => [
          'button--danger',
          'btn-link',
        ],
      ],
      '#submit' => ['::cancel'],
      '#limit_validation_errors' => [['tid']],
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function cancel(array &$form, FormStateInterface $form_state) {
    $form_state->setRedirect('simply_signups.templates');
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $requestTime = $this->time->getCurrentTime();
    $row = [
      'title' => $this->t('@title', ['@title' => $values['title']]),
      'updated' => $requestTime,
      'created' => $requestTime,
    ];
    db_insert('simply_signups_templates')->fields($row)->execute();
    $form_state->setRedirect('simply_signups.templates');
    drupal_set_message($this->t("Template <em>@title</em> successfully created. Don't forget to add fields to your new template.", ['@title' => $values['title']]));
  }

}
