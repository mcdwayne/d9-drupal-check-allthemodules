<?php
namespace Drupal\ot\Controller;

use Drupal\Core\Controller\ControllerBase;
use \Drupal\views\Views;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\Component\Utility\Xss;

class OverrideMain extends ControllerBase
{

  public function getEnabledLanguage()
  {
    $languages['all'] = 'All';
    $language = \Drupal::languageManager()->getLanguages();
    foreach ($language as $key => $value) {
      $languages[$value->getId()] = $value->getName();
    }
    return $languages;
  }

  public function checkEnabledLanguage()
  {
    $languages[] = 'all';
    $language = \Drupal::languageManager()->getLanguages();
    foreach ($language as $key => $value) {
      $languages[] = $value->getId();
    }
    return $languages;
  }

  public function getOtType()
  {
    return array('node_path'=> t('Node and Path/URL'), 'view'=> t('Views'));
  }

  public function checkOtType()
  {
    return array('node_path', 'view');
  }

  public function getOtLocation()
  {
    return array('both'=> t('Both'), 'ui'=> t('Front/UI'), 'source'=> t('HTML Title Tag'));
  }

  public function checkOtLocation()
  {
    return array('both', 'ui', 'source');
  }

  public function getOtAction()
  {
    return array(
      'ot_delete'=> t('Delete Override Title'),
      'Status'=> array(
        'ot_active'=> t('Publish Override Title'),
        'ot_deactive'=> t('Unpublish Override Title')
      ),
      'Action Location'=> array(
        'both'=> t('Both'),
        'ui'=> t('Front/UI'),
        'source'=> t('HTML title tag')
      )
    );
  }

  public function checkOtAction()
  {
    return array('ot_delete', 'ot_active', 'ot_deactive', 'both', 'ui', 'source');
  }

  public function getOtById($id)
  {
    $select = db_select('override_title', 'ot')
      ->fields('ot')
      ->condition('id', $id)
      ->execute()
      ->fetchAssoc();
    return $select;
  }

  public function getDisplayOfView($view_id)
  {
    $view = Views::getView(trim($view_id));
    if($view){
      $display_arr = $view->storage->get('display');
      foreach ($display_arr as $key => $value) {
        if($value['display_plugin'] == 'page'){
          $display_lists[$value['id']] = $value['display_title'];
        }
      }
    }

    if(!empty($display_lists)){
      return $display_lists;
    }
  }

  public function getDisplayKeyOfView($view_id)
  {
    $view = Views::getView(trim($view_id));
    if($view){
      $display_arr = $view->storage->get('display');
      foreach ($display_arr as $key => $value) {
        if($value['display_plugin'] == 'page'){
          $display_lists[] = $value['id'];
        }
      }
    }

    if(!empty($display_lists)){
      return $display_lists;
    }
  }

  public function checkOverrideTitle($language, $url)
  {
    $select = db_select('override_title', 'ot')
      ->fields('ot', array('id', 'language', 'title', 'type', 'type_id', 'display_id'))
      ->execute()
      ->fetchAll();
    if($select){
      foreach ($select as $key => $value) {
        if($value->type == 'node_path'){
          $url_object = \Drupal::service('path.validator')->getUrlIfValid($value->type_id);
          if($url_object != false){
            $url_object->setAbsolute();
            $url_build = $url_object->toString();
          }
        }
        else if($value->type == 'view'){
          $display_return = $this->getDisplayKeyOfView($value->type_id);
          if(!empty($display_return) && in_array($value->display_id, $display_return)){
            $url_build = Url::fromRoute('view.'.$value->type_id.'.'.$value->display_id, array(), array("absolute" => TRUE))->toString();
          }
        }

        if(($url_build == $url) && ($language == $value->language)){
          $view_url = Url::fromRoute('override.edit', ['id'=> $value->id]);
          return ['id'=> $value->id, 'title'=> $value->title, 'link'=> Link::fromTextAndUrl(t('Click to Edit'), $view_url)->toString()];
          break;
        }
      }
    }
  }

  public function getOtTitle()
  {
    $current_path = \Drupal::request()->getRequestUri();
    $current_path_arr = explode('/', $current_path);
    $keys_arr = array_keys($current_path_arr);
    $end_key = end($keys_arr);
    $select = db_select('override_title', 'ot')
      ->fields('ot', array('title'))
      ->condition('id', $current_path_arr[$end_key-1])
      ->execute()
      ->fetchAssoc();
    return t('Edit Override Title @ottitle', ['@ottitle'=> $select['title']]);
  }

  public function otDeleteById($id)
  {
    $delete = db_delete('override_title')
      ->condition('id', $id)
      ->execute();
    return $delete;
  }

  public function OtGetSortedData()
  {
    $error_span = '<span class="error-ot">&#10006;</span>';
    $param = \Drupal::request()->query->all();
    $title_query = @$param['title'] ? Xss::filter(@$param['title']) : '';
    $type_query = (@$param['type'] == 'all') ?  '': @$param['type'];
    $status_query = (@$param['status'] == 'all') ?  '': @$param['status'];
    $lang_query = (@$param['lang'] == 'all') ?  '': @$param['lang'];
    
    if(count($this->getEnabledLanguage()) > 2){
      $header = array(
        ['data' => t('type'), 'field' => 'type'],
        'ID(Node/Path/Views)',
        'Display ID',
        ['data' => t('title'), 'field' => 'title'],
        'Location',
        ['data' => t('status'), 'field' => 'status'],
        'Language',
        'Author',
        'Updated',
        ['data' => t('created'), 'field' => 'created', 'sort' => 'desc'],
        'Operations'
      );
    }
    else{
      $header = array(
        ['data' => t('type'), 'field' => 'type'],
        'ID(Node/Path/Views)',
        'Display ID',
        ['data' => t('title'), 'field' => 'title'],
        'Location',
        ['data' => t('status'), 'field' => 'status'],
        'Author',
        'Updated',
        ['data' => t('created'), 'field' => 'created', 'sort' => 'desc'],
        'Operations'
      );
    }

    $query = db_select('override_title', 'ot');
    $query->fields('ot');
    $query->condition('title', '%'.$title_query.'%', 'LIKE');
    !empty($type_query) ? $query->condition('type', $type_query) : '';
    !empty($status_query) ? $query->condition('status', ($status_query == 2) ? 0 : $status_query) : '';
    !empty($lang_query) ? $query->condition('language', $lang_query) : '';
    $table_sort = $query->extend('Drupal\Core\Database\Query\TableSortExtender')->orderByHeader($header);
    $pager = $table_sort->extend('Drupal\Core\Database\Query\PagerSelectExtender')->limit(20);
    $results = $pager->execute()->fetchAll();
    $status = ['UnPublished', 'Published'];
    foreach ($results as $key => $value) {
      $drop_btn = array(
        '#type' => 'dropbutton',
        '#links' => array(
          'edit' => array(
            'title' => t('Modify'),
            'url' => Url::fromRoute('override.edit', ['id'=> $value->id]),
          ),
          'delete' => array(
            'title' => t('Delete'),
            'url' => Url::fromRoute('override.delete', ['id'=> $value->id]),
          ),
        ),
      );

      if($value->type == 'node_path'){
        $url_object = \Drupal::service('path.validator')->getUrlIfValid($value->type_id);
        if($url_object != false){
          $url_object->setAbsolute();
          $url = $url_object->toString();
          $value_type_id = ($value->type_id == '/') ? '/node' : $value->type_id;
          $anchor_type_id = Link::fromTextAndUrl(t($value_type_id), $url_object)->toString();
        }
        else{
          $anchor_type_id = $error_span.$value->type_id.': not found.';
        }
      }
      else if($value->type == 'view'){
        $view = Views::getView(trim($value->type_id));
        if($view){
          $url = Url::fromRoute('view.'.$value->type_id.'.'.$value->display_id);
          $anchor_type_id = $view->storage->get('label').' ('.$value->type_id.')';

          if(array_key_exists($value->display_id, $this->getDisplayOfView($value->type_id))){
            $anchor_display_id = Link::fromTextAndUrl(t($view->storage->get('display')[$value->display_id]['display_title'].' ('.$value->display_id.')'), $url)->toString();
          }
          else{
            $anchor_display_id = $error_span.$value->display_id.': not found.';
          }
        }
        else{
          $anchor_type_id = $error_span.$value->type_id.': view not exists';
          $anchor_display_id = '';
        }
      }
      else{
        $anchor_type_id = '';
        $anchor_display_id = '';
      }

      $account = \Drupal\user\Entity\User::load($value->uid);
      if($account){
        $url = Url::fromRoute('entity.user.canonical', array('user' => $account->id()));
        $linkusername = Link::fromTextAndUrl(t($account->getUsername()), $url)->toString();
      }else{
        $linkusername = 'Anonymous';
      }

      if(count($this->getEnabledLanguage()) > 2){
        $options[$value->id] = [
          (in_array($value->type, ['node_path', 'view'])) ? $this->getOtType()[$value->type] : $error_span.'Unknown',
          $anchor_type_id,
          !empty($value->type == 'view') ? $anchor_display_id : '',
          $value->title,
          (in_array($value->location, ['both', 'ui', 'source'])) ? $this->getOtLocation()[$value->location] : $error_span.'Unknown',
          (in_array($value->status, [0, 1])) ? $status[$value->status] : $error_span.'Unknown',
          !empty($this->getEnabledLanguage()[$value->language]) ? $this->getEnabledLanguage()[$value->language] : $error_span.'Unknown',
          $linkusername,
          \Drupal::service('date.formatter')->format($value->changed, 'custom', 'd/m/Y - H:i'),
          \Drupal::service('date.formatter')->format($value->created, 'custom', 'd/m/Y - H:i'),
          array('data'=> $drop_btn)
        ];
      }
      else{
        $options[$value->id] = [
          (in_array($value->type, ['node_path', 'view'])) ? $this->getOtType()[$value->type] : $error_span.'Unknown',
          $anchor_type_id,
          !empty($value->type == 'view') ? $anchor_display_id : '',
          $value->title,
          (in_array($value->location, ['both', 'ui', 'source'])) ? $this->getOtLocation()[$value->location] : $error_span.'Unknown',
          (in_array($value->status, [0, 1])) ? $status[$value->status] : $error_span.'Unknown',
          $linkusername,
          \Drupal::service('date.formatter')->format($value->changed, 'custom', 'd/m/Y - H:i'),
          \Drupal::service('date.formatter')->format($value->created, 'custom', 'd/m/Y - H:i'),
          array('data'=> $drop_btn)
        ];
      }
    }

    return array($header, @$options);
  }

  public function getOtByIdMultiple($id_arr)
  {
    $select = db_select('override_title', 'ot')
      ->fields('ot', array('id'))
      ->condition('id', $id_arr, 'IN')
      ->execute()
      ->fetchCol();
    return $select;
  }

  public function OtDeleteMultiple($modify_ot_arr_val)
  {
    $delete = db_delete('override_title')
      ->condition('id', $modify_ot_arr_val, 'IN')
      ->execute();
    return $delete;
  }

  public function changeOtStatus($modify_ot_arr_val, $status)
  {
    $update = db_update('override_title')
      ->fields([
        'status'=> $status,
        'changed'=> REQUEST_TIME,
      ])
      ->condition('id', $modify_ot_arr_val, 'IN')
      ->execute();
    return $update;
  }

  public function changeOtLocation($modify_ot_arr_val, $action_ot)
  {
    $update = db_update('override_title')
      ->fields([
        'location'=> $action_ot,
        'changed'=> REQUEST_TIME,
      ])
      ->condition('id', $modify_ot_arr_val, 'IN')
      ->execute();
    return $update;
  }

  public function ReturnOtTitle($language_arr, $url)
  {
    $select = db_select('override_title', 'ot')
      ->fields('ot', array('id', 'type', 'language', 'type_id', 'display_id', 'title', 'location'))
      ->condition('language', $language_arr, 'IN')
      ->condition('status', 1)
      ->execute()
      ->fetchAll();
    if($select){
      foreach ($select as $key => $value) {
        if($value->type == 'node_path'){
          $url_object = \Drupal::service('path.validator')->getUrlIfValid($value->type_id);
          if($url_object != false){
            $url_object->setAbsolute();
            $url_build = $url_object->toString();
            if($url_build == $url){
              if($value->language == $language_arr[0]){
                $find_all[] = array('title'=> $value->title, 'location'=> $value->location);
              }
              if($value->language == $language_arr[1]){
                $find_other[] = array('title'=> $value->title, 'location'=> $value->location);
              }
            }
          }
        }
        else if($value->type == 'view'){
          $display_return = $this->getDisplayKeyOfView($value->type_id);
          if(!empty($display_return) && in_array($value->display_id, $display_return)){
            $url_build = Url::fromRoute('view.'.$value->type_id.'.'.$value->display_id, array(), array("absolute" => TRUE))->toString();
            if($url_build == $url){
              if($value->language == $language_arr[0]){
                $find_all[] = array('title'=> $value->title, 'location'=> $value->location);
              }
              if($value->language == $language_arr[1]){
                $find_other[] = array('title'=> $value->title, 'location'=> $value->location);
              }
            }
          }
        }
      }

      if(!empty($find_other)){
        foreach ($find_other as $key => $value) {
          return array('title'=> $value['title'], 'location'=> $value['location']);
          break;
        }
      }else if(!empty($find_all)){
        foreach ($find_all as $key => $value) {
          return array('title'=> $value['title'], 'location'=> $value['location']);
          break;
        }
      }

    }
  }

  public function cronCheckUrl()
  {
    $select = db_select('override_title', 'ot')
      ->fields('ot', array('id', 'language', 'type', 'type_id', 'display_id'))
      ->execute()
      ->fetchAll();
    if($select){
      $del_arr = [];
      foreach ($select as $key => $value) {
        if($value->type == 'node_path'){
          $url_object = \Drupal::service('path.validator')->getUrlIfValid($value->type_id);
          if($url_object == false || !in_array($value->language, $this->checkEnabledLanguage())){
            $del_arr[] = $value->id;
          }
        }
        else if($value->type == 'view'){
          $display_return = $this->getDisplayKeyOfView($value->type_id);
          if(empty($display_return) || !in_array($value->display_id, $display_return)){
            $del_arr[] = $value->id;
          }
        }else{
          $del_arr[] = $value->id;
        }
      }

      if(!empty($del_arr)){
        $delete = db_delete('override_title')
          ->condition('id', $del_arr, 'IN')
          ->execute();
      }
    }
  }

}
