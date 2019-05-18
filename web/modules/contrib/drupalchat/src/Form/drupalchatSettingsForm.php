<?php

/**
 * @file
 * Contains Drupal\drupalchat\Form\drupalchatSettingsForm
 */

namespace Drupal\drupalchat\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\drupalchat\Controller\drupalchatController;
use Drupal\Core\Url;
/**
 * Class drupalchatSettingsForm
 *
 * @package Drupal\drupalchat\Form
 */
class drupalchatSettingsForm extends ConfigFormBase {

	/**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'drupalchat.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('drupalchat.settings');

    $form['#attached']['library'][] = 'drupalchat/drupalchat-path-visibility';
    $form['#attached']['library'][] = 'drupalchat/drupalchat-settings-options';

    $seconds = array(1 => 1, 2 => 2, 3 => 3, 4 => 4, 5 => 5, 6 => 6, 7=>7, 8 => 8, 9 => 9, 10 => 10, 11 => 11, 12 => 12, 13 => 13, 14 => 14, 15 => 15, 16 => 16, 17 => 17, 18 => 18, 19 => 19, 20 => 20, 30 => 30, 40 => 40, 50 => 50, 60 => 60, 70 => 70, 80 => 80, 90 => 90, 100 => 100, 110 => 110, 120 => 120, 150 => 150, 180 => 180, 240 => 240, 300 => 300);

    $themes = drupalchatSettingsForm::_drupalchat_load_themes(drupal_get_path('module', 'drupalchat') . '/css/themes', 'css');

    $polling_method = $config->get('drupalchat_polling_method') ?: DRUPALCHAT_AJAX;

    if ($polling_method == DRUPALCHAT_LONGPOLL && ini_get('max_execution_time') < 30) {
      drupal_set_message(t('For DrupalChat Long Polling to be effective, please set max_execution_time to above 30 in your server php.ini file.'), 'warning');
    }
    
    $form['drupalchat_general_settings'] = array(
      '#type' => 'details',
      '#title' => $this->t('General Settings'),
      '#open' => TRUE,
    );

    //$default_polling_method = empty($config->get('drupalchat_polling_method')) ? DRUPALCHAT_COMMERCIAL : $config->get('drupalchat_polling_method'); 
    $form['drupalchat_general_settings']['drupalchat_polling_method'] = array(
      '#type' => 'radios',
      '#title' => $this->t('Choose Polling Method'),
      '#default_value' => $config->get('drupalchat_polling_method'),
      '#options' => array(DRUPALCHAT_COMMERCIAL => $this->t('iFlyChat Server'), DRUPALCHAT_AJAX => $this->t('Normal AJAX'), DRUPALCHAT_LONGPOLL => $this->t('Long Polling'), DRUPALCHAT_NODEJS => $this->t('Node.js (currently under development)'),),
      '#description' => $this->t('Decide the server backend for Drupal Chat.'),
    );

    $form['drupalchat_general_settings']['drupalchat_app_id'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('iFlyChat APP ID'),
      '#description' => $this->t('Please enter the APP ID by registering at <a href="https://iflychat.com" target="_blank">iFlyChat.com</a>.'),
      '#default_value' => $config->get('drupalchat_app_id')? $config->get('drupalchat_app_id') : NULL,
    );

    $form['drupalchat_general_settings']['drupalchat_external_api_key'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('iFlyChat API Key'),
      '#description' => $this->t('Please enter the API key by registering at <a href="https://iflychat.com" target="_blank">iFlyChat.com</a>.'),
      '#default_value' => $config->get('drupalchat_external_api_key')? $config->get('drupalchat_external_api_key') : NULL,
    );

    $form['drupalchat_general_settings']['drupalchat_theme'] = array(
      '#type' => 'select',
      '#title' => $this->t('Theme'),
      '#description' => $this->t('All themes from inside the <em>themes</em> folder will be displayed here.'),
      '#options' => $themes,
      '#default_value' => $config->get('drupalchat_theme') ?: 'light',
    );
    $form['drupalchat_general_settings']['drupalchat_notification_sound'] = array(
      '#type' => 'select',
      '#title' => $this->t('Notification Sound'),
      '#description' => $this->t('Select whether to play notification sound when a new message is received.'),
      '#options' => array(1 => 'Yes', 2 => 'No'),
      '#default_value' => $config->get('drupalchat_notification_sound') ?: 1,
    );
    $form['drupalchat_general_settings']['drupalchat_user_picture'] = array(
      '#type' => 'select',
      '#title' => $this->t('User Pictures'),
      '#description' => $this->t('Select whether to show user pictures in chat.'),
      '#options' => array(1 => 'Yes', 2 => 'No'),
      '#default_value' => $config->get('drupalchat_user_picture') ?: 1,
    );
    $form['drupalchat_general_settings']['drupalchat_enable_smiley'] = array(
      '#type' => 'select',
      '#title' => $this->t('Enable Smileys'),
      '#description' => $this->t('Select whether to show smileys.'),
      '#options' => array(1 => 'Yes', 2 => 'No'),
      '#default_value' => $config->get('drupalchat_enable_smiley') ?: 1,
    );
    $form['drupalchat_general_settings']['drupalchat_log_messages'] = array(
      '#type' => 'select',
      '#title' => $this->t('Log chat messages'),
      '#description' => $this->t('Select whether to log chat messages, which can be later viewed in ' . \Drupal::l(t('message inbox'), Url::fromRoute('drupalchat.messages.inbox'))),
      '#options' => array(1 => 'Yes', 2 => 'No'),
      '#default_value' => $config->get('drupalchat_log_messages') ?: 1,
    );
    $form['drupalchat_general_settings']['drupalchat_anon_prefix'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Prefix to be used with anonymous users'),
      '#description' => $this->t("Please specify the prefix to be used with anonymous users. It shouldn't be long. Ideally it should be between 4 to 7 characters."),
      '#default_value' => $config->get('drupalchat_anon_prefix') ?: 'Guest',
    );
    $form['drupalchat_general_settings']['drupalchat_anon_use_name'] = array(
      '#type' => 'select',
      '#title' => $this->t('Use random name or number for anonymous user'),
      '#description' => $this->t('Select whether to use random generated name or number to assign to a new anonymous user'),
      '#options' => array(1 => 'Name', 2 => 'Number'),
      '#default_value' => $config->get('drupalchat_anon_use_name') ?: 1,
    );
    $form['drupalchat_general_settings']['drupalchat_user_latency'] = array(
      '#type' => 'select',
      '#title' => $this->t('Chat List Latency'),
      '#description' => $this->t('The delay, in seconds, after which the user will be shown offline in the chat list(i.e. removed from the chat list) from the time he/she goes offline. Increase this value if you find the chat list is unstable and keeps on changing a lot (for example - when a user navigates from one page to another he/she goes offline and then comes back online again). Decrease it if you find that the users are shown in the chat list for too long after they have left your website.'),
      '#options' => $seconds,
      '#default_value' => $config->get('drupalchat_user_latency') ?: 30,
    );

    $drupalchat_refresh_rate_disabled = $config->get('drupalchat_polling_method') ?: DRUPALCHAT_AJAX;
    $form['drupalchat_general_settings']['drupalchat_refresh_rate'] = array(
      '#type' => 'select',
      '#title' => $this->t('Normal AJAX Refesh Rate'),
      '#description' => $this->t('The time interval, in seconds, after which DrupalChat checks for new messages.'),
      '#options' => $seconds,
      '#default_value' => $config->get('drupalchat_refresh_rate') ?: 2,
      '#disabled' => $drupalchat_refresh_rate_disabled == DRUPALCHAT_LONGPOLL ? TRUE : FALSE,
    );
    
    $form['drupalchat_pc'] = array(
      '#type' => 'details',
      '#title' => $this->t('Chat Moderation'),
      '#open' => FALSE,
    );
    $form['drupalchat_pc']['drupalchat_enable_chatroom'] = array(
      '#type' => 'radios',
      '#title' => $this->t('Enable Public Chatroom'),
      '#default_value' => $config->get('drupalchat_enable_chatroom') ?: 1,
      '#options' => array(1 => 'Yes', 2 => 'No'),
    );
    /** 
     * drupalchat path visibility 
     **/
    $form['drupalchat_path'] = array(
    	'#type' => 'details',
    	'#title' => $this->t('DrupalChat Visibility'),
      '#open' => FALSE
  	);

    $form['drupalchat_path']['drupalchat_show_embed_chat'] = array(
      '#type' => 'item',
      '#title' => $this->t("Show Embed Chat"),
      '#description' => $this->t('Click on the <a href="https://iflychat.com/docs/integration/drupal/embed-chat/how-embed-chatroom" target="_blank">link</a> to view the tutorial of embedding the chat onto a page.'),
    );

  	$access = \Drupal::currentUser()->hasPermission('use PHP for settings');
  	$options = array(
    	// 0 => $this->t('All pages except those listed'),
    	// 1 => $this->t('Only the listed pages'),
      0 => $this->t('Everywhere'),
      1 => $this->t('Frontend Only'),
      2 => $this->t('All pages except those listed'),
      3 => $this->t('Only the listed pages'),
      4 => $this->t('Disable') 
  	);

  	$description = $this->t("Specify pages by using their paths. Enter one path per line. The '*' character is a wildcard. Example paths are %blog for the blog page and %blog-wildcard for every personal blog. %front is the front page.", array('%blog' => 'blog', '%blog-wildcard' => 'blog/*', '%front' => '<front>'));

  	if (\Drupal::moduleHandler()->moduleExists('php') && $access) {
    	$options += array(5 => $this->t('Pages on which this PHP code returns <code>TRUE</code> (experts only)'));
    	$title = $this->t('Pages or PHP code');
    	$description .= ' ' . $this->t('If the PHP option is chosen, enter PHP code between %php. Note that executing incorrect PHP code can break your Drupal site.', array('%php' => '<?php ?>'));
  	}
  	else {
    	$title = $this->t('Pages');
  	}
  	$form['drupalchat_path']['drupalchat_path_visibility'] = array(
    	'#type' => 'radios',
    	'#title' => $this->t('Show DrupalChat on specific pages'),
    	'#options' => $options,
    	'#default_value' => $config->get('drupalchat_path_visibility') ?: 0,
  	);
  	$form['drupalchat_path']['drupalchat_path_pages'] = array(
    	'#type' => 'textarea',
    	'#title' => '<span>' . $title . '</span>',
    	'#default_value' => $config->get('drupalchat_path_pages') ?: NULL,
    	'#description' => $description,
  	);

  	$form['drupalchat_chatlist_cont'] = array(
    	'#type' => 'details',
    	'#title' => $this->t('DrupalChat User Online List Control'),
     	'#open' => FALSE,
  	);
  	
  	$form['drupalchat_chatlist_cont']['drupalchat_rel'] = array(
    	'#type' => 'radios',
    	'#title' => $this->t('Relationship method'),
    	'#default_value' => $config->get('drupalchat_rel')?$config->get('drupalchat_rel'):DRUPALCHAT_REL_AUTH,
    	'#options' => array(
      	DRUPALCHAT_REL_AUTH => $this->t('All users'),
    	),
    	'#description' => $this->t('This determines the method for creating the chat buddylist.'),
  	);
  	if (\Drupal::moduleHandler()->moduleExists('user_relationships')) {
    	$form['drupalchat_chatlist_cont']['drupalchat_rel']['#options'][DRUPALCHAT_REL_UR] = $this->t('User Relationship module');
  	}
  	if (\Drupal::moduleHandler()->moduleExists('flag_friend')) {
    	$form['drupalchat_chatlist_cont']['drupalchat_rel']['#options'][DRUPALCHAT_REL_FF] = $this->t('Flag Friend module');
  	}
  	if (\Drupal::moduleHandler()->moduleExists('og')) {
    	$form['drupalchat_chatlist_cont']['drupalchat_rel']['#options'][DRUPALCHAT_REL_OG] = $this->t('Organic Groups module');
  	}
  	$form['drupalchat_chatlist_cont']['drupalchat_ur_name'] = array(
    	'#type' => 'textfield',
    	'#title' => $this->t('User Relationships Role Names to integrate with'),
    	'#description' => $this->t('The singular form of User Relationships Role Names (e.g. buddy, friend, coworker, spouse) separated by comma.'),
    	'#default_value' => $config->get('drupalchat_ur_name')?$config->get('drupalchat_ur_name'):NULL,
			'#autocomplete_path' => 'drupalchat/ur-autocomplete',
  	);

    $form['drupalchat_advanced_settings'] = array(
      '#type' => 'details',
      '#title' => $this->t('Advanced Options'),
      '#open' => FALSE,
    );

    $form['drupalchat_advanced_settings']['drupalchat_session_caching'] = array(
      '#type' => 'select',
      '#title' => $this->t('Enable PHP Session Caching'),
      '#description' => $this->t('Select whether to use PHP session for caching chat authentication token.'),
      '#options' => array(1 => 'Yes', 2 => 'No'),
      '#default_value' => $config->get('drupalchat_session_caching') ?: 2,
    );
    //print_r($form);
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);

    // Load the current user.
    $user = \Drupal\user\Entity\User::load(\Drupal::currentUser()->id());
    $user_name = $user->getUsername();



    if($form_state->getValue('drupalchat_polling_method') == DRUPALCHAT_COMMERCIAL){

      $drupalchat_api_key = trim($form_state->getValue('drupalchat_external_api_key'));
      $drupalchat_app_id = trim($form_state->getValue('drupalchat_app_id'));

      $formValues = array(
        'api_key' => $drupalchat_api_key,
        'app_id' => $drupalchat_app_id
      );

      $response = drupalchatController::_drupalchat_get_auth($formValues);
      if(!array_key_exists('key', $response)){
        $form_state->setErrorByName('drupalchat_external_api_key', "Unable to connect to iFlyChat server. Error code - ". $response['code']. ". Error message - ". $response['error'] . ".");
      }

      if(!$drupalchat_app_id){ //check if app id is empty.
        $form_state->setErrorByName('drupalchat_app_id', t("Please Enter APP ID."));
      }

      if(!(strlen($drupalchat_app_id) == 36 && $drupalchat_app_id[14] == '4')){
        $form_state->setErrorByName('drupalchat_app_id', t("Invalid APP ID."));
      }

      
      if(!$drupalchat_api_key) {
        $form_state->setErrorByName('drupalchat_external_api_key', t('Please enter API key.'));
      }      

    }

  
    if ($form_state->getValue('drupalchat_rel') == DRUPALCHAT_REL_UR) {
    
      if ($form_state->getValue('drupalchat_rel')) {
        $array = drupal_explode_tags($form_state->getValue('drupalchat_rel'));
        $error = array();
        foreach($array as $key) {
          if(!db_query("SELECT COUNT(*) FROM {user_relationship_types} WHERE name = :name", array(':name' => $key))->fetchField())
            $error[] = $key;
        }
        if(!empty($error))
        $form_state->setErrorByName('drupalchat_ur_name', t('User Relationship type %type was not found.', array('%type' => drupal_implode_tags($error))));
      }
    }

  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    // print_r($form_state->getValue('drupalchat_anon_prefix'));
    // exit;
    $this->config('drupalchat.settings')
      ->set('drupalchat_polling_method', $form_state->getValue('drupalchat_polling_method'))
      ->set('drupalchat_external_api_key', $form_state->getValue('drupalchat_external_api_key'))
      ->set('drupalchat_app_id', $form_state->getValue('drupalchat_app_id'))
      ->set('drupalchat_path_visibility', $form_state->getValue('drupalchat_path_visibility'))
      ->set('drupalchat_path_pages', $form_state->getValue('drupalchat_path_pages'))
      ->set('drupalchat_rel', $form_state->getValue('drupalchat_rel'))
      ->set('drupalchat_ur_name', $form_state->getValue('drupalchat_ur_name'))
      ->set('drupalchat_theme', $form_state->getvalue('drupalchat_theme'))
      ->set('drupalchat_notification_sound', $form_state->getValue('drupalchat_notification_sound'))
      ->set('drupalchat_user_picture', $form_state->getValue('drupalchat_user_picture'))
      ->set('drupalchat_enable_smiley', $form_state->getValue('drupalchat_enable_smiley'))
      ->set('drupalchat_log_messages', $form_state->getValue('drupalchat_log_messages'))
      ->set('drupalchat_anon_use_name', $form_state->getValue('drupalchat_anon_use_name'))
      ->set('drupalchat_user_latency', $form_state->getValue('drupalchat_user_latency'))
      ->set('drupalchat_anon_prefix', $form_state->getValue('drupalchat_anon_prefix'))
      ->set('drupalchat_refresh_rate', $form_state->getValue('drupalchat_refresh_rate'))
      ->set('drupalchat_enable_chatroom', $form_state->getValue('drupalchat_enable_chatroom'))
      ->set('drupalchat_session_caching', $form_state->getValue('drupalchat_session_caching'))
      ->save();
  }

  private function _drupalchat_load_themes($outerDir, $x) {
    $dirs = array_diff(scandir($outerDir), array('.', '..'));

    $dir_array = array();
    foreach ($dirs as $d) {
      if (is_dir($outerDir . "/" . $d)) {
        if ($innerDir = drupalchatSettingsForm::_drupalchat_load_themes($outerDir . '/' . $d, $x)) {
          $dir_array[$d] = $innerDir;
        }
      }
      elseif (($x) ? preg_match('/' . $x . '$/', $d) : 1) {
        $name = drupalchatSettingsForm::_drupalchat_remove_extension($d);
        $dir_array[$name] = $name;
      }
    }
    return $dir_array;
  }

  private function _drupalchat_remove_extension($strName) {
    $ext = strrchr($strName, '.');

    if ($ext !== false) {
      $strName = substr($strName, 0, -strlen($ext));
    }
    return $strName;
  }

  

}
