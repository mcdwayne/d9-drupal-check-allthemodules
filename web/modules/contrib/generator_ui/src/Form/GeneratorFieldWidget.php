<?php
/**
 * @file
 * Contains \Drupal\generator_ui\Form\GeneratorForm .
 *
 */

namespace Drupal\generator_ui\Form;


use Drupal\Core\Form\FormStateInterface;
use Drupal\generator_ui\Controller\GeneratorController;

class GeneratorFieldWidget extends Generator {

  /**
   * Returns a unique string identifying the form.
   *
   * @return string
   *   The unique string identifying the form.
   */
  public function getFormId() {
    return 'field_widget';

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
      '#markup' => t('<h2>' . 'Please fill the blanks to create your Plugin Field Widget in D8' . '</h2>'),
      "#weight" => -3

    );
    $form['twig_file'] = array(
      "#type" => 'hidden',
      "#value" => array('widget_class'=>'widget.php.twig'),
    );
    $form['transformation_path'] = array(
      '#type'=> 'hidden',
      '#value' => true,
    );
    $form['widget_class'] = array(
      '#type' => 'textfield',
      '#title' => t('Name of the Plugin FieldWidget class'),
      '#default_value' => 'ExampleWidget',
      '#description' => t('Path of FieldWidget class: module/src/Plugin/Field/FieldWidget/'),
      '#required' => TRUE,
    );
    $form['widget_id'] = array(
      '#type' => 'textfield',
      '#title' => t('Id of the Plugin FieldWidget'),
      '#default_value' => 'tel_default',
      '#required' => TRUE,
    );
    $form['widget_label'] = array(
      '#type' => 'textfield',
      '#title' => t('Label widget'),
      '#default_value' => 'Telephone',
      '#required' => TRUE,
    );

    $form['widget_field_types'] = array(
      '#type' => 'textfield',
      '#title' => t('field types declared in FieldTypes class '),
      '#default_value' => 'telephone',
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