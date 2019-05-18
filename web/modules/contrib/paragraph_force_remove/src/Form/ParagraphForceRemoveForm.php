<?php

namespace Drupal\paragraph_force_remove\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\Core\Link;

/**
 * Create the form to remove paragraph types.
 */
class ParagraphForceRemoveForm extends FormBase {

  /**
   * Get the form id.
   */
  public function getFormId() {
    return 'parafm_form';
  }

  /**
   * Buid the form.
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // Gets all paragraph types.
    $para_types = paragraphs_type_get_types();
    // Set up the options array.
    $para_options = ["_none" => "none"];

    // Add all paragraph types to the options.
    foreach ($para_types as $id => $type) {
      $para_options[$id] = $type->label;
    }

    // A select list form element for each paragraph type.
    $form['para_type'] = [
      '#type' => 'select',
      '#title' => "Paragraph Type",
      '#options' => $para_options,
      '#ajax' => [
        'callback' => [$this, 'getData'],
        'event' => 'change',
        'progress' => [
          'type' => 'throbber',
          'message' => 'Getting the data for the paragraph type.',
        ],
        'effect' => 'fade',
        'wrapper' => "actions-wrapper",
      ],

    ];

    // Combine the base form with dynamic parts of the form
    // returned by the function.
    $form = array_merge($form, $this->getActionWrapper());
    // Hide action buttons.
    $form['action']['submit']['#attributes']['style'] = 'display: none;';
    $form['confirm_remove']['#attributes']['style'] = 'display: none;';
    $form['cancel_remove']['#attributes']['style'] = 'display: none;';

    return $form;
  }

  /**
   * Ajax return confirmation section of form.
   */
  public function removeDataPrompt(array &$form, FormStateInterface $form_state) {
    // Get default dynamic parts.
    $element = $this->getActionWrapper();
    // Get the current selected value.
    $para_type = $form_state->getValue('para_type');

    // Set the warning of deleting all the data.
    $element['action']['submit']['#prefix'] = '<div id="actions-wrapper"><div id="para-data"><h2>Are you sure you want to delete all the content data for ' . $para_type . '?</h2><p>This action cannot be undone. If confirmed, all paragraphs and revisions of this paragraph will be removed. Note that all current revisions should not be using this paragraph.</p></div>';
    // Hide the delete button.
    $element['action']['submit']['#attributes']['style'] = 'display: none;';

    return $element;
  }

  /**
   * Ajax remove paragraph type data and return message.
   */
  public function confirmRemove(array &$form, FormStateInterface $form_state) {
    // Get default dynamic parts.
    $element = $this->getActionWrapper();
    // Get the current selected value.
    $para_type = $form_state->getValue('para_type');
    // Array will hold all paragraph ids.
    $para_ids = [];

    // For each row of this paragraph being used check if it is
    // in current revision and save the id.
    $result = db_query("SELECT * FROM paragraphs_item_field_data where type = '" . $para_type . "'");
    foreach ($result as $para_revision) {
      // Make sure that current fields don't have this paragraph
      // in current revision.
      $check_query = db_query("SELECT COUNT(*) as num FROM node__" . $para_revision->parent_field_name . " where field_paragraph_target_id = " . $para_revision->id)->fetchField();

      // If the paragraph is currently being used return the
      // message and warning.
      if ((int) $check_query > 0) {
        // Create link to edit node if parent is a node.
        if ($para_revision->parent_type == 'node') {
          $url = Url::fromRoute('entity.node.edit_form', ['node' => $para_revision->parent_id]);
          $link = Link::fromTextAndUrl(t('Please edit this') . ' ' . $para_revision->parent_type, $url)->toRenderable();
        }
        else {
          $link = 'Please edit this ' . $para_revision->parent_type;
        }

        // Create message to return.
        $element['action']['submit']['#prefix'] = '<div id="actions-wrapper"><div id="para-data"><p>The ' . $para_revision->parent_type . ' with the id ' . $para_revision->parent_id . ' is using this paragraph in the field ' . $para_revision->parent_field_name . ' in its current revision. ' . render($link) . ' first before trying to force remove this paragraph. Note that all current revision must not use this paragraph in order to remove it.</p></div>';
        // Hide action buttons.
        $element['action']['submit']['#attributes']['style'] = 'display: none;';
        $element['confirm_remove']['#attributes']['style'] = 'display: none;';
        $element['cancel_remove']['#attributes']['style'] = 'display: none;';

        // Send an error status to warn user the action was not complete.
        drupal_set_message(t('The data could not be removed.'), 'error');

        return $element;
      }

      // Save the paragraph id for removal later after the check.
      if (!in_array($para_revision->id, $para_ids)) {
        $para_ids[] = $para_revision->id;
      }
    }

    // Delete the paragraphs by id.
    foreach ($para_ids as $para_id) {
      \Drupal::entityTypeManager()->getStorage('paragraph')->delete([\Drupal::entityTypeManager()->getStorage('paragraph')->load($para_id)]);
      $test .= " para id: " . $para_id;
    }

    // Create link to delete the paragraph type.
    $url = Url::fromRoute('entity.paragraphs_type.delete_form', ['paragraphs_type' => $para_type]);
    $link = Link::fromTextAndUrl(t('Delete') . ' ' . $para_type, $url)->toRenderable();

    // Create the message saying the data was deleted.
    $element['action']['submit']['#prefix'] = '<div id="actions-wrapper"><div id="para-data"><p>All content data for ' . $para_type . ' has been removed. ' . render($link) . '</p></div>';
    // Hide action buttons.
    $element['action']['submit']['#attributes']['style'] = 'display: none;';
    $element['confirm_remove']['#attributes']['style'] = 'display: none;';
    $element['cancel_remove']['#attributes']['style'] = 'display: none;';
    // Create status message telling the delete was successful.
    drupal_set_message(t('All the data for') . ' ' . $para_type . ' ' . t('was removed.'), 'status');

    return $element;
  }

  /**
   * Ajax return table of paragraph type and button to remove.
   */
  public function getData(array &$form, FormStateInterface $form_state) {
    // Get default dynamic parts.
    $element = $this->getActionWrapper();
    // Get the current selected value.
    $para_type = $form_state->getValue('para_type');

    // Create table to return the data for the paragraph.
    $response = "<table><thead><tr><th>id</th><th>revision_id</th><th>created</th><th>parent_id</th><th>parent_type</th><th>parent_field_name</th></tr></thead>";
    // Query to get the paragraph data.
    $result = db_query("SELECT * FROM paragraphs_item_field_data WHERE type = '" . $para_type . "';");
    $count = 0;

    // Create a table row for each query row returned.
    foreach ($result as $record) {
      $response .= "<tr><td>" . $record->id . "</td><td>" . $record->revision_id . "</td><td>" . date('m/d/Y', $record->created) . "</td><td>" . $record->parent_id . "</td><td>" . $record->parent_type . "</td><td>" . $record->parent_field_name . "</td></tr>";
      $count++;
    }
    $response .= "</table><p>Total Used: " . $count . "</p>";

    $element['action']['submit']['#prefix'] = '<div id="actions-wrapper"><div id="para-data">' . $response . '</div>';

    // Hide the appropriate buttons.
    if ($para_type == '_none') {
      $element['action']['submit']['#attributes']['style'] = 'display: none;';
    }
    $element['confirm_remove']['#attributes']['style'] = 'display: none;';
    $element['cancel_remove']['#attributes']['style'] = 'display: none;';

    return $element;
  }

  /**
   * Function that returns the default dynamic parts of the form.
   */
  public function getActionWrapper() {
    // Create the delete button.
    $element['action']['submit'] = [
      '#type' => 'button',
      '#value' => t('Remove all data of this type'),
      '#button_type' => 'danger',
      '#id' => 'remove-all-submit',
      '#attributes' => [
        'tabindex' => '-1',
      ],
      '#prefix' => '<div id="actions-wrapper"><div id="para-data"></div>',
      '#ajax' => [
        'callback' => [$this, 'removeDataPrompt'],
        'event' => 'click',
        'progress' => [
          'type' => 'throbber',
          'message' => 'Removoing the data...',
        ],
        'effect' => 'fade',
        'wrapper' => "actions-wrapper",
      ],
    ];

    // Create the yes confirmation button.
    $element['confirm_remove'] = [
      '#type' => 'button',
      '#value' => t('Yes'),
      '#id' => 'remove-all-submit-confirm',
      '#attributes' => [
        'tabindex' => '-1',
      ],
      '#ajax' => [
        'callback' => [$this, 'confirmRemove'],
        'event' => 'click',
        'progress' => [
          'type' => 'throbber',
          'message' => 'Removoing the data...',
        ],
        'effect' => 'fade',
        'wrapper' => "actions-wrapper",
      ],
    ];

    // Create the no confirmation button.
    $element['cancel_remove'] = [
      '#type' => 'button',
      '#value' => t('No'),
      '#id' => 'remove-all-submit-cancel',
      '#attributes' => [
        'tabindex' => '-1',
      ],
      '#suffix' => '</div>',
      '#ajax' => [
        'callback' => [$this, 'getData'],
        'event' => 'click',
        'progress' => [
          'type' => 'throbber',
          'message' => 'Removoing the data...',
        ],
        'effect' => 'fade',
        'wrapper' => "actions-wrapper",
      ],
    ];

    return $element;
  }

  /**
   * Form submit function.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    return $form;
  }

}
