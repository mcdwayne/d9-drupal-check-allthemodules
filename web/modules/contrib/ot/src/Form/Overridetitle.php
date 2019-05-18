<?php
namespace Drupal\ot\Form;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;
use \Drupal\views\Views;
use Drupal\Component\Utility\Xss;
use Drupal\ot\Controller\OverrideMain;

class Overridetitle extends FormBase
{

  public function __construct()
  {
    $this->ot_main = new OverrideMain();
  }

  public function getFormId()
  {
    return 'override_title';
  }

  public function buildForm(array $form, FormStateInterface $form_state)
  {
    $form['#attached']['library'][] = 'ot/ot.lib';
    $param = \Drupal::request()->query->all();

    /**
     * Filter START
     */

    $form['ot_inline'] = array(
      '#prefix'=> '<div class="form--inline clearfix">',
      '#suffix'=> '</div>'
    );

    $form['ot_inline']['ot_title'] = array(
      '#type' => 'textfield',
      '#title'=> t('Title'),
      '#size'=> 30,
      '#default_value'=> !empty($param['title'])? $param['title'] : ''
    );

    $type = ['all'=>'- Any -']+$this->ot_main->getOtType();
    $form['ot_inline']['ot_type'] = array(
      '#type' => 'select',
      '#title'=> t('Type'),
      '#options'=> $type,
      '#default_value'=> !empty($param['type'])? $param['type'] : 'all'
    );

    $form['ot_inline']['ot_status'] = array(
      '#type' => 'select',
      '#title'=> t('Status'),
      '#options'=> ['all'=> '- Any -', 1=> t('Published'), 2=> t('Unpublished')],
      '#default_value'=> !empty($param['status'])? $param['status'] : 'all'
    );

    if(count($this->ot_main->getEnabledLanguage()) > 2){
      $form['ot_inline']['ot_language'] = array(
        '#type'=> 'select',
        '#title'=> t('Language'),
        '#options'=> $this->ot_main->getEnabledLanguage(),
        '#default_value'=> !empty($param['lang'])? $param['lang'] : 'all',
      );
    }

    $form['ot_inline']['actions'] = array(
      '#type' => 'actions'
    );

    $form['ot_inline']['actions']['ot_filter'] = array(
      '#type'=> 'submit',
      '#name'=> 'filterot',
      '#value'=> t('Filter'),
      '#submit'=> array([$this, 'filterOtData'])
    );

    if($param){
      $form['ot_inline']['actions']['ot_reset'] = array(
        '#type'=> 'submit',
        '#name'=> 'resetot',
        '#value'=> t('Reset'),
        '#submit'=> array([$this, 'resetOtData'])
      );
    }

    /**
     * Filter END
     */


    $form['ot_action'] = array(
      '#type'=> 'select',
      '#title'=> t('Action'),
      '#options'=> $this->ot_main->getOtAction()
    );

    $form['submit'] = array(
      '#type'=> 'submit',
      '#value'=> t('Apply to selected items'),
      '#prefix'=> '<div class="form-item">',
      '#suffix'=> '</div>',
      '#attributes'=> array('class'=> ['ot-del'], 'onclick'=> 'return otDelete();'),
    );

    $form['markup']= array(
      '#type'=> 'markup',
      '#title'=> t('From'),
      '#markup'=> '<a href="#" id="ot-show-hide">#Show items</a>',
    );

    $data = $this->ot_main->OtGetSortedData();

    $form['ot_modify_multiple'] = array(
      '#type' => 'tableselect',
      '#header' => $data[0],
      '#options' => !empty($data[1]) ? $data[1] : [],
      '#empty' => t('No content available.'),
    );

    $form['pager'] = array(
      '#type' => 'pager'
    );

    return $form;
  }

  public function validateForm(array &$form, FormStateInterface $form_state)
  {
    if($form_state->getValue('filterot') || $form_state->getValue('resetot')){
      $type = array('all');
      $type = array_merge($type, $this->ot_main->checkOtType());
      if(!in_array($form_state->getValue('ot_type'), $type)){
        $form_state->setErrorByName('ot_type', t('Type field - An illegal choice has been detected. Please contact the site administrator.'));
      }

      if(!in_array($form_state->getValue('ot_status'), ['all',1,2])){
        $form_state->setErrorByName('ot_status', t('Status - An illegal choice has been detected. Please contact the site administrator.'));
      }

      if(count($this->ot_main->getEnabledLanguage()) > 2){
        $ot_language = $form_state->getValue('ot_language');
        if(!in_array($ot_language, $this->ot_main->checkEnabledLanguage())){
          $form_state->setErrorByName('ot_language', t('Language field - An illegal choice has been detected. Please contact the site administrator.'));
        }
      }
    }
    else {
      $action_ot = $form_state->getValue('ot_action');
      $modify_ot = $form_state->getValue('ot_modify_multiple');
      $modify_ot_arr = array_diff($modify_ot, [0]);
      $modify_ot_check = implode('', $modify_ot_arr);
      $modify_ot_arr_val = array_values($modify_ot_arr);

      if(empty($modify_ot_arr)){
        $form_state->setErrorByName('ot_modify_multiple', t('No Override Title content selected.'));
      }
      else if(!is_numeric($modify_ot_check)){
        $form_state->setErrorByName('ot_modify_multiple', t('An illegal choice has been detected. Please contact the site administrator.'));
      }
      else{
        $empty_check = array_diff($modify_ot_arr_val, $this->ot_main->getOtByIdMultiple($modify_ot_arr_val));
        if(!empty($empty_check)){
          $form_state->setErrorByName('ot_modify_multiple', t('An illegal choice has been detected. Please contact the site administrator.'));
        }
      }

      if(!in_array($action_ot, $this->ot_main->checkOtAction())){
        $form_state->setErrorByName('ot_action', t('An illegal choice has been detected. Please contact the site administrator.'));
      }
    }
  }

  public function submitForm(array &$form, FormStateInterface $form_state)
  {
    $action_ot = $form_state->getValue('ot_action');
    $modify_ot = $form_state->getValue('ot_modify_multiple');
    $modify_ot_arr = array_diff($modify_ot, [0]);
    $modify_ot_arr_val = array_values($modify_ot_arr);
    $s = (count($modify_ot_arr_val) >1) ? ' items.' : ' item.';
    if(in_array($action_ot, ['ot_delete'])){
      $success = $this->ot_main->OtDeleteMultiple($modify_ot_arr_val);
      if($success){
        drupal_set_message(t('Deleted '.count($modify_ot_arr_val).' Override Title content'.$s.'.'), 'status');
      }
    }
    else if(in_array($action_ot, ['ot_active', 'ot_deactive'])){
      $status = ['ot_active'=> 1, 'ot_deactive'=> 0];
      $status_string = ['ot_active'=> 'Published', 'ot_deactive'=> 'UnPublished'];
      $success = $this->ot_main->changeOtStatus($modify_ot_arr_val, $status[$action_ot]);
      if($success){
        drupal_set_message(t($status_string[$action_ot].' Override Title content was applied to '.count($modify_ot_arr_val).$s), 'status');
      }
    }
    else if(in_array($action_ot, $this->ot_main->checkOtLocation())){
      $success = $this->ot_main->changeOtLocation($modify_ot_arr_val, $action_ot);
      if($success){
        drupal_set_message(t('Location change of Override Title content was applied to '.count($modify_ot_arr_val).$s), 'status');
      }
    }

    if(!$success){
      drupal_set_message(t('Your process could not completed. Please try again.'), 'error');
    }

    $url = Url::fromRoute('override.ot');
    $form_state->setRedirectUrl($url);
  }

  public function filterOtData(array &$form, FormStateInterface $form_state)
  {
    $query = [
      'title'=> Xss::filter($form_state->getValue('ot_title')),
      'type'=> $form_state->getValue('ot_type'),
      'status'=> $form_state->getValue('ot_status'),
      'lang'=> (!$form_state->getValue('ot_language')) ? 'all' : $form_state->getValue('ot_language')
    ];

    $url = Url::fromRoute('override.ot', $query);
    $form_state->setRedirectUrl($url);
  }

  public function resetOtData(array &$form, FormStateInterface $form_state)
  {
    $url = Url::fromRoute('override.ot');
    $form_state->setRedirectUrl($url);
  }

}
