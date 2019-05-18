<?php

namespace Drupal\drd\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Path\AliasManagerInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Path\PathValidatorInterface;
use Drupal\Core\Routing\RequestContext;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Configure DRD settings for this site.
 */
class Settings extends ConfigFormBase {

  /**
   * The path alias manager.
   *
   * @var \Drupal\Core\Path\AliasManagerInterface
   */
  protected $aliasManager;

  /**
   * The path validator.
   *
   * @var \Drupal\Core\Path\PathValidatorInterface
   */
  protected $pathValidator;

  /**
   * The request context.
   *
   * @var \Drupal\Core\Routing\RequestContext
   */
  protected $requestContext;

  /**
   * Constructs a SiteInformationForm object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param \Drupal\Core\Path\AliasManagerInterface $alias_manager
   *   The path alias manager.
   * @param \Drupal\Core\Path\PathValidatorInterface $path_validator
   *   The path validator.
   * @param \Drupal\Core\Routing\RequestContext $request_context
   *   The request context.
   */
  public function __construct(ConfigFactoryInterface $config_factory, AliasManagerInterface $alias_manager, PathValidatorInterface $path_validator, RequestContext $request_context) {
    parent::__construct($config_factory);

    $this->aliasManager = $alias_manager;
    $this->pathValidator = $path_validator;
    $this->requestContext = $request_context;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('path.alias_manager'),
      $container->get('path.validator'),
      $container->get('router.request_context')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'drd_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['drd'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $site_config = $this->config('drd.general');

    $form['general'] = [
      '#type' => 'details',
      '#title' => t('General'),
      '#open' => TRUE,
    ];
    $form['general']['encryption_profile'] = [
      '#type' => 'select',
      '#title' => t('Encryption profile'),
      '#options' => \Drupal::service('encrypt.encryption_profile.manager')->getEncryptionProfileNamesAsOptions(),
      '#default_value' => $site_config->get('encryption_profile'),
      '#required' => TRUE,
      '#description' => $this->t('Select your encryption profile here. If there is no profile available yet, go to <a href="@link">Encryption profiles</a> to create one.', [
        '@link' => Url::fromRoute('entity.encryption_profile.collection')->toString(),
      ]),
    ];
    $form['general']['debug'] = [
      '#type' => 'checkbox',
      '#title' => t('Debug mode'),
      '#default_value' => $site_config->get('debug'),
    ];
    $form['general']['lock_hacked'] = [
      '#type' => 'checkbox',
      '#title' => t('Automatically lock hacked releases'),
      '#default_value' => $site_config->get('lock_hacked'),
    ];
    $form['general']['cleanup_releases'] = [
      '#type' => 'checkbox',
      '#title' => t('Cleanup un-used releases during cron'),
      '#default_value' => $site_config->get('cleanup.releases'),
    ];
    $form['general']['cleanup_majors'] = [
      '#type' => 'checkbox',
      '#title' => t('Cleanup un-used major releases during cron'),
      '#default_value' => $site_config->get('cleanup.majors'),
    ];
    $form['general']['cleanup_projects'] = [
      '#type' => 'checkbox',
      '#title' => t('Cleanup un-used projects during cron'),
      '#default_value' => $site_config->get('cleanup.projects'),
    ];

    $form['localcopy'] = [
      '#type' => 'details',
      '#title' => t('Local Copy Settings'),
      '#open' => TRUE,
    ];
    $form['localcopy']['db_user'] = [
      '#type' => 'textfield',
      '#title' => t('Database User Name'),
      '#default_value' => $site_config->get('local.db.user'),
    ];
    $form['localcopy']['db_pass'] = [
      '#type' => 'password',
      '#title' => t('Database Password'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $db_pass = $form_state->getValue('db_pass');

    $old_profile_id = $this->configFactory()->get('drd.general')->get('encryption_profile');
    $new_profile_id = $form_state->getValue('encryption_profile');

    $config = $this->configFactory()->getEditable('drd.general')
      ->set('debug', $form_state->getValue('debug'))
      ->set('lock_hacked', $form_state->getValue('lock_hacked'))
      ->set('cleanup.releases', $form_state->getValue('cleanup_releases'))
      ->set('cleanup.majors', $form_state->getValue('cleanup_majors'))
      ->set('cleanup.projects', $form_state->getValue('cleanup_projects'))
      ->set('local.db.user', $form_state->getValue('db_user'));
    if (!empty($db_pass)) {
      \Drupal::service('drd.encrypt')->encrypt($db_pass);
      $config->set('local.db.pass', $db_pass);
    }
    $config
      ->set('encryption_profile', $form_state->getValue('encryption_profile'))
      ->save();

    // By the time we get here, all settings are stored, with the old profil.
    // If the profile has changed, we now have to re-encrypt all the values.
    if ($old_profile_id != $new_profile_id) {
      \Drupal::service('drd.encrypt.update')->update($old_profile_id, $new_profile_id);
    }

    parent::submitForm($form, $form_state);
  }

}
