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

class GeneratorAction extends Generator {

  /**
   * Returns a unique string identifying the form.
   *
   * @return string
   *   The unique string identifying the form.
   */
  public function getFormId() {
    return 'generator_action';
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
      '#markup' => t('<h2>' . 'Please fill the blanks to create your Action in D8' . '</h2>'),
      '#weight' => -3
    );
    $form['twig_file'] = array(
      "#type" => 'hidden',
      "#value" => array(
        'action_class' => 'action.php.twig',
        'system.action.id_action.yml.twig'
      ),
    );
    $types = array('node' => 'node', 'user' => 'user', 'comment' => 'comment');
    $form['type_action'] = array(
      '#type' => 'select',
      '#title' => $this->t('The type'),
      '#options' => $types,
      '#description' => $this->t('Ex.: node,user,comment'),
      '#required' => TRUE,
      '#default_value' => 'node',
      '#ajax' => array(
        'callback' => '::changeAction',
        'wrapper' => "ajax_action",
      ),
    );
    $form['node_fieldset'] = array(
      '#type' => 'fieldset',
      '#title' => $this->t('Node'),
      '#prefix' => '<div id="ajax_action">',
      '#suffix' => '</div>',
    );
    $options_actions = array(
      'publishContent' => 'Publish content',
      'unpublishContent' => 'Unpublish content'
    );
    $form['node_fieldset']['options_action_node'] = array(
      '#type' => 'select',
      '#title' => $this->t('Select the type of the action'),
      '#options' => $options_actions,
      '#prefix' => '<div id="ajax_action">',
      '#suffix' => '</div>',
    );
    if ($form_state->getValue('type_action') == "user") {


      $options_actions = array(
        'addRole' => 'Adds a role to a user',
        'removeRole' => 'Removes a role from a user'
      );
      $form['user_fieldset'] = array(
        '#type' => 'fieldset',
        '#title' => $this->t('User'),
        '#prefix' => '<div id="ajax_action">',
        '#suffix' => '</div>',
      );
      $form['user_fieldset']['options_action_user'] = array(
        '#type' => 'select',
        '#title' => $this->t('Select the type of the action'),
        '#options' => $options_actions,
        '#prefix' => '<div id="ajax_choice">',
        '#suffix' => '</div>',
      );
    }
    else {
      if ($form_state->getValue('type_action') == "comment") {
        $options_actions = array(
          'publishComment' => 'Publish comment',
          'unpublishComment' => 'Unpublish comment'
        );
        $form['comment_fieldset'] = array(
          '#type' => 'fieldset',
          '#title' => $this->t('Comment'),
          '#prefix' => '<div id="ajax_action">',
          '#suffix' => '</div>',


        );
        $form['comment_fieldset']['options_action_comment'] = array(
          '#type' => 'select',
          '#title' => $this->t('Select the type of the action'),
          '#options' => $options_actions,
          '#prefix' => '<div id="ajax_action">',
          '#suffix' => '</div>',
        );
      }
    }
    $form['label_action'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Action \'s label'),
      '#required' => TRUE,
    );
    $form['id_action'] = array(
      '#type' => 'machine_name',
      '#title' => $this->t('Action \'s id'),
      '#description' => t('The id of the action, it must be unique'),
      '#required' => TRUE,
      '#machine_name' => array(
        'source' => array('label_action'),
      ),
    );


    $form['action_class'] = array(
      '#type' => 'textfield',
      '#title' => t('Name of the Action class'),
      '#default_value' => 'ExampleAction',
      '#description' => t('Has an impact of the path of the file : module/src/Plugin/Action/Xxx'),
      '#required' => TRUE,
    );

    // dpm($form_state->getValue('options_action'));
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

  /**
   * @param array $form
   * @param FormStateInterface $form_state
   * @return mixed
   */
  public function changeAction(array &$form, FormStateInterface $form_state) {
    if ($form_state->getValue('type_action') == "node") {
      return $form['node_fieldset'];
    }
    else {
      if ($form_state->getValue('type_action') == "user") {
        return $form['user_fieldset'];
      }
      else {
        if ($form_state->getValue('type_action') == "comment") {
          return $form['comment_fieldset'];
        }
      }
    }
  }


}