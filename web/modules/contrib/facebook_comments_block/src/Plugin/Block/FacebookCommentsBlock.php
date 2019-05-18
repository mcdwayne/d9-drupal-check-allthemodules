<?php

namespace Drupal\facebook_comments_block\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Template\Attribute;

/**
 * Provides a 'Facebook Comments Block' block.
 *
 * Drupal\Core\Block\BlockBase gives us a very useful set of basic functionality
 * for this configurable block. We can just fill in a few of the blanks with
 * defaultConfiguration(), blockForm(), blockSubmit(), and build().
 *
 * @Block(
 *   id = "facebook_comments_block",
 *   admin_label = @Translation("Facebook Comments Block"),
 *   category = @Translation("Social")
 * )
 */
class FacebookCommentsBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form = parent::blockForm($form, $form_state);

    $config = $this->getConfiguration();

    $form['facebook_comments_settings'] = array(
      '#type' => 'fieldset',
      '#title' => $this->t('Facebook comments box settings'),
      '#description' => $this->t('Configure facebook comments box.'),
      '#collapsible' => FALSE,
    );
    $form['facebook_comments_settings']['facebook_comments_block_settings_app_id'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Facebook Application ID'),
      '#default_value' => isset($config['facebook_comments_block_settings_app_id']) ? $config['facebook_comments_block_settings_app_id'] : '',
      '#maxlength' => 20,
      '#description' => $this->t('Optional: Enter Facebook App ID.'),
    );
    $form['facebook_comments_settings']['facebook_comments_block_settings_domain'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Main domain'),
      '#default_value' => isset($config['facebook_comments_block_settings_domain']) ? $config['facebook_comments_block_settings_domain'] : '',
      '#maxlength' => 75,
      '#description' => $this->t('Optional: If you have more than one domain you can set the main domain for facebook comments box to use the same commenting widget across all other domains.<br />ex: <i>http://www.mysite.com</i>'),
      '#required' => FALSE,
    );
    $form['facebook_comments_settings']['facebook_comments_block_settings_lang'] = array(
      '#type' => 'select',
      '#title' => $this->t('Language'),
      '#options' => array(
        'af_ZA' => $this->t('Afrikaans'),
        'ak_GH' => $this->t('Akan'),
        'am_ET' => $this->t('Amharic'),
        'ar_AR' => $this->t('Arabic'),
        'as_IN' => $this->t('Assamese'),
        'ay_BO' => $this->t('Aymara'),
        'az_AZ' => $this->t('Azerbaijani'),
        'be_BY' => $this->t('Belarusian'),
        'bg_BG' => $this->t('Bulgarian'),
        'bn_IN' => $this->t('Bengali'),
        'br_FR' => $this->t('Breton'),
        'bs_BA' => $this->t('Bosnian'),
        'ca_ES' => $this->t('Catalan'),
        'cb_IQ' => $this->t('Sorani Kurdish'),
        'ck_US' => $this->t('Cherokee'),
        'co_FR' => $this->t('Corsican'),
        'cs_CZ' => $this->t('Czech'),
        'cx_PH' => $this->t('Cebuano'),
        'cy_GB' => $this->t('Welsh'),
        'da_DK' => $this->t('Danish'),
        'de_DE' => $this->t('German'),
        'el_GR' => $this->t('Greek'),
        'en_GB' => $this->t('English - UK'),
        'en_IN' => $this->t('English - India'),
        'en_PI' => $this->t('English - Pirate'),
        'en_UD' => $this->t('English - Upside Down'),
        'en_US' => $this->t('English - US'),
        'eo_EO' => $this->t('Esperanto'),
        'es_CL' => $this->t('Spanish - Chile'),
        'es_CO' => $this->t('Spanish - Colombia'),
        'es_ES' => $this->t('Spanish - Spain'),
        'es_LA' => $this->t('Spanish'),
        'es_MX' => $this->t('Spanish - Mexico'),
        'es_VE' => $this->t('Spanish - Venezuela'),
        'et_EE' => $this->t('Estonian'),
        'eu_ES' => $this->t('Basque'),
        'fa_IR' => $this->t('Persian'),
        'fb_LT' => $this->t('Leet Speak'),
        'ff_NG' => $this->t('Fulah'),
        'fi_FI' => $this->t('Finnish'),
        'fo_FO' => $this->t('Faroese'),
        'fr_CA' => $this->t('French - Canada'),
        'fr_FR' => $this->t('French - France'),
        'fy_NL' => $this->t('Frisian'),
        'ga_IE' => $this->t('Irish'),
        'gl_ES' => $this->t('Galician'),
        'gn_PY' => $this->t('Guarani'),
        'gu_IN' => $this->t('Gujarati'),
        'gx_GR' => $this->t('Classical Greek'),
        'ha_NG' => $this->t('Hausa'),
        'he_IL' => $this->t('Hebrew'),
        'hi_IN' => $this->t('Hindi'),
        'hr_HR' => $this->t('Croatian'),
        'ht_HT' => $this->t('Haitian Creole'),
        'hu_HU' => $this->t('Hungarian'),
        'hy_AM' => $this->t('Armenian'),
        'id_ID' => $this->t('Indonesian'),
        'ig_NG' => $this->t('Igbo'),
        'is_IS' => $this->t('Icelandic'),
        'it_IT' => $this->t('Italian'),
        'ja_JP' => $this->t('Japanese'),
        'ja_KS' => $this->t('Japanese - Kansai'),
        'jv_ID' => $this->t('Javanese'),
        'ka_GE' => $this->t('Georgian'),
        'kk_KZ' => $this->t('Kazakh'),
        'km_KH' => $this->t('Khmer'),
        'kn_IN' => $this->t('Kannada'),
        'ko_KR' => $this->t('Korean'),
        'ku_TR' => $this->t('Kurdish - Kurmanji'),
        'ky_KG' => $this->t('Kyrgyz'),
        'la_VA' => $this->t('Latin'),
        'lg_UG' => $this->t('Ganda'),
        'li_NL' => $this->t('Limburgish'),
        'ln_CD' => $this->t('Lingala'),
        'lo_LA' => $this->t('Lao'),
        'lt_LT' => $this->t('Lithuanian'),
        'lv_LV' => $this->t('Latvian'),
        'mg_MG' => $this->t('Malagasy'),
        'mi_NZ' => $this->t('Māori'),
        'mk_MK' => $this->t('Macedonian'),
        'ml_IN' => $this->t('Malayalam'),
        'mn_MN' => $this->t('Mongolian'),
        'mr_IN' => $this->t('Marathi'),
        'ms_MY' => $this->t('Malay'),
        'mt_MT' => $this->t('Maltese'),
        'my_MM' => $this->t('Burmese'),
        'nb_NO' => $this->t('Norwegian - bokmal'),
        'nd_ZW' => $this->t('Ndebele'),
        'ne_NP' => $this->t('Nepali'),
        'nl_BE' => $this->t('Dutch - België'),
        'nl_NL' => $this->t('Dutch'),
        'nn_NO' => $this->t('Norwegian - nynorsk'),
        'ny_MW' => $this->t('Chewa'),
        'or_IN' => $this->t('Oriya'),
        'pa_IN' => $this->t('Punjabi'),
        'pl_PL' => $this->t('Polish'),
        'ps_AF' => $this->t('Pashto'),
        'pt_BR' => $this->t('Portuguese - Brazil'),
        'pt_PT' => $this->t('Portuguese - Portugal'),
        'qc_GT' => $this->t('Quiché'),
        'qu_PE' => $this->t('Quechua'),
        'rm_CH' => $this->t('Romansh'),
        'ro_RO' => $this->t('Romanian'),
        'ru_RU' => $this->t('Russian'),
        'rw_RW' => $this->t('Kinyarwanda'),
        'sa_IN' => $this->t('Sanskrit'),
        'sc_IT' => $this->t('Sardinian'),
        'se_NO' => $this->t('Northern Sámi'),
        'si_LK' => $this->t('Sinhala'),
        'sk_SK' => $this->t('Slovak'),
        'sl_SI' => $this->t('Slovenian'),
        'sn_ZW' => $this->t('Shona'),
        'so_SO' => $this->t('Somali'),
        'sq_AL' => $this->t('Albanian'),
        'sr_RS' => $this->t('Serbian'),
        'sv_SE' => $this->t('Swedish'),
        'sw_KE' => $this->t('Swahili'),
        'sy_SY' => $this->t('Syriac'),
        'sz_PL' => $this->t('Silesian'),
        'ta_IN' => $this->t('Tamil'),
        'te_IN' => $this->t('Telugu'),
        'tg_TJ' => $this->t('Tajik'),
        'th_TH' => $this->t('Thai'),
        'tk_TM' => $this->t('Turkmen'),
        'tl_PH' => $this->t('Filipino'),
        'tl_ST' => $this->t('Klingon'),
        'tr_TR' => $this->t('Turkish'),
        'tt_RU' => $this->t('Tatar'),
        'tz_MA' => $this->t('Tamazight'),
        'uk_UA' => $this->t('Ukrainian'),
        'ur_PK' => $this->t('Urdu'),
        'uz_UZ' => $this->t('Uzbek'),
        'vi_VN' => $this->t('Vietnamese'),
        'wo_SN' => $this->t('Wolof'),
        'xh_ZA' => $this->t('Xhosa'),
        'yi_DE' => $this->t('Yiddish'),
        'yo_NG' => $this->t('Yoruba'),
        'zh_CN' => $this->t('Simplified Chinese - China'),
        'zh_HK' => $this->t('Traditional Chinese - Hong Kong'),
        'zh_TW' => $this->t('Traditional Chinese - Taiwan'),
        'zu_ZA' => $this->t('Zulu'),
        'zz_TR' => $this->t('Zazaki'),
      ),
      '#default_value' => isset($config['facebook_comments_block_settings_lang']) ? $config['facebook_comments_block_settings_lang'] : 'en_US',
      '#description' => $this->t('Select the default language of the comments plugin.'),
      '#required' => TRUE,
    );
    $form['facebook_comments_settings']['facebook_comments_block_settings_color_schema'] = array(
      '#type' => 'select',
      '#title' => $this->t('Color scheme'),
      '#options' => array(
        'light' => $this->t('Light'),
        'dark' => $this->t('Dark'),
      ),
      '#default_value' => isset($config['facebook_comments_block_settings_color_schema']) ? $config['facebook_comments_block_settings_color_schema'] : 'light',
      '#description' => $this->t('Set the color schema of facebook comments box.'),
      '#required' => TRUE,
    );
    $form['facebook_comments_settings']['facebook_comments_block_settings_order'] = array(
      '#type' => 'select',
      '#title' => $this->t('Order of comments'),
      '#options' => array(
        'social' => t('Top'),
        'reverse_time' => t('Newest'),
        'time' => t('Oldest'),
      ),
      '#default_value' => isset($config['facebook_comments_block_settings_order']) ? $config['facebook_comments_block_settings_order'] : 'social',
      '#description' => $this->t('Set the order of comments.'),
      '#required' => TRUE,
    );
    $form['facebook_comments_settings']['facebook_comments_block_settings_number_of_posts'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Number of posts'),
      '#default_value' => isset($config['facebook_comments_block_settings_number_of_posts']) ? $config['facebook_comments_block_settings_number_of_posts'] : '5',
      '#maxlength' => 3,
      '#description' => $this->t('Select how many posts you want to display by default.'),
    );
    $form['facebook_comments_settings']['facebook_comments_block_settings_width_unit'] = array(
      '#type' => 'select',
      '#title' => $this->t('Width'),
      '#options' => array(
        'percentage' => t('100%'),
        'pixel' => t('Pixels'),
      ),
      '#default_value' => isset($config['facebook_comments_block_settings_width_unit']) ? $config['facebook_comments_block_settings_width_unit'] : 'percentage',
      '#description' => $this->t('Set width of facebook comments box.'),
      '#required' => TRUE,
    );
    $form['facebook_comments_settings']['facebook_comments_block_settings_width'] = array(
      '#type' => 'textfield',
      '#default_value' => isset($config['facebook_comments_block_settings_width']) ? $config['facebook_comments_block_settings_width'] : '500',
      '#maxlength' => 4,
      '#description' => $this->t('Enter the with in <em>px</em>.'),
      '#required' => TRUE,
      '#states' => array(
        'visible' => array(
          ':input[id="edit-settings-facebook-comments-settings-facebook-comments-block-settings-width-unit"]' => array('value' => 'pixel'),
        ),
        'required' => array(
          ':input[id="edit-settings-facebook-comments-settings-facebook-comments-block-settings-width-unit"]' => array('value' => 'pixel'),
        ),
      ),
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $this->setConfigurationValue('facebook_comments_block_settings_app_id', $form_state->getValue(array('facebook_comments_settings', 'facebook_comments_block_settings_app_id')));
    $this->setConfigurationValue('facebook_comments_block_settings_domain', rtrim(rtrim($form_state->getValue(array('facebook_comments_settings', 'facebook_comments_block_settings_domain'))), '/'));
    $this->setConfigurationValue('facebook_comments_block_settings_lang', $form_state->getValue(array('facebook_comments_settings', 'facebook_comments_block_settings_lang')));
    $this->setConfigurationValue('facebook_comments_block_settings_color_schema', $form_state->getValue(array('facebook_comments_settings', 'facebook_comments_block_settings_color_schema')));
    $this->setConfigurationValue('facebook_comments_block_settings_order', $form_state->getValue(array('facebook_comments_settings', 'facebook_comments_block_settings_order')));
    $this->setConfigurationValue('facebook_comments_block_settings_number_of_posts', $form_state->getValue(array('facebook_comments_settings', 'facebook_comments_block_settings_number_of_posts')));
    $this->setConfigurationValue('facebook_comments_block_settings_width_unit', $form_state->getValue(array('facebook_comments_settings', 'facebook_comments_block_settings_width_unit')));
    $this->setConfigurationValue('facebook_comments_block_settings_width', $form_state->getValue(array('facebook_comments_settings', 'facebook_comments_block_settings_width')));
  }

  /**
   * {@inheritdoc}
   */
  public function blockValidate($form, FormStateInterface $form_state) {
    $main_domain = $form_state->getValue(array('facebook_comments_settings', 'facebook_comments_block_settings_domain'));
    if ($main_domain !== '' && (!UrlHelper::isValid($main_domain, TRUE))) {
      drupal_set_message($this->t('Main domain must be a valid absolute URL.'), 'error');
      $form_state->setErrorByName('facebook_comments_block_settings_domain', $this->t('Main domain must be a valid absolute URL.'));
    }

    $number_of_posts = $form_state->getValue(array('facebook_comments_settings', 'facebook_comments_block_settings_number_of_posts'));
    if (!is_numeric($number_of_posts)) {
      drupal_set_message($this->t('Number of posts must be a valid number.'), 'error');
      $form_state->setErrorByName('facebook_comments_block_settings_number_of_posts', $this->t('Number of posts must be a valid number.'));
    }

    $width = $form_state->getValue(array('facebook_comments_settings', 'facebook_comments_block_settings_width'));
    if (!is_numeric($width)) {
      drupal_set_message($this->t('Width must be a valid number.'), 'error');
      $form_state->setErrorByName('facebook_comments_block_settings_width', $this->t('Width must be a valid number.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    global $base_url;
    $config = $this->getConfiguration();
    $current_unaliased_path = \Drupal::service('path.current')->getPath();
    $current_aliased_path = \Drupal::service('path.alias_manager')->getAliasByPath($current_unaliased_path);

    $main_domain = $base_url;
    if ($config['facebook_comments_block_settings_domain'] !== '') {
      $main_domain = $config['facebook_comments_block_settings_domain'];
    }

    $url = $main_domain . $current_aliased_path;

    $facebook_app_id = $config['facebook_comments_block_settings_app_id'];
    $facebook_app_id_script = ($facebook_app_id != '') ? "&appId=$facebook_app_id" : '';
    $facebook_app_lang = $config['facebook_comments_block_settings_lang'];

    $js_vars = array(
      'facebook_app_id' => $facebook_app_id,
      'facebook_app_id_script' => $facebook_app_id_script,
      'facebook_app_lang' => $facebook_app_lang,
    );

    $facebook_app_width_unit = $config['facebook_comments_block_settings_width_unit'];
    $facebook_app_width = '';
    if ($facebook_app_width_unit == 'percentage') {
      $facebook_app_width = '100%';
    } else {
      $facebook_app_width = $config['facebook_comments_block_settings_width'];
    }

    $theme_vars = array(
      'data_attributes' => array(
        'href' => $url,
        'data-href' => $url,
        'data-width' => $facebook_app_width,
        'data-numposts' => $config['facebook_comments_block_settings_number_of_posts'],
        'data-colorscheme' => $config['facebook_comments_block_settings_color_schema'],
        'data-order-by' => $config['facebook_comments_block_settings_order'],
      ),
    );

    return array(
      '#cache' => array(
        'contexts' => array('url'),
      ),
      '#theme' => 'facebook_comments_block_block',
      '#data_attributes' => new Attribute($theme_vars['data_attributes']),
      '#attached' => array(
        'library' =>  array(
          'facebook_comments_block/facebook-comments-block',
        ),
        'drupalSettings' => array(
          'facebook_comments_block_settings' => $js_vars,
        ),
      ),
    );
  }

}
