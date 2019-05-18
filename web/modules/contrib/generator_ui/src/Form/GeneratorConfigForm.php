<?php
/**
 * @file
 * Contains \Drupal\generator_ui\Form\GeneratorForm .
 *
 */

namespace Drupal\generator_ui\Form;


use Drupal\Core\Form\FormStateInterface;
use Drupal\generator_ui\Controller\GeneratorController;

class GeneratorConfigForm extends Generator {

  /**
   * Returns a unique string identifying the form.
   *
   * @return string
   *   The unique string identifying the form.
   */
  public function getFormId() {
    return 'generator_config_form';

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
   * http://www.laparoscopic.md/
   *   The form structure.
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['into'] = array(
      '#markup' => t('<h2>' . 'Please fill the blanks to create your Config Form in D8' . '</h2>'),
      "#weight" => -3

    );
    $form['twig_file'] = array(
      "#type" => 'hidden',
      "#value" => array(
        'form' => 'form_config.php.twig',
        'generator.routing.yml.twig',
        'generator.settings.yml.twig'
      ),
    );
    $form['transformation_path'] = array(
      '#type'=> 'hidden',
      '#value' => true,
    );
    $form['title'] = array(
      '#type' => 'textfield',
      '#default_value' => 'Page title',
      '#title' => t('Page title'),
    );
    $form['route'] = array(
      '#type' => 'textfield',
      '#title' => t('Route name'),
      '#default_value' => 'key',
      '#field_prefix' => 'module_name.',
      '#element_validate' => array(array(get_class($this), 'validate_route')),
      '#required' => TRUE,
      '#description' => t('The route name <strong>must</strong> be unique,so the route name must start with the module name followed by an unique key'),
    );
    // @todo : call the menu & permission form.
    $form['pathh'] = array(
      '#type' => 'textfield',
      '#title' => t('Path'),
      '#default_value' => t('admin/configs/configform'),
      '#required' => TRUE,
      '#description' => t('URL of the page relative to the domain name. Do not include trailing slash. Follow the Drupal path architecture like admin/structure/xx or admin/configs, etc.'),
    );
    $form['argums'] = array(
      '#type' => 'textfield',
      '#title' => t('Arguments in path'),
      '#states' => array(
        'visible' => array(
          ':input[name="type_routing"]' => array('value' => 'controller'),
        ),
      ),
      '#description' => t('Dynamic values will be sent. Arguments must be separated
        by / and putted in braces,Ex: {first_argument}/{second_argument}. Do not include trailing slash.'),
    );
    $form['permission'] = array(
      '#type' => 'textfield',
      '#title' => 'Permission',
      '#default_value' => 'access content',
     );
    $form['form'] = array(
      '#type' => 'textfield',
      '#title' => t('Name of the Config Form class'),
      '#default_value' => 'ExampleConfigForm',
      '#description' => t('Path of config Form class: modules/src/Form/'),
      '#required' => TRUE,
    );
    $form['form_id_'] = array(
      '#type' => 'textfield',
      '#title' => t('Id of the Form'),
      '#default_value' => 'id_form',
      '#required' => TRUE,
    );

    $form['validation'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Add validation.'),
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