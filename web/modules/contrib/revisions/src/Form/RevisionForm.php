<?php

namespace Drupal\revisions\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Url;

/**
 * Base form for Filter.
 */
class RevisionForm extends FormBase {

  /**
   * Build the simple form.
   *
   * A build form method constructs an array that defines how markup and
   * other form elements are included in an HTML form.
   *
   * @param array $form
   *   Default form array structure.
   * @param FormStateInterface $form_state
   *   Object containing current form state.
   *
   * @return array
   *   The render array defining the elements of the form.
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    // Loads all content types
    //$users = \Drupal\user\Entity\User::loadMultiple();
    $contentTypes = \Drupal::service('entity.manager')->getStorage('node_type')->loadMultiple();

    $contentTypesList = [];
    $contentTypesList['none'] = '--Select--';
    foreach ($contentTypes as $contentType) {
        $contentTypesList[$contentType->id()] = $contentType->label();
    }

    $form['type_options'] = [
      '#type' => 'value',
      '#value' => $contentTypesList
    ];
    $form['filter_content_type'] = [
      '#title' => t('Content Type'),
      '#type' => 'select',
      '#options' => $form['type_options']['#value'],
    ];


    // Getting List of Users

    $query = \Drupal::entityQuery('user');

    $users_id = $query->execute();
    $users = [];
    $users['none'] = '----Select---';
    foreach ($users_id as $uid) {

       $user = user_load($uid);
       $users[$uid] = $user->getUsername(); 
    }

    $form['type_options'] = [
      '#type' => 'value',
      '#value' => $users
    ];
    $form['filter_user'] = [
      '#title' => t('Users'),
      '#type' => 'select',
      '#options' => $form['type_options']['#value'],
    ];

    // Group submit handlers in an actions element with a key of "actions" so
    // that it gets styled correctly, and so that other modules may add actions
    // to the form. This is not required, but is convention.
    $form['actions'] = [
      '#type' => 'actions',
    ];

    // Add a submit button that handles the submission of the form.
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Filter'),
    ];

    return $form;
  }

   /**
   * Getter method for Form ID.
   *
   * The form ID is used in implementations of hook_form_alter() to allow other
   * modules to alter the render array built by this form controller.  it must
   * be unique site wide. It normally starts with the providing module's name.
   *
   * @return string
   *   The unique ID of the form defined by this class.
   */
  public function getFormId() {
    return 'revisions_form';
  }

  /**
   * Implements form validation.
   *
   * The validateForm method is the default method called to validate input on
   * a form.
   *
   * @param array $form
   *   The render array of the currently built form.
   * @param FormStateInterface $form_state
   *   Object describing the current state of the form.
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
  }

  /**
   * Implements a form submit handler.
   *
   * The submitForm method is the default method called for any submit elements.
   *
   * @param array $form
   *   The render array of the currently built form.
   * @param FormStateInterface $form_state
   *   Object describing the current state of the form.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    /*
     * This would normally be replaced by code that actually does something
     * with the title.
     */
    $filter_content_type = $form_state->getValue('filter_content_type');
    
    $route_parameters = [
      'content_type' => $form_state->getValue('filter_content_type'),
      'uid' => $form_state->getValue('filter_user'),
    ];

    $options = [];
    $query = $this->getRequest()->query;
    if ($query->has('destination')) {
      $options['query']['destination'] = $query->get('destination');
      $query->remove('destination');
    }
    $form_state->setRedirect('custom.node_revisions', $route_parameters, $options);
  }

}
