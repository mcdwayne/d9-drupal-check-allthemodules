<?php

/**
 * @file
 * Contains \Drupal\wisski_pipe\Form\Processor\EditForm.
 */

namespace Drupal\wisski_pipe\Form\Processor;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormState;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\wisski_pipe\PipeInterface;

/**
 *  Provides an edit form for processors.
 */
class EditForm extends FormBase {

  /**
   * The pipes to which the processors will be applied.
   *
   * @var \Drupal\wisski_pipe\PipeInterface
   */
  protected $pipe;

  /**
   * The processor to edit.
   *
   * @var \Drupal\wisski_pipe\ConfigurableProcessorInterface
   */
  protected $processor;

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'wisski_pipe_processor_edit_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, PipeInterface $wisski_pipe = NULL, $plugin_instance_id = NULL) {
    $this->pipe = $wisski_pipe;
    $this->processor = $this->pipe->getProcessor($plugin_instance_id);

    $form += $this->processor->buildConfigurationForm($form, $form_state);

    $form['actions'] = array('#type' => 'actions');
    $form['actions']['submit'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Save changes'),
      '#submit' => array('::submitForm'),
      '#button_type' => 'primary',
    );
    $form['actions']['delete'] = array(
      '#type' => 'link',
      '#title' => $this->t('Delete'),
      '#url' => Url::fromRoute('wisski_pipe.processor.delete', [
        'wisski_pipe' => $this->pipe->id(),
        'plugin_instance_id' => $this->processor->getUuid(),
      ]),
      '#attributes' => [
        'class' => ['button', 'button--danger'],
      ],
    );

    return $form;
  }


  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $form_state->cleanValues();
    $plugin_data = (new FormState())->setValues($form_state->getValues());
    $this->processor->submitConfigurationForm($form, $plugin_data);
    $this->pipe->save();

    drupal_set_message($this->t('Saved %label configuration.', array('%label' => $this->processor->getLabel())));
    $this->logger('wisski_pipe')->notice('The processor %label has been updated in the @pipe pipe.', [
      '%label' => $this->processor->getLabel(),
      '@pipe' => $this->pipe->label(),
    ]);

    $form_state->setRedirect('wisski_pipe.processors', [
      'wisski_pipe' => $this->pipe->id(),
    ]);
  }


  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $this->processor->validateConfigurationForm($form, $form_state);
  }


}
