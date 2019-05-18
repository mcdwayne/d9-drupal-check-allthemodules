<?php

/**
 * @file
 * Contains \Drupal\field_ui_ajax\Form\FieldConfigAjaxEditForm.
 */

namespace Drupal\field_ui_ajax\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\field_ui\Form\FieldConfigEditForm;
use Drupal\Core\Field\AllowedTagsXssTrait;
use Drupal\Core\Field\FieldFilteredMarkup;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\field\FieldConfigInterface;
use Drupal\field_ui\FieldUI;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\Core\Ajax\InvokeCommand;
use Drupal\field_ui_ajax\Component\Utility\HtmlExtra;
use Drupal\field_ui\Controller\FieldConfigListController;

/**
 * Provides a form for the field settings form.
 */
class FieldConfigAjaxEditForm extends FieldConfigEditForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);
    $storage = $form_state->getStorage();
    if (isset($storage['add_field_multistep'])) {
      if ($storage['add_field_multistep']) {
        // If in the add field workflow we need to enable cache or else Drupal
        // won't be able to properly rebuild the form because it's not called
        // from its URL.
        $form_state->setCached(TRUE);
        $form_state->set('entity_type_id', $this->entity->getTargetEntityTypeId());
        $form_state->set('bundle', $this->entity->getTargetBundle());
      }
    }

    if (HtmlExtra::getIsAjax()) {
      $form['intro'] = [
        '#weight' => -1000,
        '#markup' => '<h2>' . $form['#title'] . '</h2>',
      ];
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   * Remove the delete action and add a cancel action for AJAX called forms.
   */
  protected function actions(array $form, FormStateInterface $form_state) {
    $actions = parent::actions($form, $form_state);

    if (HtmlExtra::getIsAjax()) {
      if (!$this->entity->isNew()) {
        // Remove delete button since there is already one in the overview table and
        // handling both would make the code more dificult.
        unset($actions['delete']);
      }
      $actions['submit']['#ajax'] = [
        'callback' => '::configAjaxEditFormSubmit',
      ];

      $storage = $form_state->getStorage();
      if (isset($storage['add_field_multistep'])) {
        if ($storage['add_field_multistep']) {
          // If we are in the add field workflow we need to set the URL and
          // inform Drupal to treat this as an AJAX form because this are not
          // corectly set if the form is called from another page and/or we send
          // both a callback and an URL.
          $target_entity_type = $this->entityManager->getDefinition($this->entity->getTargetEntityTypeId());
          $route_parameters = [
            'field_config' => $this->entity->id(),
          ] + FieldUI::getRouteBundleParameter($target_entity_type, $this->entity->getTargetBundle());
          $url = new Url('entity.field_config.' . $target_entity_type->id() . '_field_edit_form', $route_parameters);
          $actions['submit']['#ajax']['url'] = $url;
          $actions['submit']['#ajax']['options']['query'][FormBuilderInterface::AJAX_FORM_REQUEST] = TRUE;
        }
      }
      else {
        // Provide the cancel link only when using AJAX and not in the add field
        // workflow.
        $selector = 'js-' . str_replace(['.', '_'], '-', $this->entity->id()) . '-edit-form';
        $actions['cancel'] = [
          '#markup' => '<a href="" class="button js-field-ui-toggle" data-field-ui-show=".action-links, .tableresponsive-toggle-columns, .js-field-ui-ajax-overview" data-field-ui-hide=".' . $selector . '">' . t('Cancel') . '</a>',
          '#weight' => 6,
        ];
      }
    }

    return $actions;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);
    if (!$form_state->hasAnyErrors() && HtmlExtra::getIsAjax()) {
      $form_state->setRebuild(TRUE);
    }
  }

  /**
   * Ajax callback for the "Save settings" button.
   *
   * This hides the form and shows back the action links and overview table.
   */
  public function configAjaxEditFormSubmit(array $form, FormStateInterface $form_state) {
    $response = new AjaxResponse();
    $selector = 'js-' . str_replace(['.', '_'], '-', $this->entity->id());
    $form_selector = $selector . '-edit-form';
    $storage = $form_state->getStorage();
    $multistep = FALSE;
    if (isset($storage['add_field_multistep'])) {
      $multistep = $storage['add_field_multistep'];
    }

    if ($form_state->hasAnyErrors()) {
      $build = [
        '#prefix' => '<div>',
        '#suffix' => '</div>',
        'messages' => [
          '#type' => 'status_messages',
        ],
        'form' => $form,
      ];
      $error_selector = $multistep ? '.block-system-main-block' : '.' . $form_selector;
      $response->addCommand(new HtmlCommand(
        $error_selector,
        $build
      ));
    }
    elseif ($multistep) {
      $entityManager = \Drupal::getContainer()->get('entity.manager');
      $output = $entityManager->getListBuilder('field_config')->render($storage['entity_type_id'], $storage['bundle']);
      $build = [
        '#prefix' => '<div>',
        '#suffix' => '</div>',
        'messages' => [
          '#type' => 'status_messages',
        ],
        'content' => $output,
      ];
      $response->addCommand(new InvokeCommand(
        '.action-links',
        'removeClass',
        ['js-field-ui-hidden']
      ));
      $response->addCommand(new HtmlCommand(
        '.block-system-main-block',
        $build
      ));
    }
    else {
      // Update the field label
      $response->addCommand(new InvokeCommand(
        '.' . $selector . ' .js-field-label',
        'text',
        [$form_state->getValue('label')]
      ));
      $response->addCommand(new InvokeCommand(
        '.' . $form_selector,
        'addClass',
        ['js-field-ui-hidden']
      ));
      $response->addCommand(new InvokeCommand(
        '.action-links, .tableresponsive-toggle-columns, .js-field-ui-ajax-overview',
        'removeClass',
        ['js-field-ui-hidden']
      ));
      $response->addCommand(new HtmlCommand(
        '.' . $form_selector,
        $form
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
