<?php

/**
 * @file
 * Contains \Drupal\field_ui_ajax\Form\FieldStorageConfigAjaxEditForm.
 */

namespace Drupal\field_ui_ajax\Form;

use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\FormState;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\field\Entity\FieldConfig;
use Drupal\field_ui\FieldUI;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Drupal\field_ui\Form\FieldStorageConfigEditForm;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\Core\Ajax\InvokeCommand;
use Drupal\field_ui_ajax\Component\Utility\HtmlExtra;
use Drupal\Core\Url;

/**
 * Provides a form for the "field storage" edit page.
 */
class FieldStorageConfigAjaxEditForm extends FieldStorageConfigEditForm {

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
   * Add the cancel action and the AJAX submit handler.
   */
  protected function actions(array $form, FormStateInterface $form_state) {
    $actions = parent::actions($form, $form_state);

    if (HtmlExtra::getIsAjax()) {
      $actions['submit']['#ajax'] = [
        'callback' => '::configStorageAjaxEditFormSubmit',
      ];
      $storage = $form_state->getStorage();
      if (isset($storage['add_field_multistep'])) {
        if ($storage['add_field_multistep']) {
          // If we are in the add field workflow we need to set the URL and
          // inform Drupal to treat this as an AJAX form because this are not
          // corectly set if the form is called from another page and/or we send
          // both a callback and an URL.
          $url = $storage['next_destination'];
          $actions['submit']['#ajax']['url'] = $url;
          $actions['submit']['#ajax']['options']['query'][FormBuilderInterface::AJAX_FORM_REQUEST] = TRUE;
        }
      }
      else {
        // Provide the cancel link only when using AJAX and not in the add field
        // workflow.
        $selector = 'js-' . str_replace(['.', '_'], '-', $form_state->getBuildInfo()['args'][0]);
        $actions['cancel'] = [
          '#markup' => '<a href="" class="button js-field-ui-toggle" data-field-ui-show=".' . $selector . '" data-field-ui-hide=".' . $selector . '-storage-form">' . t('Cancel') . '</a>',
          '#weight' => 6,
        ];
      }
    }

    return $actions;
  }

  /**
   * Ajax callback for the "Save field settings" button.
   *
   * This hides the form and show back the row that contains field information.
   */
  public static function configStorageAjaxEditFormSubmit(array $form, FormStateInterface $form_state) {
    $response = new AjaxResponse();
    $selector = 'js-' . str_replace(['.', '_'], '-', $form_state->getBuildInfo()['args'][0]);
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
      $error_selector = $multistep ? '.block-system-main-block' : '.' . $selector . '-storage-form td';
      $response->addCommand(new HtmlCommand(
        $error_selector,
        $build
      ));
    }
    elseif ($multistep) {
      $next_state = new FormState();
      $next_state->set('add_field_multistep', TRUE);
      $entityManager = \Drupal::getContainer()->get('entity.manager');
      $form_object = $entityManager->getFormObject('field_config', 'edit');
      $form_object->setEntity($storage['field_config']);
      $next_form = \Drupal::formBuilder()->buildForm($form_object, $next_state);
      $build = [
        '#prefix' => '<div>',
        '#suffix' => '</div>',
        'messages' => [
          '#type' => 'status_messages',
        ],
        'form' => $next_form,
      ];
      $response->addCommand(new HtmlCommand(
        '.block-system-main-block',
        $build
      ));
    }
    else {
      // Remove previous validation error messages
      $response->addCommand(new InvokeCommand(
        '.messages',
        'remove'
      ));
      // Remove the error class from the invalid elements
      $response->addCommand(new InvokeCommand(
        '.' . $selector . '-storage-form input, .' . $selector . '-storage-form select',
        'removeClass',
        ['error']
      ));
      // Remove the invalid aria attribute
      $response->addCommand(new InvokeCommand(
        '.' . $selector . '-storage-form input, .' . $selector . '-storage-form select',
        'removeAttr',
        ['aria-invalid']
      ));
      $response->addCommand(new InvokeCommand(
        '.' . $selector . '-storage-form',
        'addClass',
        ['js-field-ui-hidden']
      ));
      $response->addCommand(new InvokeCommand(
        '.' . $selector,
        'removeClass',
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
