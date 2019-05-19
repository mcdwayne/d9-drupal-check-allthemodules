<?php

namespace Drupal\webfactory_master\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Extension\ExtensionDiscovery;
use Drupal\Core\Extension\InfoParser;
use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\user\Entity\User;
use Drupal\webfactory\Services\Security;
use Drupal\Core\Language\LanguageManager;
use Drupal\Core\Entity\Query\QueryFactory;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Egulias\EmailValidator\EmailValidatorInterface;

/**
 * Class SatelliteEntityForm.
 *
 * @package Drupal\webfactory_master\Form
 */
class SatelliteEntityForm extends EntityForm {

  /**
   * The security service.
   *
   * @var \Drupal\webfactory\Services\Security
   */
  protected $security;

  /**
   * The app root.
   *
   * @var string
   */
  protected $appRoot;

  /**
   * The configuration object of the security service.
   *
   * @var \Drupal\Core\Config\Config
   */
  protected $securityConfig;

  /**
   * The entity query service.
   *
   * @var \Drupal\Core\Entity\Query\QueryFactory
   */
  protected $entityQuery;

  /**
   * The email validator service.
   *
   * @var \Egulias\EmailValidator\EmailValidatorInterface
   */
  protected $emailValidator;

  /**
   * Constructs a ChannelEntityEditForm.
   *
   * @param Security $security
   *   The channel source plugin manager.
   * @param string $app_root
   *   The app root.
   * @param QueryFactory $entity_query
   *   The entity query service.
   * @param EmailValidatorInterface $email_validator
   *   The email validator service.
   * @param ConfigFactoryInterface $config_factory
   *   The config factory service.
   */
  public function __construct(Security $security, $app_root, QueryFactory $entity_query, EmailValidatorInterface $email_validator, ConfigFactoryInterface $config_factory) {
    $this->security = $security;
    $this->appRoot = $app_root;
    $this->entityQuery = $entity_query;
    $this->emailValidator = $email_validator;
    $this->securityConfig = $config_factory->getEditable('webfactory_master.security');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('webfactory.services.security'),
      $container->get('app.root'),
      $container->get('entity.query'),
      $container->get('email.validator'),
      $container->get('config.factory')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    /** @var \Drupal\webfactory_master\entity\SatelliteEntity $satellite_entity */
    $satellite_entity = $this->entity;

    // SITE CONFIGURATION.
    $form['site'] = array(
      '#type' => 'details',
      '#title' => $this->t('Site configuration'),
      '#open' => TRUE,
    );
    $form['site']['label'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $satellite_entity->label(),
      '#description' => $this->t('Label for the Satellite entity.'),
      '#required' => TRUE,
    );
    $form['site']['id'] = array(
      '#type' => 'machine_name',
      '#default_value' => $satellite_entity->id(),
      '#machine_name' => array(
        'exists' => '\Drupal\webfactory_master\Entity\SatelliteEntity::load',
      ),
      '#disabled' => !$satellite_entity->isNew(),
    );
    $form['site']['host'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Host'),
      '#maxlength' => 255,
      '#default_value' => $satellite_entity->get('host'),
      '#description' => $this->t('Host for the Satellite entity.'),
      '#required' => TRUE,
    );
    $form['site']['directory'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Directory'),
      '#maxlength' => 255,
      '#default_value' => $satellite_entity->get('directory'),
      '#description' => $this->t('Directory for the Satellite entity.'),
      '#required' => TRUE,
    );
    $form['site']['mail'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Mail'),
      '#maxlength' => 255,
      '#default_value' => $satellite_entity->get('mail'),
      '#description' => $this->t('User email for the Satellite entity.'),
      '#required' => TRUE,
    );
    $form['site']['siteName'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Site name'),
      '#maxlength' => 255,
      '#default_value' => $satellite_entity->get('siteName'),
      '#description' => $this->t('Site name of the Satellite entity.'),
      '#required' => TRUE,
    );

    // TODO: put it in a service class injected.
    $profiles_list = array();
    $listing = new ExtensionDiscovery($this->appRoot);
    foreach ($listing->scan('profile') as $name => $profile) {
      $parser = new InfoParser();
      $parsed_info = $parser->parse($profile->getPathname());

      $profiles_list[$name] = $parsed_info;
    }
    $options_profile = array();
    foreach ($profiles_list as $key => $profile) {
      // Do not display hidden profiles.
      if (!isset($profile['hidden'])) {
        $options_profile[$key] = $profile['name'];
      }
    }

    $form['site']['profile'] = array(
      '#type' => 'select',
      '#title' => $this->t('Profile'),
      '#options' => $options_profile,
      '#description' => $this->t('Installation profiles available.'),
      '#default_value' => $satellite_entity->get('profile'),
      '#required' => TRUE,
      '#ajax' => array(
        'callback' => '::updateThemesList',
        'wrapper' => 'edit-profiles-list-wrapper',
      ),
    );

    $profile = $satellite_entity->get('profile');
    $options_theme = [];
    if (isset($profile)) {
      $options_theme = $this->getThemesList($profile);
    }

    $form['site']['theme'] = array(
      '#prefix' => '<div id="edit-profiles-list-wrapper">',
      '#type' => 'select',
      '#title' => $this->t('Theme'),
      '#default_value' => $satellite_entity->get('theme'),
      '#options' => $options_theme,
      '#description' => $this->t('Themes according to your profile installation.'),
      '#required' => TRUE,
      '#suffix' => '</div>',
    );

    $standard_languages = LanguageManager::getStandardLanguageList();
    $options_languages = [];
    foreach ($standard_languages as $lang_code => $lang) {
      $options_languages[$lang_code] = $lang[0];
    }

    $lang = $satellite_entity->get('language');
    if (!isset($lang)) {
      if (isset($options_languages['en'])) {
        $lang = 'en';
      }
    }
    $form['site']['language'] = array(
      '#type' => 'select',
      '#title' => $this->t('Language'),
      '#default_value' => $lang,
      '#options' => $options_languages,
      '#description' => $this->t('Installation language.'),
      '#required' => TRUE,
    );

    $channels_options = $this->entityQuery->get('channel_entity', 'AND')->execute();
    $channels = $this->entity->get('channels');

    $form['site']['channels'] = array(
      '#type' => 'select',
      '#title' => $this->t('Channel source'),
      '#multiple' => TRUE,
      '#options' => $channels_options,
      '#default_value' => $channels,
      '#empty_option' => $this->t('- Select a channel source -'),
      '#required' => TRUE,
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $mail = $form_state->getValue('mail');
    if (!$this->emailValidator->isValid($mail)) {
      $form_state->setError($form['mail'], $this->t('Invalid email address.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);

    // TODO display only if the satellite is not deployed yet.
    $build_info = $form_state->getBuildInfo();
    if ($build_info['form_id'] !== 'satellite_entity_add_form') {
      $form['actions']['deploy'] = array(
        '#type' => 'link',
        '#title' => $this->t('Deploy'),
        '#url' => $this->entity->toUrl('deploy-form'),
        '#access' => $this->entity->access('deploy'),
        '#weight' => 5,
        '#attributes' => array(
          'class' => array('button'),
        ),
      );
    }
    return $form;
  }

  /**
   * Update themes list according to selected entities.
   *
   * @param array $form
   *   The form.
   * @param FormStateInterface $form_state
   *   The form state.
   *
   * @return array
   *   Updated form element.
   */
  public function updateThemesList($form, FormStateInterface $form_state) {
    $profile_name = $form_state->getValue('profile');
    $form['site']['theme']['#options'] = $this->getThemesList($profile_name);

    return $form['site']['theme'];
  }

  /**
   * Helper to retrieve themes from profile.
   *
   * @param string $profile_name
   *   The profile name.
   *
   * @return array
   *   The theme list options.
   */
  protected function getThemesList($profile_name) {
    // TODO : put it in a service class injected.
    $profile_themes_list = [];
    $listing = new ExtensionDiscovery($this->appRoot);
    foreach ($listing->scan('profile') as $name => $profile) {
      if ($profile_name === $name) {
        $parser = new InfoParser();
        $parsed_info = $parser->parse($profile->getPathname());

        if (isset($parsed_info['themes']) && !empty($parsed_info['themes'])) {
          $profile_themes_list = $parsed_info['themes'];
        }
      }
    }

    $themes_options = [];
    foreach ($profile_themes_list as $theme) {
      $themes_options[$theme] = $theme;
    }
    return $themes_options;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\webfactory_master\entity\SatelliteEntity $satellite_entity */
    $satellite_entity = $this->entity;

    $id = $this->entity->get('id');
    $user = $this->entity->get('wsUser');
    if (!isset($user)) {
      $pass = user_password();

      $this->createUser($id, $pass);

      $token = $this->security->generateToken();
      $secured_pass = $this->security->crypt($pass, $token);

      $this->entity->set('pass', $secured_pass);
      $this->entity->set('wsUser', $id);

      $satellites = $this->securityConfig->get('satellites');
      $satellites[$id] = [
        'token' => $token,
        'id' => $id,
      ];
      $this->securityConfig->set('satellites', $satellites)->save();
    }

    $status = $this->entity->save();

    switch ($status) {
      case SAVED_NEW:
        drupal_set_message($this->t('Created the %label Satellite entity.', [
          '%label' => $satellite_entity->label(),
        ]));
        break;

      default:
        drupal_set_message($this->t('Saved the %label Satellite entity.', [
          '%label' => $satellite_entity->label(),
        ]));
    }

    $form_state->setRedirectUrl($satellite_entity->urlInfo('collection'));
  }

  /**
   * Create a user.
   *
   * @param string $name
   *   The user name.
   * @param string $pass
   *   The user password.
   */
  protected function createUser($name, $pass) {
    $edit = array();
    $edit['name'] = $name;
    $edit['mail'] = $edit['name'] . '@example.com';
    $edit['pass'] = $pass;
    $edit['status'] = 1;
    $edit['roles'] = array('webfactory');

    $account = User::create($edit);
    $account->save();
  }

}
