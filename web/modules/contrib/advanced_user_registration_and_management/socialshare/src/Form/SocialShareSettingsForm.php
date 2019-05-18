<?php

/**
 * @file
 * Contains \Drupal\captcha\Form\CaptchaSettingsForm.
 */

namespace Drupal\socialshare\Form;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Cache\Cache;

/**
 * Displays the socialshare settings form.
 */
class SocialShareSettingsForm extends ConfigFormBase {

  /**
   * The cache backend.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $cacheBackend;

  /**
   * {@inheritdoc}
   */
  public function __construct(ConfigFactoryInterface $config_factory, CacheBackendInterface $cache_backend) {
    parent::__construct($config_factory);
    $this->cacheBackend = $cache_backend;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static($container->get('config.factory'), $container->get('cache.default'));
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['socialshare.settings'];
  }

  /**
   * Implements \Drupal\Core\Form\FormInterface::getFormID().
   */
  public function getFormId() {
    return 'socialshare_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('socialshare.settings');

    module_load_include('inc', 'socialshare');
    global $base_url;
    $my_path = drupal_get_path('module', 'socialshare');
    // Configuration of which forms to protect, with what challenge.   
    $form['open_social_share_settings'] = [
      '#type' => 'details',
      '#title' => $this->t('Social Sharing Settings'),
      '#open' => TRUE,
      '#attached' => array(
         'library' => array('socialshare/drupal.socialshare_back'),
      ),
    ];
    $form['open_social_share_settings']['horizontal'] = [
      '#type' => 'item',
      '#prefix' => '<div><b>' . t('What social sharing widget theme do you want to use across your website?</b><div>Horizontal and Vertical themes can be enabled simultaneously</div>') . '</div>',
      '#markup' => ' <div id="oss_tabs"><ul><li><a id="share_horizontal" onclick="display_horizontal_widget();">Horizontal widget</a></li><li><a id="share_veritical" onclick="hidden_horizontal_widget();">Vertical widget</a></li><li><a id="share_advance" onclick="display_advance_widget();">Advance Settings</a></li></ul>'
    ];
    $form['open_social_share_settings']['interface'] = [
      '#type' => 'hidden',
      '#title' => t('selected share interface'),
      '#default_value' => $config->get('interface'),
      '#suffix' => '<div id=sharing_divwhite></div><div id=sharing_divgrey></div><div id="show_horizontal_block">',
    ];   
    $form['open_social_share_settings']['enable_horizontal'] = [
      '#type' => 'radios',
      '#title' => t('Do you want to enable horizontal social sharing for your website?'),
      '#default_value' => $config->get('enable_horizontal', 0),
      '#options' => [
        1 => $this->t('Yes'),
        0 => $this->t('No'),
      ],
    ];
    $form['open_social_share_settings']['enable_vertical'] = [
      '#type' => 'radios',
      '#title' => t('Do you want to enable vertical social sharing for your website?'),
      '#default_value' => $config->get('enable_vertical', 0),
      '#options' => [
        1 => $this->t('Yes'),
        0 => $this->t('No'),
      ],
    ];

    $form['open_social_share_settings']['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('What text do you want to display above the social sharing widget?'),
      '#default_value' => $config->get('label'),
      '#description' => $this->t('Leave empty for no text'),
    ];
    $form['open_social_share_settings']['horizontal_images'] = [
      '#type' => 'radios',
      '#default_value' => $config->get('horizontal_images',0),
      '#options' => [
        0 => '<img src="' . $base_url . '/' . $my_path . '/images/horizonSharing32.png"></img>',
        1 => '<img src="' . $base_url . '/' . $my_path . '/images/horizonSharing16.png"></img>',
        10 => '<img src="' . $base_url . '/' . $my_path . '/images/responsive-icons.png"></img>',
        2 => '<img src="' . $base_url . '/' . $my_path . '/images/single-image-theme-large.png"></img>',
        3 => '<img src="' . $base_url . '/' . $my_path . '/images/single-image-theme-small.png"></img>',
        8 => '<img src="' . $base_url . '/' . $my_path . '/images/horizontalvertical.png"></img>',
        9 => '<img src="' . $base_url . '/' . $my_path . '/images/horizontal.png"></img>',
      ],
    ];

    $form['open_social_share_settings']['vertical_images'] = [
      '#type' => 'radios',
      '#default_value' => $config->get('vertical_images',4),
      '#options' => [
        4 => '<img id= "32VerticlewithBox" src="' . $base_url . '/' . $my_path . '/images/32VerticlewithBox.png"></img>',
        5 => '<img id="VerticlewithBox" src="' . $base_url . '/' . $my_path . '/images/16VerticlewithBox.png"></img>',
        6 => '<img id="hybrid-verticle-vertical" src="' . $base_url . '/' . $my_path . '/images/hybrid-verticle-vertical.png"></img>',
        7 => '<img id="hybrid-verticle-horizontal"  src="' . $base_url . '/' . $my_path . '/images/hybrid-verticle-horizontal.png"></img>',
      ],
    ];
    $form['open_social_share_settings']['show_horizotal'] = [
      '#type' => 'hidden',
      '#suffix' => '<div id="share_show_horizontal_widget">',
    ];
    
    $counter_providers = $config->get('counter_providers');
    if (empty($counter_providers)) {
      $counterproviders = default_sharing_networks('counter_providers');
      $config->set('counter_providers',$counterproviders);
    }
   
    $form['open_social_share_settings']['counter_providers'] = [
      '#type' => 'item',
      '#id' => 'counter_providers',
      '#title' => t('What sharing networks do you want to show in the sharing widget? (All other sharing networks will be shown as part of Social9 sharing icon)'),
      '#default_value' => $config->get('counter_providers'),
      '#suffix' => '<div id="socialcounter_hidden_field">',
    ];
    foreach ($config->get('counter_providers') as $provider) {
      if (!empty($provider)) {
        $raw = $provider;
        $provider = str_replace(array(
          ' ',
          '++',
          '+',
        ), array(
          '',
          'plusplus',
          'plus',
        ), $provider);
        $form['open_social_share_settings']['counter_rearrange["'.$provider.'"]'] = [
          '#type' => 'hidden',
          '#id'=> 'input-oscounter-'.$provider,
          '#value' => $raw ,
          '#attributes' => array('class' => array('osshare_' . $provider)),
        ];
      }
    }
    
    $share_providers = $config->get('share_rearrange');       
    if (empty($share_providers)) {             
      $sharerearrange = default_sharing_networks('share_rearrange_providers');         
      $config->set('share_rearrange',$sharerearrange);
    }     
      
    $form['open_social_share_settings']['share_providers'] = [
      '#type' => 'item',
      '#id' => 'share_providers',
      '#title' => t('What sharing networks do you want to show in the sharing widget? (All other sharing networks will be shown as part of Social9 sharing icon)'),
      '#default_value' => $config->get('share_rearrange'),
      '#prefix' => '</div><div id="loginRadiusSharingLimit">' . t('You can select only 9 providers.') . '</div>',
      '#suffix' => '<div id="rearrange_sharing_text"><b>' . t('What sharing network order do you prefer for your sharing widget?(Drag around to set the order)') . '</b></div><ul id="share_rearrange_providers" class="share_rearrange_providers">',
    ];
    
    
    foreach ($config->get('share_rearrange') as $provider) {
      if (!empty($provider)) {    
      $form['open_social_share_settings']['share_rearrange['.$provider.']'] = array(
      '#type' => 'hidden',
      '#prefix' => '<li id = "edit-osshare-iconsprite32' . $provider . '" class = "share-provider ' . $provider . ' flat square size-32 horizontal" title = "' . $provider . '">',
      '#default_value' => $provider,
      '#suffix' => '</li>',
      );
      }
    }   

    $form['open_social_share_settings']['hide_share_rearrange'] = [
      '#type' => 'hidden',
      '#prefix' => '</ul>',
      '#suffix' => '</div>',
    ];
    $form['open_social_share_settings']['show_vertical'] = [
      '#type' => 'hidden',
      '#suffix' => '<div id="share_show_veritcal_widget">',
    ];
    $vertical_counter_providers = $config->get('vertical_counter_providers');
    if (empty($vertical_counter_providers)) {   
        $verticalcounterproviders = default_sharing_networks('vertical_counter_rearrange');
        $config->set('vertical_counter_providers',$verticalcounterproviders);
    }
   
    $form['open_social_share_settings']['vertical_counter_providers'] =[
      '#type' => 'item',
      '#id' => 'vertical_counter_providers',
      '#title' => t('What sharing networks do you want to show in the sharing widget? (All other sharing networks will be shown as part of LoginRadius sharing icon)'),
      '#default_value' => $config->get('vertical_counter_providers'),
      '#suffix' => '<div id="socialcounter_vertical_hidden_field" style="display:none;">',
    ];
    foreach ($config->get('vertical_counter_providers') as $provider) {
      if (!empty($provider)) {
        $raw = $provider;
        $provider = str_replace(array(
          ' ',
          '++',
          '+',
        ), array(
          '',
          'plusplus',
          'plus',
        ), $provider);
        $form['open_social_share_settings']['vertical_counter_rearrange["'.$provider.'"'] = [
          '#type' => 'hidden',
          '#id'=> 'input-oscounter-vertical-'.$provider,
          '#value' => $raw ,          
          '#attributes' => array('class' => array('osshare_vertical_' . $provider)),
        ];
      }
    }
    
    $vertical_share_providers = $config->get('vertical_share_rearrange');
    if (empty($vertical_share_providers)) {
        $verticalshareproviders = default_sharing_networks('share_rearrange_providers');  
        $config->set('vertical_share_rearrange',$verticalshareproviders);
    }    
    
    
    $form['open_social_share_settings']['vertical_share_providers'] = [
      '#type' => 'item',
      '#id' => 'vertical_share_providers',
      '#title' => t('What sharing networks do you want to show in the sharing widget? (All other sharing networks will be shown as part of LoginRadius sharing icon)'),
      '#default_value' => $config->get('vertical_share_rearrange'),
      '#prefix' => '</div><div id="loginRadiusSharingLimit_vertical">' . t('You can select only 9 providers.') . '</div>',
      '#suffix' => '<div id="rearrange_sharing_text_vertical"><b>' . t('What sharing network order do you prefer for your sharing widget?(Drag around to set the order)') . '</b></div><ul id="share_vertical_rearrange_providers" class="share_vertical_rearrange_providers">',
    ];

  foreach ($config->get("vertical_share_rearrange") as $provider) {
    if (!empty($provider)) {
        $form['open_social_share_settings']['vertical_share_rearrange['.$provider.']'] = array(
        '#type' => 'hidden',
        '#prefix' => '<li id = "edit-osshare-iconsprite32_vertical' . $provider . '" class = "share-provider ' . $provider . ' flat square size-32 horizontal" title = "' . $provider . '">',
        '#default_value' => $provider,
        '#suffix' => '</li>',
    );
    }
  }
    $form['open_social_share_settings']['hide_vertical_share_rearrange'] = [
      '#type' => 'hidden',
      '#prefix' => '</ul>',
      '#suffix' => '</div>',
    ];
    $form['open_social_share_settings']['vertical_position'] = [
      '#type' => 'radios',
      '#title'=>t('Select the position of social sharing widget'),
      '#default_value' => $config->get('vertical_position'),
      '#options' => [
        0 => t('Top Left'),
        1 => t('Top Right'),
        2 => t('Bottom Left'),
        3 => t('Bottom Right'),
      ],
      '#attributes' => ['style' => 'clear:both;'],
    ];
    $form['open_social_share_settings']['position_top'] = [
      '#type' => 'checkbox',
      '#title' => t('Show at the top of content.'),
      '#default_value' => $config->get('position_top', 1) ? 1 : 0,
      '#prefix' => '<div id="horizontal_sharing_show" style="padding-top: 15px;"> <b>Select the position of Social sharing interface</b>',
    ];
    $form['open_social_share_settings']['position_bottom'] = [
      '#type' => 'checkbox',
      '#title' => t('Show at the bottom of content.'),
      '#default_value' => $config->get('position_bottom', 1) ? 1 : 0,
      '#suffix' => '</div>',
    ];
    $form['open_social_share_settings']['show_pages'] = [
      '#type' => 'radios',
      '#title' => t('Show social share on specific pages'),
      '#default_value' => $config->get('show_pages', 0),
      '#options' => [
        0 => t('All pages except those listed'),
        1 => t('Only the listed pages'),
      ],
    ];
    $form['open_social_share_settings']['show_exceptpages'] = [
      '#type' => 'textarea',
      '#default_value' => $config->get('show_exceptpages', ''),
      '#description' => t('Enter a page title(you give on page creation) or node id (if url is http://example.com/node/1 then enter 1(node id)) with comma separated'),
      '#rows' => 5,
    ];
    $form['open_social_share_settings']['vertical_show_pages'] = [
      '#type' => 'radios',
      '#title' => t('Show social share on specific pages'),
      '#default_value' => $config->get('vertical_show_pages', 0),
      '#options' => [
        0 => t('All pages except those listed'),
        1 => t('Only the listed pages'),
      ],
    ];
    $form['open_social_share_settings']['vertical_show_exceptpages'] = [
      '#type' => 'textarea',
      '#default_value' => $config->get('vertical_show_exceptpages', ''),
      '#description' => t('Enter a page title(you give on page creation) or node id (if url is http://example.com/node/1 then enter 1(node id)) with comma separated'),
      '#rows' => 5  
    ];
        
    $form['open_social_share_settings']['opensocialshare_email_subject'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Please enter your desired email subject'),
      '#default_value' => $config->get('opensocialshare_email_subject')  
    ];
      
    $form['open_social_share_settings']['opensocialshare_email_message'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Please enter your desired email message'),
      '#default_value' => $config->get('opensocialshare_email_message')   
    ];
    
  
    $form['open_social_share_settings']['opensocialshare_is_email_content_read_only'] = [
      '#type' => 'radios',
      '#title' => t('Do you want to make the email content read only<a title="Your readers wont be able to change the Email Content if its read only."  style="text-decoration:none"> (<span style="color:#3CF;">?</span>)</a>'),
      '#default_value' => $config->get('opensocialshare_is_email_content_read_only'),
      '#options' => [
        'true' => t('Yes (read only)'),
        'false' => t('No'),
      ],
    ];   
          
    $form['open_social_share_settings']['opensocialshare_is_shorten_url'] = [
      '#type' => 'radios',
      '#title' => t('Do you want to use short URL during sharing<a title="Enable this if you want the URL to be shortened using Ish.re"  style="text-decoration:none"> (<span style="color:#3CF;">?</span>)</a>'),
      '#default_value' => $config->get('opensocialshare_is_shorten_url'),
      '#options' => [
        'true' => t('Yes'),
        'false' => t('No'),
      ],
    ];   
    $form['open_social_share_settings']['opensocialshare_facebook_app_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Enter your Facebook App ID<a title="Enter the Facebook App Id if you want to track social sharing on your Facebook app"  style="text-decoration:none"> (<span style="color:#3CF;">?</span>)</a>'),
      '#default_value' => $config->get('opensocialshare_facebook_app_id'),
    ];
    
    $form['open_social_share_settings']['opensocialshare_is_total_share'] = [
      '#type' => 'radios',
      '#title' => t('Do you want to enable Total Share to display the total share count on your website<a title="Display the total shares URL got from all social providers"  style="text-decoration:none"> (<span style="color:#3CF;">?</span>)</a>'),
      '#default_value' => $config->get('opensocialshare_is_total_share'),
      '#options' => [
        'true' => t('Yes'),
        'false' => t('No'),
      ],
    ];   
        
    $form['open_social_share_settings']['opensocialshare_is_open_single_window'] = [
      '#type' => 'radios',
      '#title' => t('Do you want to open all providers in a single window<a title="Disabling this opens all sharing providers in a new Popup"  style="text-decoration:none"> (<span style="color:#3CF;">?</span>)</a>'),
      '#default_value' => $config->get('opensocialshare_is_open_single_window'),
      '#options' => [
        'true' => t('Yes'),
        'false' => t('No'),
      ],
    ];  
    
    $form['open_social_share_settings']['opensocialshare_popup_window'] = [
      '#type' => 'checkbox',
      '#title' => t('Check this if you want to change default Popup Window Size. [Default Size 530*530 Px]'),
      '#default_value' => $config->get('opensocialshare_popup_window', 1) ? 1 : 0,
      '#prefix' => '<div id="popup_window_size"> <b>Do you want to enable custom popup window size<a title="Check this if you want to change default Popup Window Size. [Default Size 530*530 Px]"  style="text-decoration:none"> (<span style="color:#3CF;">?</span>)</a></b>',
      '#attributes' => array(
       'onchange' => "showAndHidePopupWindow();",
       ),
    ];    
    
    $form['open_social_share_settings']['opensocialshare_popup_window_size_height'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Enter the popup window height'),
      '#default_value' => $config->get('opensocialshare_popup_window_size_height'),    
    ];
    $form['open_social_share_settings']['opensocialshare_popup_window_size_width'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Enter the popup window width'),
      '#default_value' => $config->get('opensocialshare_popup_window_size_width'),  
      '#description' => t('If you want to set popup window size then set both height and width(Ex.530,530).'),
      '#suffix' => '</div>'
      //'#element_validate' => array('element_validate_number'),
    ];
    $form['open_social_share_settings']['opensocialshare_twitter_mention'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Enter your desired Twitter handle to mention during a Twitter share<a title="Handle will be mentioned as suffix as via @twitterhandle"  style="text-decoration:none"> (<span style="color:#3CF;">?</span>)</a>'),
      '#default_value' => $config->get('opensocialshare_twitter_mention'),
    ];
    $form['open_social_share_settings']['opensocialshare_twitter_hash_tags'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Enter your desired Twitter hash tag to be used during a Twitter share<a title="Hashtag will be added to all tweets"  style="text-decoration:none"> (<span style="color:#3CF;">?</span>)</a>'),
      '#default_value' => $config->get('opensocialshare_twitter_hash_tags'),
    ];  
    $form['open_social_share_settings']['opensocialshare_is_mobile_friendly'] = [
      '#type' => 'radios',
      '#title' => t('Do you want to set mobile friendly sharing widget<a title="Enable this option to show a mobile sharing interface to mobile users"  style="text-decoration:none"> (<span style="color:#3CF;">?</span>)</a> '),
      '#default_value' => $config->get('opensocialshare_is_mobile_friendly'),
      '#options' => [
        'true' => t('Yes'),
        'false' => t('No'),
      ],
    ];  
    $form['open_social_share_settings']['opensocialshare_custom_options'] = [
      '#type' => 'textarea',
      '#title' => t('Please enter custom options for open social sharing'),
      '#id' => 'add_custom_options',
      '#default_value' => $config->get('opensocialshare_custom_options', ''),   
      '#description' => t('<p style="color:black;">Choose form the list of options you want to customize from the <a target="_blank" href="http://www.social9.com/docs/custom-option-list">link</a></p>'),
      '#rows' => 5,
      '#attributes' => array(
      'onchange' => "openSocialShareCheckValidJson();"     
      ),
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
    
   // parent::SubmitForm($form, $form_state);
    $values = $form_state->getValues();  
    $inputs = $form_state->getUserInput();
    
    $sharerearrange = default_sharing_networks('share_rearrange_providers');         
    $verticalshareproviders = default_sharing_networks('share_rearrange_providers'); 
    
    $sharing_rearrange = array();
    $vertical_share_rearrange = array();
    $this->config('socialshare.settings')

      // Remove unchecked types.
      ->set('enable_horizontal', $values['enable_horizontal'])
      ->set('enable_vertical', $values['enable_vertical'])
      ->set('label', $values['label'])
      ->set('horizontal_images', $values['horizontal_images'])
      ->set('vertical_images', $values['vertical_images'])
      ->set('counter_providers', $values['counter_providers'])
      ->set('counter_rearrange', $values['counter_providers'])
      ->set('share_providers',  isset($inputs['share_rearrange']) ? $inputs['share_rearrange'] : $sharerearrange)
      ->set('share_rearrange',  isset($inputs['share_rearrange']) ? $inputs['share_rearrange'] : $sharerearrange)
      ->set('vertical_counter_rearrange', $values['vertical_counter_providers'])
      ->set('vertical_counter_providers', $values['vertical_counter_providers'])
      ->set('vertical_share_providers', isset($inputs['vertical_share_rearrange']) ? $inputs['vertical_share_rearrange'] : $verticalshareproviders)
      ->set('vertical_share_rearrange', isset($inputs['vertical_share_rearrange']) ? $inputs['vertical_share_rearrange'] : $verticalshareproviders)
      ->set('vertical_position', $values['vertical_position'])
      ->set('position_top', $values['position_top'])
      ->set('position_bottom', $values['position_bottom'])
      ->set('show_pages', $values['show_pages'])
      ->set('show_exceptpages', $values['show_exceptpages'])
      ->set('vertical_show_pages', $values['vertical_show_pages'])
      ->set('vertical_show_exceptpages', $values['vertical_show_exceptpages'])  
      ->set('opensocialshare_email_message', $values['opensocialshare_email_message'])
      ->set('opensocialshare_email_subject', $values['opensocialshare_email_subject'])
      ->set('opensocialshare_is_email_content_read_only', $values['opensocialshare_is_email_content_read_only'])          
      ->set('opensocialshare_is_shorten_url', $values['opensocialshare_is_shorten_url'])
      ->set('opensocialshare_facebook_app_id', $values['opensocialshare_facebook_app_id'])
      ->set('opensocialshare_is_total_share', $values['opensocialshare_is_total_share'])
      ->set('opensocialshare_is_open_single_window', $values['opensocialshare_is_open_single_window'])   
      ->set('opensocialshare_popup_window', $values['opensocialshare_popup_window'])
      ->set('opensocialshare_popup_window_size_height', $values['opensocialshare_popup_window_size_height'])
      ->set('opensocialshare_popup_window_size_width', $values['opensocialshare_popup_window_size_width'])
      ->set('opensocialshare_twitter_mention', $values['opensocialshare_twitter_mention'])
      ->set('opensocialshare_twitter_hash_tags', $values['opensocialshare_twitter_hash_tags']) 
      ->set('opensocialshare_is_mobile_friendly', $values['opensocialshare_is_mobile_friendly']) 
      ->set('opensocialshare_custom_options', $values['opensocialshare_custom_options'])
      ->save();

    drupal_set_message(t('Social Share settings have been saved.'), 'status');
    //Clear page cache
    foreach (Cache::getBins() as $service_id => $cache_backend) {
      if ($service_id == 'dynamic_page_cache') {
        $cache_backend->deleteAll();
      }
    }

  }


}
