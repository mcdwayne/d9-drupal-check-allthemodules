<?php

/**
 * @file
 * Contains \Drupal\field_ui_ajax\Form\EntityDisplayModeAjaxAddForm.
 */

namespace Drupal\field_ui_ajax\Form;

use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Drupal\field_ui\Form\EntityDisplayModeAddForm;
use Drupal\field_ui_ajax\Component\Utility\HtmlExtra;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Ajax\InvokeCommand;
use Drupal\field_ui_ajax\EntityDisplayModeAjaxListBuilder;
use Drupal\field_ui_ajax\EntityFormModeAjaxListBuilder;

/**
 * Provides the add form for entity display modes.
 */
class EntityDisplayModeAjaxAddForm extends EntityDisplayModeAddForm {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $entity_type_id = NULL) {
    $form = parent::buildForm($form, $form_state, $entity_type_id);
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
      $args = $form_state->getBuildInfo()['args'];
      $entity_type_id = $args[0];
      $selector = 'js-' . str_replace(['.', '_'], '-', $entity_type_id);
      $actions['cancel'] = [
        '#markup' => '<a href="" class="button js-field-ui-toggle" data-field-ui-show=".' . $selector . '-add-new" data-field-ui-hide=".' . $selector . '-add-form">' . t('Cancel') . '</a>',
        '#weight' => 6,
      ];
      $actions['submit']['#ajax'] = [
        'callback' => '::ajaxFormSubmit',
      ];
    }

    return $actions;
  }

  /**
   * Ajax callback for the "Save settings" button.
   *
   * This hides the form and shows back the action links and overview table.
   */
  public function ajaxFormSubmit(array $form, FormStateInterface $form_state) {
    $response = new AjaxResponse();
    $args = $form_state->getBuildInfo()['args'];
    $entity_type_id = $args[0];
    $selector = 'js-' . str_replace(['.', '_'], '-', $entity_type_id);

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
        '.' . $selector . '-add-form td',
        $build
      ));
    }
    else {
      $entity_types = $this->entityManager->getDefinitions();
      $build_info = $form_state->getBuildInfo();
      $args = $build_info['args'];
      $mode_id = str_replace('_add_form', '', $build_info['form_id']);
      $storage = $this->entityManager->getStorage($mode_id);
      $entity_type = $entity_types[$mode_id];
      if ($mode_id == 'entity_view_mode') {
        $list_builder = new EntityDisplayModeAjaxListBuilder($entity_type, $storage, $entity_types);
      }
      else {
        $list_builder = new EntityFormModeAjaxListBuilder($entity_type, $storage, $entity_types);
      }
      $table = $list_builder->render($this->targetEntityTypeId)[$this->targetEntityTypeId];
      $response->addCommand(new InvokeCommand(
        '.messages',
        'remove'
      ));
      $response->addCommand(new ReplaceCommand(
        '.' . $selector . '-table',
        $table
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
