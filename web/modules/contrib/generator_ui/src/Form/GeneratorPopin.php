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

class GeneratorPopin extends Generator {

  /**
   * Returns a unique string identifying the form.
   *
   * @return string
   *   The unique string identifying the form.
   */
  public function getFormId() {
    return 'generator_popin';
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
      '#markup' => t('<h2>' . 'Please fill the blanks to create a page with dialog in D8' . '</h2>'),
      '#weight' => -3
    );
    $form['twig_file'] = array(
      "#type" => 'hidden',
      "#value" => array(
        'form' => 'form_popin.php.twig',
        'controller' => 'controller_popin.php.twig',
        'popin.routing.yml.twig'
      ),
    );
    $form['form'] = array(
      '#type' => 'textfield',
      '#title' => t('Name of the Form class'),
      '#default_value' => 'PopinForm',
      '#description' => t('Has an impact of the path of the file : module/src/Form/xxx'),
      '#required' => TRUE,
    );
    $form['controller'] = array(
      '#type' => 'textfield',
      '#title' => t('Name of the controller class'),
      '#default_value' => 'PageController',
      '#description' => t('Has an impact of the path of the file : module/src/Controller/xxx'),
      '#required' => TRUE,
    );
    $form['hook_ajax_render_alter'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Generate hook_ajax_render_alter to modify and add commands.')
    );
    if ($form_state->getValue('hook_ajax_render_alter') == 1) {
      $form['twig_file'] = array(
        "#type" => 'hidden',
        "#value" => array(
          'form' => 'form_popin.php.twig',
          'controller' => 'controller_popin.php.twig',
          'popin.routing.yml.twig',
          'ajaxcommands.module.yml.twig'
        ),
      );

    }
    else {
      $form['twig_file'] = array(
        "#type" => 'hidden',
        "#value" => array(
          'form' => 'form_popin.php.twig',
          'controller' => 'controller_popin.php.twig',
          'popin.routing.yml.twig'
        ),
      );
    }
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