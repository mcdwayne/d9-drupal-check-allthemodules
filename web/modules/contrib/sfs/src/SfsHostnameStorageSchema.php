<?php

namespace Drupal\sfs;

use Drupal\Core\Entity\ContentEntityTypeInterface;
use Drupal\Core\Entity\Sql\SqlContentEntityStorageSchema;

/**
 * Defines the sfs_hostname schema handler.
 */
class SfsHostnameStorageSchema extends SqlContentEntityStorageSchema {
    
    /**
     * {@inheritdoc}
     */
    protected function getEntitySchema(ContentEntityTypeInterface $entity_type, $reset = FALSE) {
        $schema = parent::getEntitySchema($entity_type, $reset);
        
        $schema['sfs_hostname']['indexes'] += [
            'sfs_hostname_field_uid_value' => ['uid'],
            'sfs_hostname_field_hostname_value' => ['hostname'],
            'sfs_hostname_field_entity_id_value' => ['entity_id'],
            'sfs_hostname_field_entity_type_value' => ['entity_type'],
        ];
        
        return $schema;
    }
}
