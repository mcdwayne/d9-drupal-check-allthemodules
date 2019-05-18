<?php

namespace Drupal\select2boxes\Plugin\Field\FieldWidget;

use Drupal\Component\Utility\Crypt;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\Plugin\Field\FieldWidget\OptionsSelectWidget;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Site\Settings;
use Drupal\Core\Url;
use Drupal\select2boxes\AutoCreationProcessTrait;
use Drupal\select2boxes\EntityCreationTrait;
use Drupal\select2boxes\FlatteningOptionsTrait;
use Drupal\select2boxes\PreloadBuildTrait;

/**
 * Plugin implementation of the 'entity_reference autocomplete-tags' widget.
 *
 * @FieldWidget(
 *   id = "select2boxes_autocomplete_multi",
 *   label = @Translation("Select2 boxes (Multiple values)"),
 *   description = @Translation("An autocomplete entity reference field using AJAX."),
 *   field_types = {
 *     "entity_reference",
 *   },
 *   multiple_values = TRUE
 * )
 */
class MultiSelect2BoxesAutocompleteWidget extends OptionsSelectWidget {
  use PreloadBuildTrait;
  use FlatteningOptionsTrait;
  use AutoCreationProcessTrait;

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element = parent::formElement($items, $delta, $element, $form, $form_state);

    $this->flatteningOptions($element['#options']);

    $field_name = $this->fieldDefinition->getName();
    $element['#attributes'] = [
      // Disable core autocomplete.
      'data-jquery-once-autocomplete' => 'true',
      'data-select2-multiple'         => 'true',
      'data-autocomplete-path'        => $this->getAutocompletePath(),
      'class'                         => ['select2-widget', 'select2-boxes-widget'],
      'data-field-name'               => $field_name,
    ];

    // Pass an additional data attribute
    // to let select2 JS know whether it should handle input
    // for auto-create or not.
    $settings = $this->getFieldSettings();
    if (isset($settings['handler_settings']['auto_create']) && $settings['handler_settings']['auto_create'] == TRUE) {
      $element['#attributes']['data-auto-create-entity'] = 'enabled';
    }

    // Process the auto-creation when the input data is being gathered.
    $element['#select2'] = [
      'fieldName' => $field_name,
    ] + $settings;
    $element['#value_callback'] = [get_class($this), 'processAutoCreation'];

    // Attach library.
    $element['#attached']['library'][] = 'select2boxes/widget';

    // Get third party settings.
    $settings = $this->getThirdPartySettings('select2boxes');
    if (isset($settings['enable_preload']) && $settings['enable_preload'] == '1') {
      $this->attachPreload(
        $element['#attached'],
        $settings['preload_count'],
        $this->fieldDefinition
      );
    }

    $element['#needs_validation'] = FALSE;
    $element['#multiple'] = $element['#validated'] = TRUE;
    $element['#attached']['drupalSettings']['initValues'][$field_name] = $this->buildInitValues($items);
    $element['#options'] = array_intersect_key($element['#options'], array_flip($this->getSelectedOptions($items)));

    return $element;
  }

  /**
   * Build initial values.
   *
   * @param \Drupal\Core\Field\FieldItemListInterface $items
   *   Field items object.
   *
   * @return array
   *   Initial values array.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  protected function buildInitValues(FieldItemListInterface $items) {
    // Prepare required keys, from the entity type definitions.
    $target_type = $this->getFieldSetting('target_type');
    $definition  = \Drupal::entityTypeManager()->getDefinition($target_type);
    $id_key      = $definition->getKey('id');
    $label_key   = $definition->getKey('label');
    // Workaround for User entity type since it doesn't have label entity key.
    if ($target_type == 'user') {
      $label_key = 'name';
    }
    $ids = [];
    $data_table = $definition->getDataTable();
    /** @var \Drupal\Core\Field\EntityReferenceFieldItemList $items */
    /** @var \Drupal\Core\Field\Plugin\Field\FieldType\EntityReferenceItem $item */
    foreach ($items->getIterator() as $item) {
      $value = $item->getValue();
      if (!empty($value) && isset($value['target_id'])) {
        $ids[] = $item->getValue()['target_id'];
      }
    }
    if (empty($ids)) {
      return [];
    }
    $select = \Drupal::database()->select($data_table, 'init');
    $select->fields('init', [$id_key, $label_key]);
    $select->condition($id_key, $ids, 'IN');
    $entities = $select->execute()->fetchAllKeyed();
    // Additional fix for User entity - Anonymous users
    // has no value for "name" column in the database, so we attach it manually.
    if ($target_type == 'user' && !empty($entities) && isset($entities[0])) {
      $entities[0] = $this->t('Anonymous');
    }
    return !empty($entities) ? $entities : [];
  }

  /**
   * Get entity autocomplete path.
   *
   * @return \Drupal\Core\GeneratedUrl|string
   *   Entity autocomplete path.
   */
  protected function getAutocompletePath() {
    // Store the selection settings in the key/value store and pass a hashed key
    // in the route parameters.
    $selection_settings = $this->getFieldSetting('handler_settings');
    $data = serialize($selection_settings)
      . $this->getFieldSetting('target_type')
      . $this->getFieldSetting('handler');
    $selection_settings_key = Crypt::hmacBase64($data, Settings::getHashSalt());

    $key_value_storage = \Drupal::keyValue('entity_autocomplete');
    if (!$key_value_storage->has($selection_settings_key)) {
      $key_value_storage->set($selection_settings_key, $selection_settings);
    }

    $params = [
      'target_type'            => $this->getFieldSetting('target_type'),
      'selection_handler'      => $this->getFieldSetting('handler'),
      'selection_settings_key' => $selection_settings_key,
    ];

    return Url::fromRoute('system.entity_autocomplete', $params)->toString();
  }

  /**
   * Attach preloaded data.
   *
   * @param array &$attached
   *   Attached form element.
   * @param int $count
   *   Number of entries will be preloaded.
   * @param \Drupal\Core\Field\FieldDefinitionInterface $fieldDefinition
   *   The field definition.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  protected function attachPreload(array &$attached, $count, FieldDefinitionInterface $fieldDefinition) {
    $attached['drupalSettings']['preloaded_entries'][$fieldDefinition->getName()]
      = $this->buildPreLoaded($count, $fieldDefinition);
  }

}
