<?php
/**
 * @file
 * Contains \Drupal\generator_ui\Form\GeneratorBlock .
 *
 */
namespace Drupal\generator_ui\Form;


use Drupal\Core\Form\FormStateInterface;

class GeneratorCache extends Generator {

  /**
   * Returns a unique string identifying the form.
   *
   * @return string
   *   The unique string identifying the form.
   */
  public function getFormId() {
    return 'generator_cache';

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
      '#markup' => t('<h2>' . 'Please fill the blanks to generate your Cache example in D8' . '</h2>'),
      "#weight" => -3
    );
    $form['twig_file'] = array(
      "#type" => 'hidden',
      "#value" => array('form'=>'cahe.php.twig'),
    );
    $form['transformation_path'] = array(
      '#type'=> 'hidden',
      '#value' => true,
    );
    $form['form'] = array(
      '#type' => 'textfield',
      '#title' => t('Name of the Cache Form class'),
      '#default_value' => 'ExampleCacheForm',
      '#description' => t('Path of Cache Form class: module/src/Form/'),
      '#required' => TRUE,
    );
    $form['form_id_'] = array(
      '#type' => 'textfield',
      '#title' => t('Id of the Form'),
      '#default_value' => 'id_form',
      '#required' => TRUE,
    );
    $output = '<h2>' . t('NB :') . '</h2>';
    $output .= '<li>' . '<p><strong>' . t('Before generating the code of the cache, you must generate a form & routing using "generator routing" and modify the only the form.') . '</li>' . '</strong></p>';
    $output .= '<li>' . '<p><strong>' . t('This example was recuperated from Drupal examples (This example is up to date).') . '</li>' . '</strong></p>';

    $form['first'] = array(
      '#markup' => $output,
    );
    $form = parent::buildForm($form, $form_state);
    unset($form['btn_create']);
    unset($form['btn_download']);
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