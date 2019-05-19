<?php

/**
 * @file
 * Contains \Drupal\webtrees\Form\WebtreesSettingsForm.
 */

namespace Drupal\webtrees\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Path\AliasManagerInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Path\PathValidatorInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Configure site information settings for this site.
 */
class WebtreesSettingsForm extends ConfigFormBase {

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
   * Constructs a WebtreesFairSettingsForm object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param \Drupal\Core\Path\AliasManagerInterface $alias_manager
   *   The path alias manager.
   * @param \Drupal\Core\Path\PathValidatorInterface $path_validator
   *   The path validator.
   */
  public function __construct(ConfigFactoryInterface $config_factory, AliasManagerInterface $alias_manager, PathValidatorInterface $path_validator) {
    parent::__construct($config_factory);

    $this->aliasManager = $alias_manager;
    $this->pathValidator = $path_validator;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('path.alias_manager'),
      $container->get('path.validator')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'webtrees_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['webtrees.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $my_config = $this->config('webtrees.settings');

    $webtree_roles[''] = '-- '.t('Never map to this role').' --';
    $exclude_roles[''] = '-- '.t('Do not exclude any user').' --';
    $drupal_roles = array();
    foreach(user_roles(TRUE) as $role) {
      $webtree_roles[$role->id()]=$role->label();
      $drupal_roles[$role->id()]=$role->label();
      $exclude_roles[$role->id()]=$role->label();
    }

    $form['configuration'] = array(
      '#type' => 'details',
      '#title' => t('Configuration'),
      '#open' => TRUE,
    );
    $form['configuration']['enable'] = array(
    '#type' => 'checkbox',
      '#title' => t('Enable'),
      '#default_value' => $my_config->get('configuration.enable'),
      '#description' => t("Use Webtrees single sign on support"),
    );
    $form['configuration']['login'] = array(
    '#type' => 'checkbox',
      '#title' => t('Login'),
      '#default_value' => $my_config->get('configuration.login'),
      '#description' => t("Log in to Webtrees on successful log in to Drupal."),
    );
    $form['configuration']['url'] = array(
    '#type' => 'textfield',
      '#title' => t('URL'),
      '#default_value' => $my_config->get('configuration.url'),
      '#description' => t("Webtrees URL path. This is often /webtrees/. Include leading and trailing slash"),
    );
    $form['configuration']['use_webtrees'] = array(
    '#type' => 'checkbox',
      '#title' => t('Use Webtrees as Primary user list'),
      '#default_value' => $my_config->get('configuration.use_webtrees'),
      '#description' => t("Webtrees has the primary user list and it will create/update the matching Drupal users, otherwise Drupal has the primary user list."),
    );
    $form['configuration']['allow_reverse'] = array(
    '#type' => 'checkbox',
      '#title' => t('Allow reverse lookup for missing user'),
      '#default_value' => $my_config->get('configuration.allow_reverse'),
      '#description' => t("If checked then use the secondary user list if the user is not in the primary list. Valid users are added to the primary list."),
    );
    $form['configuration']['logging'] = array(
    '#type' => 'checkbox',
      '#title' => t('Logging'),
      '#default_value' => $my_config->get('configuration.logging'),
      '#description' => t("Log this module's transactions."),
    );
    $form['configuration']['exclude'] = array(
      '#type' => 'select',
      '#title' => t('Role to exclude from Webtrees log in'),
      '#options' => $exclude_roles,
      '#default_value' => $my_config->get('configuration.exclude'),
      '#description' => t("Drupal users with this role will not be checked or logged into Webtrees."),
    );

    $form['database'] = array(
      '#type' => 'details',
      '#title' => t('Database'),
      '#open' => FALSE,
    );
    $form['database'][] = array(
    '#type' => 'item',
      '#markup' => t("This information is needed to access the Webtree database. "
                    ."Use this module's Test configuration option to verify database access. "
                    ."It can be found in this Webtrees file: data/config.ini.php"
                    ),
    );

    $form['database']['use_drupal'] = array(
      '#type' => 'checkbox',
      '#title' => t('Use Drupal Settings'),
      '#default_value' => $my_config->get('database.use_drupal'),
      '#description' => t("Use Drupal's default database setting instead of the ones listed here. "
                         ."For configurations where the databases are on the same server accessible by the Drupal database user. "
                         ."Only the host, port, user name and password are used. "
                         ."The database name and prefix from this form are used. "
                         ),
    );
    $form['database']['driver'] = array(
      '#type' => 'select',
      '#title' => t('Driver'),
      '#options' => array ( 'mysql' => 'MySQL' ),
      '#default_value' => $my_config->get('database.driver'),
      '#required' => TRUE,
      '#description' => t("Database driver type."),
    );
    $form['database']['host'] = array(
      '#type' => 'textfield',
      '#title' => t('Host'),
      '#default_value' => $my_config->get('database.host'),
      '#description' => t("IP address or domain name of database server. Use localhost for the local server."),
    );
    $form['database']['port'] = array(
      '#type' => 'textfield',
      '#title' => t('Port'),
      '#default_value' => $my_config->get('database.port'),
      '#description' => t("Database server port number. Default is 3306."),
    );
    $form['database']['user'] = array(
      '#type' => 'textfield',
      '#title' => t('User name'),
      '#default_value' => $my_config->get('database.user'),
      '#description' => t("Use for login access."),
    );
    $form['database']['password'] = array(
      '#type' => 'textfield',
      '#title' => t('User password'),
      '#default_value' => $my_config->get('database.password'),
      '#description' => t("Used for login access."),
    );
    $form['database']['database'] = array(
      '#type' => 'textfield',
      '#title' => t('Database name'),
      '#default_value' => $my_config->get('database.database'),
      '#required' => TRUE,
      '#description' => t("Name of the Webtrees database. Required even if using Drupal settings."),
    );
    $form['database']['prefix'] = array(
      '#type' => 'textfield',
      '#title' => t('Database table prefix'),
      '#default_value' => $my_config->get('database.prefix'),
      '#description' => t("Table prefix for database table names. Usually 'wt_'. Required even if using Drupal settings"),
    );


    $form['role'] = array(
      '#type' => 'details',
      '#title' => t('Role mapping'),
      '#open' => FALSE,
    );
    $form['role']['webtrees'] = array(
      '#type' => 'details',
      '#title' => t('Webtrees to Drupal role mapping'),
      '#open' => TRUE,
    );
    $form['role']['webtrees'][] = array(
    '#type' => 'item',
      '#markup' => t("Role mapping is used when new users are created in the alternate user database. "
                    ."The Webtrees roles are fixed but Drupal roles be added. "
                    ."Administror and Authenticated roles are the only required ones for Drupal."
                    ),
    );
    $form['role']['webtrees']['webtrees_administrator'] = array(
      '#type' => 'select',
      '#title' => t('Administrator'),
      '#options' => $drupal_roles,
      '#default_value' => $my_config->get('role.webtrees.administrator'),
      '#description' => t("Webtrees administrator maps to this Drupal role."),
    );
    $form['role']['webtrees']['webtrees_manager'] = array(
      '#type' => 'select',
      '#title' => t('Manager'),
      '#options' => $drupal_roles,
      '#default_value' => $my_config->get('role.webtrees.manager'),
      '#description' => t("Webtrees manager maps to this Drupal role."),
    );
    $form['role']['webtrees']['webtrees_moderator'] = array(
      '#type' => 'select',
      '#title' => t('Moderator'),
      '#options' => $drupal_roles,
      '#default_value' => $my_config->get('role.webtrees.moderator'),
      '#description' => t("Webtrees moderator maps to this Drupal role."),
    );
    $form['role']['webtrees']['webtrees_editor'] = array(
      '#type' => 'select',
      '#title' => t('Editor'),
      '#options' => $drupal_roles,
      '#default_value' => $my_config->get('role.webtrees.editor'),
      '#description' => t("Webtrees editor maps to this Drupal role."),
    );
    $form['role']['webtrees']['webtrees_member'] = array(
      '#type' => 'select',
      '#title' => t('Member'),
      '#options' => $drupal_roles,
      '#default_value' => $my_config->get('role.webtrees.member'),
      '#description' => t("Webtrees member maps to this Drupal role."),
    );

    $form['role']['drupal'] = array(
      '#type' => 'details',
      '#title' => t('Drupal to Webtrees role mapping'),
      '#open' => TRUE,
    );
    $form['role']['drupal'][] = array(
    '#type' => 'item',
      '#markup' => t("Role mapping is used when new users are created in the Webtrees user database. "
                    ."Webtrees has a fixed set of roles and each user has only one role. "
                    ."The highest Webtrees role will be chosen based on the role of the Drupal user. "
                    ."The default Webtrees role will be member. "
                    ),
    );
    $form['role']['drupal']['drupal_administrator'] = array(
      '#type' => 'select',
      '#title' => t('Administrator'),
      '#options' => $webtree_roles,
      '#default_value' => $my_config->get('role.drupal.administrator'),
      '#description' => t("Drupal role to map to Webtrees administrator."),
    );
    $form['role']['drupal']['drupal_manager'] = array(
      '#type' => 'select',
      '#title' => t('Manager'),
      '#options' => $webtree_roles,
      '#default_value' => $my_config->get('role.drupal.manager'),
      '#description' => t("Drupal role to map to Webtrees manager."),
    );
    $form['role']['drupal']['drupal_moderator'] = array(
      '#type' => 'select',
      '#title' => t('Moderator'),
      '#options' => $webtree_roles,
      '#default_value' => $my_config->get('role.drupal.moderator'),
      '#description' => t("Drupal role to map to Webtrees moderator."),
    );
    $form['role']['drupal']['drupal_editor'] = array(
      '#type' => 'select',
      '#title' => t('Editor'),
      '#options' => $webtree_roles,
      '#default_value' => $my_config->get('role.drupal.editor'),
      '#description' => t("Drupal role to map to Webtrees editor."),
    );


    $form['user_defaults'] = array(
      '#type' => 'details',
      '#title' => t('Webtrees Create User Defaults'),
      '#open' => FALSE,
    );

    $form['user_defaults'][] = array(
    '#type' => 'item',
      '#markup' => t("These values will be used when creating a Webtree user based on a Drupal user. "
                    ."The role will be based on reverse mapping the Role section. "
                    ),
    );

    $form['user_defaults']['autoaccept'] = array(
      '#type' => 'checkbox',
      '#title' => t('Auto accept'),
      '#default_value' => $my_config->get('webtrees_user.autoaccept'),
      '#description' => t("Automatically accept changes submitted by the user."),
    );
    $form['user_defaults']['comment'] = array(
      '#type' => 'textfield',
      '#title' => t('Comment'),
      '#default_value' => $my_config->get('webtrees_user.comment'),
    );
    $form['user_defaults']['comment_exp'] = array(
      '#type' => 'textfield',
      '#title' => t('Comment exp'),
      '#default_value' => $my_config->get('webtrees_user.comment_exp'),
    );
    $form['user_defaults']['webtrees_language'] = array(
      '#type' => 'textfield',
      '#title' => t('Language'),
      '#default_value' => $my_config->get('webtrees_user.language'),
      '#description' => t("."),
    );
    $form['user_defaults']['contactmethod'] = array(
      '#type' => 'select',
      '#title' => t('Contact method'),
      '#options' => array (
         'messaging' => t('Internal email'),
         'messaging1' => t('Internal and external email'),
         'messaging2' => t('External email'),
         'none' => t('None'),
         ),
      '#default_value' => $my_config->get('webtrees_user.contactmethod'),
      '#description' => t("How to contact other users."),
    );
    $form['user_defaults']['webtrees_timezone'] = array(
      '#type' => 'textfield',
      '#title' => t('Timezone'),
      '#default_value' => $my_config->get('webtrees_user.timezone'),
      '#description' => t("UTC is the usual default."),
    );
    $form['user_defaults']['verified'] = array(
      '#type' => 'checkbox',
      '#title' => t('Verified'),
      '#default_value' => $my_config->get('webtrees_user.verified'),
      '#description' => t("User has been verified."),
    );
    $form['user_defaults']['verified_by_admin'] = array(
      '#type' => 'checkbox',
      '#title' => t('Verified_by_admin'),
      '#default_value' => $my_config->get('webtrees_user.verified_by_admin'),
      '#description' => t("User has been verified by the administrator."),
    );
    $form['user_defaults']['visibleonline'] = array(
      '#type' => 'checkbox',
      '#title' => t('Visible online'),
      '#default_value' => $my_config->get('webtrees_user.visibleonline'),
      '#description' => t("Logged in user will be visible to others."),
    );
    $form['user_defaults']['gedcom'] = array(
      '#type' => 'textfield',
      '#title' => t('Initial GEDCOM ID'),
      '#default_value' => $my_config->get('webtrees_user.gedcom'),
      '#description' => t("GEDCOM ID for this user. Normally 1 if there is a single tree."),
    );

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
        $config=$this->config('webtrees.settings');
        $fields = array (
                        'database.use_drupal'         => 'use_drupal',
                        'database.driver'             => 'driver',
                        'database.host'               => 'host',
                        'database.port'               => 'port',
                        'database.database'           => 'database',
                        'database.prefix'             => 'prefix',
                        'database.user'               => 'user',
                        'database.password'           => 'password',

                        'configuration.url'           => 'url',
                        'configuration.enable'        => 'enable',
                        'configuration.login'         => 'login',
                        'configuration.use_webtrees'  => 'use_webtrees',
                        'configuration.allow_reverse' => 'allow_reverse',
                        'configuration.logging'       => 'logging',
                        'configuration.exclude'       => 'exclude',

                        'role.webtrees.administrator' => 'webtrees_administrator',
                        'role.webtrees.manager'       => 'webtrees_manager',
                        'role.webtrees.moderator'     => 'webtrees_moderator',
                        'role.webtrees.editor'        => 'webtrees_editor',
                        'role.webtrees.member'        => 'webtrees_member',

                        'role.drupal.administrator'   => 'drupal_administrator',
                        'role.drupal.manager'         => 'drupal_manager',
                        'role.drupal.moderator'       => 'drupal_moderator',
                        'role.drupal.editor'          => 'drupal_editor',

                        'webtrees_user.autoaccept'    => 'autoaccept',
                        'webtrees_user.comment'       => 'comment',
                        'webtrees_user.comment_exp'   => 'comment_exp',
                        'webtrees_user.language'      => 'webtrees_language',
                        'webtrees_user.contactmethod' => 'contactmethod',
                        'webtrees_user.timezone'      => 'webtrees_timezone',
                        'webtrees_user.verified'      => 'verified',
                        'webtrees_user.verified_by_admin' => 'verified_by_admin',
                        'webtrees_user.visibleonline' => 'visibleonline',
                        'webtrees_user.gedcom'        => 'gedcom',
        );

        foreach ( $fields as $set_name => $field_name ) {
          $config->set($set_name, $form_state->getValue($field_name));
        }

        $config->save();

    parent::submitForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {

    parent::validateForm($form, $form_state);
  }
}

