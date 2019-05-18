<?php

/**
 * @file
 * Contains \Drupal\field_ui_ajax\Form\EntityDisplayModeAjaxDeleteForm.
 */

namespace Drupal\field_ui_ajax\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\field_ui\Form\EntityDisplayModeDeleteForm;
use Drupal\field_ui_ajax\Component\Utility\HtmlExtra;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\Core\Ajax\InvokeCommand;

/**
 * Provides the delete form for entity display modes.
 */
class EntityDisplayModeAjaxDeleteForm extends EntityDisplayModeDeleteForm {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);
    if (HtmlExtra::getIsAjax() && isset($form['#title'])) {
      $form['intro'] = [
        '#weight' => -1000,
        '#markup' => '<h3>' . $form['#title'] . '</h3>',
      ];
    }
    return $form;
  }

  /**
   * Returns an array of supported actions for the current entity form.
   */
  protected function actions(array $form, FormStateInterface $form_state) {
    $actions = parent::actions($form, $form_state);

    if (HtmlExtra::getIsAjax()) {
      $selector = 'js-' . str_replace(['.', '_'], '-', $this->entity->id());
      $actions['cancel']['#attributes']['class'][] = 'js-field-ui-toggle';
      $actions['cancel']['#attributes']['data-field-ui-show'] = '.' . $selector;
      $actions['cancel']['#attributes']['data-field-ui-hide'] = '.' . $selector . '-delete-form';
      $actions['submit']['#ajax'] = [
        'callback' => '::ajaxFormSubmit',
      ];
    }

    return $actions;
  }

  /**
   * Ajax callback for the "Delete" button.
   *
   * This removes all rows which belong to this custom display.
   */
  public function ajaxFormSubmit(array $form, FormStateInterface $form_state) {
    $response = new AjaxResponse();
    $selector = 'js-' . str_replace(['.', '_'], '-', $this->entity->id());

    if ($form_state->hasAnyErrors()) {
      $build = [
        '#prefix' => '<div>',
        '#suffix' => '</div>',
        'messages' => [
          '#type' => 'status_messages',
        ],
        'form' => $form,
      ];
      $response->addCommand(new HtmlCommand(
        '.' . $selector . '-delete-form td',
        $build
      ));
    }
    else {
      $response->addCommand(new InvokeCommand(
        '.messages, .' . $selector . ', .' . $selector . '-delete-form, .' . $selector . '-edit-form',
        'remove'
      ));
      $response->addCommand(new HtmlCommand(
        '#field-ui-messages',
        ['#type' => 'status_messages']
      ));
      $response->addCommand(new InvokeCommand(
        '#field-ui-messages',
        'addClass',
        ['field-ui-messages-show']
      ));
    }
    return $response;
  }

}
