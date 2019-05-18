<?php

namespace Drupal\transcoding\Plugin;

use Drupal\Component\Plugin\ConfigurablePluginInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\PluginFormInterface;
use Drupal\transcoding\TranscodingJobInterface;

interface TranscoderPluginInterface extends ConfigurablePluginInterface, PluginFormInterface {

  /**
   * Form constructor.
   *
   * Job config forms are embedded in the job creation form.
   *
   * @param array $form
   *   An associative array containing the initial structure of the plugin form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form. Calling code should pass on a subform
   *   state created through
   *   \Drupal\Core\Form\SubformState::createForSubform().
   *
   * @return array
   *   The form structure.
   */
  public function buildJobForm(array $form, FormStateInterface $form_state);

  /**
   * Form validation handler.
   *
   * @param array $form
   *   An associative array containing the structure of the job form as built
   *   by static::buildJobForm().
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form. Calling code should pass on a subform
   *   state created through
   *   \Drupal\Core\Form\SubformState::createForSubform().
   */
  public function validateJobForm(array &$form, FormStateInterface $form_state);

  /**
   * Form submission handler.
   *
   * @param array $form
   *   An associative array containing the structure of the plugin form as built
   *   by static::buildConfigurationForm().
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form. Calling code should pass on a subform
   *   state created through
   *   \Drupal\Core\Form\SubformState::createForSubform().
   * @return array Array of data to store in the service_data job entity field.
   */
  public function submitJobForm(array &$form, FormStateInterface $form_state);

  /**
   * Process a transcoding job which declares its use of this plugin.
   *
   * @param \Drupal\transcoding\TranscodingJobInterface $job
   */
  public function processJob(TranscodingJobInterface $job);

}
