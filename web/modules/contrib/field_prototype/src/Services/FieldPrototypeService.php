<?php
/**
 * Created by PhpStorm.
 * User: rabbi
 * Date: 6/28/2018
 * Time: 11:32 PM
 */

//namespace Drupal\field_prototype\Services;


class FieldPrototypeService
{
    protected function entity_type_list() {
        $entities = \Drupal::entityTypeManager()->getDefinitions();
        $entity_types = array();
        foreach($entities as $name => $entity) {
            if(in_array('Drupal\Core\Entity\FieldableEntityInterface', class_implements($entity->getOriginalClass()))) {
                //$entity_name = ucwords(str_replace('_', ' ', $entity->id()));
                $entity_types[$name] = $entity->getLabel();
            }
        }
        asort($entity_types);

        return $entity_types;
    }

    protected function bundle_list($entities) {
        $all_bundles = $bundle_list = \Drupal::service('entity_type.bundle.info')->getAllBundleInfo();
        $bundle_list = array();

        //kint($all_bundles);
        foreach($entities as $machine => $entity_name) {//kint($entity_name, $all_bundles[$entity_name]);
            foreach($all_bundles[$machine] as $bundle_name => $bundle) {
                $bundle_list[$machine][$bundle_name] = $bundle['label'];
                //kint($bundle);
                /*foreach($entity as $bundle_name => $bundle) {
                  $bundle_list[$entity_name][$bundle_name] = $bundle['label'];
                }*/
            }
            //kint('Entity bundles', $entity_bundles);
        }

        return $bundle_list;
    }

    public function getEntityTypes() {
        return $this->entity_type_list();
    }

    public function getBundles() {
        $entities = $this->getEntityTypes();
        return $this->bundle_list($entities);
    }
}