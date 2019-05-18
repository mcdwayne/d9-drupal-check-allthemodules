<?php


namespace Drupal\blazemeter\Form;
use Drupal\blazemeter\Entity\BlazemeterUser;
use Drupal\blazemeter\Entity\BlazmeterUser;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\OpenModalDialogCommand;
use Drupal\Core\Cache\MemoryBackend;
use Drupal\Core\Database\Database;
use Drupal\Core\Entity\Entity;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\KeyValueStore\KeyValueMemoryFactory;
use Drupal\Core\Path\CurrentPathStack;
use Drupal\Core\Routing\RouteProvider;
use Drupal\Core\State\State;
use GuzzleHttp;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\RequestStack;

class BlazeMeterSettingsForm extends FormBase {

  private $blazemeter_url = 'https://a.blazemeter.com';
  private $blazemeter_api_key = '10c51410c51410c51422';

  public function getFormId() {
    return 'blazemeter_settings_form';
  }

  public function buildForm(array $form, FormStateInterface $form_state) {
    global $base_url;
    $form['#title'] = array(
      '#type' => 'markup',
      '#markup' => '<div id="blazemeter-strip"><img src="' . base_path() . drupal_get_path(
          'module',
          'blazemeter'
        ) . '/logo.png"/></div>'
    );
    $form['#attached']['library'][] = 'core/drupal.dialog.ajax';
    $form['#attached']['library'][] = 'blazemeter/blazemeter';
    $config = \Drupal::configFactory()->getEditable('blazemeter.settings');
    $form['subtitle'] = array(
      '#markup' => 'Saving the form for first time can take few seconds to minutes as the virtual users are being created.'
    );
    
    if($config->get('user_key')){
      try {
	   	$client = new GuzzleHttp\Client();
		$res = $client->request('GET', $this->blazemeter_get_api_url('userinfo'), ['headers' => ['X-Api-Key' => $config->get('user_key')]]);
		$user_info = $res->getBody();
		$json_user_info = json_decode($user_info);   
      } catch(GuzzleHttp\Exception\BadResponseException $e){
	      $response_body = json_decode($e->getResponse()->getBody());
	      $error = $response_body->error->message . ' ' . $response_body->error->failed[0];
	      drupal_set_message($error, 'error');
	      return;
    	}
    }
    
    $max_users = isset($json_user_info->limitations->userPlan->concurrency->max) ? $json_user_info->limitations->userPlan->concurrency->max : 5000;
    $form['max-users'] = array(
      '#type' => 'hidden',
      '#default_value' => $max_users,
      '#attributes' => array('id' => 'edit-max-users'),
    );

    $form['anonymous'] = array(
      '#type' => 'fieldset',
      '#title' => t('Anonymous Pages'),
      '#collapsible' => FALSE,
      '#collapsed' => FALSE,
    );

    $form['anonymous']['anon'] = array(
      '#type' => 'textfield',
      '#title' => t('Max Concurrent Anonymous Visitors') . ':',
      '#default_value' => $config->get('num_of_anon_users'),
      '#suffix' => '<div id="anon-slider"></div>',
    );

    $default_domain = $config->get('domain');
    if(empty($default_domain)){
      $default_domain = $base_url . '/';
    }

    $form['anonymous']['anon_pages_title'] = array(
      '#type' => 'markup',
      '#markup' => '<div class="form-item"><label>' . t('Pages') . ':</label></div>
    <div class="description">' . t('Enter the page title, node id or relative path from %domain', array('%domain' => $default_domain)) . '</div>
    ',
    );
    $form['anonymous']['anon_page'] = array(
      '#tree' => TRUE,
      '#prefix' => '<div id="anon-page-fieldset-wrapper">',
      '#suffix' => '</div>'
    );

      if(empty($config->get('anon_page_count')) || $config->get('anon_page_count') < 2){
          $config->set('anon_page_count',2)->save();
      }

      if($form_state->isRebuilding()) {
          for ($i = 0; $i < $config->get('anon_page_count'); $i++) {
              $node_id = $config->get('anon_page_' . $i);
              if($node_id) {
                  $node = \Drupal\node\Entity\Node::load($node_id);
              } else {
                  $node = null;
              }
              $form['anonymous']['anon_page'][$i] = array(
                  '#title' => '',
                  '#type' => 'entity_autocomplete',
                  '#target_type' => 'node',
                  '#default_value' => $node,
              );
          }
      } else {
          for ($k = 0; $k < $config->get('anon_page_count'); $k++) {
              $nodeID = $config->get('anon_page_' . $k);
              $node = \Drupal\node\Entity\Node::load($nodeID);
              if($node){
                  $form['anonymous']['anon_page'][$k] = array(
                      '#title' => '',
                      '#type' => 'entity_autocomplete',
                      '#target_type' => 'node',
                      '#default_value' => $node,
                  );
              }
              else{
                  $node = null;
                  $config->set('anon_page_count',$config->get('anon_page_count')-1)->save();
              }
          }if(empty($config->get('anon_page_count')) || $config->get('anon_page_count') < 2){
              $config->set('anon_page_count',2)->save();
          }

          if($form_state->isRebuilding()) {
              for ($i = 0; $i < $config->get('anon_page_count'); $i++) {
                  $node_id = $config->get('anon_page_' . $i);
                  if($node_id) {
                      $node = \Drupal\node\Entity\Node::load($node_id);
                  } else {
                      $node = null;
                  }
                  $form['anonymous']['anon_page'][$i] = array(
                      '#title' => '',
                      '#type' => 'entity_autocomplete',
                      '#target_type' => 'node',
                      '#default_value' => $node,
                  );
              }
          } else {
              for ($k = 0; $k < $config->get('anon_page_count'); $k++) {
                  $nodeID = $config->get('anon_page_' . $k);
                  $node = \Drupal\node\Entity\Node::load($nodeID);
                  if($node){
                      $form['anonymous']['anon_page'][$k] = array(
                          '#title' => '',
                          '#type' => 'entity_autocomplete',
                          '#target_type' => 'node',
                          '#default_value' => $node,
                      );
                  }
                  else{
                      $node = null;
                      $config->set('anon_page_count',$config->get('anon_page_count')-1)->save();
                  }
              }
              if($config->get('anon_page_count') < 2) {
                  $config->set('anon_page_count',2)->save();
                  for ($m = 0; $m < 2; $m++) {
                      $node_id = $config->get('anon_page_' . $m);
                      $node = \Drupal\node\Entity\Node::load($node_id);
                      $form['anonymous']['anon_page'][$m] = array(
                          '#title' => '',
                          '#type' => 'entity_autocomplete',
                          '#target_type' => 'node',
                          '#default_value' => $node,
                      );
                  }
              }
          }
          if($config->get('anon_page_count') < 2) {
              $config->set('anon_page_count',2)->save();
              for ($m = 0; $m < 2; $m++) {
                  $node_id = $config->get('anon_page_' . $m);
                  $node = \Drupal\node\Entity\Node::load($node_id);
                  $form['anonymous']['anon_page'][$m] = array(
                      '#title' => '',
                      '#type' => 'entity_autocomplete',
                      '#target_type' => 'node',
                      '#default_value' => $node,
                  );
              }
          }
      }

    $form['anonymous']['anon_page_button'] = array(
      '#type' => 'submit',
      '#value' => t('Add Anon Page'),
      '#submit' => array(array($this, 'addAnonPageSubmit')),
      '#limit_validation_errors' => array(),
      '#ajax' => array(
        'callback' => 'Drupal\blazemeter\Form\BlazeMeterSettingsForm::addAnonPageCallback',
        'wrapper' => 'anon-page-fieldset-wrapper'
      ),
    );

    $form['authenticated'] = array(
      '#type' => 'fieldset',
      '#title' => t('Authenticated Access'),
      '#collapsible' => FALSE,
      '#collapsed' => FALSE
    );
    $form['authenticated']['auth'] = array(
      '#type' => 'textfield',
      '#title' => t('Max Concurrent Authenticated Users') . ':',
      '#default_value' => $config->get('num_of_auth_users'),
      '#suffix' => '<div id="auth-slider"></div>',
    );


    $form['authenticated']['auth_pages_title'] = array(
      '#type' => 'markup',
      '#markup' => '<div class="form-item"><label>' . t('Pages') . ':</label></div>
    <div class="description">' . t('Enter the page title, node id or relative path from %domain', array('%domain' => $default_domain)) . '</div>
    ',
    );

    $form['authenticated']['auth_page'] = array(
      '#tree' => TRUE,
      '#prefix' => '<div id="auth-page-fieldset-wrapper">',
      '#suffix' => '</div>'
    );

      if(empty($config->get('auth_page_count')) || $config->get('auth_page_count') < 2){
          $config->set('auth_page_count',2)->save();
      }

      if($form_state->isRebuilding()) {
          for ($i = 0; $i < $config->get('auth_page_count'); $i++) {
              $node_id = $config->get('auth_page_' . $i);
              if($node_id) {
                  $node = \Drupal\node\Entity\Node::load($node_id);
              } else {
                  $node = null;
              }
              $form['authenticated']['auth_page'][$i] = array(
                  '#title' => '',
                  '#type' => 'entity_autocomplete',
                  '#target_type' => 'node',
                  '#default_value' => $node,
              );
          }
      } else {
          for ($k = 0; $k < $config->get('auth_page_count'); $k++) {
              $nodeID = $config->get('auth_page_' . $k);
              $node = \Drupal\node\Entity\Node::load($nodeID);
              if($node){
                  $form['authenticated']['auth_page'][$k] = array(
                      '#title' => '',
                      '#type' => 'entity_autocomplete',
                      '#target_type' => 'node',
                      '#default_value' => $node,
                  );
              }
              else{
                  $node = null;
                  $config->set('auth_page_count',$config->get('auth_page_count')-1)->save();
              }
          }
          if($config->get('auth_page_count') < 2) {
              $config->set('auth_page_count',2)->save();
              for ($m = 0; $m < 2; $m++) {
                  $node_id = $config->get('auth_page_' . $m);
                  $node = \Drupal\node\Entity\Node::load($node_id);
                  $form['authenticated']['auth_page'][$m] = array(
                      '#title' => '',
                      '#type' => 'entity_autocomplete',
                      '#target_type' => 'node',
                      '#default_value' => $node,
                  );
              }
          }
      }

    $form['authenticated']['auth_page_button'] = array(
      '#type' => 'submit',
      '#value' => t('Add Auth Page'),
      '#limit_validation_errors' => array(),
      '#submit' => array(array($this, 'addAuthPageSubmit')),
      '#ajax' => array(
        'callback' => 'Drupal\blazemeter\Form\BlazeMeterSettingsForm::addAuthPageCallback',
        'wrapper' => 'auth-page-fieldset-wrapper',
      ),
    );

    $form['meta'] = array(
      '#type' => 'fieldset',
      '#title' => t('Meta Data'),
      '#collapsible' => FALSE,
      '#collapsed' => FALSE
    );

    $default_scenario = $config->get('default_scenario');
    if(empty($default_scenario)){
      $default_scenario = 'load';
    }

    $form['meta']['scenario'] = array(
      '#type' => 'hidden',
      '#title' => t('Load Scenario'),
      '#default_value' => $default_scenario,
      '#attributes' => array('id' => 'edit-meta-scenario'),
    );

    $load_selected = '';
    $stress_selected = '';
    $extreme_selected = '';

    if ($default_scenario == 'load') {
      $load_selected = "button-selected";
    }
    else {
      if ($default_scenario == 'stress') {
        $stress_selected = "button-selected";
      }
      else {
        if ($default_scenario == 'extreme stress') {
          $extreme_selected = "button-selected";
        }
      }
    }

    $load_scenario_html = '<div id="sc_load_tab" class="scenario"><div class="sc_img"><h3>Scheme</h3>
  <div class="load"></div></div>
  <div class="sc_desc"><h3>Description</h3><p>Gradual increase in the number of concurrent users.</p>
  <p>Server is not overwhelmed.</p>30 minute user ramp up.<p>10 minute continuous load.</p></div>
  <div class="sc_int"><h3>Intelligence</h3><p>Evaluate performance and user experience under various load levels.</p>
  <p>Pinpoint where and why website performance levels start to decline.</p><p>Pinpoint where and why website performance levels become unacceptable.</p>
  </div></div>';

    $stress_scenario_html = '<div id="sc_stress_tab" class="scenario"><div class="sc_img"><h3>Scheme</h3>
  <div class="stress"></div></div><div class="sc_desc"><h3>Description</h3>
  <p>Number of concurrent users is increased at an accelerated rate.</p>
  <p>Server is stressed and numerous threads are spawned in a shorter period of time.</p>
  <p>15 minute user ramp up.</p><p>25 minute continuous load.</p></div><div class="sc_int">
  <h3>Intelligence</h3><p>Evaluate performance and user experience during a stressful load scenario.</p>
  <p>Identify downtime or crash points.</p></div></div>';

    $extreme_scenario_html = '<div id="sc_estress_tab" class="scenario"><div class="sc_img"><h3>Scheme</h3>
  <div class="estress"></div></div>
  <div class="sc_desc"><h3>Description</h3><p>Number of concurrent users is increased at an accelerated rate.</p>
  <p>Server is overwhelmed.</p><p>5 minute user ramp up.</p>35 minute continuous load.</div><div class="sc_int">
  <h3>Intelligence</h3><p>Evaluate performance and user experience when subjected to extreme stress.</p><p>Identify downtime or crash points.</p>
  </div></div>';


    $form['meta']['scenario_html'] = array(
      '#type' => 'markup',
      '#markup' => '
    <div id="blazemeter-scenario">
    <span class="form-item span-label">' . t('Load Scenario') . ': </span>
    <span id="blazemeter-scenario-load" class="blazemeter-button ' . $load_selected . '">' . t('Load') . '</span>
    <div id="blazemeter-scenario-load-frame" class="tooltip">
      <div class="inner-tooltip">
      ' . $load_scenario_html . '
      </div>
    </div>
    <span id="blazemeter-scenario-stress" class="blazemeter-button ' . $stress_selected . '">' . t('Stress') . '</span>
     <div id="blazemeter-scenario-stress-frame" class="tooltip">
      <div class="inner-tooltip">
      ' . $stress_scenario_html . '
      </div>
    </div>
    <span id="blazemeter-scenario-extreme" class="blazemeter-button ' . $extreme_selected . '">' . t('Extreme Stress') . '</span>
     <div id="blazemeter-scenario-extreme-frame" class="tooltip">
      <div class="inner-tooltip">
      ' . $extreme_scenario_html . '
      </div>
    </div>
    </div>

    ',
    );

    if ( \Drupal::moduleHandler()->moduleExists('domain')) {
      //Multisite installation
      $domains = domain_domains();

      $form['meta']['domain'] = array(
        '#type' => 'item',
        '#title' => '<span class="form-required">*</span> ' . t('Site Home URL') . ':',
      );

      $i = 0;
      $using_custom_domain = TRUE;
      foreach ($domains as $domain) {
        $options[$domain['path']] = $domain['path'];
        $form['meta']['domain']['domain_' . $i] = array(
          '#type' => 'radio',
          '#title' => $domain['path'],
          '#return_value' => $domain['path'],
          '#default_value' => $default_domain,
          '#parents' => array('domain')
        );
        if ($default_domain == $domain['path']) {
          $using_custom_domain = FALSE;
        }
        $i++;
      }

      $form['meta']['domain']['domain_' . $i]= array(
        '#type' => 'radio',
        '#title' => t('Use other domain') . ':',
        '#return_value' => -1,
        '#default_value' => $using_custom_domain ? -1 : '',
        '#parents' => array('domain')
      );

      $form['meta']['domain']['domain_other'] = array(
        '#type' => 'textfield',
        '#default_value' => $using_custom_domain ? $default_domain : '',
        '#size' => 20,
        '#attributes' => array('onClick' => '$("input[name=domain][value=-1]").attr("checked", true);'),
      );
    }

    else {
      $form['meta']['domain'] = array(
        '#type' => 'textfield',
        '#title' => '<span class="form-required">*</span> ' . t('Site Home URL') . ':',
        '#default_value' => $base_url,
      );
    }

    $form['meta']['ip'] = array(
      '#type' => 'textfield',
      '#title' => '<span class="form-required">*</span> ' . t('IP') . ':',
      '#default_value' => $this->getIp(),
    );

    $userkey = $config->get('user_key');

    $form['meta']['userkey'] = array(
      '#type' => 'password',
      '#title' => 'User key',
      '#attributes' => array('value' => $userkey),
    );
    $form['meta']['testid'] = array(
      '#title' => t('Test ID') . ':',
      '#type' => 'textfield',
      '#default_value' => $config->get('test_id'),
      '#disabled' => TRUE,
      '#size' => 58,
    );
    $form['meta']['testname'] = array(
      '#title' => t('Test Name') . ':',
      '#type' => 'textfield',
      '#default_value' => $default_domain,
    );
    $geolocations = array(
      'eu-west-1' => 'EU West (Ireland)',
      'us-east-1' => 'US East (Virginia)',
      'us-west-1' => 'US West (N.California)',
      'us-west-2' => 'US West (Oregon)',
      'sa-east-1' => 'South America(Sao Paulo)',
      'ap-southeast-1' => 'Asia Pacific (Singapore)',
      'ap-southeast-2' => 'Australia (Sydney)',
      'ap-northeast-1' => 'Japan (Tokyo)',
    );
    $form['meta']['geolocation'] = array(
      '#title' => t('Geo Location') . ':',
      '#type' => 'select',
      '#options' => $geolocations,
      '#default_value' => '',
    );
    if(!isset($userkey)){
      $form['meta']['signup'] = array(
        '#type' => 'button',
        '#value' => 'Sign up to BlazeMeter and Get 10 Free Tests',
        '#attributes' => array('class' => array('blazemeter-button')),
        '#ajax' => array(
          'callback' => 'Drupal\blazemeter\Form\BlazeMeterSettingsForm::signUpModal',
        ),
      );
      $form['meta']['login'] = array(
        '#type' => 'button',
        '#value' => 'Login to BlazeMeter',
        '#attributes' => array('class' => array('blazemeter-button')),
        '#ajax' => array(
          'callback' => 'Drupal\blazemeter\Form\BlazeMeterSettingsForm::loginModal',
        ),
      );
    }
    else{
      $form['meta']['hasuserkey'] = array(
        '#type' => 'hidden',
        '#default_value' => 'true',
        '#attributes' => array('id' => 'edit-meta-hasuserkey'),
      );
    }

    $form['meta']['appkey'] = array(
      '#type' => 'hidden',
      '#value' => $this->blazemeter_api_key,
    );

    $form['buttons']['submit'] = array(
      '#prefix' => '<div class="submit-buttons">',
      '#type' => 'submit',
      '#value' => t('Save'),
    );

    $form['buttons']['cleanup'] = array(
      '#type' => 'submit',
      '#value' => t('Cleanup'),
    );

    $form['buttons']['help'] = array(
      '#type' => 'markup',
      '#markup' => '<a id="edit-buttons-help" class="button form-submit" target="_blank" href="https://docs.blazemeter.com/customer/portal/articles/2281341-blazemeter-to-drupal-module">' . t('Help') . '</a>',
    );
    $test_id = $config->get('test_id');
    if(isset($test_id)){
      $form['buttons']['goto'] = array(
        '#type' => 'submit',
        '#value' => t('Start Testing / Go to Test Page'),
        '#suffix' => '</div>',
      );
    }
    return $form;
  }

  public function getEditableConfigNames() {
    return ['blazemeter.settings'];
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = \Drupal::configFactory()->getEditable('blazemeter.settings');
    //clean up current configuration
    if($form_state->getTriggeringElement()['#id']=='edit-goto'){ 
      if($config->get('test_id')){
	    $url = $this->blazemeter_url . "/api/latest/tests/".$config->get('test_id')."/start";
	      try {
		    $client = new GuzzleHttp\Client();
		    $res = $client->request('GET', $url, ['headers' => ['X-Api-Key' => $config->get('user_key')]]); 
		    $testInfo = $res->getBody();
			$jsonTestInfo = json_decode($testInfo);
		    $response = new RedirectResponse($this->blazemeter_url . "/app/#masters/" . $jsonTestInfo->result->id);
	        $response->send();
	        return;
	      }
	      catch (GuzzleHttp\Exception\ClientException $e) {
			$response = $e->getResponse();
			$responseBodyAsString = $response->getBody()->getContents();
			$jsonError = json_decode($responseBodyAsString);
			drupal_set_message($jsonError->error->message, 'error');
		  }
      }
      else{
        drupal_set_message('Test not set!', 'error');
      }
    }
    if($form_state->getTriggeringElement()['#id'] == 'edit-cleanup'){
      $num_of_anon_pages = $config->get('num_of_anon_pages');
      if(isset($num_of_anon_pages)){
        for($i=0;$i<=$num_of_anon_pages;$i++){
          $config->delete('anon_page_'. $i);
        }
      }
      $num_of_auth_pages = $config->get('num_of_auth_pages');
      if(isset($num_of_auth_pages)){
        for($i=0;$i<=$num_of_anon_pages;$i++){
          $config->delete('auth_page_'. $i);
        }
      }
      $config->delete('user_key', '');
      $config->delete('test_id', '');
      $config->delete('num_of_anon_users');
      $config->delete('num_of_auth_users');
      return;
    }
    //save num of auth and anon users
    $config->set('num_of_anon_users', $form_state->getValue('anon'))->save();
    $config->set('num_of_auth_users', $form_state->getValue('auth'))->save();
    //save pages
    $anon_pages = $form_state->getValue('anon_page');
    $i=0;
    foreach($anon_pages as $page){
      if($page != NULL){
        $config->set('anon_page_'. $i , $page)->save();
        $i++;
      }
    }
    $config->set('num_of_anon_pages', $i);
    $auth_pages = $form_state->getValue('auth_page');
    $i=0;
    foreach($auth_pages as $page){
      if($page != NULL){
        $config->set('auth_page_'. $i , $page)->save();
        $i++;
      }
    }
    $config->set('num_of_auth_pages', $i);
    //create test
    $concurrency = $form_state->getValue('anon') + $form_state->getValue('auth');
    try{
      $client = new GuzzleHttp\Client(['headers' => ['X-Api-Key' => $form_state->getValue('userkey')]]);
      $response = $client->post($this->blazemeter_url . "/api/latest/tests",
        array(
          "json" => [
            'name' => $form_state->getValue('testname'),
            'configuration' => [
              'type' => 'jmeter',
              'concurrency' => $concurrency,
              'location' => $form_state->getValue('geolocation'),
              'plugins' => [
                'jmeter' => [
                  'override' => [
                    'duration'  => 20,
                  ],
                  'filename' => 'drupal8.jmx'
                ]
              ]
            ],
          ]));
    }catch(GuzzleHttp\Exception\BadResponseException $e){
      $response_body = json_decode($e->getResponse()->getBody());
      $error = $response_body->error->message . ' ' . $response_body->error->failed[0];
      drupal_set_message($error, 'error');
      return;
    }
    $response_body = json_decode($response->getBody(), TRUE);
    $test_id = $response_body['result']['id'];
    $this->build_user_properties_file($test_id, $form_state->getValue('anon'), $form_state->getValue('auth'), $form_state->getValue('domain'), $form_state->getValue('ip'), $form_state->getValue('scenario'), $form_state->getValue('userkey'));
    $anon_pages = $form_state->getValue('anon_page');
    $auth_pages = $form_state->getValue('auth_page');
    if(count(array_filter($anon_pages))>0){
      $this->build_anon_pages_csv($anon_pages, $test_id, $form_state->getValue('userkey'));
    }
    if(count(array_filter($auth_pages))>0){
      $this->build_auth_pages_csv($auth_pages, $test_id, $form_state->getValue('userkey'));
      $this->build_auth_users_csv($form_state->getValue('auth'), $test_id, $form_state->getValue('userkey'));
    }
    try{
      $client->post($this->blazemeter_url . '/api/latest/tests/' . $test_id . '/files',
        [
          'multipart' => [
            [
              'name' => 'script',
              'contents' => fopen(drupal_get_path('module','blazemeter').'/drupal8.jmx', 'r')
            ]
          ]
        ]);
    }catch(GuzzleHttp\Exception\BadResponseException $e){
      drupal_set_message(t('Failed to upload drupal8.jmx file!', 'error'));
      $response_body = json_decode($e->getResponse()->getBody());
      $error = $response_body->error->message . ' ' . $response_body->error->failed[0];
      drupal_set_message($error, 'error');
      return;
    }
    $config->set('test_id', $test_id)->save();
    if(!$config->get('user_key')) {
	    $config->set('user_key', $form_state->getValue('userkey'))->save();
    }
    drupal_set_message(t('Test successfully created in BlazeMeter. Your test id is <a target="_blank" href="'.$this->blazemeter_url.'/app/#tests/'.$test_id.'">' . $test_id . '</a>'));
  }

  private function build_user_properties_file($test_id, $num_of_anon_users, $num_of_auth_users, $domain, $ip, $rampup, $api_key){
    $parts = parse_url($domain);
    $load = '';
    if($rampup == 'load') {
      $load = '1800';
    }
    elseif($rampup == 'stress') {
      $load = '900';
    }
    elseif($rampup == 'extreme stress') {
      $load = '300';
    }
    $data = '#GENERATED BY API
#numberOfEngines = 0
# anon_user_load/=numberOfEngines+1;
Anon='.$num_of_anon_users.'
# auth_user_load/=numberOfEngines+1;
Auth='.$num_of_auth_users.'
Host='.$parts["host"].'
IP='.$ip.'
Protocol='.$parts["scheme"].'
Rampup='.$load.'
Delay=10000
Login=/user
DrupalPath='.$parts["path"].'
PerformancePort=-1
WebPath='.$parts["path"];

    $file = fopen('user.properties', 'w');
    fwrite($file, $data);
    fclose($file);
    $client = new GuzzleHttp\Client(['headers' => ['X-Api-Key' => $api_key]]);
    try{
      $response = $client->post($this->blazemeter_url . '/api/latest/tests/' . $test_id . '/files',
        [
          'multipart' => [
            [
            'name' => 'script',
            'contents' => fopen('user.properties', 'r')
            ]
          ]
        ]);
    }catch(GuzzleHttp\Exception\BadResponseException $e){
      drupal_set_message(t('Failed to upload user.properties file!', 'error'));
      $response_body = json_decode($e->getResponse()->getBody());
      $error = $response_body->error->message . ' ' . $response_body->error->failed[0];
      drupal_set_message($error, 'error');
      return;
    }
    return;
  }

  private function build_anon_pages_csv($anon_pages, $test_id, $api_key){
    $anon_pages_csv = fopen('anon_pages.csv', 'w');
    $anon_pages_array = array();
    foreach($anon_pages as $node_id){
      if($node_id != NULL && $node_id !=''){
        $anon_pages_array[] = $GLOBALS['base_url'] . '/node/' . $node_id;
      }
    }
    fputcsv($anon_pages_csv, $anon_pages_array);
    fclose($anon_pages_csv);
    $client = new GuzzleHttp\Client(['headers' => ['X-Api-Key' => $api_key]]);
    //upload it to blazemeter
    try{
      $client->post($this->blazemeter_url . '/api/latest/tests/' . $test_id . '/files',
        [
          'multipart' => [
            [
              'name' => 'script',
              'contents' => fopen('anon_pages.csv', 'r')
            ]
          ]
        ]);
    }catch(GuzzleHttp\Exception\BadResponseException $e){
      drupal_set_message(t('Failed to upload anon_pages.csv file!', 'error'));
      $response_body = json_decode($e->getResponse()->getBody());
      $error = $response_body->error->message . ' ' . $response_body->error->failed[0];
      drupal_set_message($error, 'error');
      return;
    }

    return;
  }

  private function build_auth_pages_csv($auth_pages, $test_id, $api_key){
    $auth_pages_csv = fopen('auth_pages.csv', 'w');
    $auth_pages_array = array();
    foreach($auth_pages as $node_id){
      if($node_id != NULL && $node_id !=''){
        $auth_pages_array[] = $GLOBALS['base_url'] . '/node/' . $node_id;
      }
    }
    fputcsv($auth_pages_csv, $auth_pages_array);
    fclose($auth_pages_csv);
    $client = new GuzzleHttp\Client(['headers' => ['X-Api-Key' => $api_key]]);
    try{
      $client->post($this->blazemeter_url . '/api/latest/tests/' . $test_id . '/files',
        [
          'multipart' => [
            [
              'name' => 'script',
              'contents' => fopen('auth_pages.csv', 'r')
            ]
          ]
        ]);
    }catch(GuzzleHttp\Exception\BadResponseException $e){
      drupal_set_message(t(['Failed to upload auth_pages.csv file!', 'error']));
      $response_body = json_decode($e->getResponse()->getBody());
      $error = $response_body->error->message . ' ' . $response_body->error->failed[0];
      drupal_set_message($error, 'error');
      return;
    }

    return;
  }

  private function build_auth_users_csv($num_of_users, $test_id, $api_key){

    $num_of_users_in_database = \Drupal::entityQuery('blazemeter_user')->count()->execute();
    if($num_of_users > $num_of_users_in_database) {
      $batch = array(
        'operations' => array(
          array(
            $this->blazemeter_user_save($num_of_users, $num_of_users_in_database)
          )
        ),
        'finished' => 'blazemeter_batch_finished',
        'title' => t('Creating Blazemeter Users'),
        'init_message' => t('Creating Blazemeter Users'),
        'progress_message' => t('Creating Blazemeter Users (@percentage%).'),
        'error_message' => t('Creating Blazemeter Users has encountered an error.'),
      );

      batch_set($batch);
    }
    $auth_users_csv = fopen('auth_users.csv', 'w');
    for($i=0;$i<$num_of_users;$i++){
      $user = BlazemeterUser::load($i);
      $username = $user->username();
      $password = $user->password();
      $content = array($username, $password);
      fputcsv($auth_users_csv, $content);
    }
    fclose($auth_users_csv);

    $client = new GuzzleHttp\Client(['headers' => ['X-Api-Key' => $api_key]]);
    try{
      $client->post($this->blazemeter_url . '/api/latest/tests/' . $test_id . '/files',
        [
          'multipart' => [
            [
              'name' => 'script',
              'contents' => fopen('auth_users.csv', 'r')
            ]
          ]
        ]);
    }catch(GuzzleHttp\Exception\BadResponseException $e){
      drupal_set_message($e->getMessage(), 'error');
      drupal_set_message(t('Failed to upload auth_users.csv file!', 'error'));
      return;
    }

    return;

  }

  public static function blazemeter_user_save($num_of_users,$num_of_users_in_database){

    for($i=$num_of_users_in_database;$i<$num_of_users;$i++){
      $username = 'blaze' . $i;
      $password = user_password(10);
      $language = \Drupal::languageManager()->getCurrentLanguage()->getId();

      $user = \Drupal\user\Entity\User::create();
      $user->setPassword($password);
      $user->enforceIsNew();
      $user->setEmail('email');
      $user->setUsername($username);//This username must be unique and accept only a-Z,0-9, - _ @ .
      $user->set("init", 'email');
      $user->set("langcode", $language);
      $user->set("preferred_langcode", $language);
      $user->set("preferred_admin_langcode", $language);
      $user->activate();
      $user->save();
      $blazemeter_user = BlazemeterUser::create(
        array(
          'id' => $i,
          'username' => $username,
          'password' => $password,
        )
      );
      $blazemeter_user->save();

    }

  }

  public function getLoginForm(){
    $form = \Drupal::formBuilder()->getForm('Drupal\blazemeter\Form\BlazeMeterLoginForm');
    return render($form);
  }

  public function getSignUpForm(){
    $form = \Drupal::formBuilder()->getForm('Drupal\blazemeter\Form\BlazeMeterSignUpForm');
    return render($form);
  }

  public function addAnonPageCallback(array &$form, FormStateInterface $form_state){
      $config = \Drupal::configFactory()->getEditable('blazemeter.settings');
      $anon_page = $form_state->getValue('anon_page');
      $i=0;
      foreach($anon_page as $field){
          if($field){
              $config->set('anon_page_'. $i , $field)->save();
              $i++;
          }
      }
    return $form['anonymous']['anon_page'];
  }

  public function addAnonPageSubmit(array &$form, FormStateInterface &$form_state){
      $config = \Drupal::configFactory()->getEditable('blazemeter.settings');
      $num_of_pages = $config->get('anon_page_count');
      $config->set('anon_page_count', $num_of_pages + 1)->save();
    $form_state->setRebuild(TRUE);
  }

  public function addAuthPageCallback(array &$form, FormStateInterface $form_state){
      $config = \Drupal::configFactory()->getEditable('blazemeter.settings');
      $auth_page = $form_state->getValue('auth_page');
      $i=0;
      foreach($auth_page as $field){
          if($field){
              $config->set('auth_page_'. $i , $field)->save();
              $i++;
          }
      }
    return $form['authenticated']['auth_page'];
  }

  public function addAuthPageSubmit(array &$form, FormStateInterface &$form_state){
      $config = \Drupal::configFactory()->getEditable('blazemeter.settings');
      $num_of_pages = $config->get('auth_page_count');
      $config->set('auth_page_count', $num_of_pages + 1)->save();
    $form_state->setRebuild(TRUE);
  }

  public function route_autocomplete(){
    $connection = Database::getConnection();
    $router = new RouteProvider($connection, new State(new KeyValueMemoryFactory()), new CurrentPathStack(new RequestStack()), new MemoryBackend('data'), \Drupal::service('path_processor_manager'), \Drupal::service('cache_tags.invalidator'));
  }

  public function getIp(){
	$client = new GuzzleHttp\Client();
	$res = $client->request('GET', $this->blazemeter_url . "/api/latest/image/helpers/ip");
	$ip = $res->getBody();
    return str_replace('"', "", $ip);
  }

  public function blazemeter_get_api_url($method) {
    switch (strtolower($method)) {
      case 'userinfo':
      	$url = $this->blazemeter_url . "/api/latest/user";
        break;
    }
    return $url;
  }

  function signUpModal() {
    $response = new AjaxResponse();
    $title = 'Registration form';
    $form = \Drupal::formBuilder()->getForm('Drupal\blazemeter\Form\BlazeMeterSignUpForm');
    $response->setAttachments($form['#attached']);
    $options = array(
      'id' => 'blazemeter-signup-form',
      'dialogClass' => 'popup-dialog-class',
      'width' => '30%',
    );
    $form = render($form);
    $modal = new OpenModalDialogCommand($title, $form, $options);
    $response->addCommand($modal);
    return $response;
  }

  function loginModal() {
    $response = new AjaxResponse();
    $title = 'Log In form';

    $form = \Drupal::formBuilder()->getForm('Drupal\blazemeter\Form\BlazeMeterLoginForm');
    $response->setAttachments($form['#attached']);
    $options = array(
      'id' => 'blazmeter-login-form',
      'dialogClass' => 'popup-dialog-class',
      'width' => '30%',
    );
    $form = render($form);
    $modal = new OpenModalDialogCommand($title, $form, $options);
    $response->addCommand($modal);
    return $response;
  }

  public function validateForm(array &$form, FormStateInterface $form_state){
    $trigger_button_id = $form_state->getTriggeringElement()['#attributes']['data-drupal-selector'];
    if($trigger_button_id =='edit-cleanup' || $trigger_button_id =='edit-anon-page-button' || $trigger_button_id =='edit-auth-page-button' || $trigger_button_id =='edit-signup' || $trigger_button_id =='edit-login'){
      return;
    }
    $anon_pages = $form_state->getValue('anon_page');
    $auth_pages = $form_state->getValue('auth_page');
    if(count(array_filter($anon_pages)) == 0 && count(array_filter($auth_pages)) == 0 ){
      $form_state->setErrorByName('anon_page', $this->t('You must select at least one route!'));
      $form_state->setErrorByName('auth_page');
    }
    if(count(array_filter($anon_pages))>0){
      if($form_state->getValue('anon') == 0){
        $form_state->setErrorByName('anon', $this->t('You must select number of anonymous users!'));
      }
    }
    if(count(array_filter($auth_pages))>0){
      if($form_state->getValue('auth') == 0){
        $form_state->setErrorByName('auth', $this->t('You must select number of authenticated users!'));
      }
    }
    if(empty($form_state->getValue('testname'))){
      $form_state->setErrorByName('testname', $this->t('Name must be selected!'));
    }
    if(!(filter_var($form_state->getValue('ip'), FILTER_VALIDATE_IP))){
      $form_state->setErrorByName('ip', $this->t('Invalid IP address'));
    }
    if(empty($form_state->getValue('userkey'))){
      $form_state->setErrorByName('userkey', $this->t('Userkey not set. Please login or signup on Blazemeter!'));
    } else {
	      $client = new GuzzleHttp\Client();
	      try {
		    $res = $client->request('GET', $this->blazemeter_get_api_url('userinfo'), ['headers' => ['X-Api-Key' => $form_state->getValue('userkey')]]);
		    $user_info = $res->getBody();
			$json_user_info = json_decode($user_info);
			$max_users = isset($json_user_info->limitations->userPlan->concurrency->max) ? $json_user_info->limitations->userPlan->concurrency->max : 5000;
			  if($form_state->getValue('anon') > $max_users) {
				  $form_state->setErrorByName('anon', $this->t('Max Concurrent Anonymous Visitors must be integer number between 0 and ' . $max_users . '!'));
			  }
			  if($form_state->getValue('auth') > $max_users) {
				  $form_state->setErrorByName('auth', $this->t('Max Concurrent Authenticated Users must be integer number between 0 and ' . $max_users . '!'));
			  } 
	      }
	      catch (GuzzleHttp\Exception\ClientException $e) {
			$response = $e->getResponse();
			$form_state->setErrorByName('userkey', $this->t('Userkey not valid!'));
		  } 
    }

  }

}

?>