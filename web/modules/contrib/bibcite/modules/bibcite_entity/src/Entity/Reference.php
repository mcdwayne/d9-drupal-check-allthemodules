<?php

namespace Drupal\bibcite_entity\Entity;

use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\user\UserInterface;

/**
 * Defines the Reference entity.
 *
 * @ingroup bibcite_entity
 *
 * @ContentEntityType(
 *   id = "bibcite_reference",
 *   label = @Translation("Reference"),
 *   bundle_label = @Translation("Reference type"),
 *   handlers = {
 *     "view_builder" = "Drupal\bibcite_entity\ReferenceViewBuilder",
 *     "list_builder" = "Drupal\bibcite_entity\ReferenceListBuilder",
 *     "views_data" = "Drupal\bibcite_entity\ReferenceViewsData",
 *
 *     "form" = {
 *       "default" = "Drupal\bibcite_entity\Form\ReferenceForm",
 *       "add" = "Drupal\bibcite_entity\Form\ReferenceForm",
 *       "edit" = "Drupal\bibcite_entity\Form\ReferenceForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "access" = "Drupal\bibcite_entity\ReferenceAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "bibcite_reference",
 *   admin_permission = "administer bibcite_reference",
 *   entity_keys = {
 *     "id" = "id",
 *     "bundle" = "type",
 *     "label" = "title",
 *     "uuid" = "uuid",
 *     "langcode" = "langcode",
 *     "uid" = "uid",
 *   },
 *   common_reference_target = TRUE,
 *   bundle_entity_type = "bibcite_reference_type",
 *   links = {
 *     "canonical" = "/bibcite/reference/{bibcite_reference}",
 *     "edit-form" = "/bibcite/reference/{bibcite_reference}/edit",
 *     "delete-form" = "/bibcite/reference/{bibcite_reference}/delete",
 *     "add-page" = "/bibcite/reference/add",
 *     "delete-multiple-form" = "/admin/content/bibcite/reference/delete",
 *     "collection" = "/admin/content/bibcite/reference",
 *   },
 *   field_ui_base_route = "entity.bibcite_reference_type.edit_form",
 * )
 */
class Reference extends ContentEntityBase implements ReferenceInterface {

  use EntityChangedTrait;

  /**
   * {@inheritdoc}
   */
  public function cite($style = NULL) {
    // @todo Make a better dependency injection.
    /** @var \Drupal\bibcite\CitationStylerInterface $styler */
    $styler = \Drupal::service('bibcite.citation_styler');

    if ($style) {
      $styler->setStyleById($style);
    }

    $serializer = \Drupal::service('serializer');

    $data = $serializer->normalize($this, 'csl');
    return $styler->render($data);
  }

  /**
   * {@inheritdoc}
   */
  public function getCreatedTime() {
    return $this->get('created')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setCreatedTime($timestamp) {
    $this->set('created', $timestamp);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getOwner() {
    return $this->get('uid')->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function getOwnerId() {
    return $this->getEntityKey('uid');
  }

  /**
   * {@inheritdoc}
   */
  public function setOwnerId($uid) {
    $this->set('uid', $uid);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setOwner(UserInterface $account) {
    $this->set('uid', $account->id());
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    /*
     * Main attributes.
     */

    $fields['title'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Title'))
      ->setDescription(t('The title of the Reference.'))
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => 2,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setSettings([
        'text_processing' => 0,
      ])
      ->setDefaultValue('');

    $fields['uid'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Authored by'))
      ->setDescription(t('The username of the content author.'))
      ->setSetting('target_type', 'user')
      ->setDefaultValueCallback('Drupal\bibcite_entity\Entity\Reference::getCurrentUserId')
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'weight' => 100,
        'settings' => [
          'match_operator' => 'CONTAINS',
          'size' => '60',
          'placeholder' => '',
        ],
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['author'] = BaseFieldDefinition::create('bibcite_contributor')
      ->setLabel(t('Author'))
      ->setCardinality(FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED)
      ->setDisplayOptions('form', [
        'type' => 'bibcite_contributor_widget',
        'weight' => 3,
      ])
      ->setDisplayOptions('view', [
        'type' => 'bibcite_contributor_label',
        'weight' => 3,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['keywords'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Keywords'))
      ->setSetting('target_type', 'bibcite_keyword')
      ->setCardinality(FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED)
      ->setDisplayOptions('view', [
        'type' => 'entity_reference_label',
        'weight' => 4,
      ])
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete_tags',
        'weight' => 4,
        'settings' => [
          'match_operator' => 'CONTAINS',
          'size' => '60',
          'placeholder' => '',
        ],
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE)
      ->setSettings([
        'handler' => 'default:bibcite_keyword',
        'handler_settings' => [
          'target_bundles' => ['bibcite_keyword'],
          'auto_create' => TRUE,
        ],
      ]);

    /*
     * CSL fields.
     */

    $weight = 5;

    $default_string = function ($label, $hint = '') use (&$weight) {
      $weight++;
      return BaseFieldDefinition::create('string')
        ->setLabel($label)
        ->setDescription($hint)
        ->setDisplayOptions('view', [
          'label' => 'above',
          'type' => 'string',
          'weight' => $weight,
        ])
        ->setDisplayOptions('form', [
          'type' => 'string_textfield',
          'weight' => $weight,
        ])
        ->setDisplayConfigurable('form', TRUE)
        ->setDisplayConfigurable('view', TRUE)
        ->setDefaultValue('');
    };

    $default_integer = function ($label, $hint = '') use (&$weight) {
      $weight++;
      return BaseFieldDefinition::create('integer')
        ->setLabel($label)
        ->setDescription($hint)
        ->setDisplayOptions('view', [
          'type' => 'number_integer',
          'weight' => $weight,
        ])
        ->setDisplayOptions('form', [
          'type' => 'number',
          'weight' => $weight,
        ])
        ->setDisplayConfigurable('form', TRUE)
        ->setDisplayConfigurable('view', TRUE)
        ->setDefaultValue(NULL);
    };

    $default_string_long = function ($label, $rows = 1, $hint = '') use (&$weight) {
      $weight++;
      return BaseFieldDefinition::create('string_long')
        ->setLabel($label)
        ->setDescription($hint)
        ->setDisplayOptions('view', [
          'type' => 'text_default',
          'weight' => $weight,
        ])
        ->setDisplayOptions('form', [
          'type' => 'string_textarea',
          'settings' => [
            'rows' => $rows,
          ],
          'weight' => $weight,
        ])
        ->setDisplayConfigurable('form', TRUE)
        ->setDisplayConfigurable('view', TRUE);
    };

    /*
     * Text fields.
     */
    $fields['bibcite_abst_e'] = $default_string_long(t('Abstract'), 4);
    $fields['bibcite_abst_f'] = $default_string_long(t('French Abstract'), 4);
    $fields['bibcite_notes'] = $default_string_long(t('Notes'), 4);
    $fields['bibcite_custom1'] = $default_string_long(t('Custom 1'));
    $fields['bibcite_custom2'] = $default_string_long(t('Custom 2'));
    $fields['bibcite_custom3'] = $default_string_long(t('Custom 3'));
    $fields['bibcite_custom4'] = $default_string_long(t('Custom 4'));
    $fields['bibcite_custom5'] = $default_string_long(t('Custom 5'));
    $fields['bibcite_custom6'] = $default_string_long(t('Custom 6'));
    $fields['bibcite_custom7'] = $default_string_long(t('Custom 7'));
    $fields['bibcite_auth_address'] = $default_string_long(t('Author Address'));

    /*
     * Number fields.
     */
    $fields['bibcite_year'] = $default_integer(t('Year of Publication'), t('Format: yyyy'));

    /*
     * String fields.
     */
    $fields['bibcite_secondary_title'] = $default_string(t('Secondary Title'));
    $fields['bibcite_volume'] = $default_string(t('Volume'));
    $fields['bibcite_edition'] = $default_string(t('Edition'));
    $fields['bibcite_section'] = $default_string(t('Section'));
    $fields['bibcite_issue'] = $default_string(t('Issue'));
    $fields['bibcite_number_of_volumes'] = $default_string(t('Number of Volumes'));
    $fields['bibcite_number'] = $default_string(t('Number'));
    $fields['bibcite_pages'] = $default_string(t('Number of Pages'));
    $fields['bibcite_date'] = $default_string(t('Date Published'), t('Format: mm/yyyy'));
    $fields['bibcite_type_of_work'] = $default_string(t('Type of Work'), t('Masters Thesis'));
    $fields['bibcite_lang'] = $default_string(t('Publication Language'));
    $fields['bibcite_reprint_edition'] = $default_string(t('Reprint Edition'));
    $fields['bibcite_publisher'] = $default_string(t('Publisher'));
    $fields['bibcite_place_published'] = $default_string(t('Place Published'));
    $fields['bibcite_issn'] = $default_string(t('ISSN Number'));
    $fields['bibcite_isbn'] = $default_string(t('ISBN Number'));
    $fields['bibcite_accession_number'] = $default_string(t('Accession Number'));
    $fields['bibcite_call_number'] = $default_string(t('Call Number'));
    $fields['bibcite_other_number'] = $default_string(t('Other Numbers'));
    $fields['bibcite_citekey'] = $default_string(t('Citation Key'));
    $fields['bibcite_url'] = $default_string(t('URL'));
    $fields['bibcite_doi'] = $default_string(t('DOI'));
    $fields['bibcite_research_notes'] = $default_string(t('Research Notes'));
    $fields['bibcite_tertiary_title'] = $default_string(t('Tertiary Title'));
    $fields['bibcite_short_title'] = $default_string(t('Short Title'));
    $fields['bibcite_alternate_title'] = $default_string(t('Alternate Title'));
    $fields['bibcite_translated_title'] = $default_string(t('Translated Title'));
    $fields['bibcite_original_publication'] = $default_string(t('Original Publication'));
    $fields['bibcite_other_author_affiliations'] = $default_string(t('Other Author Affiliations'));
    $fields['bibcite_remote_db_name'] = $default_string(t('Remote Database Name'));
    $fields['bibcite_remote_db_provider'] = $default_string(t('Remote Database Provider'));
    $fields['bibcite_label'] = $default_string(t('Label'));
    $fields['bibcite_access_date'] = $default_string(t('Access Date'));
    $fields['bibcite_refereed'] = $default_string(t('Refereed Designation'));

    $fields['bibcite_pmid'] = $default_string(t('PMID'));

    /*
     * Entity dates.
     */

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDescription(t('The time that the entity was created.'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('The time that the entity was last edited.'));

    return $fields;
  }

  /**
   * Default value callback for 'uid' base field definition.
   *
   * @see ::baseFieldDefinitions()
   *
   * @return array
   *   An array of default values.
   */
  public static function getCurrentUserId() {
    return [\Drupal::currentUser()->id()];
  }

}
