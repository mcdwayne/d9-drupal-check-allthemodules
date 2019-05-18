<?php

/**
 * @file
 * Contains \Drupal\field_ui_ajax\Form\EntityFormDisplayAjaxEditForm.
 */

namespace Drupal\field_ui_ajax\Form;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\PluginSettingsInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\field_ui\FieldUI;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\field_ui\Form\EntityFormDisplayEditForm;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\Core\Ajax\InvokeCommand;

/**
 * Edit form for the EntityViewDisplay entity type.
 */
class EntityFormDisplayAjaxEditForm extends EntityFormDisplayEditForm {

  public function entityFormDisplayAjaxSubmit(array $form, FormStateInterface $form_state) {
    $response = new AjaxResponse();

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
        '.js-' . $this->displayContext . '-' . $this->entity->getTargetEntityTypeId() . '-' . $this->entity->getMode(),
        $build
      ));
    }
    else {
      // Remove previous validation error messages
      $response->addCommand(new InvokeCommand(
        '.messages, abbr.warning',
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
