<?php

namespace Drupal\jsnippet\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;

/**
 * Class JSnippetFormBase.
 *
 * @ingroup jsnippet
 */
class JSnippetFormBase extends EntityForm {

  /**
   * Overrides Drupal\Core\Entity\EntityFormController::form().
   *
   * Builds the entity add/edit form.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   An associative array containing the current state of the form.
   *
   * @return array
   *   An associative array containing the snippet add/edit form.
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // Get anything we need from the base class.
    $form = parent::buildForm($form, $form_state);

    $snippet = $this->entity;

    // Build the form.
    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $snippet->label(),
      '#required' => TRUE,
    ];
    $form['id'] = [
      '#type' => 'machine_name',
      '#title' => $this->t('Machine name'),
      '#default_value' => $snippet->id(),
      '#machine_name' => [
        'exists' => '\Drupal\jsnippet\Entity\JSnippet::load',
        'replace_pattern' => '([^a-z0-9_]+)|(^custom$)',
        'error' => 'The machine-readable name must be unique, and can only contain lowercase letters, numbers, and underscores. Additionally, it can not be the reserved word "custom".',
      ],
      '#disabled' => !$snippet->isNew(),
    ];
    
    if ($type = $snippet->get('type')) {
      $type = TRUE;
    }
    
    $form['type'] = [
      '#type' => 'select',
      '#title' => $this->t('Type'),
      '#description' => $this->t('Select between either JavaScript or CSS.'),
      '#options' => [
        'js' => $this->t('JavaScript'),
        'css' => $this->t('CSS'),
      ],
      '#default_value' => $snippet->get('type'),
      '#disabled' => $type,
    ];
    $form['snippet'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Snippet'),
      '#description' => $this->t('For an external JavaScript or CSS library, provide the URL. For settings or other JavaScript or CSS snippets, provide the raw code without &lt;script&gt; or &lt;style&gt; tags.'),
      '#default_value' => $snippet->get('snippet'),
    ];
    $form['behavior'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Wrap snippet in a Drupal Behavior?'),
      '#description' => $this->t('Checking this box will wrap the included code with a Drupal behavior, following Drupal best practices for JavaScript.'),
      '#default_value' => $snippet->get('behavior'),
      '#states' => [
        'visible' => [
          'select[name="type"]' => ['value' => 'js'],
        ],
      ],
    ];
    $form['scope'] = [
      '#type' => 'select',
      '#title' => $this->t('Scope'),
      '#default_value' => $snippet->get('scope'),
      '#description' => $this->t('Specify the type and scope of the JS snippet. Code based on jQuery must be loaded in the footer by default to ensure that jQuery is loaded and available for use.'),
      '#options' => [
        'footer' => $this->t('Library scoped to footer'),
        'header' => $this->t('Library scoped to head'),
      ],
      '#states' => [
        'visible' => [
          'select[name="type"]' => ['value' => 'js'],
        ],
      ],
    ];
    
    $form['#attached']['library'][] = 'jsnippet/jsnippet';

    // Return the form.
    return $form;
  }

  /**
   * Overrides Drupal\Core\Entity\EntityFormController::actions().
   *
   * To set the submit button text, we need to override actions().
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   An associative array containing the current state of the form.
   *
   * @return array
   *   An array of supported actions for the current entity form.
   */
  protected function actions(array $form, FormStateInterface $form_state) {
    // Get the basic actions from the base class.
    $actions = parent::actions($form, $form_state);

    // Change the submit button text.
    $actions['submit']['#value'] = $this->t('Save');

    // Return the result.
    return $actions;
  }

  /**
   * Overrides Drupal\Core\Entity\EntityFormController::save().
   *
   * Saves the entity. This is called after submit() has built the entity from
   * the form values. Do not override submit() as save() is the preferred
   * method for entity form controllers.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   An associative array containing the current state of the form.
   */
  public function save(array $form, FormStateInterface $form_state) {
    // EntityForm provides us with the entity we're working on.
    $snippet = $this->getEntity();

    $status = $snippet->save();

    // Grab the URL of the new entity. We'll use it in the message.
    $url = $snippet->urlInfo();

    // Create an edit link.
    $edit_link = Link::fromTextAndUrl($this->t('Edit'), $url)->toString();

    if ($status == SAVED_UPDATED) {
      // If we edited an existing entity...
      drupal_set_message($this->t('JSnippet %label has been updated.', ['%label' => $snippet->label()]));
      $this->logger('contact')->notice('JSnippet %label has been updated.', ['%label' => $snippet->label(), 'link' => $edit_link]);
    }
    else {
      // If we created a new entity...
      drupal_set_message($this->t('JSnippet %label has been added.', ['%label' => $snippet->label()]));
      $this->logger('contact')->notice('JSnippet %label has been added.', ['%label' => $snippet->label(), 'link' => $edit_link]);
    }

    // Redirect the user back to the listing route after the save operation.
    $form_state->setRedirect('entity.jsnippet.collection');
  }

}
