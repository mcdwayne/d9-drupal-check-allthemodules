<?php
/**
 * Created by PhpStorm.
 * User: USER
 * Date: 6/7/18
 * Time: 5:51 PM
 */

namespace Drupal\drupal_helper;

use Drupal\paragraphs\Entity\Paragraph;

class DrupalCRUD
{
    protected function create_init_entity($entity_type,$bundle, $id = null)
    {
        $entity_def = \Drupal::entityTypeManager()->getDefinition($entity_type);
        $array = array(
            $entity_def->get('entity_keys')['bundle']=>$bundle
        );
        return \Drupal::entityTypeManager()->getStorage($entity_type)->create($array);
    }

    public function save_entity($entity_type,$type,$fields,$reference_object=null){
        if($reference_object==null){
            $reference_object = $this ;
        }
        if(isset($fields['id'])&&is_numeric($fields['id'])){
            $entity_new = \Drupal::entityTypeManager()->getStorage($entity_type)->load($fields['id']);
        }else{
            $entity_new = $this->create_init_entity($entity_type,$type);
        }


        if(!empty($fields)){
            $keys = array_keys($fields);
            if(!in_array('nid',$keys)){
                $fields['nid']= null;
            }
            if(!in_array('title',$keys)){
                $fields['title']= 'YOUR - TITLE - EMPTY';
            }

            foreach ($fields as $key => $field){
                if ($entity_new->hasField($key)) {
                    $status = true ;
                    $field_type = $entity_new->get($key)->getFieldDefinition()->getType();
                  //  $setting_field = $entity_new->get($field)->getFieldDefinition()->getSettings();

                    //hook by type
                    if($reference_object && $field_type && method_exists($reference_object, $field_type)){
                        $entity_new = $reference_object->{$field_type}($entity_new,$key,$field);
                        $status = false ;
                    }
                    ///hook by name
                    if($reference_object && method_exists($reference_object, $key)){
                        $entity_new = $reference_object->{$key}($entity_new,$key,$field);
                        $status = false ;
                    }
                    //default
                    if($status){
                        $this->item_default($entity_new,$key,$field);
                    }

                }
            }

        }

        $status = $entity_new->save();
        return [ 'object' => $entity_new , 'status'=>$status ];
    }
    // ********* SAVE FUNCTION ENTITY ******** //
     public function save_paragraph($type,$fields,$reference_object=null){
         return $this->save_entity('paragraph',$type,$fields,$reference_object);
     }
     public function save_node($type,$fields,$reference_object=null){
        return $this->save_entity('node',$type,$fields,$reference_object);
     }


    //********** Reference format *********//
    //default
    public function item_default($entity_parent,$field_name,$field_value){
            $entity_parent->set($field_name,$field_value);
        return $entity_parent ;
    }

    //string
    public function string($entity_parent,$field_name,$field_value){
          if(is_string($field_value)){
            $entity_parent->set($field_name,$field_value);
          }
          return $entity_parent ;
    }
    //float
    public function float($entity_parent,$field_name,$field_value){
        if(is_float($field_value)){
            $entity_parent->set($field_name,$field_value);
        }
        return $entity_parent ;
    }
    //paragraph
    public function entity_reference_revisions($entity_parent,$field_name,$field_value){
        if(is_object($field_value)){
            $paragraph[] =  [
                'target_id' => $field_value->id(),
                'target_revision_id' => $field_value->getRevisionId(),
            ];
            $entity_parent->set($field_name,$paragraph);
        }
        return $entity_parent ;
    }
    //entity_reference
    public function entity_reference($entity_parent,$field_name,$field_value){
        if(is_object($field_value)){
            $entity_parent->{$field_name}->entity = $field_value;
        }
        return $entity_parent ;
    }
    public  function entity_reference_user($entity_parent,$field_name,$field_value){
        if(is_object($field_value)){
            $entity_parent->{$field_name}->entity = $field_value;
        }
        return $entity_parent ;
    }


}