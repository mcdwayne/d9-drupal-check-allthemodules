<?php

namespace Drupal\twinesocial\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\Core\Url;

/**
 * Class TwinesocialSettingsForm.
 */
class TwinesocialSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'twinesocial.twinesocialsettings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'twinesocial_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('twinesocial.twinesocialsettings');

    $account_id = \Drupal::request()->query->get('account_id');
    $details = $this->getAccountDetails($account_id);

    $form_app = array();
    foreach ($details->apps as $key => $app) {
      $form_app['apps'][$app->baseUrl] = $this->t($app->name);
      foreach ($app->collections as $app_key => $collection) {
        if(strtolower($collection->name)==='all')
          $collection->id = 0;
        $form_app['collections'][$key][$collection->id] = 'Only display posts from my "'.$collection->name.'" collection.';
      }
    }
    foreach ($details->themes as $key => $theme) {
      $form_app['theme'][$theme->name] = $this->t("<img width='180px;' src='https:".$theme->thumbnail."'>")."<br><b>".$this->t($theme->title)."</b>";
    }
    foreach ($details->languages as $key => $language) {
      $form_app['language'][$language->culture] = $this->t($language->name);
    }
    foreach ($details->colors as $key => $color) {
      $form_app['colors'][$color->name] = $this->t($color->title);
    }
    $form['#attached']['library'][] = 'twinesocial/twinesocial-library';

    $url = \Drupal::url('twinesocial.twinesocial_admin_settings_form', ['twinesocial_reset' => 1]);


    $ret_val = '<h3>'.t('Include TwineSocial on a Drupal Page').'</h3>';
    $ret_val .= '<ul>';
    $ret_val .= '<li>'.t('Select the given options below and click on Create Code button to generate TwineSocial embed code.').'</li>';
    $ret_val .= '<li>'.t('Copy the embed code and then go to Content->Add Content and then select the type of content type you wish to create.').'</li>';
    $ret_val .= '<li>'.t('In the textbox select Text Format as "Full HTML".').'</li>';
    $ret_val .= '<li>'.t('Go to SOURCE and paste the embed code where you want to show TwineSocial on your page.').'</li>';
    $ret_val .= '</ul>';

    $form['heading_text'] = [
      '#type' => 'markup',
      '#markup' => '<p class="twine_header">Hi User, Your Account ID is '.$account_id.'. Below are your account campaigns, collections, theme options and other details. Please select the options you want and then click on create code button to generate iFrame code for your selection.<br>If you want to deactive your Twinesocial account from this module click on the deactive button below.</p><a class="button" href="'.$url.'">Deactivate</a><br>'.$ret_val.'<br>For more details go to Help->TwineSocial, it contains a lot of information about Twinesocial, drupal module instructions and support links.<hr>',
    ];
    $form['message'] = [
      '#type' => 'markup',
      '#markup' => '<br><div class="result_message"></div>',
    ];
    $form['campaign_id'] = [
      '#type' => 'select',
      '#id' => 'campaign_id',
      '#name' => 'campaign_id',
      '#title' => '<h4>'.$this->t('Campaigns').'</h4>',
      '#description' => $this->t('Choose Your Social Hub'),
      '#options' => $form_app['apps'],//['abc' => $this->t('abc'), 'def' => $this->t('def'), 'ghhi' => $this->t('ghhi')],
      '#size' => 1,
      '#default_value' => $config->get('campaign_id'),
      '#ajax' => [
        'callback' => '::setCollection',
      ],
    ];
    $form['theme'] = array(
      '#type' => 'radios',
      '#id' => 'theme_options',
      '#name' => 'theme_options',
      '#default_value' => 'classic',
      '#title' => '<h4>'.$this->t('Choose a Theme').'</h4>',
      '#options' => $form_app['theme'],
      '#attributes' => array('class' => array('container-inline')),
    );

    $form['collection'] = array(
      '#type' => 'select',
      '#id' => 'collection',
      '#name' => 'collection',
      '#title' => '<h4>'.$this->t('Show Collections').'</h4>',
      '#default_value' => $config->get('collection'),
      '#size' => 1,
      '#options' => current($form_app['collections']),
      '#prefix' => '<br><br><hr><h4>Theme Options</h4><hr>',
    );

    $form['language'] = array(
      '#type' => 'select',
      '#id' => 'language',
      '#name' => 'language',
      '#title' => '<h4>'.$this->t('Widget UI Language').'</h4>',
      '#default_value' => $config->get('language'),
      '#size' => 1,
      '#options' => $form_app['language'],
    );

    $form['color'] = array(
      '#type' => 'select',
      '#id' => 'color',
      '#name' => 'color',
      '#title' => '<h4>'.$this->t('Color Scheme').'</h4>',
      '#default_value' => $config->get('color'),
      '#size' => 1,
      '#options' => $form_app['colors'],
    );
    $form['pagesize'] = array(
      '#type' => 'select',
      '#id' => 'pagesize',
      '#name' => 'pagesize',
      '#title' => '<h4>'.$this->t('Only Show').'</h4>',
      '#default_value' => 20,
      '#size' => 1,
      '#options' => array(1=>'1 post', 5=>'5 posts', 10=>'10 posts', 20=>'20 posts (recommended)', 50=>'50 posts'),
    );

    $form['scrolloptions'] = array(
      '#type' => 'select',
      '#id' => 'scrolloptions',
      '#name' => 'scrolloptions',
      '#title' => '<h4>'.$this->t('When scrolling to bottom of your hub').'</h4>',
      '#default_value' => 2,
      '#size' => 1,
      '#options' => array(1=>'Do nothing', 2=>'Auto-load more posts', 3=>'Show a "Load More Posts" button'),
    );

    $form['nav'] = array(
      '#type' => 'select',
      '#id' => 'nav',
      '#name' => 'nav',
      '#title' => '<h4>'.$this->t('Site Navigation').'</h4>',
      '#description' => $this->t('Enable navigation tabstrip at the top of your hub.'),
      '#default_value' => 'no',
      '#size' => 1,
      '#options' => array('no'=>'Hide', 'yes'=>'Show'),
    );

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Create Code'),
      '#ajax' => [
        'callback' => '::createCode',
      ],
    ];
    $form['message2'] = [
      '#type' => 'markup',
      '#markup' => '<div class="result_message"></div>',
      '#prefix' => '<br><br>',
    ];


    //drupal_add_css(drupal_get_path('module', 'twinesocial') . 'css/theme/twinesocial.css');
    //return parent::buildForm($form, $form_state);
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    //parent::submitForm($form, $form_state);

    //$this->config('twinesocial.twinesocialsettings')
      //->set('collection_id', $form_state->getValue('collection_id'))
    //  ->set('campaign_id', $form_state->getValue('campaign_id'))
    //  ->save();
  }

  public function setCollection(array $form, FormStateInterface $form_state) {

    $response = new AjaxResponse();
    $account_id = \Drupal::request()->query->get('account_id');
    $details = $this->getAccountDetails($account_id);
    foreach ($details->apps as $key => $app) {
      if($form_state->getValue('campaign_id')==$app->baseUrl){
        foreach ($app->collections as $app_key => $collection) {
          if(strtolower($collection->name)==='all')
            $collection->id = 0;
          $html_content[] = "<option value='".$collection->id."'>Only display posts from my '".$collection->name."' collection.</option>";
          $new_collections[$collection->id] = 'Only display posts from my "'.$collection->name.'" collection.';
        }
      }
    }
    $response->addCommand(
      new HtmlCommand(
        '#collection',
        join($html_content))
    );
    return $response;;
  }
  public function createCode(array $form, FormStateInterface $form_state) {

    $response = new AjaxResponse();

    $scrolloptions = $form_state->getValue('scrolloptions');
    $scrollText = "";
    if($scrolloptions == 1){
      $scrollText = "&autoload=no";
    }elseif ($scrolloptions == 3) {
      $scrollText = "&showLoadMore=yes&autoload=no";
    }
    $navText = "";
    if($form_state->getValue('nav')=='yes'){
      $navText = "&showNav=yes";
    }
    $pageSizeText = "";
    if($form_state->getValue('pagesize')!=20){
      $pageSizeText = "&pagesize=".$form_state->getValue('pagesize');
    }
    $collectionText = "";
    if($form_state->getValue('collection')!=0){
      $collectionText = "&collection=".$form_state->getValue('collection');
    }
    $languageTxt = "";
    if(strtolower($form_state->getValue('language'))!='en'){
      $languageTxt = "&lang=".$form_state->getValue('language');
    }

    $themeLayout = "";
    if(strtolower($form_state->getValue('theme'))!='classic'){
      $themeLayout = "&theme-layout=".$form_state->getValue('theme');
    }

    $themeColor = "";
    if(strtolower($form_state->getValue('color'))!='white'){
      $themeColor = "&theme-color=".$form_state->getValue('color');
    }

    $navText = "";
    if(strtolower($form_state->getValue('nav'))=='yes')
      $navText = '&showNav=yes';

    $script_url = 'https://apps.twinesocial.com/embed?app='.$form_state->getValue('campaign_id').$collectionText;
    $script_url .= $navText.$scrollText.$themeLayout.$themeColor.$languageTxt;
    $script_url .= $navText.$pageSizeText;


    $html_content = '<div class="twine_social_result">';
    $html_content .= '<h4>'."Twinesocial Embed Code".'</h4>';
    $html_content .= '&lt;script id="twine-script" src="'.$script_url.'"&gt;&lt;/script&gt;';
    $html_content .= '</div>';

    $response->addCommand(
      new HtmlCommand(
        '.result_message',
        $html_content)
    );
    $response->addCommand(
      new HtmlCommand(
        '.result_message2',
        $html_content)
    );
    return $response;;
  }

  public function getAccountDetails($account_id) {
    $url = 'https://apps.twinesocial.com/api/v1?method=accountinfo&accountId='.$account_id;
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_URL,$url);
    $result=curl_exec($ch);
    $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpcode == 200) {
      return json_decode($result);
    }
    else{
      return false;
    }
  }

}
