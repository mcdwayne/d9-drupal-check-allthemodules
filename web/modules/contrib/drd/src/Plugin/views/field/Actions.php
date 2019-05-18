<?php

namespace Drupal\drd\Plugin\views\field;

use Drupal\Core\Form\FormStateInterface;
use Drupal\system\Plugin\views\field\BulkForm;
use Drupal\views\Plugin\views\display\DisplayPluginBase;
use Drupal\views\ViewExecutable;

/**
 * Defines a DRD Entity operations bulk form element.
 *
 * @ViewsField("drd_entity_actions")
 */
class Actions extends BulkForm {

  /**
   * Service to handle remote actions.
   *
   * @var \Drupal\drd\RemoteActionsInterface
   */
  private $actionService;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, $entity_manager, $language_manager, $messenger) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $entity_manager, $language_manager, $messenger);
    $this->actionService = \Drupal::service('drd.remote.actions');
  }

  /**
   * {@inheritdoc}
   */
  public function init(ViewExecutable $view, DisplayPluginBase $display, array &$options = NULL) {
    parent::init($view, $display, $options);
    $this->actionService->setMode($this->getEntityType());
  }

  /**
   * {@inheritdoc}
   */
  public function viewsForm(&$form, FormStateInterface $form_state) {
    // Make sure we do not accidentally cache this form.
    // @todo Evaluate this again in https://www.drupal.org/node/2503009.
    $form['#cache']['max-age'] = 0;

    // Add the tableselect javascript.
    $form['#attached']['library'][] = 'core/drupal.tableselect';
    $use_revision = array_key_exists('revision', $this->view->getQuery()->getEntityTableInfo());

    // Only add the bulk form options and buttons if there are results.
    if (!empty($this->view->result)) {
      // Render checkboxes for all rows.
      $form[$this->options['id']]['#tree'] = TRUE;
      foreach ($this->view->result as $row_index => $row) {
        $entity = $this->getEntityTranslation($this->getEntity($row), $row);

        $form[$this->options['id']][$row_index] = [
          '#type' => 'checkbox',
          // We are not able to determine a main "title" for each row, so we can
          // only output a generic label.
          '#title' => $this->t('Update this item'),
          '#title_display' => 'invisible',
          '#default_value' => !empty($form_state->getValue($this->options['id'])[$row_index]) ? 1 : NULL,
          '#return_value' => $this->calculateEntityBulkFormKey($entity, $use_revision),
        ];
      }

      // Ensure consistent container for filters/operations in the view header.
      $form['header'] = [
        '#type' => 'container',
        '#weight' => -100,
      ];

      // Build the bulk operations action widget for the header.
      // Allow themes to apply .container-inline on this separate container.
      $form['header'][$this->options['id']] = [];
      $this->actionService->buildForm($form['header'][$this->options['id']], $form_state);
      $form['actions'] = $form['header'][$this->options['id']]['actions'];
    }
    else {
      // Remove the default actions build array.
      unset($form['actions']);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function viewsFormValidate(&$form, FormStateInterface $form_state) {
    $this->actionService->validateForm($form, $form_state);
    parent::viewsFormValidate($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function viewsFormSubmit(&$form, FormStateInterface $form_state) {
    if ($form_state->get('step') != 'views_form_views_form') {
      return;
    }

    // Filter only selected checkboxes.
    $selected = array_filter($form_state->getValue($this->options['id']));
    $entities = [];
    foreach ($selected as $bulk_form_key) {
      $entities[$bulk_form_key] = $this->loadEntityFromBulkFormKey($bulk_form_key);
    }

    $this->actionService
      ->setSelectedEntities($entities)
      ->submitForm($form, $form_state);
    $action = $this->actionService->getSelectedAction();

    $operation_definition = $action->getPlugin()->getPluginDefinition();
    if (!empty($operation_definition['confirm_form_route_name'])) {
      $options = [
        'query' => $this->getDestinationArray(),
      ];
      $form_state->setRedirect($operation_definition['confirm_form_route_name'], [], $options);
    }
    else {
      $count = $this->actionService->getExecutedCount();
      if ($count) {
        /* @var ActionBase $actionPlugin */
        $actionPlugin = $action->getPlugin();
        if ($actionPlugin->canBeQueued()) {
          drupal_set_message($this->formatPlural($count, '%action was queued for @count item.', '%action was queued for @count items.', [
            '%action' => $action->label(),
          ]));
        }
        else {
          drupal_set_message($this->formatPlural($count, '%action was executed for @count item.', '%action was executed for @count items.', [
            '%action' => $action->label(),
          ]));
        }
      }
    }
  }

}
