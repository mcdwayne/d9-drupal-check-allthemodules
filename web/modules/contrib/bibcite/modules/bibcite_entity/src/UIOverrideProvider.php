<?php

namespace Drupal\bibcite_entity;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Render\Element;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Collection of hardcoded overrides for reference form and view.
 */
class UIOverrideProvider {

  use StringTranslationTrait;

  /**
   * Reference type storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $typeStorage;

  /**
   * Service configuration.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $config;

  /**
   * Construct new UIOverrideProvider object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   Entity type manager service.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   Config factory service.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, ConfigFactoryInterface $config_factory) {
    $this->typeStorage = $entity_type_manager->getStorage('bibcite_reference_type');
    $this->config = $config_factory->get('bibcite_entity.reference.settings');
  }

  /**
   * Override elements attributes based on bundle configuration.
   *
   * @param array $element
   *   Element render array.
   * @param string $bundle_id
   *   Entity bundle identifier.
   */
  public function referenceFormFieldsOverride(array &$element, $bundle_id) {
    /** @var \Drupal\bibcite_entity\Entity\ReferenceTypeInterface $bundle */
    if (($bundle = $this->typeStorage->load($bundle_id)) && $bundle->isRequiredOverride()) {
      foreach ($bundle->getFields() as $field_name => $field_config) {
        if (isset($element[$field_name])) {
          if (!$field_config['visible']) {
            $element[$field_name]['#access'] = FALSE;
          }

          if (!empty($field_config['label'])) {
            $this->setFormElementParameter($element[$field_name], '#title', $field_config['label']);
          }

          if (!empty($field_config['hint'])) {
            $this->setFormElementParameter($element[$field_name], '#description', $field_config['hint']);
          }

          if ($field_config['required']) {
            $this->setFormElementParameter($element[$field_name], '#required', $field_config['required']);
          }
        }
      }
    }
  }

  /**
   * Override fields attributes for reference view.
   *
   * @param array $element
   *   Element render array.
   * @param string $bundle_id
   *   Entity bundle identifier.
   */
  public function referenceViewFieldsOverride(array &$element, $bundle_id) {
    /** @var \Drupal\bibcite_entity\Entity\ReferenceTypeInterface $bundle */
    if (($bundle = $this->typeStorage->load($bundle_id)) && $bundle->isRequiredOverride()) {
      foreach ($bundle->getFields() as $field_name => $field_config) {
        if (isset($element[$field_name])) {
          if (!$field_config['visible']) {
            $element[$field_name]['#access'] = FALSE;
          }

          if (!empty($field_config['label'])) {
            $element[$field_name]['#title'] = $field_config['label'];
          }
        }
      }
    }
  }

  /**
   * Override fields attributes for reference display form.
   *
   * @param array $element
   *   Element render array.
   * @param string $bundle_id
   *   Entity bundle identifier.
   */
  public function referenceDisplayFormFieldsOverride(array &$element, $bundle_id) {
    /** @var \Drupal\bibcite_entity\Entity\ReferenceTypeInterface $bundle */
    if (($bundle = $this->typeStorage->load($bundle_id)) && $bundle->isRequiredOverride()) {
      foreach ($bundle->getFields() as $field_name => $field_config) {
        if (isset($element['fields'][$field_name])) {
          if (!empty($field_config['label'])) {
            $element['fields'][$field_name]['human_name']['#plain_text'] = $field_config['label'];
          }
        }
      }
    }
  }

  /**
   * Restructure form elements to the vertical tabs view.
   *
   * @param array $element
   *   Element render array.
   */
  public function referenceFormTabsRestructure(array &$element) {
    if ($this->config->get('ui_override.enable_form_override')) {
      $field_groups = $this->getGroupedFields();

      // Place tabs under the title.
      $weight = $element['title']['#weight'];

      $element['tabs'] = [
        '#type' => 'vertical_tabs',
        '#weight' => ++$weight,
      ];

      foreach ($field_groups as $group_id => $group) {
        foreach ($group['elements'] as $field_id) {
          if (isset($element[$field_id]) && $element[$field_id]['#access']) {
            if (!isset($element[$group_id])) {
              $element[$group_id] = [
                '#type' => 'details',
                '#title' => $group['title'],
                '#group' => 'tabs',
              ];
            }

            $element[$field_id]['#group'] = $group_id;
          }
        }
      }
    }
  }

  /**
   * Restructure form elements to the details view.
   *
   * @param array $element
   *   Element render array.
   */
  public function referenceFormDetailsRestructure(array &$element) {
    if ($this->config->get('ui_override.enable_form_override')) {
      $field_groups = $this->getGroupedFields();

      // Place all details elements under the title.
      $weight = $element['title']['#weight'];

      foreach ($field_groups as $group_id => $group) {
        foreach ($group['elements'] as $field_id) {
          if (isset($element[$field_id]) && $element[$field_id]['#access']) {
            if (!isset($element[$group_id])) {
              $element[$group_id] = [
                '#type' => 'details',
                '#title' => $group['title'],
                '#weight' => ++$weight,
              ];
            }

            $element[$group_id][$field_id] = $element[$field_id];

            unset($element[$field_id]);
          }
        }
      }
    }
  }

  /**
   * Get array of grouped fields.
   */
  protected function getGroupedFields() {
    return [
      'authors' => [
        'title' => $this->t('Authors'),
        'elements' => [
          'author',
        ],
      ],
      'abstract' => [
        'title' => $this->t('Abstract'),
        'elements' => [
          'bibcite_abst_e',
        ],
      ],
      'publication' => [
        'title' => $this->t('Publication'),
        'elements' => [
          'bibcite_year',
          'bibcite_secondary_title',
          'bibcite_volume',
          'bibcite_edition',
          'bibcite_section',
          'bibcite_issue',
          'bibcite_number_of_volumes',
          'bibcite_number',
          'bibcite_pages',
          'bibcite_date',
          'bibcite_type_of_work',
          'bibcite_lang',
          'bibcite_reprint_edition',
        ],
      ],
      'publisher' => [
        'title' => $this->t('Publisher'),
        'elements' => [
          'bibcite_publisher',
          'bibcite_place_published',
        ],
      ],
      'identifiers' => [
        'title' => $this->t('Identifiers'),
        'elements' => [
          'bibcite_issn',
          'bibcite_isbn',
          'bibcite_accession_number',
          'bibcite_call_number',
          'bibcite_other_number',
          'bibcite_citekey',
          'bibcite_pmid',
        ],
      ],
      'locators' => [
        'title' => $this->t('Locators'),
        'elements' => [
          'bibcite_url',
          'bibcite_doi',
        ],
      ],
      'notes' => [
        'title' => $this->t('Notes'),
        'elements' => [
          'bibcite_notes',
          'bibcite_research_notes',
        ],
      ],
      'alternate_titles' => [
        'title' => $this->t('Alternative titles'),
        'elements' => [
          'bibcite_tertiary_title',
          'bibcite_short_title',
          'bibcite_alternate_title',
          'bibcite_translated_title',
          'bibcite_original_publication',
        ],
      ],
      'other' => [
        'title' => $this->t('Other'),
        'elements' => [
          'keywords',
          'bibcite_other_author_affiliations',
          'bibcite_abst_f',
          'bibcite_custom1',
          'bibcite_custom2',
          'bibcite_custom3',
          'bibcite_custom4',
          'bibcite_custom5',
          'bibcite_custom6',
          'bibcite_custom7',
          'bibcite_remote_db_name',
          'bibcite_remote_db_provider',
          'bibcite_auth_address',
          'bibcite_label',
          'bibcite_access_date',
          'bibcite_refereed',
        ],
      ],
    ];
  }

  /**
   * Set a value of attribute for field element.
   *
   * @param array $element
   *   Field element array.
   * @param string $attribute
   *   Attribute name.
   * @param mixed $value
   *   Attribute value.
   */
  protected function setFormElementParameter(array &$element, $attribute, $value) {
    if (isset($element['widget']['target_id'])) {
      $element['widget']['target_id'][$attribute] = $value;
    }
    else {
      foreach (Element::children($element['widget']) as $element_value_key) {
        $element['widget'][$element_value_key]['value'][$attribute] = $value;
      }
    }
  }

}
