<?php

namespace Drupal\berf\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\Plugin\Field\FieldFormatter\EntityReferenceEntityFormatter;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the 'better entity reference' field formatter.
 *
 * @FieldFormatter(
 *   id = "better_entity_reference_view",
 *   label = @Translation("Advanced Rendered Entity"),
 *   description = @Translation("Display a configured set of referenced entities using entity_view()."),
 *   field_types = {
 *     "entity_reference"
 *   }
 * )
 */
class BetterEntityReferenceFormatter extends EntityReferenceEntityFormatter {

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $plugin_id,
      $plugin_definition,
      $configuration['field_definition'],
      $configuration['settings'],
      $configuration['label'],
      $configuration['view_mode'],
      $configuration['third_party_settings'],
      $container->get('logger.factory'),
      $container->get('entity_type.manager'),
      $container->get('entity_display.repository')
    );
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return array(
      'selection_mode' => 'all',
      'amount' => 1,
      'offset' => 0,
      'reverse' => FALSE,
    ) + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $elements = parent::settingsForm($form, $form_state);
    $cardinality = $this->fieldDefinition->getFieldStorageDefinition()->getCardinality();

    $elements['selection_mode'] = [
      '#type' => 'select',
      '#options' => $this->getSelectionModes(),
      '#title' => t('Selection mode'),
      '#default_value' => $this->getSetting('selection_mode'),
      '#required' => TRUE,
    ];

    $show_advanced = [
      'visible' => [
        ':input[name="fields[' . $this->fieldDefinition->getName() . '][settings_edit_form][settings][selection_mode]"]' => [
          'value' => 'advanced',
        ],
      ],
    ];

    $elements['amount'] = [
      '#type' => 'number',
      '#step' => 1,
      '#min' => 1,
      '#title' => t('Amount of displayed entities'),
      '#default_value' => $this->getSetting('amount'),
      '#states' => $show_advanced,
    ];
    if ($cardinality > 0) {
      $elements['amount']['#max'] = $cardinality;
    }

    $elements['offset'] = [
      '#type' => 'number',
      '#step' => 1,
      '#min' => 0,
      '#title' => t('Offset'),
      '#default_value' => $this->getSetting('offset'),
      '#states' => $show_advanced,
      '#element_validate' => [[$this, 'validateOffset']],
    ];

    $elements['reverse'] = [
      '#type' => 'checkbox',
      '#title' => t('Reverse order'),
      '#desctiption' => t('Check this if you want to show the last added entities of the field. For example use amount 2 and "Reverse order" in order to display the last two entities in the field.'),
      '#default_value' => $this->getSetting('reverse'),
      '#states' => $show_advanced,
    ];

    return $elements;
  }

  /**
   * Validation callback for the offset element.
   *
   * @param array $element
   *   The form element to process.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function validateOffset(array &$element, FormStateInterface $form_state) {
    $cardinality = $this->fieldDefinition->getFieldStorageDefinition()->getCardinality();
    $field_settings = $form_state->getValues()['fields'][$form_state->getTriggeringElement()['#field_name']]['settings_edit_form']['settings'];
    $offset_maximum = $cardinality - $field_settings['amount'];
    // If cardinality of the field is limited, the offset has to be lower than
    // the field's cardinality minus the submitted amount value.
    if ($cardinality > 0 && $field_settings['offset'] > $offset_maximum) {
      $form_state->setError(
        $element,
        t(
          'The maximal offset for the submitted amount is @offset',
          ['@offset' => $offset_maximum]
        )
      );
    }
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = parent::settingsSummary();

    $summary[] = t(
      'Selection mode: @mode',
      ['@mode' => $this->getSelectionModes()[$this->getSetting('selection_mode')]]
    );
    if ($this->getSetting('selection_mode') == 'advanced') {
      $amount = $this->getSetting('amount') ? $this->getSetting('amount') : 1;
      $summary[] = \Drupal::translation()->formatPlural(
        $amount,
        $this->getSetting('reverse') ? 'Showing @amount entity starting at @offset in reverse order' : 'Showing @amount entity starting at @offset',
        $this->getSetting('reverse') ? 'Showing @amount entities starting at @offset in reverse order' : 'Showing @amount entities starting at @offset',
        [
          '@amount' => $amount,
          '@offset' => $this->getSetting('offset') ? $this->getSetting('offset') : 0,
        ]
      );
    }

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    switch ($this->getSetting('selection_mode')) {
      case 'advanced':
        $elements = $this->getAdvancedSelection(
          $items,
          $langcode,
          $this->getSetting('amount'),
          $this->getSetting('offset')
        );
        break;

      case 'first':
        $elements = $this->getAdvancedSelection(
          $items,
          $langcode,
          1,
          0
        );
        break;

      case 'last':
        $elements = $this->getAdvancedSelection(
          $items,
          $langcode,
          1,
          $items->count() - 1
        );
        break;

      default;
        $elements = parent::viewElements($items, $langcode);
        break;

    }

    return $elements;
  }

  /**
   * Gets the render array of entities considering formatter's advanced options.
   *
   * @param \Drupal\Core\Field\FieldItemListInterface $items
   *   The field values to be rendered.
   * @param string $langcode
   *   The language that should be used to render the field.
   * @param int $amount
   *   The amount of field items to show.
   * @param int $offset
   *   The offset to apply for displayed items.
   *
   * @return array
   *   A renderable array for $items, as an array of child elements keyed by
   *   consecutive numeric indexes starting from 0.
   */
  protected function getAdvancedSelection(FieldItemListInterface $items, $langcode, $amount, $offset) {
    $elements = [];
    $count = 0;
    $entities = $this->getEntitiesToView($items, $langcode);
    if ($this->getSetting('reverse')) {
      $entities = array_reverse($entities);
    }

    foreach ($entities as $delta => $entity) {

      // Show entities if offset was reached and amount limit isn't reached yet.
      if ($delta >= $offset && $count < $amount) {
        // Due to render caching and delayed calls, the viewElements() method
        // will be called later in the rendering process through a '#pre_render'
        // callback, so we need to generate a counter that takes into account
        // all the relevant information about this field and the referenced
        // entity that is being rendered.
        $recursive_render_id = $items->getFieldDefinition()
          ->getTargetEntityTypeId()
          . $items->getFieldDefinition()->getTargetBundle()
          . $items->getName()
          . $entity->id();

        if (isset(static::$recursiveRenderDepth[$recursive_render_id])) {
          static::$recursiveRenderDepth[$recursive_render_id]++;
        }
        else {
          static::$recursiveRenderDepth[$recursive_render_id] = 1;
        }

        // Protect ourselves from recursive rendering.
        if (static::$recursiveRenderDepth[$recursive_render_id] > static::RECURSIVE_RENDER_LIMIT) {
          $this->loggerFactory->get('entity')
            ->error('Recursive rendering detected when rendering entity %entity_type: %entity_id, using the %field_name field on the %bundle_name bundle. Aborting rendering.', [
              '%entity_type' => $entity->getEntityTypeId(),
              '%entity_id' => $entity->id(),
              '%field_name' => $items->getName(),
              '%bundle_name' => $items->getFieldDefinition()->getTargetBundle(),
            ]);
          return $elements;
        }

        $view_builder = $this->entityTypeManager->getViewBuilder($entity->getEntityTypeId());
        $elements[$delta] = $view_builder->view(
          $entity,
          $this->getSetting('view_mode'),
          $entity->language()->getId()
        );

        // Add a resource attribute to set the mapping property's value to the
        // entity's url. Since we don't know what the markup of the entity will
        // be, we shouldn't rely on it for structured data such as RDFa.
        if (!empty($items[$delta]->_attributes) && !$entity->isNew() && $entity->hasLinkTemplate('canonical')) {
          $items[$delta]->_attributes += array('resource' => $entity->toUrl()->toString());
        }

        $count++;
      }
    }

    if ($this->getSetting('reverse')) {
      $elements = array_reverse($elements);
    }

    return $elements;
  }

  /**
   * Get the formatter's selection mode options.
   *
   * @return array
   *   Array of available selection modes.
   */
  protected function getSelectionModes() {
    return [
      'all' => t('All'),
      'first' => t('First entity'),
      'last' => t('Last entity'),
      'advanced' => t('Advanced'),
    ];
  }

}
