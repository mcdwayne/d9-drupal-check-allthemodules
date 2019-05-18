<?php

namespace Drupal\lti_tool_provider_provision\Entity;

use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityTypeInterface;

/**
 * @ContentEntityType(
 *   id = "lti_tool_provider_provision",
 *   label = @Translation("LTI Tool Provider Provision Entity"),
 *   base_table = "lti_tool_provider_provision",
 *   fieldable = FALSE,
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *   },
 * )
 */
class LtiToolProviderProvision extends ContentEntityBase implements ContentEntityInterface
{
    /**
     * {@inheritdoc}
     */
    public static function baseFieldDefinitions(EntityTypeInterface $entity_type)
    {
        $fields = parent::baseFieldDefinitions($entity_type);

        $fields['consumer_id'] = BaseFieldDefinition::create('string')
            ->setLabel(t('Consumer Id'))
            ->setSettings(
                [
                    'max_length' => 512,
                    'text_processing' => 0,
                ]
            );

        $fields['context_id'] = BaseFieldDefinition::create('string')
            ->setLabel(t('Context Id'))
            ->setSettings(
                [
                    'max_length' => 512,
                    'text_processing' => 0,
                ]
            );

        $fields['resource_link_id'] = BaseFieldDefinition::create('string')
            ->setLabel(t('Resource Link Id'))
            ->setSettings(
                [
                    'max_length' => 512,
                    'text_processing' => 0,
                ]
            );

        $fields['provision_type'] = BaseFieldDefinition::create('string')
            ->setLabel(t('Provision Type'))
            ->setSettings(
                [
                    'max_length' => 512,
                    'text_processing' => 0,
                ]
            );

        $fields['provision_bundle'] = BaseFieldDefinition::create('string')
            ->setLabel(t('Provision Bundle'))
            ->setSettings(
                [
                    'max_length' => 512,
                    'text_processing' => 0,
                ]
            );

        $fields['provision_id'] = BaseFieldDefinition::create('string')
            ->setLabel(t('Provision Id'))
            ->setSettings(
                [
                    'max_length' => 512,
                    'text_processing' => 0,
                ]
            );

        return $fields;
    }
}
