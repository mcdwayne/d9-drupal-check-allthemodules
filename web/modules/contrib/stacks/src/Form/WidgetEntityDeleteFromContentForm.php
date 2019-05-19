<?php

namespace Drupal\stacks\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a form for deleting Widget Entity entities.
 *
 * @ingroup stacks
 */
class WidgetEntityDeleteFromContentForm extends FormBase {

  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['warning'] = [
      '#markup' => t('Note: this element will be only removed from the current content')
    ];

    $form['actions']['#type'] = 'actions';

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#button_type' => 'primary',
      '#value' => 'Delete',
      '#attributes' => [
        'class' => [
          'use-ajax-submit'
        ]
      ]
    ];

    return $form;
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    $build_info = $form_state->getBuildInfo()['args'];
    $nid = isset($build_info[0]['#extra']['nid']) ? $build_info[0]['#extra']['nid'] : null;
    $id = isset($build_info[0]['#extra']['id']) ? $build_info[0]['#extra']['id'] : null;
    $field_name = isset($build_info[0]['#extra']['field_name']) ? $build_info[0]['#extra']['field_name'] : null;
    $delta = isset($build_info[0]['#extra']['delta']) ? $build_info[0]['#extra']['delta'] : null;

    if (isset($nid) && isset($id)) {
      // Load current node and remove the entity from the field
      $node = \Drupal::entityTypeManager()->getStorage('node')->load($nid);

      // Clearing node cache for this specific content.
      $node->save();

      // Removing the destination parameter inherited from Contextual Links,
      // which breaks the dialog callback in the front-end editor
      \Drupal::request()->query->set('destination', '');

      // Get the widget id to replace the content in the page
      $form_state->setRedirect('stacks.admin.ajax_close', [
        'nid' => $nid,
        'id' => 0,
        'field_name' => $field_name,
        'delta' => $delta,
      ]);

    }
  }

  public function getFormId() {
    return 'widget_entity_delete_from_content_form';
  }
}