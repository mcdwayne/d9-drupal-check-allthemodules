<?php

namespace Drupal\tacjs;

/**
 * Class TacjsSettings.
 */
class TacjsSettings {


  const SOCIAL_NETWORKS = [
    'facebook' => [
      'value' => 'facebook_id',
      'name' => 'Facebook',
      'description' => '',
    ],
    'twitter' => [
      'value' => 'hidden',
      'name' => 'Twitter',
    ],
    'facebook_pixel' => [
      'value' => 'facebookpixelId',
      'name' => 'facebookPixel',
      'description' => 'tarteaucitron.user.facebookpixelId =YOUR-ID',
    ],
    'twitter_cards' => [
      'value' => 'hidden',
      'name' => 'TwitterCards',
    ],
    'addtoany_share' => [
      'value' => 'hidden',
      'name' => 'AddToAnyShare',
    ],
    'googleplus' => [
      'value' => 'hidden',
      'name' => 'Googleplus',
    ],
    'linkedin' => [
      'value' => 'hidden',
      'name' => 'Linkedin',
    ],
    'pinterest' => [
      'value' => 'hidden',
      'name' => 'Pinterest',
    ],
    'shareaholic' => [
      'value' => 'shareaholic',
      'name' => 'Sharedaholic',
      'description' => 'tarteaucitron.user.shareaholicSiteId =site_id',
    ],
    'shareThis' => [
      'value' => 'shareThis',
      'name' => 'ShareThis',
      'description' => 'tarteaucitron.user.sharethisPublisher = publisher',
    ],
    'twitter_timelines' => [
      'value' => 'hidden',
      'name' => 'TwitterTimeLines',
    ],
    'facebook_like_box' => [
      'value' => 'hidden',
      'name' => 'FacebookLikeBox',
    ],
    'addthis' => [
      'value' => 'addthis',
      'name' => 'Addthis',
      'description' => 'tarteaucitron.user.addthisPubId = YOUR-PUB-ID',
    ],
    'addtoany_feed' => [
      'value' => 'hidden',
      'name' => 'AddToAnyFeed',
    ],
    'google_plus_badge' => [
      'value' => 'hidden',
      'name' => 'GooglePlusBadge',
    ],
    'ekomi' => [
      'value' => 'ekomi',
      'name' => 'Ekomi',
      'description' => ' tarteaucitron.user.ekomiCertId = CERT-ID',
    ],
  ];

  const APIS = [
    'google_jsapi' => [
      'value' => 'hidden',
      'name' => 'GoogleJsapi',
    ],
    'google_maps' => [
      'value' => 'google_maps',
      'name' => 'GoogleMaps',
    ],
    'google_maps_search_query' => [
      'value' => 'hidden',
      'name' => 'GoogleMapsSearchQuery',
    ],
    'google_tag_manager' => [
      'value' => 'google_tag_manager',
      'name' => 'GoogleTagManager',
    ],
    'reCAPTCHA' => [
      'value' => 'hidden',
      'name' => 'reCAPTCHA',
    ],
    'timeline_js' => [
      'value' => 'hidden',
      'name' => 'TimelineJS',
    ],
    'typekit_adobe' => [
      'value' => 'typekit_adobe',
      'name' => 'TypekitId',
    ],
  ];

  const COMMENT = [
    'disqus' => [
      'value' => 'disqus',
      'name' => 'Disqus',
    ],
    'facebook_commentaire' => [
      'value' => 'hidden',
      'name' => 'FacebookCommentaire',
    ],
  ];

  const  MESURE_AUDIENCE = [
    'alexa' => [
      'value' => 'alexa',
      'name' => 'Alexa',
    ],
    'clicky' => [
      'value' => 'clicky',
      'name' => 'Clicky',
    ],
    'crazyegg' => [
      'value' => 'crazyegg',
      'name' => 'Crazyegg',
    ],
    'etracker' => [
      'value' => 'etracker',
      'name' => 'Etracker',
    ],
    'ferank' => [
      'value' => 'hidden',
      'name' => 'FERank',
    ],
    'getplus' => [
      'value' => 'getplus',
      'name' => 'Getplus',
    ],
    'gajs' => [
      'value' => 'gajs',
      'name' => 'Gajs',
    ],
    'gtag' => [
      'value' => 'gtag',
      'name' => 'Gtag',
    ],
    'multiplegtag' => [
      'value' => 'multiplegtag',
      'name' => 'Multiplegtag',
    ],
    'analytics' => [
      'value' => 'analytics',
      'name' => 'Analytics',
    ],
    'koban' => [
      'value' => 'koban_api',
      'name' => 'Koban',
      'value_' => 'Koban_url',
    ],
    'mautic' => [
      'value' => 'mautic',
      'name' => 'Mautic',
    ],
    'microsoftcampaignanalytics' => [
      'value' => 'hidden',
      'name' => 'MicrosoftCampaignAnalytics',
    ],
    'statcounter' => [
      'value' => 'hidden',
      'name' => 'StatCounter',
    ],
    'visualrevenue' => [
      'value' => 'visualrevenue',
      'name' => 'VisualRevenue',
    ],
    'webmecanik' => [
      'value' => 'webmecanik',
      'name' => 'Webmecanik',
    ],
    'wysistat' => [
      'value' => 'hidden',
      'name' => 'Wysistat',
    ],
    'xiti' => [
      'value' => 'xiti',
      'name' => 'Xiti',
    ],
  ];

  const  REGIE_PUBLICITAIRE = [
    'adsensesearchresult' => [
      'value' => 'adsensesearchresult',
      'name' => 'AdsenseSearchResult',
    ],
    'googleadwordsremarketing' => [
      'value' => 'googleadwordsremarketing',
      'name' => 'Googleadwordsremarketing',
    ],
  ];

  const  SUPPORT = [
    'purechat' => [
      'value' => 'purechat',
      'name' => 'Purechat',
    ],
    'uservoice' => [
      'value' => 'uservoice',
      'name' => 'Uservoice',
    ],
    'zopim' => [
      'value' => 'zopim',
      'name' => 'Zopim',
    ],
  ];

  /**
   * Function return config selected.
   *
   * @param mixed $type
   *   Variable for check variable "les services regie publicitaire".
   *
   * @return array
   *   This function return all services selects.
   */
  public function getFieldsSelects($type = NULL) {

    $values_selected = $this->getValuesStepOne();
    $fields = [];
    $fields['social_networks'] = !empty($values_selected['type_social_networks']) ? $this->getFields($values_selected['type_social_networks'], TacjsSettings::SOCIAL_NETWORKS) : [];
    $fields['apis'] = !empty($values_selected['type_apis']) ? $this->getFields($values_selected['type_apis'], TacjsSettings::APIS) : [];
    $fields['comment'] = !empty($values_selected['type_commentaire']) ? $this->getFields($values_selected['type_commentaire'], TacjsSettings::COMMENT) : [];
    $fields['mesure_audience'] = !empty($values_selected['type_mesure_audience']) ? $this->getFields($values_selected['type_mesure_audience'], TacjsSettings::MESURE_AUDIENCE) : [];
    $fields['support'] = !empty($values_selected['type_support']) ? $this->getFields($values_selected['type_support'], TacjsSettings::SUPPORT) : [];
    $fields['regie_pub'] = !empty($values_selected['type_regie_publicitaire']) ? $this->getFields($values_selected['type_regie_publicitaire'], TacjsSettings::REGIE_PUBLICITAIRE, $type) : [];

    return $fields;

  }

  /**
   * GetFields.
   *
   * @param mixed $values
   *   It has all congif chooses in backoffice.
   * @param mixed $data
   *   Constant variable contains the services.
   * @param mixed $type
   *   this variable juste for check if service == "Regi publicitire".
   *
   * @return array
   *   Return all services selected.
   */
  protected function getFields($values, $data, $type = NULL) {
    $result = [];
    if ($type == "module") {
      $config = \Drupal::getContainer()
        ->get('config.factory')
        ->getEditable('tacjs.admin_settings_form');
      $result['options'] = $config->get('type_regie_publicitaire');
    }
    foreach ($values as $item) {
      if (!empty($data[$item])) {
        if ($type == "module") {
          $pos = array_search($data[$item]['value'], $result['options']);
          if ($pos != FALSE) {
            unset($result['options'][$item]);
          }
        }
        $result[] = $data[$item];
      }
    }
    return $result;

  }

  /**
   * Get Values Step One Form.
   *
   * @return mixed
   *   Return result.
   */
  private function getValuesStepOne() {
    $config = \Drupal::getContainer()
      ->get('config.factory')
      ->getEditable('tacjs.admin_settings_form');
    $result = unserialize($config->get('donnes_step_one'));
    return $result;
  }

  /**
   * Serialize Form values.
   *
   * @param mixed $formvalues
   *   Variable serialize contains all config.
   *
   * @return string
   *   Return variable.
   */
  public function serializeValuesForm(&$formvalues) {
    $formvalues = $this->cleanValues($formvalues);
    return serialize($formvalues);
  }

  /**
   * Clean value : remove item is not necessary.
   *
   * @param mixed $formvalues
   *   Variable contains all config.
   *
   * @return mixed
   *   return clean variable withour submit,form_id...
   */
  public function cleanValues($formvalues) {

    if (isset($formvalues['submit'])) {
      unset($formvalues['submit']);
    }
    if (isset($formvalues['form_build_id'])) {
      unset($formvalues['form_build_id']);
    }
    if (isset($formvalues['form_token'])) {
      unset($formvalues['form_token']);
    }
    if (isset($formvalues['form_id'])) {
      unset($formvalues['form_id']);
    }
    if (isset($formvalues['op'])) {
      unset($formvalues['op']);
    }

    return $formvalues;

  }

 /**
  * GetOptionsTacjs.
  *
  * @return mixed
  */
 public function getOptionsTacjs(){
    $config = \Drupal::getContainer()
      ->get('config.factory')
      ->getEditable('tacjs.admin_settings_form');
    $data['cookie_name'] = $config->get('cookie_name');
    $data['high_privacy'] = $config->get('high_privacy');
    $data['orientation'] = $config->get('orientation');
    $data['adblocker'] = $config->get('adblocker');
    $data['show_alertSmall'] = $config->get('show_alertSmall');
    $data['removeCredit'] = $config->get('removeCredit');
    $data['cookieslist'] = $config->get('cookieslist');
    $data['orientation'] = $config->get('orientation');
    $data['handleBrowserDNTRequest'] = $config->get('handleBrowserDNTRequest');
    return $data;
  }

}
