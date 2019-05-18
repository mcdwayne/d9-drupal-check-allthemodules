<?php

namespace Drupal\multi_render_formatter\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\Plugin\Field\FieldFormatter\EntityReferenceEntityFormatter;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'entity_multi_render' formatter.
 *
 * @FieldFormatter(
 *   id = "entity_multi_render",
 *   label = @Translation("Entity Multi Render Mode"),
 *   description = @Translation("Display the referenced entities rendered based on a behavior field."),
 *   field_types = {
 *     "entity_reference"
 *   }
 * )
 */
class EntityMultiRender extends EntityReferenceEntityFormatter {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {

    $settings = [];
    $settings['behavior_field'] = '';
    $settings['view_modes'] = [];
    return $settings + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {

    // Get Current Field.
    $current_field_name = $this->fieldDefinition->getName();

    // Manages all field of bundle fields.
    $target_bundle = $form['#bundle'];
    $target_entity = $form['#entity_type'];

    // Get Compatible field list.
    $behavior_selectors = MultiFomatterHelper::getBehaviorFieldPossible($form['#fields'], $target_entity, $target_bundle, $current_field_name);

    // If no behaviors selector, print error message.
    if (count($behavior_selectors) == 0) {
      $form['item'] = [
        '#type' => 'fieldset',
      ];
      $form['item']['message'] = ['#markup' => t('No compatible behavior selector field detected (boolean or list). Please choose another formatter.')];
      return $form;
    }

    // Make Behavior field selector.
    $form['behavior_field'] = [
      '#type' => 'select',
      '#description' => $this->t('select'),
      '#title' => $this
        ->t('Choose the behavior selector field'),
      '#options' => $behavior_selectors,
      '#default_value' => $this->getSetting('behavior_field'),
    ];

    // If more than one possible behavior field, add AjaxCallback.
    if (count($behavior_selectors) > 1) {
      $form['behavior_field']['#ajax'] = [
        'wrapper' => 'view_mode_selectors',
        'callback' => [$this, 'ajaxCallback'],
      ];

      $form['view_modes'] = [
        '#prefix' => '<div id="view_mode_selectors">',
        '#suffix' => '</div>',
      ];
    }

    // Get Target Field.
    $target_field = NULL;
    if (count($behavior_selectors) == 1) {
      // If only one possible value, use it.
      $target_field = array_keys($behavior_selectors)[0];
    }
    else {

      // If more than One possible.
      $target_value = [
        'fields',
        $current_field_name,
        'settings_edit_form',
        'settings',
        'behavior_field',
      ];

      if ($form_state->getValue($target_value)) {
        // Listen Ajax.
        $target_field = $form_state->getValue($target_value);
      }
      else {
        // Search in settings.
        $target_field = $this->getSetting('behavior_field');
      }
    }

    // If a behavior field are selected.
    if ($target_field != NULL) {
      $target_bundle = $form['#bundle'];
      $target_entity = $form['#entity_type'];

      // Get list of possible behaviors.
      $values = MultiFomatterHelper::getBehaviorList($target_entity, $target_bundle, $target_field);
      if ($values != NULL) {

        $defaults = $this->getSetting('view_modes');
        // Get list of possible view modes.
        $list_options = $this->entityDisplayRepository->getViewModeOptions($this->getFieldSetting('target_type'));

        // For Each view, create a selectbox.
        foreach ($values as $key => $label) {
          $form['view_modes'][$key] = [
            '#type' => 'select',
            '#options' => $list_options,
            '#title' => t('View mode for %label behavior', ['%label' => $label]),
            '#default_value' => $defaults[$key] ?? 'default',
            '#required' => TRUE,
          ];
        }
      }
    }

    return $form;
  }

  /**
   * Use Ajax Callback for list of behaviors.
   *
   * @param array $form
   *   Form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   FormState.
   *
   * @return mixed
   *   Ajax output.
   */
  public function ajaxCallback(array &$form, FormStateInterface $form_state) {
    $field_name = $this->fieldDefinition->getItemDefinition()->getFieldDefinition()->getName();
    $element_to_return = 'view_modes';

    return $form['fields'][$field_name]['plugin']['settings_edit_form']['settings'][$element_to_return];
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];

    $configs = $this->getSettings();

    // Get basic data for summary.
    $current_field_name = $this->fieldDefinition->getName();
    $bundle = $this->fieldDefinition->get('bundle');
    $entity_type = $this->fieldDefinition->get('entity_type');

    // Get Compatible field list.
    $fields = \Drupal::entityManager()->getFieldDefinitions($entity_type, $bundle);
    $possible_fields = MultiFomatterHelper::getBehaviorFieldPossible(array_keys($fields), $entity_type, $bundle, $current_field_name);

    // If no compatible fields, print error message.
    if (count($possible_fields) == 0) {
      $summary[] = t('No compatible behavior selector field detected (boolean or list). Please choose another formatter.');
      return $summary;
    }
    elseif ($configs['behavior_field'] == '') {
      // If no selection, invite user to configure formatter.
      $summary[] = t('Choose a behavior selector.');
      return $summary;
    }

    // Make summary message.
    $summary[] = t('Behavior source field :') . ' ' . $configs['behavior_field'];
    $summary[] = '';
    $summary[] = t('List of view modes configured :');

    $list_options = $this->entityDisplayRepository->getViewModeOptions($this->getFieldSetting('target_type'));
    $list_behaviors = MultiFomatterHelper::getBehaviorList($entity_type, $bundle, $configs['behavior_field']);

    foreach ($configs['view_modes'] as $key => $value) {
      $mode = $list_options[$value];
      $behavior = $list_behaviors[$key];
      $summary[] = t('Use %mode view mode for %behavior behavior', ['%behavior' => $behavior, '%mode' => $mode]);
    }

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];
    $view_modes = $this->getSetting('view_modes');
    $behavior_field = $this->getSetting('behavior_field');

    $entity = $items->getEntity();
    if ($entity == NULL) {
      return;
    }

    $current_behavior_used = $entity->$behavior_field->value ?? NULL;
    if ($current_behavior_used == NULL) {
      return;
    }

    $view_mode = $view_modes[$current_behavior_used] ?? NULL;
    if ($view_mode == NULL) {
      return;
    }

    /*
     * For the end of the method, that is the parent code.
     */
    foreach ($this->getEntitiesToView($items, $langcode) as $delta => $entity) {
      // Due to render caching and delayed calls, the viewElements() method
      // will be called later in the rendering process through a '#pre_render'
      // callback, so we need to generate a counter that takes into account
      // all the relevant information about this field and the referenced
      // entity that is being rendered.
      $recursive_render_id = $items->getFieldDefinition()->getTargetEntityTypeId()
        . $items->getFieldDefinition()->getTargetBundle()
        . $items->getName()
        // We include the referencing entity, so we can render default images
        // without hitting recursive protections.
        . $items->getEntity()->id()
        . $entity->getEntityTypeId()
        . $entity->id();

      if (isset(static::$recursiveRenderDepth[$recursive_render_id])) {
        static::$recursiveRenderDepth[$recursive_render_id]++;
      }
      else {
        static::$recursiveRenderDepth[$recursive_render_id] = 1;
      }

      // Protect ourselves from recursive rendering.
      if (static::$recursiveRenderDepth[$recursive_render_id] > static::RECURSIVE_RENDER_LIMIT) {
        $this->loggerFactory->get('entity')->error('Recursive rendering detected when rendering entity %entity_type: %entity_id, using the %field_name field on the %bundle_name bundle. Aborting rendering.', [
          '%entity_type' => $entity->getEntityTypeId(),
          '%entity_id' => $entity->id(),
          '%field_name' => $items->getName(),
          '%bundle_name' => $items->getFieldDefinition()->getTargetBundle(),
        ]);
        return $elements;
      }

      $view_builder = $this->entityTypeManager->getViewBuilder($entity->getEntityTypeId());
      $elements[$delta] = $view_builder->view($entity, $view_mode, $entity->language()->getId());

      // Add a resource attribute to set the mapping property's value to the
      // entity's url. Since we don't know what the markup of the entity will
      // be, we shouldn't rely on it for structured data such as RDFa.
      if (!empty($items[$delta]->_attributes) && !$entity->isNew() && $entity->hasLinkTemplate('canonical')) {
        $items[$delta]->_attributes += ['resource' => $entity->toUrl()->toString()];
      }
    }

    return $elements;

  }

}
