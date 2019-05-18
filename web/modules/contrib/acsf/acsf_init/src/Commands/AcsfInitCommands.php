<?php

namespace Drush\Commands;

use Drupal\acsf\AcsfException;
use Drupal\acsf\AcsfInitHtaccessException;
use Drupal\acsf\AcsfSite;
use Drupal\user\Entity\Role;
use Drupal\user\Entity\User;
use Drush\Exceptions\UserAbortException;

/**
 * Provides drush commands to set up a codebase for Acquia Cloud Site Factory.
 *
 * This class' namespace choice is off but it is necessary. All the commands in
 * this file are executed with the --include flag, and without reusing this
 * namespace the commands would not be found.
 *
 * @package Drush\Commands
 */
class AcsfInitCommands extends DrushCommands {

  /**
   * Content that we need to add to customers' .htaccess files.
   */
  const ACSF_HTACCESS_PATCH = 'RewriteCond %{REQUEST_URI} !/sites/g/apc_rebuild.php$';

  /**
   * The marker that tells us where we need to add our .htaccess patch.
   */
  const ACSF_HTACCESS_PATCH_MARKER = 'RewriteRule "^(.+/.*|autoload)\.php($|/)" - [F]';

  /**
   * A comment to include with the htaccess patch line.
   */
  const ACSF_HTACCESS_PATCH_COMMENT = '  # ACSF requirement: allow access to apc_rebuild.php.';

  /**
   * Initial delimiter string to enclose code added by acsf-init.
   */
  const ACSF_INIT_CODE_DELIMITER_START = '// ===== Added by acsf-init, please do not delete. Section start. =====';

  /**
   * Closing delimiter string to enclose code added by acsf-init.
   */
  const ACSF_INIT_CODE_DELIMITER_END = '// ===== Added by acsf-init, please do not delete. Section end. =====';

  /**
   * Add the necessary classes.
   *
   * @hook pre-command *
   */
  public function preInit() {
    $path = dirname(dirname(dirname(__DIR__))) . '/src';
    $classes = [
      'AcsfException',
      'AcsfInitException',
      'AcsfInitHtaccessException',
    ];
    foreach ($classes as $class) {
      require_once "$path/$class.php";
    }
  }

  /**
   * Make this repository compatible with Acquia Site Factory.
   *
   * Installs/updates the non-standard Drupal components for this repository to
   * be compatible with Acquia Site Factory. This command will update in place,
   * so there is no harm in running it multiple times.
   *
   * @command acsf-init
   *
   * @option skip-default-settings Do not edit the default settings.php file.
   *   Use this option when the edited default settings.php is causing issues in
   *   a local environment.
   *
   * @bootstrap root
   *
   * @param array $options
   *   The command options supplied to the executed command.
   *
   * @throws AcsfException
   *   If the command cannot be executed.
   */
  public function init(array $options = ['skip-default-settings' => FALSE]) {
    $drupal_root = realpath(DRUPAL_ROOT);
    if (basename($drupal_root) !== 'docroot') {
      // If for some reason we wanted to enable this command to run on directory
      // structures where no /docroot exists, that's possible in theory... but
      // we need to to audit / change the code in this file to
      // - derive the repo root in a good way;
      // - properly distinguish the repo root from the drupal root;
      // - figure out what to do with remaining instances of "docroot", if any;
      // - figure out what to do when drupal root and repo root are equal...
      //   like not copy hook files?
      throw new AcsfException(dt("Drupal must be installed in a subdirectory of your code repository, named 'docroot'."));
    }
    $repo_root = dirname($drupal_root);
    $skip_default_settings = $options['skip-default-settings'];
    $this->output()->writeln(dt('Installing ACSF requirements.'));

    // Create the required directories.
    foreach ($this->getRequiredDirs($repo_root) as $name => $dir) {
      // Skip '../sites/default' if --skip-default-settings is set.
      if ($skip_default_settings && $name == 'site env default') {
        continue;
      }

      $this->output()->writeln(dt('Creating directory for !name at !dir', [
        '!name' => $name,
        '!dir' => $dir,
      ]));
      if (!file_exists($dir)) {
        if (mkdir($dir, 0755, TRUE)) {
          $this->logger()->success(dt('Success'));
        }
        else {
          $this->logger()->error(dt('Error'));
        }
      }
      else {
        $this->logger()->notice(dt('Already Exists'));
      }
    }

    // Copy the required files.
    $lib_path = sprintf('%s/lib', dirname(dirname(dirname(__FILE__))));
    foreach ($this->getRequiredFiles($repo_root) as $location) {
      $file = $location['filename'];

      $dest = sprintf('%s/%s', $location['dest'], $file);

      // Some files only contain a destination as they are already in place.
      if (isset($location['source']) && isset($location['dest'])) {
        $source = sprintf('%s/%s/%s', $lib_path, $location['source'], $file);
        $this->output()->writeln(dt('Copying !file to !dest.', [
          '!file' => $source,
          '!dest' => $dest,
        ]));
        if (file_exists($dest)) {
          $confirm = $this->io()->confirm(dt('Destination file exists, continue?'));
          if ($confirm === FALSE) {
            continue;
          }
        }
        // Copy the file into the destination.
        if (copy($source, $dest)) {
          $this->logger()->success(dt('Copy Success: !file', ['!file' => $file]));
        }
        else {
          $this->logger()->error(dt('Copy Error: !file', ['!file' => $file]));
        }
        // If the file exists, it could be set to 0444, so we have to ensure
        // that it is writable before overwriting it. The copy would fail
        // otherwise.
        if (!is_writable($dest)) {
          if (!chmod($dest, 0666)) {
            $this->logger()->error(dt('Chmod Error: !file', ['!file' => $file]));
          };
        }
      }

      // Chmod the file if required.
      $mod = isset($location['mod']) ? $location['mod'] : FALSE;
      if ($mod && chmod($dest, $mod)) {
        $this->logger()->success(dt('Chmod Success: !file', ['!file' => $file]));
      }
      elseif ($mod) {
        $this->logger()->error(dt('Chmod Error: !file', ['!file' => $file]));
      }
    }

    try {
      $this->patchHtaccess();
    }
    catch (AcsfInitHtaccessException $e) {
      $this->logger()->error($e->getMessage());
    }

    // The default settings.php file needs special handling. On the ACSF
    // infrastructure our own business logic needs to execute while on ACE or on
    // a local environment the default settings.php could be used to drive other
    // sites. For this reason the ACSF specific code will be included in the
    // file instead of rewriting it to contain only our code.
    if (!$skip_default_settings) {
      $this->output()->writeln(dt('Updating the default settings.php file with the ACSF specific business logic.'));
      $edit_allowed = TRUE;
      $default_settings_php_path = $repo_root . '/docroot/sites/default/settings.php';
      if (file_exists($default_settings_php_path)) {
        $edit_allowed = $this->io()->confirm(dt('Destination file exists, continue?'));
      }
      if ($edit_allowed !== FALSE) {
        // If the file exists, it could be set to 0444, so we have to ensure
        // that it is writable.
        if (file_exists($default_settings_php_path) && !is_writable($default_settings_php_path)) {
          if (!chmod($default_settings_php_path, 0666)) {
            $this->logger()->error(dt('Chmod Error: !file', ['!file' => $default_settings_php_path]));
          }
        }
        if (file_exists($default_settings_php_path)) {
          // If the current default settings.php file has the same content as
          // acsf.legacy.default.settings.php then this file can be rewritten
          // according to the new approach. A simple strict equality check
          // should be enough since the acsf-init-verify checked the deployed
          // files by comparing md5 hashes, so even a single character
          // difference would have caused an error in the code deployment
          // process.
          $current_default_settings_php = file_get_contents($default_settings_php_path);
          $legacy_default_settings_php = file_get_contents($lib_path . '/sites/default/acsf.legacy.default.settings.php');
          if ($current_default_settings_php === $legacy_default_settings_php) {
            $this->defaultSettingsPhpCreate($default_settings_php_path);
          }
          else {
            // Update the default settings.php file with the latest ACSF code.
            $this->defaultSettingsPhpUpdate($default_settings_php_path);
          }
        }
        else {
          // The default settings.php file does not exist yet, so create a new
          // file with the necessary include.
          $this->defaultSettingsPhpCreate($default_settings_php_path);
        }
      }
    }

    // Verify that the files are in sync.
    clearstatcache();

    try {
      $this->initVerify($options);
      $this->output()->writeln(dt("Be sure to commit any changes to your repository before deploying. This includes files like sites/default/settings.php; you can use 'git status --ignored' to make sure no changes are inadvertantly ignored by a custom git configuration."));
    }
    catch (AcsfException $e) {
      $this->logger()->error($e->getMessage());
    }
  }

  /**
   * Verifies that acsf-init was successfully run in the current version.
   *
   * @command acsf-init-verify
   *
   * @option skip-default-settings Skip verifying the default settings.php file.
   *
   * @bootstrap root
   *
   * @param array $options
   *   The command options supplied to the executed command.
   *
   * @throws AcsfException
   *   If something is wrong with the current codebase.
   */
  public function initVerify(array $options = ['skip-default-settings' => NULL]) {
    $drupal_root = realpath(DRUPAL_ROOT);
    if (basename($drupal_root) !== 'docroot') {
      // If for some reason we wanted to enable this command to run on directory
      // structures where no /docroot exists, that's possible in theory... but
      // we need to to audit / change the code in this file to
      // - derive the repo root in a good way;
      // - properly distinguish the repo root from the drupal root;
      // - figure out what to do with remaining instances of "docroot", if any;
      // - figure out what to do when drupal root and repo root are equal...
      //   like not copy hook files?
      throw new AcsfException(dt("Drupal must be installed in a subdirectory of your code repository, named 'docroot'."));
    }
    $repo_root = dirname($drupal_root);
    $skip_default_settings = $options['skip-default-settings'];

    $lib_path = sprintf('%s/lib', dirname(dirname(dirname(__FILE__))));
    $error = FALSE;
    foreach ($this->getRequiredFiles($repo_root) as $location) {
      $file = $location['filename'];

      $dest = sprintf('%s/%s', $location['dest'], $file);

      // Some files only contain a destination as they are already in place.
      if (isset($location['source']) && isset($location['dest'])) {
        $source = sprintf('%s/%s/%s', $lib_path, $location['source'], $file);
        if (!file_exists($dest)) {
          $error = TRUE;
          $this->logger()->error(dt('The file !file is missing.', [
            '!file' => $file,
          ]));
        }
        elseif (md5_file($source) != md5_file($dest)) {
          $error = TRUE;
          $this->logger()->error(dt('The file !file is out of date.', [
            '!file' => $file,
          ]));
        }
      }

      // Verify the file is executable.
      // Note: The approach here is to not check for the exact file perms (in
      // other words to not test against the 'mod' element), since git - by
      // design - does not respect anything beyond a simple executable bit, the
      // other perms may be filesystem/OS-dependent, and can't be guaranteed to
      // be consistent.
      if (file_exists($dest) && !empty($location['test_executable'])) {
        $dest_permissions = fileperms($dest);
        // We do want to test the owner executable bit, and the group executable
        // bit as well.
        // e.g. to test whether the owner has execute permission, it is the case
        // of testing with: 00000000 01000000 (which is 0100 in octal, 64 in
        // decimal).
        if (($dest_permissions & (0100 | 0010)) != (0100 | 0010)) {
          $error = TRUE;
          $this->logger()->error(dt('The file !file is not executable. Make this file executable for the owner and group, then commit it again.', [
            '!file' => $file,
          ]));
        }
      }
    }

    if (!$this->testHtaccessIsPatched()) {
      $error = TRUE;
      $this->logger()->error(dt('The .htaccess file has not been patched to allow access to apc_rebuild.php.'));
    }

    // Skip the default settings.php file if --skip-default-settings is set.
    if (!$skip_default_settings) {
      // Check that the default settings.php contains the necessary ACSF
      // business logic.
      $acsf_business_logic = $this->defaultSettingsPhpIncludeGet();
      // Break up the business logic by lines.
      $acsf_business_logic_fragments = explode("\n", $acsf_business_logic);
      // Examine each line in the business logic to make sure it appears in the
      // file. This way minor indentation changes will not cause failure.
      $missing_piece = FALSE;
      $default_settings_php_contents = file_get_contents($repo_root . '/docroot/sites/default/settings.php');
      foreach ($acsf_business_logic_fragments as $line) {
        if (strpos($default_settings_php_contents, $line) === FALSE) {
          $missing_piece = TRUE;
          break;
        }
      }
      if ($missing_piece) {
        $error = TRUE;
        $this->logger()->error(dt('The default settings.php file is out of date.'));
      }
    }

    if ($error) {
      throw new AcsfException(dt('Please run drush acsf-init to correct these problems and commit the resulting code changes.'));
    }
    else {
      // The Site Factory code deployment uses this string to determine if the
      // acsf-init has been properly run. If this is changed, also ensure that
      // the check in VcsVerifyAcsf matches.
      $this->logger()->success(dt('acsf-init required files ok'));
    }
  }

  /**
   * Connect a site to a factory by setting up the right database variables.
   *
   * @command acsf-connect-factory
   *
   * @aliases acsf-cf
   *
   * @option site-admin-mail The email address of the Site Factory admin /
   *   Gardens admin user. This is typically the "Site Factory admin" user on
   *   the factory. These email addresses have to match in order for the initial
   *   OpenID connection to bind these accounts.
   * @option site-owner-name The name of the site owner.
   * @option site-owner-mail The email address of the site owner.
   * @option site-owner-roles A list of comma-separated roles (machine names)
   *   that should be granted to the site owner (optional).
   *
   * @usage drush acsf-connect-factory --site-admin-mail="user3@example.com"
   *   --site-owner-name="John Smith" --site-owner-mail="john.smith@example.com"
   *   Connect the site to the factory and sets the owner to John Smith.
   *
   * @bootstrap full
   *
   * @param array $options
   *   The command options supplied to the executed command.
   *
   * @throws \Drupal\acsf\AcsfException
   *    If the provided email address is invalid.
   * @throws \InvalidArgumentException
   *   If one or more arguments are missing or invalid.
   * @throws \Drush\Exceptions\UserAbortException
   *   If the customer does not allow the creation of a new account.
   */
  public function connectFactory(array $options = [
    'site-admin-mail' => NULL,
    'site-owner-name' => NULL,
    'site-owner-mail' => NULL,
    'site-owner-roles' => NULL,
  ]) {

    // Preliminary validation before starting to modify the database.
    $site_admin_mail = trim($options['site-admin-mail']);
    $site_owner_name = trim($options['site-owner-name']);
    $site_owner_mail = trim($options['site-owner-mail']);
    $site_owner_roles = array_filter(explode(',', $options['site-owner-roles']));

    // Validate email addresses.
    $validator = \Drupal::service('email.validator');
    if (!$validator->isValid($site_admin_mail)) {
      throw new \InvalidArgumentException(dt('The site-admin-mail value is not a valid email address.'));
    }
    if (!$validator->isValid($site_owner_mail)) {
      throw new \InvalidArgumentException(dt('The site-owner-mail value is not a valid email address.'));
    }

    $user_storage = \Drupal::entityTypeManager()->getStorage('user');

    // Make sure there is no regular user account with the admin email address.
    $site_admin_mail_accounts = $user_storage->loadByProperties([
      'mail' => $site_admin_mail,
    ]);
    $site_admin_mail_account = reset($site_admin_mail_accounts);
    if ($site_admin_mail_account && $site_admin_mail_account->id() > 1) {
      throw new AcsfException(dt('Unable to sync the admin account, the email address @mail is already used by the user account @uid.', [
        '@mail' => $site_admin_mail,
        '@uid' => $site_admin_mail_account->id(),
      ]));
    }

    // The site owner's email address may have been changed on the factory (for
    // instance, if the user updated their email address on the factory and the
    // new email address has not yet been synced to the site). First, try to
    // locate the user account by site-owner-mail.
    $site_owner_accounts = $user_storage->loadByProperties([
      'mail' => $site_owner_mail,
    ]);
    $site_owner_account = reset($site_owner_accounts);
    if ($site_owner_account && $site_owner_account->getUsername() !== $site_owner_name) {
      throw new AcsfException(dt('The site-owner-name value does not match the name of the user loaded by site-owner-mail.'));
    }
    // If the site owner user account is not found, try to locate it by
    // site-owner-name.
    if (!$site_owner_account) {
      $site_owner_accounts = $user_storage->loadByProperties([
        'name' => $site_owner_name,
      ]);
      $site_owner_account = reset($site_owner_accounts);
    }
    // If the site owner account is still not found then either the customer has
    // made a typo or maybe there is going to be a new owner who needs a new
    // account. Ask for confirmation to create a new account.
    if (!$site_owner_account) {
      if (!$this->io()->confirm(dt('The site owner name or email address that you provided does not correspond to any account on the site. Do you want to create a new account?'))) {
        throw new UserAbortException();
      }
    }

    // Clear all caches ahead of time so Drupal has a chance to rebuild
    // registries.
    drupal_flush_all_caches();
    acsf_build_registry();
    $this->logger()->info(dt('Cleared all caches.'));

    // Set default settings for user accounts.
    $admin_role_ids = \Drupal::EntityQuery('user_role')
      ->condition('is_admin', TRUE)
      ->execute();

    // Take over uid 1 with our Site Factory admin user.
    $admin_account = User::load(1);
    // Create a new user if uid 1 doesn't exist.
    if (!$admin_account) {
      $admin_account = User::create(['uid' => 1]);
    }
    // Ensure the default admin role is added to the account.
    $admin_account->addRole(reset($admin_role_ids));
    // Set login time to avoid e-mail verification needed error.
    $admin_account->setLastLoginTime(1)
      ->setUsername('Site Factory admin')
      ->setEmail($site_admin_mail)
      ->setPassword(user_password())
      ->activate()
      ->save();

    // Create or update site owner account.
    // Prepare roles for site owner.
    if (!$site_owner_account) {
      $site_owner_account = User::create();
    }

    foreach ($site_owner_roles as $owner_role) {
      if (Role::load($owner_role)) {
        $site_owner_account->addRole($owner_role);
      }
      else {
        $this->logger()->warning(dt('The role @role does not exist; not adding it to the site owner.', ['@role' => $owner_role]));
      }
    }
    // Site owners also get the default administrator role.
    $site_owner_account->addRole(reset($admin_role_ids));

    $site_owner_account->setLastLoginTime(1)
      ->setUsername($site_owner_name)
      ->setEmail($site_owner_mail)
      ->setPassword(user_password())
      ->activate()
      ->save();

    $this->logger()->info(dt('Synched Site Factory admin and site owner accounts.'));

    // Remove acsf variable so that it can be repopulated with the right value
    // on the next acsf-site-sync.
    \Drupal::service('acsf.variable_storage')->delete('acsf_site_info');

    // Reset the local site data and run acsf-site-sync to fetch factory data
    // about the site.
    $site = AcsfSite::load();
    $site->clean();
    \Drupal::service('acsf.commands')->siteSync();
    $this->logger()->info(dt('Executed acsf-site-sync to gather site data from factory and reset all acsf variables.'));

    // Set other configuration related to connections to/from the site factory.
    if (\Drupal::moduleHandler()->moduleExists('acsf_sso')) {
      // Repopulate/overwrite the subset of SAML auth data which is factory /
      // sitegroup/env/factory-site-nid specific.
      module_load_include('install', 'acsf_sso');
      acsf_sso_install_set_env_dependent_config();
    }

    // Clear all caches.
    drupal_flush_all_caches();
    $this->logger()->info(dt('Cleared all caches.'));

    // Send a theme event notification to the Factory.
    \Drupal::service('acsf.theme_notification')->sendNotification('site', 'create');
  }

  /**
   * Remove Acquia Site Factory components from this repository.
   *
   * Uninstalls components that allow this Drupal repository to be compatible
   * with Acquia Site Factory.
   *
   * @command acsf-uninstall
   *
   * @bootstrap root
   */
  public function uninstall() {
    $this->output()->writeln('Removing ACSF requirements.');

    $drupal_root = realpath(DRUPAL_ROOT);
    $repo_root = dirname($drupal_root);
    if (basename($drupal_root) !== 'docroot') {
      // We're not failing if Drupal is not installed in 'docroot' (as in
      // acsf-init), so that files can still be removed from strange installs.
      // hooks/ will be checked inside the docroot.
      $repo_root = $drupal_root;
    }
    foreach ($this->getRequiredFiles($repo_root) as $location) {
      $file = $location['filename'];
      $dest = sprintf('%s/%s', $location['dest'], $file);

      // Some files only contain a destination as they are already in place.
      if (isset($location['source']) && file_exists($dest)) {
        $confirm = $this->io()->confirm(dt('Delete !file?', ['!file' => $dest]));
        if ($confirm === FALSE) {
          continue;
        }
        if (unlink($dest)) {
          $this->logger()->success(dt('Success'));
        }
        else {
          $this->logger()->error(dt('Error'));
        }
      }
    }

    // Remove the ACSF specific business logic from the default setting.php.
    if (file_exists($repo_root . '/docroot/sites/default/settings.php')) {
      $default_settings_php_contents = file_get_contents($repo_root . '/docroot/sites/default/settings.php');
      $default_settings_php_contents = preg_replace('/' . preg_quote(self::ACSF_INIT_CODE_DELIMITER_START, '/') . '.*?' . preg_quote(self::ACSF_INIT_CODE_DELIMITER_END, '/') . '/sm', '', $default_settings_php_contents);
      file_put_contents($repo_root . '/docroot/sites/default/settings.php', $default_settings_php_contents);
    }
  }

  /**
   * Lists all required directories to create.
   */
  public function getRequiredDirs($repo_root) {
    return [
      'cloud hooks' => sprintf('%s/hooks/common/post-db-copy', $repo_root),
      'cloud hook deploy' => sprintf('%s/hooks/common/pre-web-activate', $repo_root),
      'acquia hook dir' => sprintf('%s/hooks/acquia', $repo_root),
      'cloud hook samples' => sprintf('%s/hooks/samples', $repo_root),
      'site config logic' => sprintf('%s/sites/g', DRUPAL_ROOT),
      'site env default' => sprintf('%s/sites/default', DRUPAL_ROOT),
    ];
  }

  /**
   * Lists all required files to create/delete.
   */
  public function getRequiredFiles($repo_root) {
    // Array elements should use the following guidelines:
    // - Use the 'source' element to indicate where the file should be copied
    //   from. Note: Some files do not have a source as they are already in
    //   place.
    // - Use the 'dest' element to specify where the file will be copied to.
    // - Use the 'mod' element to describe an octal for the file permissions -
    //   must be chmod() compatible. e.g. 0755
    // - Use the 'test_executable' element to enforce testing executability of
    //   the file. "Executable" files are expected to be owner and group
    //   executable.
    return [
      [
        'filename' => 'README.md',
        'source' => 'cloud_hooks',
        'dest' => sprintf('%s/hooks', $repo_root),
      ],
      [
        'filename' => '000-acquia_required_scrub.php',
        'source' => 'cloud_hooks/common/post-db-copy',
        'dest' => sprintf('%s/hooks/common/post-db-copy', $repo_root),
        'mod' => 0750,
        'test_executable' => TRUE,
      ],
      [
        'filename' => '000-acquia-deployment.php',
        'source' => 'cloud_hooks/common/pre-web-activate',
        'dest' => sprintf('%s/hooks/common/pre-web-activate', $repo_root),
        'mod' => 0750,
        'test_executable' => TRUE,
      ],
      [
        'filename' => 'db_connect.php',
        'source' => 'cloud_hooks/acquia',
        'dest' => sprintf('%s/hooks/acquia', $repo_root),
      ],
      [
        'filename' => 'uri.php',
        'source' => 'cloud_hooks/acquia',
        'dest' => sprintf('%s/hooks/acquia', $repo_root),
      ],
      [
        'filename' => 'acquia-cloud-site-factory-post-db.sh',
        'source' => 'cloud_hooks/samples',
        'dest' => sprintf('%s/hooks/samples', $repo_root),
      ],
      [
        'filename' => 'hello-world.sh',
        'source' => 'cloud_hooks/samples',
        'dest' => sprintf('%s/hooks/samples', $repo_root),
      ],
      [
        'filename' => 'sites.php',
        'source' => 'sites',
        'dest' => sprintf('%s/sites', DRUPAL_ROOT),
      ],
      [
        'filename' => 'apc_rebuild.php',
        'source' => 'sites/g',
        'dest' => sprintf('%s/sites/g', DRUPAL_ROOT),
      ],
      [
        'filename' => '.gitignore',
        'source' => 'sites/g',
        'dest' => sprintf('%s/sites/g', DRUPAL_ROOT),
      ],
      [
        'filename' => 'services.yml',
        'source' => 'sites/g',
        'dest' => sprintf('%s/sites/g', DRUPAL_ROOT),
      ],
      [
        'filename' => 'settings.php',
        'source' => 'sites/g',
        'dest' => sprintf('%s/sites/g', DRUPAL_ROOT),
      ],
      [
        'filename' => 'SimpleRest.php',
        'source' => 'sites/g',
        'dest' => sprintf('%s/sites/g', DRUPAL_ROOT),
      ],
      [
        'filename' => 'sites.inc',
        'source' => 'sites/g',
        'dest' => sprintf('%s/sites/g', DRUPAL_ROOT),
      ],
      [
        'filename' => '.gitignore',
        'source' => 'sites/default',
        'dest' => sprintf('%s/sites/default', DRUPAL_ROOT),
      ],
      [
        'filename' => 'acsf.settings.php',
        'source' => 'sites/default',
        'dest' => sprintf('%s/sites/default', DRUPAL_ROOT),
      ],
    ];
  }

  /**
   * Returns the path to the .htaccess file within the codebase.
   *
   * @return string
   *   The full absolute path to the .htaccess file.
   */
  public function getHtaccessPath() {
    return DRUPAL_ROOT . '/.htaccess';
  }

  /**
   * Returns the code to be added to the default settings.php file.
   */
  public function defaultSettingsPhpIncludeGet() {
    // Heredoc does not handle constants.
    $delimiter_start = self::ACSF_INIT_CODE_DELIMITER_START;
    $delimiter_end = self::ACSF_INIT_CODE_DELIMITER_END;
    return <<<INCLUDE
${delimiter_start}
\$_acsf_infrastructure = include dirname(__FILE__) . '/acsf.settings.php';
if (\$_acsf_infrastructure === 'acsf-infrastructure') {
  return;
}
${delimiter_end}
INCLUDE;
  }

  /**
   * Determines whether htaccess allows access to our apc_rebuild.php script.
   */
  public function testHtaccessIsPatched() {
    if (!file_exists($this->getHtaccessPath()) || !($content = file_get_contents($this->getHtaccessPath()))) {
      // If the htaccess file does not exist or is empty, then it cannot forbid
      // access to apc_rebuild.php, so we can consider this a pass, albeit a
      // weird one.
      return TRUE;
    }

    // If a customer was for some reason really sure that they did not
    // want our line in their .htaccess file, then adding it verbatim, but
    // commented-out would suffice.
    return strpos($content, self::ACSF_HTACCESS_PATCH) !== FALSE;
  }

  /**
   * Re-creates the default settings.php file.
   *
   * @param string $default_settings_php_path
   *   The path to the default settings.php file.
   */
  private function defaultSettingsPhpCreate($default_settings_php_path) {
    $result = file_put_contents($default_settings_php_path, "<?php\n\n" . $this->defaultSettingsPhpIncludeGet() . "\n");
    if ($result) {
      $this->logger()->success(dt('File create success: sites/default/settings.php'));
    }
    else {
      $this->logger()->error(dt('File create error: sites/default/settings.php'));
    }
  }

  /**
   * Updates the default settings.php with the ACSF specific script include.
   *
   * @param string $default_settings_php_path
   *   The path to the default settings.php file.
   */
  private function defaultSettingsPhpUpdate($default_settings_php_path) {
    $default_settings_php_contents = file_get_contents($default_settings_php_path);
    // Check if the default settings.php contains our code block. The m modifier
    // makes it possible to match text over multiple lines and the s modifier
    // allows the . wildcard to match newline characters.
    if (!preg_match('/' . preg_quote(self::ACSF_INIT_CODE_DELIMITER_START, '/') . '.*?' . preg_quote(self::ACSF_INIT_CODE_DELIMITER_END, '/') . '/ms', $default_settings_php_contents)) {
      // Code block not detected: add it after the opening php tag.
      $this->logger()->notice(dt('ACSF include not detected in sites/default/settings.php.'));
      // Using preg_replace instead of str_replace to be able to control how
      // many times the replace gets executed.
      $default_settings_php_contents = preg_replace('/<\?php/', "<?php\n\n" . $this->defaultSettingsPhpIncludeGet() . "\n", $default_settings_php_contents, 1, $count);
      if ($count === 0) {
        $this->logger()->error(dt('Could not find <?php tag in sites/default/settings.php.'));
      }
    }
    else {
      // Code block found: update it with the latest version.
      $this->logger()->notice(dt('ACSF include detected in sites/default/settings.php.'));
      $default_settings_php_contents = preg_replace('/' . preg_quote(self::ACSF_INIT_CODE_DELIMITER_START, '/') . '.*?' . preg_quote(self::ACSF_INIT_CODE_DELIMITER_END, '/') . '/ms', $this->defaultSettingsPhpIncludeGet(), $default_settings_php_contents);
    }
    $result = file_put_contents($default_settings_php_path, $default_settings_php_contents);
    if ($result) {
      $this->logger()->success(dt('File edit success: sites/default/settings.php'));
    }
    else {
      $this->logger()->error(dt('File edit error: sites/default/settings.php'));
    }
  }

  /**
   * Patches the htaccess file to allow access to our apc_rebuild.php script.
   *
   * We do not use the unix patch utility here, as it might be too fussy about
   * line numbers and context.  We want to ensure that our line is present in
   * the .htaccess file in an appropriate place, without caring too much about
   * the content of surrounding lines.  There will be extreme cases where this
   * patching can fail if modifications to the customer's .htaccess file cause
   * it to be wildly different to stock Drupal 8.  In this case, they can
   * manually add the line, and we will accept that manual modification.
   * This doesn't seem like a situation we need to handle until we have at least
   * one case. If a customer was for some reason really sure that they did not
   * want our line in their .htaccess file, then adding it verbatim, but
   * commented-out would suffice.
   *
   * @throws \Drupal\acsf\AcsfInitHtaccessException
   *   If the function couldn't patch the .htaccess file.
   */
  private function patchHtaccess() {
    if ($this->testHtaccessIsPatched()) {
      return;
    }

    $this->output()->writeln(dt('Patching .htaccess file.'));
    // All the lines we've scanned so far up until the marker line.
    $output_lines = [];
    // The output as a string.
    $output = '';
    // Iterate line-by-line until we reach the line which our rule MUST precede.
    $fp = fopen($this->getHtaccessPath(), 'r+');
    $marker_found = FALSE;
    while (($line = fgets($fp, 4096)) !== FALSE) {
      $output_lines[] = $line;

      if (strpos($line, self::ACSF_HTACCESS_PATCH_MARKER) !== FALSE) {
        $marker_found = TRUE;
        // ... And then backtrack all the lines of comments that precede that
        // marker line.
        $marker_index = count($output_lines) - 1;
        while ($marker_index > 0 && preg_match('/^\s*#.*$/', $output_lines[$marker_index - 1])) {
          $marker_index--;
        }
        if ($marker_index == 0) {
          // This should never happen - it could only happen if the entire
          // .htaccess file preceding the marker line were all comments.  We
          // cannot consider this valid, as the minimum requirements for
          // rewrites are not met.  We'd expect at a minimum
          // <IfModule mod_rewrite.c> ... RewriteEngine on.
          throw new AcsfInitHtaccessException('Reached the beginning of the file but was unable to find a place to insert the .htaccess patch.  The .htaccess file can be manually handled to fix this error.');
        }

        // Insert our patch with preceding comment, before the marker index,
        // i.e. after the first line seen which is not a comment.
        // Also, prepending 2 whitespaces before the htaccess patch
        // so that it's indented properly.
        array_splice($output_lines, $marker_index, 0, [
          self::ACSF_HTACCESS_PATCH_COMMENT . "\n",
          '  ' . self::ACSF_HTACCESS_PATCH . "\n",
        ]);
        $output = implode('', $output_lines);

        break;
      }
    }

    if (!$marker_found) {
      // We were unable to locate the marker that disallows access to PHP files
      // - it may have been modified which makes it impossible to locate.
      // Alternatively, it may have been removed entirely, which actually means
      // we don't need to patch the file at all. @see
      // drush_acsf_init_patch_htaccess() for info on how to convince the
      // verification to accept the file.
      throw new AcsfInitHtaccessException('Unable to locate the marker for patching the .htaccess file. This file will need manual patching to allow access to the apc_rebuild.php file.');
    }

    // Then append the rest of the file.
    while (($line = fgets($fp, 4096)) !== FALSE) {
      $output .= "$line";
    }

    file_put_contents($this->getHtaccessPath(), $output);

    if ($this->testHtaccessIsPatched()) {
      $this->logger()->info(dt('Successfully patched .htaccess file.'));
    }
    else {
      $this->logger()->error(dt('Failed to patch .htaccess file.'));
    }
  }

}
