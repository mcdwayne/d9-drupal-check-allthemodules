<?php

/**
 * @file
 * Contains \Drupal\wisski_pipe\Form\Processor\DeleteForm.
 */

namespace Drupal\wisski_pipe\Form\Processor;

use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\wisski_pipe\PipeInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Provides a form to remove a processor from a pipe.
 */
class DeleteForm extends ConfirmFormBase {

  /**
   * The pipes that the processor is applied to.
   *
   * @var \Drupal\wisski_pipe\PipeInterface
   */
  protected $pipe;

  /**
   * The processor to be removed from the pipe.
   *
   * @var \Drupal\wisski_pipe\ProcessorInterface
   */
  protected $processor;

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to delete the @plugin processor from the %pipe pipe?', ['%pipe' => $this->pipe->label(), '@plugin' => $this->processor->getLabel()]);
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return Url::fromRoute('wisski_pipe.processors', [
      'wisski_pipe' => $this->pipe->id(),
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'wisski_pipe_processor_delete_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, PipeInterface $wisski_pipe = NULL, $plugin_instance_id = NULL) {
    $this->pipe = $wisski_pipe;

    if (!$this->pipe->getProcessors()->has($plugin_instance_id)) {
      throw new NotFoundHttpException();
    }

    $this->processor = $this->pipe->getProcessor($plugin_instance_id);
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->pipe->removeProcessor($this->processor->getUuid());
    $this->pipe->save();

    drupal_set_message($this->t('The processor %label has been deleted.', ['%label' => $this->processor->getLabel()]));
    $this->logger('wisski_pipe')->notice('The processor %label has been deleted in the @pipe pipe.', [
      '%label' => $this->processor->getLabel(),
      '@pipe' => $this->pipe->label(),
    ]);

    $form_state->setRedirect('wisski_pipe.processors', [
      'wisski_pipe' => $this->pipe->id(),
    ]);

  }

}
