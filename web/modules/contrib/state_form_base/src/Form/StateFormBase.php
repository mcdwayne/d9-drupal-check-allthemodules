<?php

namespace Drupal\state_form_base\Form;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\State\StateInterface;

/**
 * Base class for implementing system state forms.
 */
abstract class StateFormBase extends FormBase {


  /**
   * The state storage service.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * Constructs a \Drupal\system\ConfigFormBase object.
   *
   * @param \Drupal\Core\State\StateInterface $state
   *   The state storage service.
   */
  public function __construct(StateInterface $state) {
    $this->state = $state;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('state')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['actions']['#type'] = 'actions';
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save configuration'),
      '#button_type' => 'primary',
    ];

    // By default, render the form using system-config-form.html.twig.
    $form['#theme'] = 'system_config_form';

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    drupal_set_message($this->t('The state configuration have been saved.'));
  }

}
