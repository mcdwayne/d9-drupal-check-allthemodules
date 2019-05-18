<?php

namespace Drupal\entity_embed_extras\Plugin\entity_embed_extras\DialogEntityDisplay;

use Drupal\Core\Form\FormStateInterface;
use Drupal\entity_embed_extras\DialogEntityDisplay\DialogEntityDisplayBase;
use Drupal\views\Views;
use Drupal\views\Entity\View as ViewEntity;
use Drupal\Core\Entity\EntityInterface;

/**
 * Displays current selection using a view.
 *
 * @DialogEntityDisplay(
 *   id = "view",
 *   label = @Translation("View"),
 *   description = @Translation("Use a pre-configured view to view the selected entity.")
 * )
 */
class View extends DialogEntityDisplayBase {

  /**
   * {@inheritdoc}
   */
  public function isConfigurable() {
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'view' => NULL,
      'view_display' => NULL,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormElement(EntityInterface $entity, array &$original_form, FormStateInterface $form_state) {

    $args = [$entity->id()];

    $view = Views::getView($this->configuration['view']);
    if (is_object($view)) {
      $view->setArguments($args);
      $view->setDisplay($this->configuration['view_display']);
      $view->preExecute();
      $view->execute();
      return $view->buildRenderable($this->configuration['view_display'], $args);
    }
    else {
      return [
        '#markup' => $entity->label(),
      ];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $subform = [];

    $options = [];

    /** @var \Drupal\entity_embed\Plugin\EmbedType\Entity $entityEmbed */
    $entityEmbed = $form_state->getFormObject()->getEntity();

    $entityTypeId = $entityEmbed->get('type_settings')['entity_type'];

    // Get all views displays.
    $views = $this->entityTypeManager->getStorage('view')->loadMultiple();
    foreach ($views as $view_id => $view) {
      foreach ($view->get('display') as $display_id => $display) {

        // Do not display views displays that do not accept an argument.
        if (empty($display['display_options']['arguments'])) {
          continue;
        }

        // Do not display views displays for other entity types.
        $firstArg = reset($display['display_options']['arguments']);
        if (empty($firstArg['entity_type']) || $firstArg['entity_type'] != $entityTypeId) {
          continue;
        }

        $options[$view_id . '.' . $display_id] = $this->t('@view : @display', ['@view' => $view->label(), '@display' => $display['display_title']]);
      }
    }

    if (!empty($this->configuration['view']) && !empty($this->configuration['view_display'])) {
      $default_value = $this->configuration['view'] . '.' . $this->configuration['view_display'];
    }
    else {
      $default_value = NULL;
    }

    $subform['view'] = [
      '#type' => 'select',
      '#title' => $this->t('View'),
      '#default_value' => $default_value,
      '#options' => $options,
      '#required' => TRUE,
      '#description' => $this->t('Select the View and View Display with which to display the selected entity in the embed dialog.'),
    ];

    if (empty($options)) {
      $subform['view']['#options'] = [
        '_none' => $this->t('None'),
      ];

      $subform['view']['#description'] = $this->t('No appropriate views are configured.  Please create a view that displays one item of this entity type and accepts the entity id as the first argument.');

      $subform['view']['#default_value'] = '_none';
    }

    return $subform;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();

    if (!empty($values['view'])) {
      if ($values['view'] == '_none') {
        $this->configuration['view'] = '_none';
        $this->configuration['view_display'] = '_none';
      }
      else {
        list($view_id, $display_id) = explode('.', $values['view']);
        $this->configuration['view'] = $view_id;
        $this->configuration['view_display'] = $display_id;
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    $dependencies = [];
    if (!empty($this->configuration['view']) && $this->configuration['view'] != '_none') {
      $view = ViewEntity::load($this->configuration['view']);
      if ($view) {
        $dependencies[$view->getConfigDependencyKey()] = [$view->getConfigDependencyName()];
      }
    }

    return $dependencies;
  }

}
