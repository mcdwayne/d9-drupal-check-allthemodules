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

class GeneratorBatch extends Generator {

  /**
   * Returns a unique string identifying the form.
   *
   * @return string
   *   The unique string identifying the form.
   */
  public function getFormId() {
    return 'generator_batch';
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
      '#markup' => t('<h2>' . 'Please fill the blanks to create your Batch example in D8' . '</h2>'),
      '#weight' => -3
    );
    $form['twig_file'] = array(
      "#type" => 'hidden',
      "#value" => array('controller'=>'controller_batch.php.twig', 'batch.module.yml.twig','batch.routing.yml.twig'),
    );
    $form['controller'] = array(
      '#type' => 'textfield',
      '#title' => t('Name of the Controller class'),
      '#default_value' => 'BatchController',
      '#description' => t('Has an impact of the path of the file : module/src/Controller/xxx'),
      '#required' => TRUE,
    );

    $form = parent::buildForm($form, $form_state);
    return $form;
  }
}