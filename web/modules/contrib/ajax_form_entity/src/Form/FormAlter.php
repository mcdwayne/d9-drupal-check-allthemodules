<?php

namespace Drupal\ajax_form_entity\Form;

use Drupal\Core\Ajax\AfterCommand;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\BeforeCommand;
use Drupal\Core\Ajax\RemoveCommand;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class FormAlter.
 *
 * @package Drupal\ajax_form_entity\Form
 */
class FormAlter {

  /**
   * Ajax form submit, rebuild the form.
   *
   * @param array $form
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   */
  public static function ajaxFormEntityFormSubmit(array $form, FormStateInterface $form_state) {
    $build_info = $form_state->getBuildInfo();
    if (empty($build_info['callback_object'])) {
      return;
    }
    /* @var $callback \Drupal\Core\Entity\EntityForm */
    $callback = &$build_info['callback_object'];
    // Get the entity.
    $entity = $callback->getEntity();
    $entity_type = $entity->getEntityTypeId();
    $bundle = $entity->bundle();

    // Replace the form entity with an empty instance if needed.
    $configurations = $form_state->getValue('ajax_form_entity');
    if ($configurations['reload'] != 'reload_entity') {
      // @todo : injection
      $new_entity = \Drupal::entityTypeManager()
        ->getStorage($entity_type)
        ->create(['type' => $bundle]);
      $callback->setEntity($new_entity);
    }

    // Clear user input.
    $input = $form_state->getUserInput();
    // We should not clear the system items from the user input.
    $clean_keys = $form_state->getCleanValueKeys();
    $clean_keys[] = 'ajax_page_state';
    foreach ($input as $key => $item) {
      if (!in_array($key, $clean_keys) && substr($key, 0, 1) !== '_') {
        unset($input[$key]);
      }
    }

    // Store new entity for display in the AJAX callback.
    $input['entity'] = $entity;
    $form_state->setUserInput($input);

    // Rebuild the form state values.
    $form_state->setRebuild();
    $form_state->setStorage([]);
  }

  /**
   * Ajax callback to handle special ajax form entity magic.
   *
   * @param array $form
   *   Nested array of form elements that comprise the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *    The current state of the form.
   *
   * @return array|\Drupal\Core\Ajax\AjaxResponse
   */
  public static function ajaxFormEntityCallback(array &$form, FormStateInterface $form_state) {
    // If errors, returns the form with errors and messages.
    if ($form_state->hasAnyErrors()) {
      return $form;
    }
    // Else show the result.
    else {
      $user_inputs = $form_state->getUserInput();
      if (empty($user_inputs['entity'])) {
        return $form;
      }
      /* @var $entity \Drupal\Core\Entity\Entity */
      $entity = $user_inputs['entity'];
      $entity_type = $entity->getEntityTypeId();
      $configurations = $form_state->getValue('ajax_form_entity');

      $response = new AjaxResponse();

      // Special case of node page : handle title.
      // @todo : reload the full block with title.
      // @todo : what if in Views but display full ?
      if ($entity_type == 'node' && $configurations['view_mode'] == 'full') {
        /* @var $entity \Drupal\Node\Entity\Node */
        $title = $entity->getTitle();
        $entity->setTitle('');
        $replace = '<h1>' . $title . '</h1>';
        $response->addCommand(new ReplaceCommand('h1', $replace));
      }

      // Get messages even if not shown.
      $status_messages = ['#type' => 'status_messages'];
      $message = [
        '#markup' => \Drupal::service('renderer')
          ->renderRoot($status_messages),
      ];

      // Remove old messages.
      $response->addCommand(new RemoveCommand('.messages'));

      // Send the content back (append, prepend of send in a custom div).
      if ($configurations['send_content']) {
        if ($configurations['show_message']) {
          $output['message'] = $message;
        }
        /* @var $render_view_mode \Drupal\Core\Render\Markup */
        $output['entity']['#markup'] = render(\Drupal::entityTypeManager()
          ->getViewBuilder($entity_type)
          ->view($entity, $configurations['view_mode']));
        if ($configurations['selector_type'] === 'prepend') {
          $response->addCommand(new BeforeCommand($configurations['content_selector'], $output));
        }
        elseif ($configurations['selector_type'] === 'append') {
          $response->addCommand(new AfterCommand($configurations['content_selector'], $output));
        }
        elseif ($configurations['content_selector']) {
          $response->addCommand(new ReplaceCommand($configurations['content_selector'], $output));
        }
      }
      elseif ($configurations['show_message']) {
        $response->addCommand(new ReplaceCommand($configurations['content_selector'], $message));
      }

      // Reload (or remove) the form.
      if ($configurations['reload']) {
        $response->addCommand(new ReplaceCommand($configurations['form_selector'], $form));
      }
      else {
        $response->addCommand(new RemoveCommand($configurations['form_selector']));
      }

      // Case of popin.
      $response->addCommand(new RemoveCommand('.ui-dialog'));
      $response->addCommand(new RemoveCommand('.ui-widget-overlay'));

      return $response;
    }
  }
}