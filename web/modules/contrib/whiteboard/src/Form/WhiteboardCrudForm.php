<?php

/**
 * @file
 * Contains \Drupal\whiteboard\Form\WhiteboardCrudForm.
 */

namespace Drupal\whiteboard\Form;

use Drupal\whiteboard\Whiteboard;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Add, edit whiteboard form.
 */
class WhiteboardCrudForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'whiteboard_crud';
  }

  /**
   * Implements \Drupal\Core\Form\FormInterface::buildForm().
   *
   * Basic crud form.
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    //$form_state['whiteboard'] = $whiteboard->get('wbid') ? $whiteboard : new Whiteboard();
    $whiteboard = new Whiteboard();
    $form['title'] = array(
      '#type' => 'textfield',
      '#title' => 'Name of this whiteboard',
      '#default_value' => $whiteboard->get('wbid') ? $whiteboard->get('title') : '',
      '#required' => TRUE,
    );
    if ($formats = filter_formats($user)) {
      $format_options = array();
      foreach ($formats as $format) {
        $format_options[$format->id()] = $format->label();
      }
      $form['format'] = array(
        '#type' => 'select',
        '#title' => t('Marks output format'),
        '#options' => $format_options,
        '#default_value' => $whiteboard->get('format'),
      );
    }
    $form['submit'] = array(
      '#type' => 'submit',
      '#value' => 'Create whiteboard'
    );

    return $form;
  }

  /**
   * Implements \Drupal\Core\Form\FormInterface::submitForm().
   *
   * Submit handler for crud from.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $data = $form_state->getUserInput();
    $whiteboard = new Whiteboard();
    $whiteboard->set('wbid', $data['wbid'] ? $data['wbid'] : NULL);
    $whiteboard->set('title', $data['title']);
    $whiteboard->set('uid', \Drupal::currentUser()->id());
    $whiteboard->set('marks', '');
    $whiteboard->set('format', $data['format']);
    $whiteboard->save();
    drupal_set_message(t('Whiteboard %title saved successfully.', array('%title' => $whiteboard->get('wbid'))));
  }  
}
