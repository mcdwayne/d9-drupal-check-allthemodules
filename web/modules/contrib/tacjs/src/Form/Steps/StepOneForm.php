<?php

namespace Drupal\tacjs\Form\Steps;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class StepOneForm.
 *
 * @package Drupal\tacjs\Form
 */
class StepOneForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'tacjs_configuration_one_step';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $config = \Drupal::getContainer()
      ->get('config.factory')
      ->getEditable('tacjs.admin_settings_form');

    $form['tarteaucitron'] = [
      '#type' => 'details',
      '#title' => $this
        ->t('Configuration Global Tarte au Citron'),
    ];
    // Réseaux Sociaux.
    $form['tarteaucitron']['type_social_networks'] = [
      '#type' => 'select',
      '#multiple' => TRUE,
      '#title' => $this->t('Sélectionner le Service Réseaux Sociaux'),
      '#default_value' => $config->get('type_social_networks'),
      '#options' => [
        'addthis' => $this->t('AddThis'),
        'addtoany_feed' => $this->t('AddToAny (feed)'),
        'addtoany_share' => $this->t('AddToAny (share)'),
        'ekomi' => $this->t('eKomi'),
        'facebook' => $this->t('Facebook'),
        'facebook_like_box' => $this->t('Facebook (like box)'),
        'facebook_pixel' => $this->t('Facebook Pixel'),
        'googleplus' => $this->t('Google+'),
        'google_plus_badge' => $this->t('Google+ (badge)'),
        'linkedin' => $this->t('Linkedin'),
        'pinterest' => $this->t('Pinterest'),
        'shareaholic' => $this->t('Shareaholic'),
        'shareThis' => $this->t('ShareThis'),
        'twitter' => $this->t('Twitter'),
        'twitter_cards' => $this->t('Twitter (cards)'),
        'twitter_timelines' => $this->t('Twitter (timelines)'),
      ],
    ];
    // Videos.
    $form['tarteaucitron']['type_video'] = [
      '#type' => 'select',
      '#multiple' => TRUE,
      '#title' => $this->t('Sélectionner le Service Video'),
      '#default_value' => $config->get('type_video'),
      '#options' => [
        'calameo' => $this->t('Calaméo'),
        'dailymotion' => $this->t('Dailymotion'),
        'issuu' => $this->t('Issuu'),
        'prezi' => $this->t('Prezi'),
        'slideShare' => $this->t('SlideShare'),
        'vimeo' => $this->t('Vimeo'),
        'youtube' => $this->t('Youtube'),
      ],
    ];
    // Type API.
    $form['tarteaucitron']['type_apis'] = [
      '#type' => 'select',
      '#multiple' => TRUE,
      '#title' => $this->t('Sélectionner le Service APIs'),
      '#default_value' => $config->get('type_apis'),
      '#options' => [
        'google_jsapi' => $this->t('Google jsapi'),
        'google_maps' => $this->t('Google Maps'),
        'google_maps_search_query' => $this->t('Google Maps (search query)'),
        'google_tag_manager' => $this->t('Google Tag Manager'),
        'reCAPTCHA' => $this->t('reCAPTCHA'),
        'timeline_js' => $this->t('Timeline JS'),
        'typekit_adobe' => $this->t('Typekit (adobe)'),
      ],
    ];
    // Comment.
    $form['tarteaucitron']['type_commentaire'] = [
      '#type' => 'select',
      '#multiple' => TRUE,
      '#title' => $this->t('Sélectionner le Service Commentaire'),
      '#default_value' => $config->get('type_commentaire'),
      '#options' => [
        'DisqusInstaller' => $this->t('Disqus'),
        'facebook_commentaire' => $this->t('Facebook (comment)'),
      ],
    ];
    // Mesure d'audience.
    $form['tarteaucitron']['type_mesure_audience'] = [
      '#type' => 'select',
      '#multiple' => TRUE,
      '#title' => $this
        ->t('Sélectionner le Service Mesure d audiance'),
      '#default_value' => $config->get('type_mesure_audience'),
      '#options' => [
        'alexa' => $this->t('Alexa'),
        'clicky' => $this->t('Clicky'),
        'crazyegg' => $this->t('Crazy Egg'),
        'etracker' => $this->t('eTracker'),
        'ferank' => $this->t('FERank'),
        'getplus' => $this->t('Get+'),
        'gajs' => $this->t('Google Analytics (ga.js)'),
        'gtag' => $this->t('Google Analytics (gtag.js)'),
        'multiplegtag' => $this->t('Google Analytics (gtag.js) [for multiple UA]'),
        'analytics' => $this->t('Google Analytics (universal)'),
        'koban' => $this->t('Koban'),
        'mautic' => $this->t('Mautic'),
        'microsoftcampaignanalytics' => $this->t('Microsoft Campaign Analytics'),
        'statcounter' => $this->t('StatCounter'),
        'visualrevenue' => $this->t('VisualRevenue'),
        'webmecanik' => $this->t('Webmecanik'),
        'wysistat' => $this->t('Wysistat'),
        'xiti' => $this->t('Xiti'),
      ],
    ];
    // Regie Publicitaire.
    $form['tarteaucitron']['type_regie_publicitaire'] = [
      '#type' => 'select',
      '#multiple' => TRUE,
      '#title' => $this->t('Sélectionner le Service Régie publicitaire'),
      '#default_value' => $config->get('type_regie_publicitaire'),
      '#options' => [
        'aduptech_ads' => $this->t('Ad Up Technology (ads)'),
        'aduptech_conversion' => $this->t('Ad Up Technology (conversion)'),
        'aduptech_retargeting' => $this->t('Ad Up Technology (retargeting)'),
        'amazon' => $this->t('Amazon'),
        'clicmanager' => $this->t('Clicmanager'),
        'criteo' => $this
          ->t('Criteo'),
        'datingaffiliation' => $this->t('Dating Affiliation'),
        'datingaffiliationpopup' => $this->t('Dating Affiliation (popup)'),
        'ferankpub' => $this->t('FERank (pub)'),
        'adsense' => $this->t('Google Adsense'),
        'adsensesearchform' => $this->t('Google Adsense Search (form)'),
        'adsensesearchresult' => $this->t('Google Adsense Search (result)'),
        'googleadwordsconversion' => $this->t('Google Adwords (conversion)'),
        'googleadwordsremarketing' => $this->t('Google Adwords (remarketing)'),
        'googlepartners' => $this->t('Google Partners Badge'),
        'prelinker' => $this->t('Prelinker'),
        'pubdirecte' => $this->t('Pubdirecte'),
        'shareasale' => $this->t('ShareASale'),
        'twenga' => $this->t('Twenga'),
        'vshop' => $this->t('vShop'),
      ],
    ];
    // Support.
    $form['tarteaucitron']['type_support'] = [
      '#type' => 'select',
      '#multiple' => TRUE,
      '#title' => $this->t('Sélectionner le Service support'),
      '#default_value' => $config->get('type_support'),
      '#options' => [
        'purechat' => $this->t('PureChat'),
        'uservoice' => $this->t('UserVoice'),
        'zopim' => $this->t('Zopim'),
      ],
    ];
    // Actions.
    $form['actions']['submit']['#value'] = $this->t('Next');

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => t('Save'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    $config = \Drupal::getContainer()
      ->get('config.factory')
      ->getEditable('tacjs.admin_settings_form');
    // Get all values and save it in save variable.
    $formvalues = $form_state->getValues();
    $formvalues = \Drupal::service('tacjs.settings')
      ->serializeValuesForm($formvalues);
    $config->set('donnes_step_one', $formvalues);
    // Get values fields.
    $type_social_networks = $form_state->getValue('type_social_networks');
    $type_video = $form_state->getValue('type_video');
    $type_apis = $form_state->getValue('type_apis');
    $type_commentaire = $form_state->getValue('type_commentaire');
    $type_mesure_audience = $form_state->getValue('type_mesure_audience');
    $type_regie_publicitaire = $form_state->getValue('type_regie_publicitaire');
    $type_support = $form_state->getValue('type_support');
    // Save values.
    $config->set('type_social_networks', $type_social_networks);
    $config->set('type_video', $type_video);
    $config->set('type_apis', $type_apis);
    $config->set('type_commentaire', $type_commentaire);
    $config->set('type_mesure_audience', $type_mesure_audience);
    $config->set('type_regie_publicitaire', $type_regie_publicitaire);
    $config->set('type_support', $type_support);
    $config->save();
    // Redirect to step two.
    $form_state->setRedirect('tacjs.step_two');
  }

}

