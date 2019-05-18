<?php

namespace Drupal\pagedesigner\Entity;

use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\RevisionableContentEntityBase;
use Drupal\Core\Entity\RevisionableInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\user\UserInterface;

/**
 * Defines the Pagedesigner Element entity.
 *
 * @ingroup pagedesigner
 *
 * @ContentEntityType(
 *   id = "pagedesigner_element",
 *   label = @Translation("Pagedesigner Element"),
 *   bundle_label = @Translation("Pagedesigner type"),
 *   handlers = {
 *     "storage" = "Drupal\pagedesigner\ElementStorage",
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\pagedesigner\ElementListBuilder",
 *     "views_data" = "Drupal\pagedesigner\Entity\ElementViewsData",
 *     "translation" = "Drupal\pagedesigner\ElementTranslationHandler",
 *
 *     "form" = {
 *       "default" = "Drupal\pagedesigner\Form\ElementForm",
 *       "add" = "Drupal\pagedesigner\Form\ElementForm",
 *       "edit" = "Drupal\pagedesigner\Form\ElementForm",
 *       "delete" = "Drupal\pagedesigner\Form\ElementDeleteForm",
 *     },
 *     "access" = "Drupal\pagedesigner\ElementAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\pagedesigner\ElementHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "pagedesigner_element",
 *   data_table = "pagedesigner_element_field_data",
 *   revision_table = "pagedesigner_element_revision",
 *   revision_data_table = "pagedesigner_element_field_revision",
 *   translatable = TRUE,
 *   admin_permission = "administer pagedesigner element entities",
 *   entity_keys = {
 *     "id" = "id",
 *     "revision" = "vid",
 *     "bundle" = "type",
 *     "label" = "name",
 *     "uuid" = "uuid",
 *     "uid" = "user_id",
 *     "langcode" = "langcode",
 *     "status" = "status",
 *   },
 *   links = {
 *     "canonical" = "/admin/content/pagedesigner_element/{pagedesigner_element}",
 *     "add-page" = "/admin/content/pagedesigner_element/add",
 *     "add-form" = "/admin/content/pagedesigner_element/add/{pagedesigner_type}",
 *     "edit-form" = "/admin/content/pagedesigner_element/{pagedesigner_element}/edit",
 *     "delete-form" = "/admin/content/pagedesigner_element/{pagedesigner_element}/delete",
 *     "version-history" = "/admin/content/pagedesigner_element/{pagedesigner_element}/revisions",
 *     "revision" = "/admin/content/pagedesigner_element/{pagedesigner_element}/revisions/{pagedesigner_element_revision}/view",
 *     "revision_revert" = "/admin/content/pagedesigner_element/{pagedesigner_element}/revisions/{pagedesigner_element_revision}/revert",
 *     "revision_delete" = "/admin/content/pagedesigner_element/{pagedesigner_element}/revisions/{pagedesigner_element_revision}/delete",
 *     "translation_revert" = "/admin/content/pagedesigner_element/{pagedesigner_element}/revisions/{pagedesigner_element_revision}/revert/{langcode}",
 *     "collection" = "/admin/content/pagedesigner_element",
 *   },
 *   bundle_entity_type = "pagedesigner_type",
 *   field_ui_base_route = "entity.pagedesigner_type.edit_form"
 * )
 */
class Element extends RevisionableContentEntityBase implements ElementInterface
{

    use EntityChangedTrait;

    /**
     * {@inheritdoc}
     */
    public static function preCreate(EntityStorageInterface $storage_controller, array &$values)
    {
        parent::preCreate($storage_controller, $values);
        $values += [
            'user_id' => \Drupal::currentUser()->id(),
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function urlRouteParameters($rel)
    {
        $uri_route_parameters = parent::urlRouteParameters($rel);

        if ($rel === 'revision_revert' && $this instanceof RevisionableInterface) {
            $uri_route_parameters[$this->getEntityTypeId() . '_revision'] = $this->getRevisionId();
        } elseif ($rel === 'revision_delete' && $this instanceof RevisionableInterface) {
            $uri_route_parameters[$this->getEntityTypeId() . '_revision'] = $this->getRevisionId();
        }

        return $uri_route_parameters;
    }

    /**
     * {@inheritdoc}
     */
    public function preSave(EntityStorageInterface $storage)
    {
        parent::preSave($storage);

        foreach (array_keys($this->getTranslationLanguages()) as $langcode) {
            $translation = $this->getTranslation($langcode);

            // If no owner has been set explicitly, make the anonymous user the owner.
            if (!$translation->getOwner()) {
                $translation->setOwnerId(0);
            }
        }

        // If no revision author has been set explicitly, make the pagedesigner_element owner the
        // revision author.
        if (!$this->getRevisionUser()) {
            $this->setRevisionUserId($this->getOwnerId());
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return $this->get('name')->value;
    }

    /**
     * {@inheritdoc}
     */
    public function setName($name)
    {
        $this->set('name', $name);
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getCreatedTime()
    {
        return $this->get('created')->value;
    }

    /**
     * {@inheritdoc}
     */
    public function setCreatedTime($timestamp)
    {
        $this->set('created', $timestamp);
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getOwner()
    {
        return $this->get('user_id')->entity;
    }

    /**
     * {@inheritdoc}
     */
    public function getOwnerId()
    {
        return $this->get('user_id')->target_id;
    }

    /**
     * {@inheritdoc}
     */
    public function setOwnerId($uid)
    {
        $this->set('user_id', $uid);
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function setOwner(UserInterface $account)
    {
        $this->set('user_id', $account->id());
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function isPublished()
    {
        return (bool) $this->getEntityKey('status');
    }

    /**
     * {@inheritdoc}
     */
    public function setPublished($published)
    {
        $this->set('status', $published ? true : false);
        return $this;
    }

    public function saveEdit()
    {
        $this->setPublished(false);
        $this->setNewRevision(true);
        $this->save();
    }

    /**
     * {@inheritDoc}
     */
    public function loadNewestPublished()
    {
        $entity = $this;
        $col = \Drupal::database()->query(
            'SELECT vid FROM {pagedesigner_element_field_revision} WHERE id=:nid AND status = 1 AND COALESCE(deleted,0) =  0 ORDER BY vid DESC',
            array(
                ':nid' => $this->id(),
            )
        )
            ->fetchCol();
        if (count($col) == 0) {
            return null;
        }
        $vid = reset($col);
        if ($vid != $this->getRevisionId()) {
            $entity = \Drupal::entityTypeManager()
                ->getStorage($this->entityTypeId)
                ->loadRevision($vid);
        }
        return $entity;
    }

    /**
     * {@inheritdoc}
     */
    public static function baseFieldDefinitions(EntityTypeInterface $entity_type)
    {
        $fields = parent::baseFieldDefinitions($entity_type);

        $fields['user_id'] = BaseFieldDefinition::create('entity_reference')
            ->setLabel(t('Authored by'))
            ->setDescription(t('The user ID of author of the Pagedesigner Element entity.'))
            ->setRevisionable(true)
            ->setSetting('target_type', 'user')
            ->setSetting('handler', 'default')
            ->setTranslatable(true)
            ->setDisplayOptions('view', [
                'label' => 'hidden',
                'type' => 'author',
                'weight' => 0,
            ])
            ->setDisplayOptions('form', [
                'type' => 'entity_reference_autocomplete',
                'weight' => 5,
                'settings' => [
                    'match_operator' => 'CONTAINS',
                    'size' => '60',
                    'autocomplete_type' => 'tags',
                    'placeholder' => '',
                ],
            ])
            ->setDisplayConfigurable('form', true)
            ->setDisplayConfigurable('view', true);

        $fields['name'] = BaseFieldDefinition::create('string')
            ->setLabel(t('Name'))
            ->setDescription(t('The name of the Pagedesigner Element entity.'))
            ->setRevisionable(true)
            ->setSettings([
                'max_length' => 50,
                'text_processing' => 0,
            ])
            ->setDefaultValue('')
            ->setDisplayOptions('view', [
                'label' => 'above',
                'type' => 'hidden',
                'weight' => -4,
            ])
            ->setDisplayOptions('form', [
                'type' => 'text_textfield',
                'weight' => -4,
            ])
            ->setDisplayConfigurable('form', false)
            ->setDisplayConfigurable('view', false)
            ->setRequired(false);

        $fields['status'] = BaseFieldDefinition::create('boolean')
            ->setLabel(t('Publishing status'))
            ->setDescription(t('A boolean indicating whether the Pagedesigner Element is published.'))
            ->setRevisionable(true)
            ->setDefaultValue(true)
            ->setDisplayOptions('form', [
                'type' => 'boolean_checkbox',
                'weight' => -3,
            ]);

        $fields['created'] = BaseFieldDefinition::create('created')
            ->setLabel(t('Created'))
            ->setDescription(t('The time that the entity was created.'));

        $fields['changed'] = BaseFieldDefinition::create('changed')
            ->setLabel(t('Changed'))
            ->setDescription(t('The time that the entity was last edited.'));

        $fields['revision_translation_affected'] = BaseFieldDefinition::create('boolean')
            ->setLabel(t('Revision translation affected'))
            ->setDescription(t('Indicates if the last edit of a translation belongs to current revision.'))
            ->setReadOnly(true)
            ->setRevisionable(true)
            ->setTranslatable(true);

        $fields['container'] = BaseFieldDefinition::create('entity_reference')
            ->setLabel(t('Node'))
            ->setDescription(t('The container of this element.'))
            ->setRevisionable(true)
            ->setSetting('target_type', 'node')
            ->setSetting('handler', 'default')
            ->setCardinality(1)
            ->setTranslatable(true)
            ->setDisplayOptions('view', [
                'label' => 'hidden',
                'type' => 'node',
                'weight' => 0,
            ])
            ->setDisplayOptions('form', [
                'type' => 'entity_reference_autocomplete',
                'weight' => -100,
                'cardinality' => '-1',
                'settings' => [
                    'match_operator' => 'CONTAINS',
                    'size' => '60',
                    'autocomplete_type' => 'tags',
                    'placeholder' => '',
                    'cardinality' => '-1',
                ],
            ])
            ->setDisplayConfigurable('form', false)
            ->setDisplayConfigurable('view', false);

        $fields['parent'] = BaseFieldDefinition::create('entity_reference')
            ->setLabel(t('Parent'))
            ->setDescription(t('The parent of this element.'))
            ->setRevisionable(true)
            ->setSetting('target_type', 'pagedesigner_element')
            ->setSetting('handler', 'default')
            ->setCardinality(1)
            ->setTranslatable(true)
            ->setDisplayOptions('view', [
                'label' => 'hidden',
                'type' => 'pagedesigner_element',
                'weight' => 0,
            ])
            ->setDisplayOptions('form', [
                'type' => 'entity_reference_autocomplete',
                'weight' => -100,
                'cardinality' => '-1',
                'settings' => [
                    'match_operator' => 'CONTAINS',
                    'size' => '60',
                    'autocomplete_type' => 'tags',
                    'placeholder' => '',
                    'cardinality' => '-1',
                ],
            ])
            ->setDisplayConfigurable('form', false)
            ->setDisplayConfigurable('view', false);

        $fields['children'] = BaseFieldDefinition::create('entity_reference')
            ->setLabel(t('Children'))
            ->setDescription(t('The children of this element.'))
            ->setRevisionable(true)
            ->setSetting('target_type', 'pagedesigner_element')
            ->setSetting('handler', 'default')
            ->setCardinality(-1)
            ->setTranslatable(true)
            ->setDisplayOptions('view', [
                'label' => 'hidden',
                'type' => 'node',
                'weight' => 0,
            ])
            ->setDisplayOptions('form', [
                'type' => 'entity_reference_autocomplete',
                'weight' => -100,
                'cardinality' => '-1',
                'settings' => [
                    'match_operator' => 'CONTAINS',
                    'size' => '60',
                    'autocomplete_type' => 'tags',
                    'placeholder' => '',
                    'cardinality' => '-1',
                ],
            ])
            ->setDisplayConfigurable('form', false)
            ->setDisplayConfigurable('view', false);

        $fields['deleted'] = BaseFieldDefinition::create('boolean')
            ->setLabel(t('Is this element deleted'))
            ->setDescription(t('Indicates if the element is deleted.'))
            ->setDisplayOptions('form', [
                'type' => 'boolean_checkbox',
                'weight' => -3,
            ])
            ->setReadOnly(false)
            ->setRevisionable(true)
            ->setTranslatable(true);

        return $fields;
    }

}
