<?php

/**
 * @file
 * Contains \Drupal\field_ui_ajax\Form\EntityViewDisplayAjaxEditForm.
 */

namespace Drupal\field_ui_ajax\Form;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\PluginSettingsInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\field_ui\FieldUI;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\field_ui\Form\EntityViewDisplayEditForm;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\AppendCommand;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\Core\Ajax\InvokeCommand;
use Drupal\field_ui_ajax\Component\Utility\HtmlExtra;
use Drupal\Core\Render\Element;

/**
 * Edit form for the EntityViewDisplay entity type.
 */
class EntityViewDisplayAjaxEditForm extends EntityViewDisplayEditForm {

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);
    if (HtmlExtra::getIsAjax()) {
      /** @var \Drupal\Core\Menu\LocalTaskManagerInterface $manager */
      $manager = \Drupal::service('plugin.manager.menu.local_task');
      $links = $manager->getLocalTasks(\Drupal::routeMatch()->getRouteName(), 1);
      $tabs = count(Element::getVisibleChildren($links['tabs'])) > 0 ? $links['tabs'] : [];
      $tabs = [
        '#theme' => 'menu_local_tasks',
        '#secondary' => $tabs,
      ];
      $form['#secondary_tabs'] = $tabs;
    }
  }

  public function entityViewDisplayAjaxSubmit(array $form, FormStateInterface $form_state) {
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
      if (isset($form['#secondary_tabs'])) {
        $response->addCommand(new HtmlCommand(
          '.js-secondary-local-tasks',
          $form['#secondary_tabs']
        ));
      }
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
