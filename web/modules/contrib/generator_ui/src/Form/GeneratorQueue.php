<?php
/**
 * @file
 * Contains \Drupal\generator_ui\Form\GeneratorForm .
 *
 */

namespace Drupal\generator_ui\Form;


use Drupal\Core\Form\FormStateInterface;


class GeneratorQueue extends Generator {

  /**
   * Returns a unique string identifying the form.
   *
   * @return string
   *   The unique string identifying the form.
   */
  public function getFormId() {
    return 'generator_queue';
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
      '#markup' => t('<h2>' . 'Please fill the blanks to create your Queue example  in D8' . '</h2>'),
      '#weight' => -3
    );
    $form['twig_file'] = array(
      "#type" => 'hidden',
      "#value" => array(
        'controller_class'=>'controller_queue.php.twig',
        'queue_class'=>'queue.php.twig',
        'queue.routing.yml.twig',
      ),
    );
    $form['transformation_path'] = array(
      '#type'=> 'hidden',
      '#value' => true,
    );
    $form['controller_class'] = array(
      '#type' => 'textfield',
      '#title' => t('Name of the Controller class'),
      '#default_value' => 'ExampleController',
      '#description' => t('Has an impact of the path of the file : module/src/Controller/xxx'),
      '#required' => TRUE,
    );
    $form['queue_class'] = array(
      '#type' => 'textfield',
      '#title' => t('Name of the QueueWorker class'),
      '#default_value' => 'ExampleQueue',
      '#description' => t('Has an impact of the path of the file : module/src/Plugin/QueueWorker/xxx'),
      '#required' => TRUE,
    );
    $form['title_queue'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Title the the queue'),
      "default_value" => "example_queue_id",
      '#required' => TRUE,

    );
    $form['id_queue'] = array(
      '#type' => 'machine_name',
      '#title' => $this->t('Id the the queue'),
      "default_value" => "example_queue_id",
      '#required' => TRUE,
      '#machine_name' => array(
        'source' => array('title_queue'),
      ),
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