<?php

namespace Drupal\partnersite_profile\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class AdminSettingsForm.
 */
class AdminSettingsForm extends ConfigFormBase {

    /**
     * Drupal\Core\Config\ConfigFactory definition.
     *
     * @var \Drupal\Core\Config\ConfigFactory
     */
    protected $configFactory;



  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'partnersite_profile.adminsettings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'admin_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('partnersite_profile.adminsettings');
    $form['general'] = array(
      '#type'  => 'fieldset',
      '#title' => t("General Configuration"),
    );
    $form['general']['set_expiry'] = array(
      '#type'          => 'textfield',
      '#title'         => t('Expiry'),
      '#default_value' => $config->get('expiry'),
      '#size'          => strlen(PHP_INT_MAX),
      '#maxlength'     => strlen(PHP_INT_MAX),
      '#description'   => t("Link expiration window to apply. For example, Input number of days to be set for valid without expiring or timeout."),
    );
    $form['general']['profile_user'] = array(
      '#type'          => 'radios',
      '#title'         => t('Select Profile'),
      '#default_value' => $config->get('profile_user'),
      '#options'       => array(
          'autocomplete' => t('Autocomplete textfield'),
          'select'       => t('Select list'),
      ),
      '#description'   => t("Select Profile user"),
    );

      $form['general']['login_auth'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Authentication Service'),
      '#description' => $this->t('Authentication Service : Local. External to be supported in upcoming version.'),
      '#maxlength' => 10,
      '#size' => 10,
      '#default_value' => $config->get('login_auth'),
    ];
    $form['general']['init_user'] = [
      '#type' => 'select',
      '#title' => $this->t('Create User Record'),
      '#description' => $this->t('Authentication Service : Local or external user entry creation required.'),
			'#options' => [
				'0' => $this->t( 'No'),
				'1' => $this->t( 'Yes')
			],
      '#default_value' => $config->get('init_user'),
    ];
    $form['alternative'] = array(
        '#type' => 'fieldset',
        '#title' => t('Alternative Access Link Generator Parameters')
    );
    $form['alternative']['default_mapping_basekey'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Default Mapping Basekey'),
      '#description' => $this->t('Base key set to use for translating characters!'),
      '#maxlength' => 10,
      '#size' => 10,
      '#default_value' => $config->get('default_mapping_basekey'),
    ];
    $form['destination'] = array(
          '#type'  => 'fieldset',
          '#title' => t("Destination settings"),
      );
    $form['destination']['available'] = array(
          '#type'        => 'fieldset',
          '#title'       => t("Available"),
          '#description' => t("Which paths to make available for selection."),
      );
    $form['destination']['available']['target_front'] = array(
          '#type'          => 'checkbox',
          '#title'         => t("Front page"),
          '#description'   => t("The front page of the website."),
          '#default_value' => $config->get('target_front'),
      );
    $form['destination']['available']['target_custom'] = array(
          '#type'          => 'textarea',
          '#title'         => t('Custom paths'),
          '#default_value' => $config->get('target_custom'),
          '#description'   => t("Enter one path per line.  You may also supply a display name for the path using a key|value pair, where the key is the destination and the value is the display name."),
      );
    $form['destination']['default'] = array(
          '#type'        => 'fieldset',
          '#title'       => t("Default path"),
          '#description' => t("Default path"),
      );
    $form['destination']['default']['fallback_destination_default'] = AdminSettingsForm::listDestinations($config->get('fallback_destination_default'), t("Default destination"));
    $form['destination']['default']['fallback_destination_default']['#required'] = FALSE;

		$form['mail_settings'] = array(
			'#type' => 'fieldset',
			'#title' => t('Email Related Settings')
		);

		$system_token_utilized  = t('Available variables are: [site:name], [site:url], [user:display-name], [user:account-name], [user:mail], [site:login-url], [user:login-one-time].');
		$form['mail_settings'] ['partnersite_profile'] = array(
			'#type' => 'details',
			'#title' => t('Email with Access Link for Partner Profiles'),
			'#group' => 'email',
			'#description' => t('Customize Emails to Partners with Access and other details.') . ' ' . $system_token_utilized,
			'#weight' => 99,
		);
		$form['mail_settings']['partnersite_profile']['profile_mail_subject'] = array(
			'#type' => 'textfield',
			'#title' => t('Mail Subject Template'),
			'#default_value' => $config->get('profile_mail.subject'),
			'#maxlength' => 180,
		);
		$form['mail_settings']['partnersite_profile']['profile_mail_body']    = array(
			'#type' => 'textarea',
			'#title' => t('Mail Body Template'),
			'#default_value' => $config->get('profile_mail.body'),
			'#rows' => 12,
		);
    return parent::buildForm($form, $form_state);
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
    parent::submitForm($form, $form_state);

      $expiry_time = ltrim($form_state->getValue('set_expiry'));

      if (empty($expiry_time)) {
          $expiry_time = '0';
      }

      $this->config('partnersite_profile.adminsettings')
      ->set('default_mapping_basekey', $form_state->getValue('default_mapping_basekey'))
      ->set('sample_domains', $form_state->getValue('sample_domains'))
      ->set('login_auth', $form_state->getValue('login_auth'))
      ->set('init_user', $form_state->getValue('init_user'))
      ->set('expiry', $expiry_time)
      ->set('target_front', $form_state->getValue('target_front'))
      ->set('target_custom', $form_state->getValue('target_custom'))
      ->set('fallback_destination_default', $form_state->getValue('fallback_destination_default'))
      ->set('profile_user', $form_state->getValue('profile_user'))
			->set('profile_mail.subject', $form_state->getValue('profile_mail_subject'))
			->set('profile_mail.body', $form_state->getValue('profile_mail_body'))
      ->save();
  }

    /**
     * @param null $path
     * Additional path or fallback path
     * @param null $title
     * Setting the form title
     * @return array
     * Return render array for select dropdown
     */
  public static function listDestinations($path = NULL, $title = NULL) {
    // Set a default path if $path not given.
    if (is_null($path)) {
        $path = \Drupal::config('partnersite_profile.adminsettings')->get('fallback_destination_default');
    }
    $form = array(
        '#type' => 'select',
        '#default_value' => $path,
        '#options' => array('' => t("- Choose a page -")) + self::miscSettingOptions($path),
        '#required' => TRUE,
    );
    if ($title) {
        $form['#title'] = $title;
    }
    return $form;
  }

    /**
     * @param null $path
     * Default path or fall back path
     * @return array
     * Return array of options for the select destination dropdown
     */

    public static function miscSettingOptions($path = NULL) {
        $options = array();
        $config = \Drupal::config('partnersite_profile.adminsettings');

        if ($config->get('target_front')
        ) {
            $options['destination[front]'] = t("Front page");
        }
        if ($config->get('target_custom')
        ) {
            $customs = explode("\n", $config->get('target_custom'));
            if (is_array($customs)) {
                foreach ($customs as $custom) {
                    $custom_option = explode("|", $custom);
                    $options[$custom_option[0]] = isset($custom_option[1]) ? $custom_option[1] : $custom_option[0];
                }
            }
        }

         if ($path && !isset($options[$path])) {
            $options[$path] = t("Front page");
        }

        \Drupal::moduleHandler()->alter("partnersite_destination_options", $options);

        return $options;
    }



}
