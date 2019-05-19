<?php

/**
 * @file
 * Contains \Drupal\wisski_pipe\Plugin\wisski_pipe\Processor\RunPipe.
 */

namespace Drupal\wisski_pipe\Plugin\wisski_pipe\Processor;

use Drupal\wisski_pipe\ProcessorInterface;
use Drupal\wisski_pipe\ProcessorBase;
use Drupal\wisski_pipe\Entity\Pipe;
use Drupal\Core\Form\FormStateInterface;

/**
 * @Processor(
 *   id = "run_pipe",
 *   label = @Translation("Run a pipe"),
 *   description = @Translation("Run a refered pipe. The data is passed to the pipe as is."),
 *   tags = { "pipe", "recursive" }
 * )
 */
class RunPipe extends ProcessorBase {

  protected $pipe_id;

  protected $lock = FALSE;
  

  /**
   * {@inheritdoc}
   */
  public function setConfiguration(array $configuration) {
    parent::setConfiguration($configuration);
    $this->pipe_id = $this->configuration['pipe_id'];
  }


  /**
   * {@inheritdoc}
   */
  public function getConfiguration() {
    $conf = array(
      'pipe_id' => $this->pipe_id,
    ) + parent::getConfiguration();
    return $conf;
  }


  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return array(
      'pipe_id' => 0,
    ) + parent::defaultConfiguration();
  }


  /**
   * Returns the ID of the pipe that is run by this processor
   *
   * @return string
   */
  public function getPipeID() {
    return $this->pipe_id;
  }

  
  /**
   * {@inheritdoc}
   */
  public function doRun() {
    
    $pipe_id = $this->pipe_id;
    $this->logInfo("Entering nested pipe {id}.", ["id" => $pipe_id]);

    $this->data = \Drupal::service('wisski_pipe.pipe')->run($pipe_id, $this->data, $this->ticket, $this->logger);

    $this->logInfo("Exiting nested pipe {id}.", ["id" => $pipe_id]);

  }


  /**
   * {@inheritdoc}
   */
  public function runsOnPipes() {
    return [$this->pipe_id];
  }


  /**
   * {@inheritdoc}
   */
  public function getSummary() {
    return "Runs pipe " . $this->pipe_id;
    return $this->t("Runs pipe %id.", ["%id" => $this->pipe_id]);
  }

  
  /**
   * {@inheritdoc}
   */
  public function getTags() {
    
    if ($this->isRunning) throw new LogicException("There seems to be a loop in pipe " . $this->pipe_id);
    $this->isRunning = TRUE;

    $pipe = \Drupal::service('wisski_pipe.pipe')->load($this->pipe_id);
    
    $tags = $this->pluginDefinition['tags'];
    $tags = array_combine($tags, $tags);
    if (isset($pipe)) $tags = array_merge($pipe->getTags());
    
    $this->isRunning = FALSE;

    return $tags;

  }

  
  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    
    $service = \Drupal::service('wisski_pipe.pipe');
    $pipes = $service->loadMultiple();
    $options = [];
    foreach ($pipes as $pipe) {
      if (!$this->isNestedIn($pipe->id())) { 
        $options[$pipe->id()] = $pipe->label();
      }
    }

    $form['pipe_id'] = [
      '#type' => 'select',
      '#options' => $options,
      '#default_value' => $this->pipe_id,
      '#description' => 'You may not build loops when running pipes. If your desired pipe does not appear, it may by due to preventing loops.',
      '#multiple' => FALSE,
    ];
    
    return $form;
  }


  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    $pipe_id = $form_state->getValue('pipe_id');
    if (empty($pipe_id)) {
      $form_state->setErrorByName('pipe_id', 'Please select a pipe.');
    }
    if ($this->isNestedIn($pipe_id)) {
      $form_state->setErrorByName('pipe_id', 'Selecting this pipe would cause a loop. Please select another pipe.');
    }
  }


  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $this->pipe_id = $form_state->getValue('pipe_id');
  }
  

  protected function isNestedIn($pipe_id) {
    
    $pipe = \Drupal::service('wisski_pipe.pipe')->load($pipe_id);
    if (!empty($pipe)) {
      foreach ($pipe->getProcessors() as $processor) {
        if ($this->getUuid() == $processor->getUuid()) {
          return TRUE;
        } else {
          $pipes = $processor->runsOnPipes();
          if (isset($pipes)) {
            foreach ($pipes as $pid) {
              $result = $this->isNestedIn($pid);
              if ($result) return TRUE;
            }
          }
        }
      }
    }
    
    return FALSE;

  }

}
