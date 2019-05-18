<?php
namespace Drupal\ot\Form;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\Component\Utility\Xss;
use Drupal\ot\Controller\OverrideMain;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class Edit extends FormBase
{

  public function __construct()
  {
    $this->ot_main = new OverrideMain();
    $current_path = \Drupal::request()->getRequestUri();
    $current_path_arr = explode('/', $current_path);
    $keys_arr = array_keys($current_path_arr);
    $end_key = end($keys_arr);
    $this->id = $current_path_arr[$end_key-1];
    $this->default = $this->ot_main->getOtById($this->id);
    if($this->default['type'] == 'view'){
      $display_return = $this->ot_main->getDisplayOfView($this->default['type_id']);
      if($display_return){
        $this->options = array('' => '- Select -')+$display_return;
      }
    }
  }

  public function getFormId()
  {
    return 'override_title_edit';
  }

  public function buildForm(array $form, FormStateInterface $form_state)
  {
    if(!$this->ot_main->getOtById($this->id)){
      throw new NotFoundHttpException();
    }
    $form['#attached']['library'][] = 'ot/ot.lib';

    if(count($this->ot_main->getEnabledLanguage()) > 2){
      $form['ot_language'] = array(
        '#type'=> 'select',
        '#title'=> t('Language'),
        '#options'=> $this->ot_main->getEnabledLanguage(),
        '#default_value'=> $this->default['language'],
        '#required'=> true,
      );
    }

    $form['ot_type'] = array(
      '#type'=> 'radios',
      '#title'=> t('Type'),
      '#options'=> $this->ot_main->getOtType(),
      '#description'=> t('Check one options from above for the type of pages.'),
      '#default_value'=> $this->default['type'],
      '#required'=> true
    );

    $form['ot_type_id'] = array(
      '#type'=> 'textfield',
      '#title'=> ($this->default['type'] == 'view') ? 'Views' : ($form_state->getValue('ot_type') == 'view') ? 'Views' : t('Node and Path/URL'),
      '#default_value'=> $this->default['type_id'],
      '#description'=> t("You can enter an internal path such as /node/add or Views machine_name as 'content'. Enter / or /node for the front page. Do not use &lt;front&gt;."),
      '#required'=> true,
      '#ajax'=> array(
        'callback'=> '::otTypeExists',
        'effect' => 'fade',
        'event'=> 'change',
        'progress' => array(
          'type' => 'throbber',
          'message' => t('Collecting information...'),
        ),
      )
    );

    $ot_display_title = !empty($form_state->getValue('ot_type_id')) ? $form_state->getValue('ot_type_id') : ($this->default['type'] == 'view') ? $this->default['type_id'] : '';
    $form['ot_display'] = array(
      '#type'=> 'select',
      '#title'=> t('Views dispaly of: '.$ot_display_title),
      '#options'=> $this->ot_main->getDisplayOfView($this->default['type_id']),
      '#default_value'=> !empty($this->default['display_id'])? $this->default['display_id'] :'',
      '#empty_options'=> t('- Select display -'),
      '#required'=> ($form_state->getValue('ot_type') == 'view') ? true : ($this->default['type'] == 'view') ? true : false,
      '#validated' => true,
      '#states'=> array(
        'visible'=> array(
          'input[name="ot_type"]'=> array('value'=> 'view'),
          'input[name="ot_type_id"]'=> array('filled' => TRUE),
        ),
        'disabled'=> array(
          'input[name="ot_type"]'=> array('value'=> 'node_path')
        )
      ),
      '#prefix'=> '<div id="ot-type-id-exists">',
      '#suffix'=> '</div>',
    );

    $form['ot_title'] = array(
      '#type'=> 'textfield',
      '#title'=> t('Override Title'),
      '#required'=> true,
      '#default_value'=> $this->default['title'],
    );

    $form['ot_location'] = array(
      '#type'=> 'radios',
      '#title'=> t('Action Location'),
      '#description'=> t('Check one options from above to show the override title.'),
      '#options'=> $this->ot_main->getOtLocation(),
      '#default_value'=> $this->default['location'],
      '#required'=> true
    );

    $form['ot_status'] = array(
      '#type'=> 'checkbox',
      '#title'=> t('Published'),
      '#attributes'=> array(
        'checked'=> ($this->default['status'] == 1) ? true : false
      )
    );

    $form['actions'] = array(
      '#type' => 'actions'
    );

    $form['actions']['ot_submit'] = array(
      '#type'=> 'submit',
      '#value'=> t('Submit'),
      '#attributes'=> array(
        'class'=> array('button button--primary')
      )
    );

    $form['actions']['ot_delete'] = array(
      '#type'=> 'link',
      '#title'=> t('Delete'),
      '#url' => Url::fromRoute('override.delete', ['id'=> $this->id]),
      '#attributes'=> array(
        'class'=> array('button button--danger')
      )
    );

    return $form;
  }

  public function otTypeExists(array &$form, FormStateInterface $form_state)
  {
    $ot_type = $form_state->getValue('ot_type');
    $ot_type_id = $form_state->getValue('ot_type_id');
    if(is_numeric($ot_type) || !in_array($ot_type, $this->ot_main->checkOtType())){
      $elm = '<div role="contentinfo" aria-label="Error message" class="messages messages--error"><div role="alert"><h2 class="visually-hidden">Error message</h2> Type field - An illegal choice has been detected. Please contact the site administrator.</div></div>';
    }else{
      if(empty($ot_type_id)){
        $elm = '<div role="contentinfo" aria-label="Error message" class="messages messages--error"><div role="alert"><h2 class="visually-hidden">Error message</h2> Node and Path/URL field - Cannot be empty.</div></div>';
      }
      else if($ot_type == 'node_path'){
        $url_object = \Drupal::service('path.validator')->getUrlIfValid($ot_type_id);
        $url_external = \Drupal\Component\Utility\UrlHelper::isExternal($ot_type_id);
        if(($url_object != FALSE) && ($url_external == FALSE)) {
          $url_object->setAbsolute();
          $url = $url_object->toString();
          $elm = '<div role="contentinfo" aria-label="Status message" class="messages messages--status"><h2 class="visually-hidden">Status message</h2> Node and Path/URL field - "'.$url.'" is a valid Path/URL.</div>';
        }else{
          $elm = '<div role="contentinfo" aria-label="Error message" class="messages messages--error"><div role="alert"><h2 class="visually-hidden">Error message</h2> Node and Path/URL field - "'.$ot_type_id.'" is not a valid Path/URL.</div></div>';
        }
      }else if($ot_type == 'view'){
        $display_return = $this->ot_main->getDisplayOfView($ot_type_id);
        if(!empty($display_return)){
          $display_return = array('' => '- Select -')+$display_return;
          $form['ot_display']['#title'] = 'Views dispaly of: '.$ot_type_id;
          $form['ot_display']['#options'] = $display_return;
          $form['ot_display']['#required'] = true;
          $form['ot_display']['#prefix'] = '';
          $form['ot_display']['#suffix'] = '';
          $elm = drupal_render($form['ot_display']);
        }else{
          $elm = '<div role="contentinfo" aria-label="Error message" class="messages messages--error"><div role="alert"><h2 class="visually-hidden">Error message</h2> Views field - machine_name: "'.$ot_type_id.'" does not exist or not have any dispaly page.</div></div>';
        }
      }
    }

    $ajaxresponse = new AjaxResponse();
    $ajaxresponse->addCommand(new HtmlCommand('#ot-type-id-exists', $elm));
    return $ajaxresponse;
  }

  public function validateForm(array &$form, FormStateInterface $form_state)
  {
    $ot_type = $form_state->getValue('ot_type');
    $ot_type_id = $form_state->getValue('ot_type_id');
    $ot_display = @$form_state->getValue('ot_display');
    $ot_title = @$form_state->getValue('ot_title');
    $ot_location = $form_state->getValue('ot_location');
    $language = !empty($form_state->getValue('ot_language')) ? $form_state->getValue('ot_language') : 'all';

    if(count($this->ot_main->getEnabledLanguage()) > 2){
      $ot_language = $form_state->getValue('ot_language');
      if(empty($ot_language)){
        $form_state->setErrorByName('ot_language', t('Language field - Cannot be empty.'));
      }else{
        if(!in_array($ot_language, $this->ot_main->checkEnabledLanguage())){
          $form_state->setErrorByName('ot_language', t('Language field - An illegal choice has been detected. Please contact the site administrator.'));
        }
      }
    }

    if(empty($ot_type)){
      $form_state->setErrorByName('ot_type', t('Type field - Cannot be empty.'));
    }else{
      if(!in_array($ot_type, $this->ot_main->checkOtType())){
        $form_state->setErrorByName('ot_type', t('Type field - An illegal choice has been detected. Please contact the site administrator.'));
      }
    }

    if(empty($ot_type_id)){
      $form_state->setErrorByName('ot_type_id', t('Node and Path/URL or Views field - Cannot be empty.'));
    }

    if(empty($ot_title)){
      $form_state->setErrorByName('ot_title', t('Override Title field - Cannot be empty.'));
    }

    if(empty($ot_location)){
      $form_state->setErrorByName('ot_location', t('Action Location field - Cannot be empty.'));
    }else{
      if(!in_array($ot_location, $this->ot_main->checkOtLocation())){
        $form_state->setErrorByName('ot_location', t('Action Location field - An illegal choice has been detected. Please contact the site administrator.'));
      }
    }

    if($ot_type == 'node_path'){
      $url_restrict = ['<front>', 'admin/structure/ot'];
      foreach ($url_restrict as $key => $value) {
        if (strpos($ot_type_id, $value) !== false) {
          $form_state->setErrorByName('ot_type_id', t('Node and Path/URL field - URL given cannot be override.'));
          break;
        }
      }

      $url_object = \Drupal::service('path.validator')->getUrlIfValid($ot_type_id);
      $url_external = \Drupal\Component\Utility\UrlHelper::isExternal($ot_type_id);
      if(($url_object == false) || ($url_external == true)) {
        $form_state->setErrorByName('ot_type_id', t('Node and Path/URL - entered is not valid.'));
      }
      else{
        $url_object->setAbsolute();
        $url = $url_object->toString();
        $ot_title_return = $this->ot_main->checkOverrideTitle($language, $url);
        if(!empty($ot_title_return['id']) && $ot_title_return['id'] != $this->id){
          $form_state->setErrorByName('ot_type_id', t('Node and Path/URL - title already been override. '.$ot_title_return['link']));
        }
      }
    }
    else if($ot_type == 'view'){
      if(empty($ot_display)){
        $form_state->setErrorByName('ot_display', t('Views dispaly field - Cannot be empty.'));
      }else{
        $display_return = $this->ot_main->getDisplayOfView($ot_type_id);
        if(empty($display_return)){
          $form_state->setErrorByName('ot_type_id', t('Views field - Does not exists or have no display page.'));
        }

        if(!empty($display_return) && !empty($ot_display)){
          $url = Url::fromRoute('view.'.$ot_type_id.'.'.$ot_display, array(), array("absolute" => TRUE))->toString();
          $ot_title_return = $this->ot_main->checkOverrideTitle($language, $url);
          if(!empty($ot_title_return['id']) && $ot_title_return['id'] != $this->id){
            $form_state->setErrorByName('ot_type_id', t('Views field - title already been override for the display. '.$ot_title_return['link']));
          }
        }
        $form['ot_display']['#title'] = 'Views dispaly of: '.$ot_type_id;
        if(!empty($display_return)){
          $display_return = array('' => '- Select -')+$display_return;
          $form['ot_display']['#options'] = $display_return;
          $form['ot_display']['#default_value'] = $ot_display;
        }
      }
    }
  }

  public function submitForm(array &$form, FormStateInterface $form_state)
  {
    $display_id = ($form_state->getValue('ot_type') == 'view') ? $form_state->getValue('ot_display') : null;
    $language = !empty($form_state->getValue('ot_language')) ? $form_state->getValue('ot_language') : 'all';
    $user = \Drupal::service('current_user')->id();
    try{
      db_update('override_title')
      ->fields([
        'type'=> $form_state->getValue('ot_type'),
        'uid'=> !empty($user) ? $user : 0,
        'language'=> $language,
        'type_id'=> $form_state->getValue('ot_type_id'),
        'display_id'=> $display_id,
        'title' => Xss::filter($form_state->getValue('ot_title')),
        'location' => $form_state->getValue('ot_location'),
        'status' => $form_state->getValue('ot_status'),
        'changed'=> REQUEST_TIME,
      ])
      ->condition('id', $this->id)
      ->execute();
      if($form_state->getValue('ot_type') == 'node_path'){
        drupal_set_message(t('Node and Path/URL: '.$form_state->getValue('ot_title'). '(language: '.$this->ot_main->getEnabledLanguage()[$language].'), title has been override.'), 'status');
      }else{
        drupal_set_message(t('Views: '.$form_state->getValue('ot_title'). '(language: '.$this->ot_main->getEnabledLanguage()[$language].'), title has been override.'), 'status');
      }

      $url = Url::fromRoute('override.ot');
      $form_state->setRedirectUrl($url);
    }
    catch (Exception $e){
      if($form_state->getValue('ot_type') == 'node_path'){
        drupal_set_message(t('Node and Path/URL: '.$form_state->getValue('ot_title'). '(language: '.$this->ot_main->getEnabledLanguage()[$language].'), title cannot be override. Please try again!'), 'error');
      }else{
        drupal_set_message(t('Views: '.$form_state->getValue('ot_title'). '(language: '.$this->ot_main->getEnabledLanguage()[$language].'), title cannot be override. Please try again!'), 'error');
      }
    }
  }
}
