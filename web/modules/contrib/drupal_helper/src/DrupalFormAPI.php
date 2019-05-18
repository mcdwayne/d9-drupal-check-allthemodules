<?php
/**
 * Created by PhpStorm.
 * User: USER
 * Date: 7/30/18
 * Time: 9:43 AM
 */

namespace Drupal\drupal_helper;



class DrupalFormAPI
{
    public function  render_form_paragraph($bundle,$para_id=null){
        return  $this->render_form_entity('paragraph',$bundle,$para_id);
    }
    public function  render_form_node($bundle,$nid=null){
        return  $this->render_form_entity('node',$bundle,$nid);
    }
    public function  render_form_taxomony_term($bundle,$nid=null){
        return  $this->render_form_entity('taxomony_term',$bundle,$nid);
    }
    public function  render_form_user($uid=null){
        return  $this->render_form_entity('user','user',$uid);
    }
    public function  render_form_block_content($bundle,$nid=null){
        return  $this->render_form_entity('block_content',$bundle,$nid);
    }
    //** @param $entity is node ,paragraph ,taxonomy_term , block_content ,... */
    public function  render_form_entity($entity,$bundle,$id=null){
        if($id&& is_numeric($id)){
            $paragraph = \Drupal::entityTypeManager()
                ->getStorage($entity)
                ->load($id);
        }else{
            $values = array('type' => $bundle);
            $paragraph = \Drupal::entityTypeManager()
                ->getStorage($entity)
                ->create($values);

        }
        $form = \Drupal::entityTypeManager()
            ->getFormObject($entity, 'default')
            ->setEntity($paragraph);
        return \Drupal::formBuilder()->getForm($form);
    }

}