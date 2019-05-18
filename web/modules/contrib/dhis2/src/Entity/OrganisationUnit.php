<?php

namespace Drupal\dhis\Entity;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\user\UserInterface;

/**
 * Defines the Organisation unit entity.
 *
 * @ingroup dhis
 *
 * @ContentEntityType(
 *   id = "organisation_unit",
 *   label = @Translation("Organisation unit"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\dhis\OrganisationUnitListBuilder",
 *     "views_data" = "Drupal\dhis\Entity\OrganisationUnitViewsData",
 *
 *     "form" = {
 *       "default" = "Drupal\dhis\Form\OrganisationUnitForm",
 *       "add" = "Drupal\dhis\Form\OrganisationUnitForm",
 *       "edit" = "Drupal\dhis\Form\OrganisationUnitForm",
 *       "delete" = "Drupal\dhis\Form\OrganisationUnitDeleteForm",
 *     },
 *     "access" = "Drupal\dhis\OrganisationUnitAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\dhis\OrganisationUnitHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "organisation_unit",
 *   admin_permission = "administer organisation unit entities",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "name",
 *     "uuid" = "uuid",
 *     "uid" = "user_id",
 *     "langcode" = "langcode",
 *     "status" = "status",
 *   },
 *   links = {
 *     "canonical" = "/admin/structure/organisation_unit/{organisation_unit}",
 *     "add-form" = "/admin/structure/organisation_unit/add",
 *     "edit-form" = "/admin/structure/organisation_unit/{organisation_unit}/edit",
 *     "delete-form" = "/admin/structure/organisation_unit/{organisation_unit}/delete",
 *     "collection" = "/admin/structure/organisation_unit",
 *   },
 *   field_ui_base_route = "organisation_unit.settings"
 * )
 */
class OrganisationUnit extends ContentEntityBase implements OrganisationUnitInterface
{

    use EntityChangedTrait;

    /**
     * {@inheritdoc}
     */
    public static function preCreate(EntityStorageInterface $storage_controller, array &$values)
    {
        parent::preCreate($storage_controller, $values);
        $values += array(
            'user_id' => \Drupal::currentUser()->id(),
        );
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
        return (bool)$this->getEntityKey('status');
    }

    /**
     * {@inheritdoc}
     */
    public function setPublished($published)
    {
        $this->set('status', $published ? TRUE : FALSE);
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function setOrgunitUid($orgunituid)
    {
        $this->set('orgunituid', $orgunituid);
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getOrgunitUid()
    {
        return $this->get('orgunituid')->value;
    }


    /**
     * {@inheritdoc}
     */
    public static function baseFieldDefinitions(EntityTypeInterface $entity_type)
    {
        $fields = parent::baseFieldDefinitions($entity_type);

        $fields['user_id'] = BaseFieldDefinition::create('entity_reference')
            ->setLabel(t('Authored by'))
            ->setDescription(t('The user ID of author of the Organisation unit entity.'))
            ->setRevisionable(TRUE)
            ->setSetting('target_type', 'user')
            ->setSetting('handler', 'default')
            ->setTranslatable(TRUE)
            ->setDisplayOptions('view', array(
                'label' => 'hidden',
                'type' => 'author',
                'weight' => 0,
            ))
            ->setDisplayOptions('form', array(
                'type' => 'entity_reference_autocomplete',
                'weight' => 5,
                'settings' => array(
                    'match_operator' => 'CONTAINS',
                    'size' => '60',
                    'autocomplete_type' => 'tags',
                    'placeholder' => '',
                ),
            ))
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        $fields['name'] = BaseFieldDefinition::create('string')
            ->setLabel(t('Name'))
            ->setDescription(t('The name of the Organisation unit entity.'))
            ->setSettings(array(
                'max_length' => 50,
                'text_processing' => 0,
            ))
            ->setDefaultValue('')
            ->setDisplayOptions('view', array(
                'label' => 'above',
                'type' => 'string',
                'weight' => -4,
            ))
            ->setDisplayOptions('form', array(
                'type' => 'string_textfield',
                'weight' => -4,
            ))
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        $fields['orgunituid'] = BaseFieldDefinition::create('string')
            ->setLabel(t('Uid'))
            ->setDescription((t('Unique organisation unit identifier')))
            ->setSettings(array(
                'max_length' => 50,
                'text_processing' => 0,
            ))
            ->setDisplayOptions('form', array(
                'type' => 'string_textfield',
                'weight' => -4,
            ))
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        $fields['status'] = BaseFieldDefinition::create('boolean')
            ->setLabel(t('Publishing status'))
            ->setDescription(t('A boolean indicating whether the Organisation Unit should be synchronized with remote.'))
            ->setDefaultValue(FALSE)
            ->setDisplayOptions('form', array(
                //'type' => 'string_textfield',
                'weight' => -4,
            ))
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        $fields['created'] = BaseFieldDefinition::create('created')
            ->setLabel(t('Created'))
            ->setDescription(t('The time that the entity was created.'));

        $fields['changed'] = BaseFieldDefinition::create('changed')
            ->setLabel(t('Changed'))
            ->setDescription(t('The time that the entity was last edited.'));

        return $fields;
    }

}
