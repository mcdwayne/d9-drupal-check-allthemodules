<?php

/**
 * @file
 * Contains \Drupal\monster_menus\Form\ConfigForm.
 */

namespace Drupal\monster_menus\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\Core\Database\Connection;
use Drupal\monster_menus\Constants;
use Drupal\user\Entity\Role;
use Drupal\user\PermissionHandlerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class ConfigForm extends ConfigFormBase {

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * The permission handler.
   *
   * @var \Drupal\user\PermissionHandlerInterface
   */
  protected $permissionHandler;

  /**
   * Constructs a ConfigForm object.
   *
   * @param \Drupal\Core\Database\Connection $database
   *   The database connection.
   * @param \Drupal\user\PermissionHandlerInterface $permission_handler
   *   The permission handler.
   */
  public function __construct(Connection $database, PermissionHandlerInterface $permission_handler) {
    $this->database = $database;
    $this->permissionHandler = $permission_handler;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('database'),
      $container->get('user.permissions')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'mm_admin_config';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['monster_menus.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // Note: When creating form elements that refer to config settings
    // containing sub-elements (with dots), change the dots to dashes. For
    // instance:
    //   $form['foo-bar'] = [
    //     '#type' => 'text',
    //     '#default_value' => $settings->get('foo.bar')
    //   ]
    $settings = $this->config('monster_menus.settings');
    if ($this->currentUser()->hasPermission('administer all menus')) {
      $form['general'] = array(
        '#type' => 'details',
        '#title' => $this->t('General'),
      );
      $form['general']['recycle_auto_empty'] = array(
        '#type' => 'select',
        '#title' => $this->t('Automatic recycle bin deletion interval'),
        '#description' => $this->t('Automatically delete content in recycle bins that has been there longer than this amount of time'),
        '#default_value' => $settings->get('recycle_auto_empty'),
        '#options' => array(
          -1                       => $this->t('(don\'t use recycle bins)'),
          0                        => $this->t('(never auto delete)'),
          30*60                    => $this->t('30 minutes'),
          60*60                    => $this->t('1 hour'),
          2*60*60                  => $this->t('2 hours'),
          6*60*60                  => $this->t('6 hours'),
          12*60*60                 => $this->t('12 hours'),
          24*60*60                 => $this->t('1 day'),
          2*24*60*60               => $this->t('2 days'),
          3*24*60*60               => $this->t('3 days'),
          7*24*60*60               => $this->t('1 week'),
          2*7*24*60*60             => $this->t('2 weeks'),
          30*24*60*60              => $this->t('30 days'),
          60*24*60*60              => $this->t('60 days'),
          intval(365/4*24*60*60)   => $this->t('3 months'),
          intval(365/2*24*60*60)   => $this->t('6 months'),
          intval(365/4*3*24*60*60) => $this->t('9 months'),
          365*24*60*60             => $this->t('1 year'),
        )
      );
      $form['general']['access_cache_time'] = array(
        '#type' => 'select',
        '#options' => array(0 => $this->t('(disabled)'), 30 => $this->t('30 seconds'), 60 => $this->t('1 minute'), 120 => $this->t('2 minutes'), 180 => $this->t('3 minutes'), 240 => $this->t('4 minutes'), 300 => $this->t('5 minutes'), 600 => $this->t('10 minutes'), 900 => $this->t('15 minutes'), 1200 => $this->t('20 minutes'), 1800 => $this->t('30 minutes'), 2700 => $this->t('45 minutes'), 3600 => $this->t('1 hour')),
        '#title' => $this->t('Permissions cache time'),
        '#description' => $this->t("Save time by caching the data needed to determine if a given user has access to a page or piece of content. The cache is automatically cleared whenever a piece content or any of its parent pages' permissions are modified. Setting this value too high can lead to lots of data being stored in the cache table."),
        '#default_value' => $settings->get('access_cache_time'),
      );
      $form['general']['prevent_showpage_removal'] = array(
        '#type' => 'select',
        '#options' => array(
          Constants::MM_PREVENT_SHOWPAGE_REMOVAL_NONE => $this->t('Do not check'),
          Constants::MM_PREVENT_SHOWPAGE_REMOVAL_WARN => $this->t('Show a warning'),
          Constants::MM_PREVENT_SHOWPAGE_REMOVAL_HALT => $this->t('Prevent non-admins from moving or renaming'),
        ),
        '#title' => $this->t('Protect dynamic content (hook_mm_showpage_routing) from removal'),
        '#description' => $this->t('This option detects when the user is about to either move or rename a page that is referred to (or is the parent of a page referred to) in a hook_mm_showpage_routing() implemention. If the "Prevent" option is chosen, users with the "administer all menus" permission will only see a warning.'),
        '#default_value' => $settings->get('prevent_showpage_removal'),
      );

      $form['vgroup'] = array(
        '#type' => 'details',
        '#title' => $this->t('Virtual Groups'),
      );
      $form['vgroup']['vgroup-regen_chunk'] = array(
        '#type' => 'number',
        '#title' => $this->t('Chunk size used to split virtual group regeneration queries'),
        '#min' => 1,
        '#size' => 5,
        '#default_value' => $settings->get('vgroup.regen_chunk'),
        '#description' => $this->t('To gain speed when regenerating dirty virtual groups, the separate queries are concatenated into one big query with a UNION. If the maximum SQL query buffer length is being exceeded or the large queries are taking too much memory, this value should be reduced.'),
      );
      $form['vgroup']['vgroup-regen_chunks_per_run'] = array(
        '#type' => 'number',
        '#title' => $this->t('Number of concatenated virtual group queries per cron run'),
        '#min' => 1,
        '#size' => 5,
        '#default_value' => $settings->get('vgroup.regen_chunks_per_run'),
        '#description' => $this->t('If cron is taking too long to complete, this value should be reduced. The total number of dirty virtual groups updated per cron run is this number times the chunk size.'),
      );
      $form['vgroup']['vgroup-errors_email'] = array(
        '#type' => 'textfield',
        '#title' => $this->t('Where to send virtual group warnings'),
        '#default_value' => mm_default_setting('vgroup.errors_email', ini_get('sendmail_from')),
        '#description' => $this->t('Warnings are generated for any virtual groups that decrease in size too rapidly. This is the e-mail address where warnings are sent. If left blank, the site e-mail address is used.'),
        '#maxlength' => 1024,
        '#size' => 100,
      );
      $form['vgroup']['vgroup-group_info_message'] = array(
        '#type' => 'textfield',
        '#title' => $this->t('Message displayed in group information for virtual groups'),
        '#default_value' => $settings->get('vgroup.group_info_message'),
        '#description' => $this->t('Two variables are available for substitution: @gid is the group ID and @owner is the themed owner of the group.'),
        '#maxlength' => 1024,
        '#size' => 100,
      );
      $form['vgroup']['group-group_info_message'] = array(
        '#type' => 'textfield',
        '#title' => $this->t('Message displayed in group information for regular groups'),
        '#default_value' => $settings->get('group.group_info_message'),
        '#description' => $this->t('Two variables are available for substitution: @gid is the group ID and @owner is the themed owner of the group.'),
        '#maxlength' => 1024,
        '#size' => 100,
      );

      $form['mm_page'] = array(
        '#type' => 'details',
        '#title' => $this->t('Page Display'),
      );
      $form['mm_page']['pages-hide_empty_pages_in_menu'] = array(
        '#type' => 'checkbox',
        '#title' => $this->t('Hide empty pages in menus'),
        '#default_value' => $settings->get('pages.hide_empty_pages_in_menu'),
      );
      $form['mm_page']['pages-enable_rss'] = array(
        '#type' => 'checkbox',
        '#title' => $this->t('Allow content creators to control the availability of RSS feeds on a per-page basis'),
        '#default_value' => $settings->get('pages.enable_rss'),
      );

      $form['mm_node'] = array(
        '#type' => 'details',
        '#title' => $this->t('Node Display'),
      );

      if (mm_module_exists('comment')) {
        $form['mm_node']['comments'] = array(
          '#type' => 'details',
          '#title' => $this->t('Comments'),
        );
        $form['mm_node']['comments']['comments-show_count_instead'] = array(
          '#type' => 'checkbox',
          '#title' => $this->t('Show comment count instead of full comments'),
          '#description' => $this->t('This option takes effect when viewing pages. When checked, nodes having comments show a link with the number of comments. Clicking on the link displays the node by itself, with the comments. If unchecked, all comments are displayed under their nodes, on the same page.'),
          '#default_value' => $settings->get('comments.show_count_instead'),
        );
        $form['mm_node']['comments']['comments-finegrain_readability'] = array(
          '#type' => 'checkbox',
          '#title' => $this->t('Control comment readability at the node level'),
          '#description' => $this->t('This option lets users say who can read comments posted to each node on an individual basis. A default value for new nodes can also be set at the page level.'),
          '#default_value' => $settings->get('comments.finegrain_readability'),
        );

        $labels = $settings->get('comments.readable_labels');
        $labels[] = array();
        if (count($labels) == 1) {
          $labels[] = array();
        }
        $form['mm_node']['comments']['comments-readable_labels'] = array(
          '#prefix' => '<table><tr><th>' . $this->t('Permission') . '</th><th>' . $this->t('Description') . '</th></tr>',
          '#suffix' => '</table>',
          '#tree' => TRUE,
        );
        $i = 0;
        foreach ($labels as $label) {
          $form['mm_node']['comments']['comments-readable_labels'][$i]['perm'] = array(
            '#type' => 'textfield',
            '#prefix' => '<tr><td>',
            '#default_value' => isset($label['perm']) ? $label['perm'] : '',
            '#size' => 30,
            '#suffix' => '</td><td>',
          );
          $form['mm_node']['comments']['comments-readable_labels'][$i]['desc'] = array(
            '#type' => 'textfield',
            '#suffix' => '</td></tr>',
            '#default_value' => isset($label['desc']) ? $label['desc'] : '',
            '#size' => 30,
          );
          $i++;
        }
        $form['mm_node']['comments']['desc'] = array(
          '#type' => 'item',
          '#input' => FALSE,
          '#description' => $this->t('<p><em>Permission</em> is the label appearing on the <a href=":url">Permissions</a> page; these are effectively ANDed with the <em>access comments</em> permission. Example: <em>comments readable by everyone</em></p><p><em>Description</em> is what users see in the list of choices for setting readability at the page/node level, it answers the question, "Who can read comments?" Example: <em>everyone</em></p><p>To remove a row, clear either value. <strong>Changing data in the Permission column or removing rows may affect the readability of comments in existing nodes!</strong> Don\'t forget to update the permissions after making changes here.</p>', array(':url' => Url::fromRoute('user.admin_permissions')->toString()))
        );
      }

      $form['mm_userhome'] = array(
        '#type' => 'details',
        '#title' => $this->t('User Home Pages'),
      );
      $form['mm_userhome']['user_homepages-enable'] = array(
        '#type' => 'checkbox',
        '#title' => $this->t('Use user home directories'),
        '#description' => $this->t('When enabled, each newly-added user gets a personal home page, starting at <a href=":url">/users</a>. Note: If you disable and then re-enable this option, any users created during the time it was disabled will not have home pages.', array(':url' => mm_content_get_mmtid_url(mm_content_users_mmtid())->toString())),
        '#default_value' => $settings->get('user_homepages.enable'),
      );
      $form['mm_userhome']['user_homepages-virtual'] = array(
        '#type' => 'checkbox',
        '#title' => $this->t('Use virtual user directories'),
        '#description' => $this->t('If you have many users, the entire user list at <a href=":url">/users</a> can get very long. This feature will split the users into smaller chunks, based on the letter of the alphabet with which their name begins.', array(':url' => mm_content_get_mmtid_url(mm_content_users_mmtid())->toString())),
        '#default_value' => $settings->get('user_homepages.virtual'),
      );
      $form['mm_userhome']['user_homepages-default_homepage'] = array(
        '#type' => 'textarea',
        '#title' => $this->t('Default personal homepage message'),
        '#description' => $this->t('What users see when viewing their own, empty homepage. Provide some instructions telling them how to create content.'),
        '#default_value' => $settings->get('user_homepages.default_homepage'),
      );

      $form['mm_username'] = array(
        '#type' => 'details',
        '#title' => $this->t('User Names'),
        '#description' => $this->t('These names are displayed in content attribution lines and group membership lists.'),
      );
      $form['mm_username']['usernames-anon'] = array(
        '#type' => 'textfield',
        '#title' => $this->t('Long name of the Anonymous user'),
        '#default_value' => $settings->get('usernames.anon'),
      );
      $form['mm_username']['usernames-admin'] = array(
        '#type' => 'textfield',
        '#title' => $this->t('Long name of the Administrator user'),
        '#default_value' => $settings->get('usernames.admin'),
      );
      $form['mm_username']['usernames-disabled'] = array(
        '#type' => 'textfield',
        '#title' => $this->t('Long name for all disabled users'),
        '#default_value' => $settings->get('usernames.disabled'),
      );

      $form['mm_nodelist'] = array(
        '#type' => 'details',
        '#title' => $this->t('Node Chooser'),
      );
      $form['mm_nodelist']['nodes-nodelist_pager_limit'] = array(
        '#type' => 'select',
        '#title' => $this->t('Number of nodes to show per page in the node chooser'),
        '#options' => array(
          10 => 10,
          20 => 20,
          50 => 50,
          100 => 100,
        ),
        '#default_value' => $settings->get('nodes.nodelist_pager_limit'),
      );

      $form['mm_sitemap'] = array(
        '#type' => 'details',
        '#title' => $this->t('Site Map'),
      );
      $form['mm_sitemap']['help'] = array(
        '#markup'=> $this->t('<p>Monster Menus will respond to a request for <code>/-mm-sitemap</code> by generating a standard <code>/sitemap.xml</code> file. You should call this URL periodically, in the same way you do cron.php, but less frequently. Once it has been generated, the <code>sitemap.xml</code> file contains links to any pages that are publicly readable, and not hidden or recycled.</p>'),
      );
      $form['mm_sitemap']['sitemap-exclude_list'] = array(
        '#type' => 'textarea',
        '#wysiwyg' => FALSE,
        '#title' => $this->t('Paths to exclude from the sitemap'),
        '#default_value' => join("\n", $settings->get('sitemap.exclude_list')),
        '#description' => $this->t('A list of paths, one per line, which should not be part of the <code>sitemap.xml</code>. Do not include leading or trailing slashes. Example: <code>foo/bar/baz</code>'),
      );
      $form['mm_sitemap']['sitemap-max_level'] = array(
        '#type' => 'select',
        '#options' => array(-1 => $this->t('(disabled)'), 0 => $this->t('(Home only)'), 1 => $this->t('1 level'), 2 => 2, 3 => 3, 4 => 4, 5 => 5, 6 => 6, 7 => 7, 8 => 8, 9 => 9, 10 => 10, 11 => 11, 12 => 12, 13 => 13, 14 => 14, 15 => 15, 1000 => $this->t('(unlimited)')),
        '#title' => $this->t('Number of levels to generate'),
        '#default_value' => $settings->get('sitemap.max_level'),
        '#description' => $this->t('The maximum depth in the tree to use for the sitemap. Set this too high and, on a large site, your sitemap.xml file may become too large to be useful.'),
      );
    }

    mm_module_invoke_all_array('mm_config_alter', array(&$form, $settings));

    if ($form) {
      return parent::buildForm($form, $form_state);
    }

    $form['msg'] = array('#markup' => $this->t('You are not allowed to change any of the settings.'));
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    foreach ($form_state->getValue('comments-readable_labels') as $index => $row) {
      if (empty($row['perm']) || empty($row['desc'])) {
        $form_state->unsetValue(['comments-readable_labels', $index]);
      }
    }

    foreach (explode(',', $form_state->getValue('vgroup-errors_email')) as $email) {
      if ($email && !\Drupal::service('email.validator')->isValid(trim($email))) {
        $form_state->setErrorByName('vgroup-errors_email', $this->t('One or more email addresses were not in the correct format.'));
      }
    }

    $form_state->setValue(['sitemap-exclude_list'], preg_split('{/*\s*[\r\n]+\s*/*}', trim($form_state->getValue('sitemap-exclude_list'), " \r\n/"), -1, PREG_SPLIT_NO_EMPTY));
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $settings = $this->config('monster_menus.settings');
    // Look for existing settings with names matching the $form_state data
    // set by the user and set their values.
    foreach ($form_state->getValues() as $name => $value) {
      $name = str_replace('-', '.', $name);
      if (!is_null($settings->get($name))) {
        $settings->set($name, $value);
      }
    }
    $settings->save();
    // Re-fetch custom permissions to potentially add new permissions to list.
    $this->permissionHandler->getPermissions();

    $state = \Drupal::state();
    // If comments.finegrain_readability has never been set before, create the
    // default settings using the current 'access comments' setting.
    if ($form_state->getValue('comments-finegrain_readability') && !$state->get('monster_menus.finegrain_comment_readability_ever_set', FALSE)) {
      $state->set('monster_menus.finegrain_comment_readability_ever_set', TRUE);
      foreach (array_keys(user_roles(FALSE, 'access comments')) as $rid) {
        user_role_grant_permissions($rid, [Constants::MM_COMMENT_READABILITY_DEFAULT]);
      }
      foreach (array_keys(Role::loadMultiple()) as $rid) {
        user_role_grant_permissions($rid, ['access comments']);
      }

      \Drupal::messenger()->addStatus($this->t('Because you enabled the <em>Control comment readability at the node level</em> setting for the first time, the <em>access comments</em> permission has been enabled for all roles. This is necessary in order for this feature to work.'));
      \Drupal::messenger()->addStatus($this->t('You should now go to <a href=":url">Permissions</a> to set the roles for each permission you just created.', array(':url' => Url::fromRoute('user.admin_permissions', [], ['fragment' => 'module-monster_menus'])->toString())));
    }

    parent::submitForm($form, $form_state);
  }

}
