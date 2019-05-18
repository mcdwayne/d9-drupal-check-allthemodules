<?php

namespace Drupal\lti_tool_provider\Entity;

use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityTypeInterface;

/**
 * Defines the LTIToolProviderConsumer entity.
 *
 * @ingroup lti_tool_provider
 *
 * @ContentEntityType(
 *   id = "lti_tool_provider_consumer",
 *   label = @Translation("Consumer entity"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\lti_tool_provider\ConsumerListBuilder",
 *     "form" = {
 *       "add" = "Drupal\lti_tool_provider\Form\ConsumerForm",
 *       "edit" = "Drupal\lti_tool_provider\Form\ConsumerForm",
 *       "delete" = "Drupal\lti_tool_provider\Form\ConsumerDeleteForm",
 *     },
 *     "access" = "Drupal\lti_tool_provider\ConsumerAccessController",
 *   },
 *   base_table = "lti_tool_provider_consumer",
 *   admin_permission = "administer lti_tool_provider module",
 *   fieldable = FALSE,
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "consumer",
 *     "uuid" = "uuid",
 *   },
 *   links = {
 *     "canonical" = "/admin/config/lti-tool-provider/consumer",
 *     "collection" = "/admin/config/lti-tool-provider/consumer",
 *     "edit-form" = "/admin/config/lti-tool-provider/consumer/{lti_tool_provider_consumer}/edit",
 *     "delete-form" = "/admin/config/lti-tool-provider/consumer/{lti_tool_provider_consumer}/delete",
 *   },
 * )
 */
class Consumer extends ContentEntityBase implements ContentEntityInterface
{
    /**
     * {@inheritdoc}
     */
    public static function baseFieldDefinitions(EntityTypeInterface $entity_type)
    {
        $fields = parent::baseFieldDefinitions($entity_type);

        $fields['consumer'] = BaseFieldDefinition::create('string')
            ->setLabel(t('Consumer'))
            ->setDescription(t('The name of the Consumer entity.'))
            ->setRequired(true)
            ->setSettings(
                [
                    'max_length' => 512,
                    'text_processing' => 0,
                ]
            )
            ->setDisplayOptions(
                'view',
                [
                    'label' => 'hidden',
                    'type' => 'string',
                    'weight' => 1,
                ]
            )
            ->setDisplayOptions(
                'form',
                [
                    'type' => 'string',
                    'weight' => 1,
                ]
            )
            ->setDisplayConfigurable('form', true)
            ->setDisplayConfigurable('view', true);

        $fields['consumer_key'] = BaseFieldDefinition::create('string')
            ->setLabel(t('Key'))
            ->setDescription(t('The key of the Consumer entity.'))
            ->setRequired(true)
            ->setSettings(
                [
                    'max_length' => 512,
                    'text_processing' => 0,
                ]
            )
            ->setDisplayOptions(
                'view',
                [
                    'label' => 'inline',
                    'type' => 'string',
                    'weight' => 2,
                ]
            )
            ->setDisplayOptions(
                'form',
                [
                    'type' => 'string',
                    'weight' => 2,
                ]
            )
            ->setDisplayConfigurable('form', true)
            ->setDisplayConfigurable('view', true);

        $fields['consumer_secret'] = BaseFieldDefinition::create('string')
            ->setLabel(t('Secret'))
            ->setDescription(t('The secret of the Consumer entity.'))
            ->setRequired(true)
            ->setSettings(
                [
                    'max_length' => 512,
                    'text_processing' => 0,
                ]
            )
            ->setDisplayOptions(
                'view',
                [
                    'label' => 'inline',
                    'type' => 'string',
                    'weight' => 3,
                ]
            )
            ->setDisplayOptions(
                'form',
                [
                    'type' => 'string',
                    'weight' => 3,
                ]
            )
            ->setDisplayConfigurable('form', true)
            ->setDisplayConfigurable('view', true);

        $fields['name'] = BaseFieldDefinition::create('string')
            ->setLabel(t('Name field'))
            ->setDescription(t('The LTI field to get the users unique name from. Default is "lis_person_contact_email_primary"'))
            ->setRequired(true)
            ->setDefaultValue('lis_person_contact_email_primary')
            ->setSettings(
                [
                    'max_length' => 512,
                    'text_processing' => 0,
                ]
            )
            ->setDisplayOptions(
                'view',
                [
                    'label' => 'inline',
                    'type' => 'string',
                    'weight' => 4,
                ]
            )
            ->setDisplayOptions(
                'form',
                [
                    'type' => 'string',
                    'weight' => 4,
                ]
            )
            ->setDisplayConfigurable('form', true)
            ->setDisplayConfigurable('view', true);

        $fields['mail'] = BaseFieldDefinition::create('string')
            ->setLabel(t('Mail field'))
            ->setDescription(t('The LTI field to get the users email from. Default is "lis_person_contact_email_primary"'))
            ->setRequired(true)
            ->setDefaultValue('lis_person_contact_email_primary')
            ->setSettings(
                [
                    'max_length' => 512,
                    'text_processing' => 0,
                ]
            )
            ->setDisplayOptions(
                'view',
                [
                    'label' => 'inline',
                    'type' => 'string',
                    'weight' => 5,
                ]
            )
            ->setDisplayOptions(
                'form',
                [
                    'type' => 'string',
                    'weight' => 5,
                ]
            )
            ->setDisplayConfigurable('form', true)
            ->setDisplayConfigurable('view', true);

        $fields['created'] = BaseFieldDefinition::create('created')
            ->setLabel(t('Date created'))
            ->setDescription(t('Date the consumer was created'));

        return $fields;
    }

}
