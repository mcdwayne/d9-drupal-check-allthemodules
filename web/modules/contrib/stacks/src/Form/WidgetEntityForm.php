<?php

namespace Drupal\stacks\Form;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Extension\ModuleHandler;
use Drupal\Core\Url;
use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Routing\RedirectDestination;
use Drupal\Core\Routing\RedirectDestinationInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Form controller for Widget Entity edit forms.
 *
 * @ingroup stacks
 */
class WidgetEntityForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    /* @var $entity \Drupal\stacks\Entity\WidgetEntity */
    $form = parent::buildForm($form, $form_state);

    // Setting mode to AJAX if route is different than the entity editing form
    $current_route = \Drupal::routeMatch()->getRouteName();
    $params = \Drupal::routeMatch()->getParameters()->all();

    $form['#extra'] = [
      'ajax_processed' => FALSE,
      'nid' => '',
      'widget_id' => '',
      'field_name' => '',
      'delta' => '',
    ];

    if ($current_route == 'stacks.admin.ajax_edit') {

      // Check if loaded within a jQueryUI dialog.
      $in_dialog = FALSE;
      if (isset($_POST['_drupal_ajax'])) {
        if ($_POST['_drupal_ajax'] == "1") {
          $in_dialog = TRUE;
        }
      }

      if (!$in_dialog) {
        $response = new RedirectResponse('/system/403');
        $response->send();
      }

      $form['#extra']['ajax_processed'] = TRUE;
      $form['#extra']['nid'] = $params['nid'];
      $form['#extra']['widget_id'] = $params['id'];
      $form['#extra']['field_name'] = $params['field_name'];
      $form['#extra']['delta'] = $params['delta'];

      // Check if entity exist
      $entity_id = $this->entity->id();

      if (isset($entity_id)) {
        $form['actions']['presave'] = [
          '#type' => 'button',
          '#button_type' => 'primary',
          '#value' => 'Save',
        ];

        $form['actions']['presave']['#attributes']['class'][] = 'modal-stacks-save';

        $form['actions']['submit']['#attributes']['class'][] = 'hidden';

        unset($form['actions']['delete']);

        $form['#bundle'] = $this->entity->bundle();
        $form['#entity_type'] = $this->entity->getEntityTypeId();

        // Get configuration set for all widget types
        $config_sets = \Drupal::configFactory()->listAll('stacks.widget_entity_type');
        $plugin = '';

        // Invoke all form alters to show form customisations in the front-end editor
        $module_handler = \Drupal::moduleHandler();

        //TODO: invokeAll is not working properly
        // $module_handler->invokeAll('inline_entity_form_entity_form_alter', [$form, $form_state]);
        stacks_inline_entity_form_entity_form_alter($form, $form_state);

        foreach ($config_sets as $config_set) {
          $editable_config = \Drupal::configFactory()->getEditable($config_set);
          $plugin = $editable_config->get('plugin');

          // Set proper widget handlers to each widget type
          if (strpos($config_set, $form['#bundle'])) {
            switch ($plugin) {
              case 'content_feed':
                if ($module_handler->moduleExists('stacks_content_feed')) {
                  $form['field_cfeed_taxonomy_terms']['#attached']['library'][] = 'stacks_content_feed/admin.content_feed_forms';
                }
                break;
            }
          }
        }
      }
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $entity = $this->entity;
    $status = parent::save($form, $form_state);

    switch ($status) {
      case SAVED_NEW:
        drupal_set_message(t('Created the %label Widget Entity.', [
          '%label' => $entity->label(),
        ]));
        break;

      default:
        drupal_set_message(t('Saved the %label Widget Entity.', [
          '%label' => $entity->label(),
        ]));
    }

    // If using the front-end editor, executes a different action set.
    if ($form['#extra']['ajax_processed']) {

      // Removing the destination parameter inherited from Contextual Links,
      // which breaks the dialog callback in the front-end editor
      \Drupal::request()->query->set('destination', '');

      // Get the widget id to replace the content in the page
      $form_state->setRedirect('stacks.admin.ajax_close', [
        'nid' => $form['#extra']['nid'],
        'id' => $form['#extra']['widget_id'],
        'field_name' => $form['#extra']['field_name'],
        'delta' => $form['#extra']['delta']
      ]);
    }
    else {
      $form_state->setRedirect('entity.widget_entity.canonical', ['widget_entity' => $entity->id()]);
    }
  }

}
