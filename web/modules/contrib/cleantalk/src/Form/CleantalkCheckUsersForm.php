<?php

namespace Drupal\cleantalk\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;
use Drupal\user\Entity\User;
use Drupal\cleantalk\CleantalkHelper;

class CleantalkCheckUsersForm extends FormBase {

  /**
   * {@inheritdoc}
   */

  public function getFormId() {

    return 'cleantalk_check_users_form';

  }

  /**
   * {@inheritdoc}
   */

  public function submitForm(array &$form, FormStateInterface $form_state) {

    parent::submitForm($form, $form_state);

  }

  /**
   * {@inheritdoc}
   */

  protected function getEditableConfigNames() {

    return ['cleantalk.check_users'];

  }

  public function buildForm(array $form, \Drupal\Core\Form\FormStateInterface $form_state) {

    if (!empty(\Drupal::config('cleantalk.settings')->get('cleantalk_authkey'))) {

      $form_state->setStorage(array('spam_users'=>$this->cleantalk_find_spammers_users()));
      
      if (isset($form_state->getStorage()['spam_users'])) {       

        $form['spam_users'] = array (
        '#type' => 'table',
        '#header' => array('Username','E-mail','Status','Registered','Last visit', ''),
        '#empty' => t('No users found'),
        );
        $spam_users = $form_state->getStorage()['spam_users'];

        foreach ($spam_users as $user) {

          // Show user name

          $form['spam_users'][$user['id']][0] = array(
            '#type' => 'label',
            '#title' => $user['name'],
          );

          // Show user mail

          $form['spam_users'][$user['id']][1] = array(
            '#type' => 'label',
            '#title' => $user['mail'],
          );
          
          // Show user status

          $form['spam_users'][$user['id']][2] = array(
            '#type' => 'label',
            '#title' => $user['status'],
          );

          // Show user date created

          $form['spam_users'][$user['id']][3] = array(
            '#type' => 'label',
            '#title' => $user['created'],
          );

          // Show user login

          $form['spam_users'][$user['id']][4] = array(
            '#type' => 'label',
            '#title' => $user['login'],
          );

          // Show button for each user

          $form['spam_users'][$user['id']]['removememberbutton']['dummyNode'] = array(
            '#type' => 'submit',
            '#value' => 'Remove',
            '#name' => 'remove_' . $user['id'],
            '#submit' => array('::cleantalk_remove_user'),
          );

        }
          $data = array();
          foreach ($spam_users as $user) {

            array_push($data,$user['id']);

          }

          $form['delete_all_spammers_users']  = array(
              '#type' => 'submit',
              '#value' => t('Delete all'),
              '#name' => 'delete_all_' . implode('_',$data),
              '#submit' => array('::cleantalk_delete_all_spammers_users'),
            );
        }

      }

      else {

        drupal_set_message('Access key is not valid.','error');

      }

    return $form;

  }

  public function cleantalk_find_spammers_users() {

    $ct_authkey = trim(\Drupal::config('cleantalk.settings')->get('cleantalk_authkey'));

    if ($ct_authkey) {

      $ids = \Drupal::entityQuery('user')->execute();
      $users = User::loadMultiple($ids);
      $data = array();
      $spam_users=array();

      foreach ($users as $user) {

        array_push($data,$user->get('mail')->value);

      }

      $data=implode(',',$data);

      $result=CleantalkHelper::api_method__spam_check_cms($ct_authkey, $data);

      if(isset($result['error_message'])) {

            drupal_set_message($result['error_message'],'error');

      }

      else {

        foreach($result as $key => $value) {

          if ($value['appears'] == '1' ) {

            foreach ($users as $user) {

              if ($user->get('mail')->value == $key) {

                $spam_users[] = $user;

              }

            }

          }

        }  

      }

      $storage_array = array();
      $id=0;

      foreach ($spam_users as $user) {

        $storage_array[$id]['id'] = $user->id();
        $storage_array[$id]['name'] = $user->get('name')->value;
        $storage_array[$id]['mail'] = $user->get('mail')->value;
        $storage_array[$id]['status'] = ($user->get('status')->value==1)?'Active':'Inactive';
        $storage_array[$id]['created'] = date("Y-m-d H:i:s",$user->get('created')->value);
        $storage_array[$id]['login'] = date("Y-m-d H:i:s",$user->get('login')->value);
        $id++;

      }

      return $storage_array; 

    }

  }

  public function cleantalk_remove_user(array $form, \Drupal\Core\Form\FormStateInterface $form_state ) {

      $userid = $form_state->getTriggeringElement()['#array_parents'][1];
      user_cancel(array(), $userid, 'user_cancel_delete');

  }

  public function cleantalk_delete_all_spammers_users(array $form, \Drupal\Core\Form\FormStateInterface $form_state ) {

      $post_array = str_replace('delete_all_', '', $form_state->getTriggeringElement()['#name']);
      $ids = explode('_',$post_array);

      foreach ($ids as $id) {

        user_cancel(array(), $id, 'user_cancel_delete');   

      }
  }

}