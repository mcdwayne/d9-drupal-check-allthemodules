<?php
/**
 * @file
 * Contains \Drupal\generator_ui\Form\GeneratorForm .
 *
 */

namespace Drupal\generator_ui\Form;


use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\generator_ui\Controller\GeneratorController;

class GeneratorDynamicPermission extends Generator {

  /**
   * Returns a unique string identifying the form.
   *
   * @return string
   *   The unique string identifying the form.
   */
  public function getFormId() {
    return 'generator_dynamic_permissions';
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
      '#markup' => t('<h2>' . 'Please fill the blanks to create your Dynamic permission in D8' . '</h2>'),
      '#weight' => -3
    );
    $form['twig_file'] = array(
      "#type" => 'hidden',
      "#value" => array('dynamic_permission' => 'permissions-class.php.twig',
      'dynamic.permissions.yml.twig'),
    );
    $form['dynamic_permission'] = array(
      '#type' => 'textfield',
      '#title' => t('Name of the Dynamic permission class'),
      '#default_value' => 'ExamplePermission',
      '#description' => t('Has an impact of the path of the file : module/src/xxx'),
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