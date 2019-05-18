<?php

/**
 * @file
 * Contains \Drupal\captcha\Form\CaptchaSettingsForm.
 */

namespace Drupal\socialprofiledata\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Cache\Cache;

/**
 * Displays the socialprofiledata settings form.
 */
class SocialProfileDataSettingsForm extends ConfigFormBase {


  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['socialprofiledata.settings'];
  }

  /**
   * Implements \Drupal\Core\Form\FormInterface::getFormID().
   */
  public function getFormId() {
    return 'socialprofiledata_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('socialprofiledata.settings');
     global $base_url;
    // Configuration of which forms to protect, with what challenge.
    $form['socialprofiledata'] = [
      '#type' => 'details',
      '#title' => $this->t('Social Profile Data'),
       '#open' => TRUE,
    ];
  $form['socialprofiledata']['profile_selection'] = [
    '#title' => t('Please select the user profile data fields you would like to save in your database:'),
    '#description' => t('For a list of all fields <a href="https://secure.loginradius.com/datapoints" target="_blank"> https://secure.loginradius.com/datapoints </a>'),
    '#type' => 'checkboxes',
      '#multiple' => TRUE,
    '#options' => [
      'basic_profile_data' => t('Basic Profile Data<a title="Data fields include: Social ID, Social ID Provider, Prefix, First Name, Middle Name, Last Name, Suffix, Full Name, Nick Name, Profile Name, Birthdate, Gender, Country Code, Country Name, Thumbnail Image Url, Image Url, Local Country, Profile Country"  style="text-decoration:none"> (<span style="color:#3CF;">?</span>)</a>'),
      'extended_location_data' => t('Extended Location Data<a title="Data fields include: Main Address, Hometown, State, City, Local City, Profile City, Profile Url, Local Language, Language" style="text-decoration:none"> (<span style="color:#3CF;">?</span>)</a>'),
      'extended_profile_data' => t('Extended Profile Data<a title="Data fields include: Website, Favicon, Industry, No of login, About, Timezone, Verified, Last Profile Update, Created, Relationship Status, Favorite Quote, Interested In, Interests, Religion, Political View, HTTPS Image Url, Followers Count, Friends Count, Is Geo Enabled, Total Status Count, Number of Recommenders, Honors, Associations, Hirable, Repository Url, Age, Professional Headline, Provider Access Token, Provider Token Secret, Positions, Companies, Education, Phone Numbers, IM Accounts, Addresses, Sports, Inspirational People, Skills, Current Status, Certifications, Courses, Volunteer, Recommendations Received, Languages, Patents, Favorites" style="text-decoration:none"> (<span style="color:#3CF;">?</span>)</a>'),
      'followed_companies_on' => t('Followed Companies on LinkedIn<a title="A list of all the companies this user follows on LinkedIn."  style="text-decoration:none"> (<span style="color:#3CF;">?</span>)</a>'),
      'facebook_profile_likes' => t('Facebook Profile Likes<a title="A list of Likes on the Facebook profile of user"  style="text-decoration:none"> (<span style="color:#3CF;">?</span>)</a>'),
      'facebook_profile_events' => t('Facebook Profile Events<a title="A list of events (birthdays, invitation, etc.) on the Facebook profile of user"  style="text-decoration:none"> (<span style="color:#3CF;">?</span>)</a>'),
      'status_message' => t('Status Messages<a title="Facebook wall activity, Twitter tweets and LinkedIn status of the user, including links"  style="text-decoration:none"> (<span style="color:#3CF;">?</span>)</a>'),
      'facebook_posts' => t('Facebook Posts<a title="Facebook posts of the user, including links"  style="text-decoration:none"> (<span style="color:#3CF;">?</span>)</a>'),
      'twitter_mentions' => t('Twitter Mentions<a title="A list of tweets that the user is mentioned in."  style="text-decoration:none"> (<span style="color:#3CF;">?</span>)</a>'),
      'lr_groups' => t('Groups<a title="A list of the Facebook groups of user."  style="text-decoration:none"> (<span style="color:#3CF;">?</span>)</a>'),
      'lr_contacts' => t('Contacts/Friend Data<a title="For email providers (Google and Yahoo), a list of the contacts of user in his/her address book. For social networks (Facebook, Twitter, and LinkedIn), a list of the people in the network of user."  style="text-decoration:none"> (<span style="color:#3CF;">?</span>)</a>'),
  ],
    '#default_value' => (array) $config->get('profile_selection'),
  ];
  $form['socialprofiledata']['show_profile'] = [
    '#type' => 'radios',
    '#title' => t('Do you want to show all the saved user profile data for each user in the Drupal admin panel?<a title="If enabled, you will be able to see a list of the saved data collected from each user on the PEOPLE page in the Drupal admin panel."  style="text-decoration:none"> (<span style="color:#3CF;">?</span>)</a>'),
    '#default_value' => $config->get('show_profile') ?  $config->get('show_profile') : 0,
    '#options' => [
      1 => t('YES, display the option to view profile data on the  <a href="@block_socialprofiledata" target="_blank">People</a> page', array('@block_socialprofiledata' => $base_url.'/admin/people')),
      0 => t('No'),
    ],
  ];
    
    // Submit button.
    $form['actions'] = ['#type' => 'actions'];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => t('Save configuration'),
    ];

    return parent::buildForm($form, $form_state);
  }
/**
 * Check table exist int database and get scheme of that table and create table.
 *
 * @param string $table_name Database table name
 */
function socialprofiledata_check_table_exists_and_create($table_name) {
  if (!\Drupal\Core\Database\Database::getConnection()->schema()->tableExists($table_name)) {
    $function_table_name = 'socialprofiledata_' . $table_name;
    $schema = $function_table_name();
    \Drupal\Core\Database\Database::getConnection()->schema()->createTable($table_name, $schema);

  }
}

/**
 * Check admin selected this option to create table in your database.
 *
 * @param string $key Stored key value of extended data in database.
 * @param string $value Database table name
 * @param array $profile_selection Array of values that contain the extended data selection
 */
function socialprofiledata_check_table_selected_in_database($key, $value, $profile_selection) {
  if (in_array($key, $profile_selection)) {
    $this->socialprofiledata_check_table_exists_and_create($value);
  }
}
  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $sl_config = \Drupal::config('sociallogin.settings');
    $apiKey = $sl_config->get('api_key'); 
    $apiSecret = $sl_config->get('api_secret'); 
    if($apiKey == ''){
      $apiKey = '';
      $apiSecret = '';
    }
    
    module_load_include('inc', 'sociallogin');
    $data = get_authentication($apiKey, $apiSecret);   
    if (isset($data['status']) && $data['status'] != 'status') {
      drupal_set_message($data['message'], $data['status']);
      return FALSE;
    }
    
    parent::SubmitForm($form, $form_state);
    $this->config('socialprofiledata.settings')
      ->set('profile_selection', $form_state->getValue('profile_selection'))
      ->set('show_profile', $form_state->getValue('show_profile'))
 
      ->save();
       $socialprofiledata_profile_selection = explode(',', implode(',', $form_state->getValue('profile_selection', '')));
  module_load_include('inc', 'socialprofiledata', 'socialprofiledata.sql');

  if (in_array('basic_profile_data', $socialprofiledata_profile_selection)) {
    $this->socialprofiledata_check_table_exists_and_create('loginradius_basic_profile_data');
    $this->socialprofiledata_check_table_exists_and_create('loginradius_emails');
  }

  if (in_array('extended_profile_data', $socialprofiledata_profile_selection)) {
    $extended_profile_data_array = array(
      'loginradius_extended_profile_data',
      'loginradius_positions',
      'loginradius_companies',
      'loginradius_education',
      'loginradius_phone_numbers',
      'loginradius_IMaccounts',
      'loginradius_addresses',
      'loginradius_sports',
      'loginradius_inspirational_people',
      'loginradius_skills',
      'loginradius_current_status',
      'loginradius_certifications',
      'loginradius_courses',
      'loginradius_volunteer',
      'loginradius_recommendations_received',
      'loginradius_languages',
      'loginradius_patents',
      'loginradius_favorites',
      'loginradius_books',
      'loginradius_games',
      'loginradius_television_show',
      'loginradius_movies',
    );
    if(is_array($extended_profile_data_array)){
    foreach ($extended_profile_data_array as $table_name) {
      $this->socialprofiledata_check_table_exists_and_create($table_name);
    }
  }
  }

  $create_table = array(
    'extended_location_data' => 'loginradius_extended_location_data',
    'followed_companies_on' => 'loginradius_linkedin_companies',
    'facebook_profile_likes' => 'loginradius_facebook_likes',
    'facebook_profile_events' => 'loginradius_facebook_events',
    'status_message' => 'loginradius_status',
    'facebook_posts' => 'loginradius_facebook_posts',
    'twitter_mentions' => 'loginradius_twitter_mentions',
    'lr_groups' => 'loginradius_groups',
    'lr_contacts' => 'loginradius_contacts',
  );

  foreach ($create_table as $table_key => $table_name) {
    $this->socialprofiledata_check_table_selected_in_database($table_key, $table_name, $socialprofiledata_profile_selection);
  }
    drupal_set_message(t('Social Profile data settings have been saved.'), 'status');
    //Clear page cache
    foreach (Cache::getBins() as $service_id => $cache_backend) {
      if ($service_id == 'dynamic_page_cache') {
        $cache_backend->deleteAll();
      }
    }

  }
}
