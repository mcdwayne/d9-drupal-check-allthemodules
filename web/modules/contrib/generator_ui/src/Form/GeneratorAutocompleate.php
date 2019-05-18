<?php
/**
 * @file
 * Contains \Drupal\generator_ui\Form\GeneratorAutocompleate .
 *
 */

namespace Drupal\generator_ui\Form;


use Drupal\Core\Form\FormStateInterface;

class GeneratorAutocompleate extends Generator {

  /**
   * Returns a unique string identifying the form.
   *
   * @return string
   *   The unique string identifying the form.
   */
  public function getFormId() {
    return 'autocomplete_form';
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
      '#markup' => t('<h2>' . 'Please fill the blanks to create your example autocompleate in D8' . '</h2>'),
      '#weight' => -3
    );
    $form['twig_file'] = array(
      "#type" => 'hidden',
      "#value" => array( 'form'=>'form_autocompleate.php.twig','controller'=>'controller_autocompleate.php.twig','autocompleate.routing.yml.twig'),
    );
    $form['transformation_path'] = array(
      '#type'=> 'hidden',
      '#value' => true,
    );
    $form['form'] = array(
      '#type' => 'textfield',
      '#title' => t('Name of the Form class'),
      '#default_value' => 'ExampleForm',
      '#description' => t('Has an impact of the path of the file : module/src/Form/xxx'),
      '#required' => TRUE,
    );
    $form['controller'] = array(
      '#type' => 'textfield',
      '#title' => t('Name of the controller class'),
      '#default_value' => 'ExampleController',
      //'#field_suffix' => 'Controller',
      '#description' => t('Must ends with "Controller" word.'),
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