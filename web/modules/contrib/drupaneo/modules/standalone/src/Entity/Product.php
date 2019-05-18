<?php

namespace Drupal\drupaneo_standalone\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Field\FieldStorageDefinitionInterface;

/**
 * Defines Product entity.
 *
 * @ContentEntityType(
 *   id = "product",
 *   label = @Translation("Product"),
 *   handlers = {
 *     "list_builder" = "Drupal\drupaneo_standalone\Entity\ProductListBuilder"
 *   },
 *   base_table = "product",
 *   admin_permission = "administer site configuration",
 *   entity_keys = {
 *     "id" = "id"
 *   },
 *   links = {
 *     "collection" = "/admin/drupaneo/products"
 *   }
 * )
 */
class Product extends ContentEntityBase {

    /**
     * {@inheritdoc}
     */
    public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
        $fields = parent::baseFieldDefinitions($entity_type);

        $fields['identifier'] = BaseFieldDefinition::create('string')
            ->setLabel(t('Identifier'))
            ->setDescription(t('Product identifier, i.e. the value of the only pim_catalog_identifier attribute.'));

        $fields['family'] = BaseFieldDefinition::create('string')
            ->setLabel(t('Family'))
            ->setDescription(t('Family code from which the product inherits its attributes and attributes requirements.'));

        $fields['categories'] = BaseFieldDefinition::create('string')
            ->setLabel(t('Categories'))
            ->setCardinality(FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED)
            ->setDescription(t('Codes of the categories in which the product is classified.'));

        $fields['attributes'] = BaseFieldDefinition::create('map')
            ->setLabel(t('Attributes'))
            ->setDescription(t('Product attributes.'));

        $fields['created'] = BaseFieldDefinition::create('created')
            ->setLabel(t('Created'))
            ->setDescription(t('Date of creation.'));

        return $fields;
    }
}
