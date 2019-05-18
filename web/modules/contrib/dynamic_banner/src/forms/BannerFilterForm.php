<?php
namespace Drupal\dynamic_banner\forms;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Component\Utility\Html;
use Drupal\Core\Url;
use Drupal\Core\Database\Database;
//dynamic_banneruse Drupal\formbuilder\forms\FormBuilderModel; 

class BannerFilterForm extends FormBase {

  public function getFormID() {
    return 'frm_bannerfilterform';
  }

  public function buildForm(array $form, FormStateInterface $form_state){
  $_SESSION = array();
  $filters = self::dynamic_banner_filters();
  
  $form['filters'] = array(
    '#type'        => 'fieldset', 
    '#title'       => t('Filter dynamic banner'), 
    '#collapsible' => TRUE, 
    '#collapsed'   => empty($_SESSION['dynamic_banner_filter']),
  );
  if(!empty($filters)){
      foreach ($filters as $key => $filter) {
        $form['filters']['status'][$key] = array(
          '#title'    => $filter['title'], 
          '#type'     => 'select', 
          '#multiple' => TRUE, 
          '#size'     => 8, 
          '#options'  => $filter['options'],
        );
        if (!empty($_SESSION['dynamic_banner_filter'][$key])) {
          $form['filters']['status'][$key]['#default_value'] = $_SESSION['dynamic_banner_filter'][$key];
        }
      }

  }
  
  $form['filters']['actions'] = array(
    '#type'       => 'actions', 
    '#attributes' => array('class' => array('container-inline')),
  );
  $form['filters']['actions']['submit'] = array(
    '#type'  => 'submit', 
    '#value' => t('Filter'),
  );
  if (!empty($_SESSION['dynamic_banner_filter'])) {
    $form['filters']['actions']['reset'] = array(
      '#type' => 'button',
      '#button_type' => 'reset',
      '#value' => t('Clear -'),
      '#weight' => 9,
      '#validate' => array(),
      '#attributes' => array(
            'onclick' => 'this.form.reset(); return false;',
          ),
    );

  }

  return $form;

 }

/**
 * Validate result from dynamic banner administrative filter form.
*/
public function validateForm(array &$form, FormStateInterface $form_state) {
  /*
  if ($form_state->getValue('op') == t('Filter') && empty($form_state->getValue('type'))) {
    form_set_error('type', t('You must select something to filter by.'));
   }*/
} 

/**
*  Process filter form submission
*/
  public function submitForm(array &$form, FormStateInterface $form_state) {
      /*$op = $form_state->getValue('op');
      $filters = dblog_filters();
      switch ($op) {
        case t('Filter'):
          foreach ($filters as $name => $filter) {
            if (isset($form_state->getValue($name))) {
              $_SESSION['dynamic_banner_filter'][$name] = $form_state->getValue($name);
            }
          }
          break;
        case t('Reset'):
          $_SESSION['dynamic_banner_filter'] = array();
          break;
      }*/
      $form_state->setRedirect('cdb.listbanners');
  } 


/**
 * The specific filters that can be used for banners
 */
public static function dynamic_banner_filters() {
  $filters = array();
  $filters['type'] = array(
    'title'   => t('Type'),
    'where'   => 'd.url ?',
    'options' => array('NOT LIKE %* AND NOT LIKE %!','LIKE %*','LIKE %!'));
  return $filters;
}
}