<?php

/**
 * @file
 * Contains \Drupal\field_ui_ajax\Form\EntityDisplayModeAjaxEditForm.
 */

namespace Drupal\field_ui_ajax\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\field_ui\Form\EntityDisplayModeEditForm;
use Drupal\field_ui_ajax\Component\Utility\HtmlExtra;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\Core\Ajax\InvokeCommand;

/**
 * Provides the edit form for entity display modes.
 */
class EntityDisplayModeAjaxEditForm extends EntityDisplayModeEditForm {

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
      if (!$this->entity->isNew()) {
        // Remove delete button since there is already one in the overview table and
        // handling both would make the code more dificult.
        unset($actions['delete']);
      }
      $selector = 'js-' . str_replace(['.', '_'], '-', $this->entity->id());
      $actions['cancel'] = [
        '#markup' => '<a href="" class="button js-field-ui-toggle" data-field-ui-show=".' . $selector . '" data-field-ui-hide=".' . $selector . '-edit-form">' . t('Cancel') . '</a>',
        '#weight' => 6,
      ];
      $actions['submit']['#ajax'] = [
        'callback' => '::ajaxFormSubmit',
      ];
    }

    return $actions;
  }

  /**
   * Ajax callback for the "Save" button.
   *
   * This hides the form row and shows back the row that contains the display
   * which was edited.
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
        '.' . $selector . '-edit-form td',
        $build
      ));
    }
    else {
      $response->addCommand(new InvokeCommand(
        '.messages',
        'remove'
      ));
      // Update the field label
      $response->addCommand(new InvokeCommand(
        '.' . $selector . ' .js-field-label',
        'text',
        [$form_state->getValue('label')]
      ));
      $response->addCommand(new InvokeCommand(
        '.' . $selector,
        'removeClass',
        ['js-field-ui-hidden']
      ));
      $response->addCommand(new InvokeCommand(
        '.' . $selector . '-edit-form',
        'addClass',
        ['js-field-ui-hidden']
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
