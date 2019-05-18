<?php

/**
 * @file
 * Contains \Drupal\generator_ui\Form\GeneratorInfo .
 *
 */

namespace Drupal\generator_ui\Form;

//Use the necessary libraries
use Drupal\Core\Form\FormStateInterface;
use Drupal\generator_ui\Controller\GeneratorController;


class GeneratorAjaxLink extends Generator {

  /**
   * Returns a unique string identifying the form.
   *
   * @return string
   *   The unique string identifying the form.
   */
  public function getFormId() {
    return 'ajax_link';

  }

  /**
   * Form constructor.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return array
   *   The form structure.
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['choice'] = array(
      '#type' => 'select',
      '#title' => 'Select link with Modal/Dialog/Message',
      '#options' => array(
        'modal' => 'modal',
        'dialog' => 'dialog',
        'message' => 'message'
      ),
    );
    $form['twig_file'] = array(
      "#type" => 'hidden',
      "#value" => array(
        'controller' => 'ajaxlinkcontroller.php.twig',
        'ajax.routing.yml.twig',

      ),
    );
    $form['into'] = array(
      '#markup' => t('<h2>' . 'Please fill the blanks to create your ajax link in D8' . '</h2>'),
      "#weight" => -2
    );
    $form['transformation_path'] = array(
      '#type' => 'hidden',
      '#value' => TRUE,
    );

    $form['controller'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Name of the controller class'),
      '#default_value' => 'LinkController',
      '#description' => t('Path of controller class: module/src/Controller'),
    );
    $form = parent::buildForm($form, $form_state);
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {

    return parent::validateForm($form, $form_state);

  }

  /**
   * Form submission handler.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public
  function submitForm(array &$form, FormStateInterface $form_state) {
    return parent::submitForm($form, $form_state);

  }
}