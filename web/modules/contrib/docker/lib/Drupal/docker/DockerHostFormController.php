<?php

/**
 * @file
 * Definition of Drupal\docker\DockerHostFormController.
 */

namespace Drupal\docker;

use Drupal\Core\Entity\EntityFormControllerNG;

/**
 * Base for controller for docker host forms.
 */
class DockerHostFormController extends EntityFormControllerNG {

  /**
   * Overrides Drupal\Core\Entity\EntityFormController::form().
   */
  public function form(array $form, array &$form_state) {
    $docker_host = $this->entity;
    $form['#id'] = drupal_html_id('docker_host_form');

    // Add the author name field depending on the current user.
    $form['host'] = array(
      '#type' => 'textfield',
      '#title' => t('Hostname or IP'),
      '#default_value' => $docker_host->host->value,
      '#required' => TRUE,
      '#maxlength' => 60,
      '#size' => 30,
    );

    // Add author e-mail and homepage fields depending on the current user.
    $form['port'] = array(
      '#type' => 'number',
      '#title' => t('Port'),
      '#default_value' => $docker_host->port->value,
      '#required' => TRUE,
      '#maxlength' => 64,
      '#size' => 30,
    );

    $form['status'] = array(
      '#type' => 'radios',
      '#title' => t('Status'),
      '#default_value' => isset($docker_host->status->value) ? $docker_host->status->value : 1,
      '#options' => array(
        0 => t('Inactive'),
        1 => t('Active'),
      ),
    );

    $now = time();
    $form['created'] = array(
      '#type' => 'value',
      '#value' => isset($docker_host->created->value) ? $docker_host->created->value : $now
    );

    $form['changed'] = array(
      '#type' => 'value',
      '#value' => isset($docker_host->changed->value) ? $docker_host->changed->value : $now
    );

    return parent::form($form, $form_state, $docker_host);
  }

  /**
   * Overrides Drupal\Core\Entity\EntityFormController::actions().
   */
  protected function actions(array $form, array &$form_state) {
    $element = parent::actions($form, $form_state);
    // Mark the submit action as the primary action, when it appears.
    $element['submit']['#button_type'] = 'primary';
    return $element;
  }

  /**
   * Overrides Drupal\Core\Entity\EntityFormController::validate().
   */
  public function validate(array $form, array &$form_state) {
    parent::validate($form, $form_state);
  }

  /**
   * Overrides Drupal\Core\Entity\EntityFormController::save().
   */
  public function save(array $form, array &$form_state) {
    $docker_host = $this->entity;
    $docker_host->save();
    $form_state['redirect'] = 'docker/hosts';
  }
}