<?php

/**
 * @file
 * Contains \Drupal\generator_ui\Form\GeneratorFormatter .
 *
 */

namespace Drupal\generator_ui\Form;

//Use the necessary libraries
use Drupal\Core\Form\FormStateInterface;
use Drupal\generator_ui\Controller\GeneratorController;


class GeneratorFormatter extends Generator {

  /**
   * Returns a unique string identifying the form.
   *
   * @return string
   *   The unique string identifying the form.
   */
  public function getFormId() {
    return 'formatter';

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
    $form['into'] = array(
      '#markup' => t('<h2>' . 'Please fill the blanks to create your Plugin Formatter in D8' . '</h2>'),
      "#weight" => -3
    );
    $form['twig_file'] = array(
      "#type" => 'hidden',
      "#value" => array('formatter_class'=>'formatter.php.twig','generator.schema.yml.twig'),
    );
    $form['transformation_path'] = array(
      '#type'=> 'hidden',
      '#value' => true,
    );
    $form['formatter_class'] = array(
      '#type' => 'textfield',
      '#title' => t('Name of the Plugin Formatter class'),
      '#default_value' => 'ExampleFormatter',
      '#description' => t('Path of FieldFormatter class: modules/src/Plugin/Plugin/Field/FieldFormatter'),
      '#required' => TRUE,
    );
    $form['formatter_id'] = array(
      '#type' => 'textfield',
      '#title' => t('Id of the Plugin Formatter'),
      '#default_value' => 'id_formatter',
      '#required' => TRUE,
    );
    $form['formatter_label'] = array(
      '#type' => 'textfield',
      '#title' => t('Label of the Plugin Formatter'),
      '#default_value' => 'Label Formatter',
      '#required' => TRUE,
    );
    $form['field_types'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Plugin types'),
      '#autocomplete_route_name' => 'generator.field_type_autocomplete',
      '#default_value' => 'string_long,email',
      '#description' => t('Plugin types.' . "\n" . 'If you have multiple field types, separate them with a comma
                Ex: entity_reference,boolean,email'),
      '#required' => TRUE,

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
  public function submitForm(array &$form, FormStateInterface $form_state) {
    return parent::submitForm($form, $form_state);
  }

}
