<?php

namespace Drupal\lti_tool_provider\Entity;

use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityTypeInterface;

/**
 * Defines the LTIToolProviderNonce entity.
 *
 * @ingroup lti_tool_provider
 *
 * @ContentEntityType(
 *   id = "lti_tool_provider_nonce",
 *   label = @Translation("LTI Tool Provider Nonce Entity"),
 *   base_table = "lti_tool_provider_nonce",
 *   fieldable = FALSE,
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "nonce",
 *     "uuid" = "uuid",
 *   },
 * )
 */
class Nonce extends ContentEntityBase implements ContentEntityInterface
{
    /**
     * {@inheritdoc}
     */
    public static function baseFieldDefinitions(EntityTypeInterface $entity_type)
    {
        $fields = parent::baseFieldDefinitions($entity_type);

        $fields['nonce'] = BaseFieldDefinition::create('string')
            ->setLabel(t('Nonce'))
            ->setSettings(
                [
                    'max_length' => 512,
                    'text_processing' => 0,
                ]
            );

        $fields['consumer_key'] = BaseFieldDefinition::create('string')
            ->setLabel(t('Consumer Key'))
            ->setSettings(
                [
                    'max_length' => 512,
                    'text_processing' => 0,
                ]
            );

        $fields['timestamp'] = BaseFieldDefinition::create('created')
            ->setLabel(t('Timestamp'));

        return $fields;
    }

}
