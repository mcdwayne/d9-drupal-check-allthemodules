<?php

namespace Drupal\microformats\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure Microformats contactinfo settings for this site.
 */
class MicroformatsSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'microformats_admin_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'microformats.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $markup = '<div class="intro">These values appear in the site info block (in hcard format). <br />If you switch from personal to 
the organization the name values will be deleted.</div>';
    $config = $this->config('microformats.settings');
    $form['microformats']['#markup'] = t($markup);

    $form['microformats']['#tree'] = TRUE;
    $form['microformats']['type'] = [
      '#type' => 'radios',
      '#title' => t('Contact information type'),
      '#description' => t('Is this for an individual or a business?'),
      '#options' => [
        'personal' => t('Personal'),
        'business' => t('Organization/Business'),
      ],
      '#default_value' => $config->get('siteinfo_type'),
    ];
    $form['microformats']['fn_n'] = [
      '#type' => 'fieldset',
      '#title' => t('Full Name'),
      '#description' => t('If this site is your personal site, enter your full name here.'),
      '#states' => [
            // Hide this fieldset if type is set to “business”.
        'invisible' => [
          ':input[name="microformats[type]"]' => ['value' => 'business'],
        ],
      ],
      '#prefix' => '<div id="edit-hcard-fn-n-wrapper">',
      '#suffix' => '</div>',
    ];
    $form['microformats']['fn_n']['given-name'] = [
      '#type' => 'textfield',
      '#title' => t('First Name'),
      '#description' => t('Your first name.'),
      '#default_value' => $config->get('siteinfo_given-name'),
    ];
    $form['microformats']['fn_n']['family-name'] = [
      '#type' => 'textfield',
      '#title' => t('Last Name'),
      '#description' => t('Your last name.'),
      '#default_value' => $config->get('siteinfo_family-name'),
    ];
    $form['microformats']['org'] = [
      '#type' => 'textfield',
      '#title' => t('Organization/Business Name'),
      '#default_value' => $config->get('siteinfo_org'),
      '#description' => t('The name of your organization or business.'),
      '#prefix' => '<div class="contactinfo-org-wrapper clearfix">',
    ];
    $form['microformats']['use_site_name'] = [
      '#type' => 'checkbox',
      '#title' => t('Use site name'),
      '#default_value' => $config->get('siteinfo_use_site_name'),
      '#suffix' => '</div>',
    ];
    $form['microformats']['tagline'] = [
      '#type' => 'textfield',
      '#title' => t('Tagline'),
      '#default_value' => $config->get('siteinfo_tagline'),
      '#description' => t('A short tagline.'),
      '#prefix' => '<div class="contactinfo-tagline-wrapper clearfix">',
    ];
    $form['microformats']['use_site_slogan'] = [
      '#type' => 'checkbox',
      '#title' => t('Use site slogan'),
      '#default_value' => $config->get('siteinfo_use_site_slogan'),
      '#suffix' => '</div>',
    ];
    $form['microformats']['adr'] = [
      '#type' => 'fieldset',
      '#title' => t('Address'),
      '#description' => t('Enter the contact address for this website.'),
    ];
    $form['microformats']['adr']['street-address'] = [
      '#type' => 'textfield',
      '#title' => t('Street Address'),
      '#default_value' => $config->get('siteinfo_street-address'),
    ];
    $form['microformats']['adr']['extended-address'] = [
      '#type' => 'textfield',
      '#title' => t('Extended Address'),
      '#default_value' => $config->get('siteinfo_extended-address'),
    ];
    $form['microformats']['adr']['locality'] = [
      '#type' => 'textfield',
      '#title' => t('City'),
      '#default_value' => $config->get('siteinfo_locality'),
    ];
    $form['microformats']['adr']['region'] = [
      '#type' => 'textfield',
      '#title' => t('State/Province'),
      '#default_value' => $config->get('siteinfo_region'),
      '#size' => 10,
    ];
    $form['microformats']['adr']['postal-code'] = [
      '#type' => 'textfield',
      '#title' => t('Postal Code'),
      '#default_value' => $config->get('siteinfo_postal-code'),
      '#size' => 10,
    ];
    $form['microformats']['adr']['country-name'] = [
      '#type' => 'textfield',
      '#title' => t('Country'),
      '#default_value' => $config->get('siteinfo_country-name'),
    ];
    $form['microformats']['location'] = [
      '#type' => 'fieldset',
      '#title' => t('Geographical Location'),
      '#description' => t('Enter your geographical coordinates to help people locate you.'),
    ];
    $form['microformats']['location']['longitude'] = [
      '#type' => 'textfield',
      '#title' => t('Longitude'),
      '#default_value' => $config->get('siteinfo_longitude'),
      '#description' => t('Longitude, in full decimal format (like -121.629562).'),
    ];
    $form['microformats']['location']['latitude'] = [
      '#type' => 'textfield',
      '#title' => t('Latitude'),
      '#default_value' => $config->get('siteinfo_latitude'),
      '#description' => t('Latitude, in full decimal format (like 38.827382).'),
    ];
    $form['microformats']['phone'] = [
      '#type' => 'fieldset',
      '#title' => t('Phones'),
      '#description' => t('Enter the numbers at which you would like to be reached.'),
    ];
    $form['microformats']['phone']['voice'] = [
      '#type' => 'textfield',
      '#title' => t('Voice Phone Number(s)'),
      '#default_value' => $config->get('siteinfo_voice'),
      '#description' => t('Voice phone numbers, separated by commas.'),
    ];
    $form['microformats']['phone']['fax'] = [
      '#type' => 'textfield',
      '#title' => t('Fax Number(s)'),
      '#default_value' => $config->get('siteinfo_fax'),
      '#description' => t('Fax numbers, separated by commas.'),
    ];
    $form['microformats']['email'] = [
      '#type' => 'textfield',
      '#title' => t('Email'),
      '#default_value' => $config->get('siteinfo_email'),
      '#description' => t('Enter this site’s contact email address. This address will be displayed publicly, do not enter a private address.'),
      '#element_validate' => ['contactinfo_validate_email'],
    ];
    $moduleHandler = \Drupal::service('module_handler');
    if ($moduleHandler->moduleExists('invismail')) {
      $form['microformats']['email']['#description'] .= ' ' . t('This address will be obfuscated to protect it from spammers.');
    }
    else {
      $form['microformats']['email']['#description'] .= ' ' . t('Install the <a href="http://drupal.org/project/invisimail" target="_blank">Invisimail</a> module to protect this address from spammers.');
    }
    $drupalConfig = \Drupal::config('system.site');
    $form['#attached']['drupalSettings']['microformats']['sitename'] = $drupalConfig->get('name');
    $form['#attached']['drupalSettings']['microformats']['siteslogan'] = $drupalConfig->get('slogan');

    $form['#attached']['library'][] = 'microformats/microformatsadmin';
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    $values = $form_state->getValue('microformats');
    $this->config('microformats.settings')
      ->set('siteinfo_type', $values['type'])
      ->set('siteinfo_given-name', $values['fn_n']['given-name'])
      ->set('siteinfo_family-name', $values['fn_n']['family-name'])
      ->set('siteinfo_org', $values['org'])
      ->set('siteinfo_use_site_name', $values['use_site_name'])
      ->set('siteinfo_tagline', $values['tagline'])
      ->set('siteinfo_use_site_slogan', $values['use_site_slogan'])
      ->set('siteinfo_street-address', $values['adr']['street-address'])
      ->set('siteinfo_extended-address', $values['adr']['extended-address'])
      ->set('siteinfo_locality', $values['adr']['locality'])
      ->set('siteinfo_region', $values['adr']['region'])
      ->set('siteinfo_postal-code', $values['adr']['postal-code'])
      ->set('siteinfo_country-name', $values['adr']['country-name'])
      ->set('siteinfo_latitude', $values['location']['latitude'])
      ->set('siteinfo_longitude', $values['location']['longitude'])
      ->set('siteinfo_voice', $values['phone']['voice'])
      ->set('siteinfo_fax', $values['phone']['fax'])
      ->set('siteinfo_email', $values['email'])
      ->save();

    parent::submitForm($form, $form_state);
  }

}
