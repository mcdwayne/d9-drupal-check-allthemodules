<?php

namespace Drupal\entity_reference_crud_display\Plugin\Field\FieldFormatter;

use Drupal\Component\Serialization\Json;
use Drupal\Component\Utility\Html;
use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\Plugin\Field\FieldFormatter\EntityReferenceEntityFormatter;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Routing\RedirectDestinationTrait;
use Drupal\Core\Url;

/**
 * Plugin implementation of the 'entity_reference_crud_display_formatter'
 *
 * @FieldFormatter(
 *   id = "entity_reference_crud_display_formatter",
 *   label = @Translation("Entiy Reference CRUD Display"),
 *   description = @Translation("Display links on the referenced entities that when clicked will call create, update and delete via AJAX."),
 *   field_types = {
 *     "entity_reference"
 *   }
 * )
 */
class EntityReferenceCrudDisplayFormatter extends EntityReferenceEntityFormatter {

  use RedirectDestinationTrait;

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'link' => TRUE,
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $elements['view_mode'] = [
      '#type' => 'select',
      '#options' => $this->entityDisplayRepository->getViewModeOptions($this->getFieldSetting('target_type')),
      '#title' => t('View mode'),
      '#default_value' => $this->getSetting('view_mode'),
      '#required' => TRUE,
    ];

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];

    $view_modes = $this->entityDisplayRepository->getViewModeOptions($this->getFieldSetting('target_type'));
    $view_mode = $this->getSetting('view_mode');
    $summary[] = t('Rendered as @mode', ['@mode' => isset($view_modes[$view_mode]) ? $view_modes[$view_mode] : $view_mode]);

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $view_mode = $this->getSetting('view_mode');
    $elements = [];
    $field_name = $items->getName();


    // Getting entity target type.
    $target_type = $items->getFieldDefinition()
    ->getSetting('target_type');

    // Getting the bundle of the entity target.
    $target_bundle = $items->getFieldDefinition()
    ->getSetting('handler_settings');

    if (is_array($target_bundle) && isset($target_bundle['target_bundles'])) {
      $target_bundle = $target_bundle['target_bundles'];
      reset($target_bundle);
      $target_bundle = key($target_bundle);
    }
    // If there is no bundle in the entity, put 0.
    else {
      $target_bundle = '0';
    }

    if ($target_bundle == '0') {
      $entity_target = \Drupal::entityTypeManager()
      ->getStorage($target_type)
      ->create([]);
    }
    else {
      $entity_target = \Drupal::entityTypeManager()
      ->getStorage($target_type)
      ->create(['type' => $target_bundle]);
    }

    $parent_entity = $items->getEntity();
    $parent_entity_id = $parent_entity->id();
    $parent_entity_type = $parent_entity->getEntityTypeId();

    // Link 'New' to ajax create form.
    // Only show the 'create' link for users that have access to update parent
    // entity.
    if ($parent_entity->access('update') && $entity_target && $entity_target->access('create')) {
      // Creating a link 'New', for add new entity.
      $options_create = [
        'method' => 'ajax',
        'entity_target_type' => $target_type,
        'entity_target_bundle' => $target_bundle,
        'entity_parent_type' => $parent_entity_type,
        'entity_parent' => $parent_entity_id,
        'view_mode' => $view_mode,
        'field_name' => $field_name,
      ];

      // Create ajax url from from create.
      $uri = Url::fromRoute('entity_reference_crud_display.new_entity_form', $options_create);

      $cardinality = $parent_entity->getFieldDefinition($field_name)->getFieldStorageDefinition()->getCardinality();
      if ($cardinality == -1 || count($items) < $cardinality) {
        $elements[-3] = [
          '#type' => 'link',
          '#title' => t('New'),
          '#url' => $uri,
          '#options' => $uri->getOptions() + [
            'attributes' => [
              'id' => [
                $field_name . '-' . $target_type . '-link-new',
              ],
              'class' => [
                'field--type-entity-reference__new',
                'use-ajax',
              ],
            ],
          ],
        ];
      }
      else {
        $elements[-3] = [
          '#type' => 'container',
          '#attributes' => [
            'id' => [
              $field_name . '-' . $target_type . '-link-new',
            ],
          ],
        ];
      }

      // Now we add the container to entity form create.
      $elements[-2]['field_create_container'] = [
        '#type' => 'container',
        '#attributes' => [
          'id' => "ajax-create-entity-$target_type",
        ],
      ];
    }

    // Show each entity with a link 'Edit'.
    foreach ($this->getEntitiesToView($items, $langcode) as $delta => $entity) {
      $view_builder = $this->entityTypeManager->getViewBuilder($entity->getEntityTypeId());

      // Now we add the container to render after the links. This is where the
      // AJAX loaded content will be injected in to.
      $elements[$delta]['field_container'] = [
        '#type' => 'container',
        '#attributes' => [
          'id' => "entity-reference-crud-$target_type-$delta",
          'class' => 'entity-reference-crud-item',
        ],
      ];

      $elements[$delta]['field_container'][] = $view_builder->view($entity, $view_mode, $entity->language()->getId());

      // Set up the options for our route, we default the method to 'nojs'
      // since the drupal ajax library will replace that for us.
      $options = [
        'method' => 'ajax',
        'entity_target_type' => $entity->getEntityTypeId(),
        'entity_target' => $entity->id(),
        'entity_target_bundle' => $target_bundle,
        'entity_parent_type' => $parent_entity_type,
        'entity_parent' => $parent_entity_id,
        'view_mode' => $view_mode,
        'delta' => $delta,
        'field_name' => $field_name,
      ];

      // Now we create the path from our route, passing the options it needs.
      $uri_edit = Url::fromRoute('entity_reference_crud_display.edit_entity_form', $options);
      $uri_delete = Url::fromRoute('entity_reference_crud_display.delete_confirm', $options);

      // And create a link element. We need to add the 'use-ajax' class so
      // that.
      // Drupal's core AJAX library will detect this link and ajaxify it.
      // Only show the 'edit' link for users that have access to update parent
      // entity and update target entity.
      if ($parent_entity->access('update') && $entity->access('update')) {
        $elements[$delta]['field_container'][] = [
          '#type' => 'link',
          '#title' => t('Edit'),
          '#url' => $uri_edit,
          '#options' => $uri_edit->getOptions() + [
            'attributes' => [
              'class' => [
                'use-ajax',
                'edit-button',
              ],
            ],
          ],
        ];
      }

      // Only show the 'delete' link for users that have access to update parent
      // entity and delete target entity.
      if ($parent_entity->access('update') && $entity->access('delete')) {
        $elements[$delta]['field_container'][] = [
          '#type' => 'link',
          '#title' => t('Delete'),
          '#url' => $uri_delete,
          '#options' => $uri_delete->getOptions() + [
            'attributes' => [
              'class' => [
                'use-ajax',
                'delete-button',
              ],
              'data-dialog-type' => 'modal',
              'data-dialog-options' => Json::encode([
                'width' => 700,
              ]),
            ],
          ],
          '#attached' => ['library' => ['core/drupal.dialog.ajax']],
        ];
      }
    }

    // Attach CSS and JS dependencies.
    $elements['#attached']['library'][] = 'entity_reference_crud_display/entity_view';

    return $elements;
  }

  /**
   * Generate the output appropriate for one field item.
   *
   * @param \Drupal\Core\Field\FieldItemInterface $item
   *   One field item.
   *
   * @return string
   *   The textual output generated.
   */
  protected function viewValue(FieldItemInterface $item) {
    // The text value has no text format assigned to it, so the user input
    // should equal the output, including newlines.
    return nl2br(Html::escape($item->value));
  }

}
