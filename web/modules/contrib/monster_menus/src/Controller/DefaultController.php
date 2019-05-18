<?php /**
 * @file
 * Contains \Drupal\monster_menus\Controller\DefaultController.
 */

namespace Drupal\monster_menus\Controller;

use Drupal\Component\Utility\Html;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Database\Connection;
use Drupal\Core\Database\Query\Condition;
use Drupal\Core\Link;
use Drupal\Core\Render\Element\Form;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;
use Drupal\monster_menus\Constants;
use Drupal\monster_menus\Entity\MMTree;
use Drupal\monster_menus\Form\SearchReplaceForm;
use Drupal\monster_menus\GetTreeIterator\SitemapDumpIter;
use Drupal\monster_menus\Plugin\MMTreeBrowserDisplay\Fallback;
use Drupal\monster_menus\Plugin\MMTreeBrowserDisplay\Groups;
use Drupal\monster_menus\Plugin\MMTreeBrowserDisplay\Users;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;
use Drupal\node\NodeInterface;
use Drupal\user\Entity\User;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Default controller for the monster_menus module.
 */
class DefaultController extends ControllerBase {

  /**
   * The database connection.
   *
   * @var Connection
   */
  protected $database;

  /**
   * The service container.
   *
   * @var ContainerInterface
   */
  protected $container;

  /**
   * Constructs a DefaultController object.
   *
   * @param ContainerInterface $container
   *   The service container.
   * @param Connection $database
   *   The database connection.
   */
  public function __construct(ContainerInterface $container, Connection $database) {
    $this->container = $container;
    $this->database = $database;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container,
      $container->get('database')
    );
  }

  public static function menuAccessCreateHomepage(AccountInterface $account) {
    $user = \Drupal::currentUser();
    return AccessResult::allowedIf(mm_get_setting('user_homepages.enable') &&
      $account->isAuthenticated() &&
      ($user->hasPermission('administer all users') || $account->id() == $user->id()));
  }

  public static function getNodeTypeLabel($type) {
    static $cache;

    if (!isset($cache['type'])) {
      $node = Node::create(['type' => $type]);
      $cache['type'] = node_get_type_label($node);
    }
    return $cache['type'];
  }

  /**
   * Create a user's home directory in the MM tree.
   *
   * @param User $user
   *   The user object describing the account being added
   * @return RedirectResponse
   */
  public function createHomepage(User $user) {
    return mm_content_create_homepage($user);
  }

  public static function menuAccessListUserHomepages() {
    return AccessResult::allowedIf(mm_get_setting('user_homepages.virtual'));
  }

  /**
   * List user homepages starting with a certain letter.
   *
   * @param int $mmtid
   *   The pseudo-MMTID (integer < 0) of the page to show
   * @param Request $request
   *   Request object
   * @return array
   *   Render array for the page
   */
  public function listUserHomepages($mmtid, Request $request) {
    $mm_tree = MMTree::create(['mmtid' => $mmtid]);
    $view = MMTreeViewController::create($this->container);
    return $view->view($mm_tree, $request);
  }

  /**
   * Retrieve a string of autocomplete suggestions for existing users
   */
  public function autocomplete(Request $request, $want_username, $misc = NULL) {
    $string = $request->query->get('q');
    $limit = 15;
    $min_string = 2;

    $matches = [];
    $too_short = [['value' => '', 'label' => $this->t('Please type some more characters')]];

    $string = trim($string);
    if (!empty($string)) {
      $result = NULL;
      $hook = mm_module_implements('mm_autocomplete_alter');
      if ($hook) {
        $function = $hook[0] . '_mm_autocomplete_alter';
        $result = $function($string, $limit, $min_string, $misc);
        if (empty($result)) {
          $matches = $too_short;
        }
      }
      elseif (mb_strlen($string) >= $min_string) {
        // Consider Anonymous and Administrator first
        $startswith = $contains = '';
        for ($i = 0; $i <= 1; $i++) {
          $name = mm_content_uid2name($i);
          if (($pos = stristr($name, $string)) !== FALSE) {
            $stmt = "(SELECT $i AS uid, '' AS name, '' AS pref_fml, '' AS pref_lfm, '$name' AS lastname, '' AS firstname, '' AS middlename) UNION ";
            if (!$pos) {
              $startswith .= $stmt;
            }
            else {
              $contains .= $stmt;
            }
          }
        }
        $status_limit = $this->currentUser()->hasPermission('administer all users') ? '' : ' status = 1 AND';
        $result = $this->database->query('SELECT * FROM (' . $startswith . $contains . "(SELECT uid, name, '' AS pref_fml, '' AS pref_lfm, '' AS lastname, '' AS firstname, '' AS middlename " . "FROM {users_field_data} WHERE$status_limit uid > 1 AND name = :name_exact " . 'ORDER BY name) UNION ' . "(SELECT uid, name, '', '', '', '', '' " . "FROM {users_field_data} WHERE$status_limit uid > 1 AND name LIKE :name_start " . 'ORDER BY name) UNION ' . "(SELECT uid, name, '', '', '', '', '' " . "FROM {users_field_data} WHERE$status_limit uid > 1 AND name LIKE :name_any " . 'ORDER BY name)) x ' . 'LIMIT ' . intval($limit + 1), [
          ':name_exact' => $string,
          ':name_start' => $string . '%',
          ':name_any' => '%_' . $string . '%',
        ]);
      }
      else {
        $matches = $too_short;
      }

      if (!empty($result)) {
        foreach ($result as $usr) {
          if (count($matches) == $limit) {
            $matches[] = ['value' => '', 'label' => '...'];
            break;
          }
          else {
            $name = Html::escape(mm_content_uid2name($usr->uid, 'lfmu'));
            if (!$want_username) {
              $matches[] = ['value' => $usr->uid . '-' . $name, 'label' => $name];
            }
            elseif ($usr->name) {
              $matches[] = ['value' => $usr->name, 'label' => $name];
            }
          }
        }
      }
    }

    return new JsonResponse($matches);
  }

  public function menuAccessSitemap() {
    return AccessResult::allowedIf(mm_get_setting('sitemap.max_level') >= 0);
  }

  public function saveSitemap() {
    $max_level = mm_get_setting('sitemap.max_level');
    if ($max_level >= 0) {
      $iter = new SitemapDumpIter($max_level);
      // Use the anonymous user, so permissions tests are valid.
      $params = [
        Constants::MM_GET_TREE_FILTER_NORMAL => TRUE,
        Constants::MM_GET_TREE_FILTER_USERS => TRUE,
        Constants::MM_GET_TREE_ITERATOR => $iter,
        Constants::MM_GET_TREE_RETURN_BLOCK => TRUE,
        Constants::MM_GET_TREE_RETURN_PERMS => TRUE,
        Constants::MM_GET_TREE_USER => User::getAnonymousUser(),
      ];
      mm_content_get_tree(1, $params);
      $iter->finish();
    }
  }

  public function showSitemap() {
    return new BinaryFileResponse('public://sitemap.xml', 200, ['Content-Type' => 'text/xml']);
  }

  static public function menuAccessShowGroup(MMTree $mm_tree, AccountInterface $account) {
    $perms = mm_content_user_can($mm_tree->id(), NULL, $account);
    return AccessResult::allowedIf($account->isAuthenticated() && $perms[Constants::MM_PERMS_READ] && $perms[Constants::MM_PERMS_IS_GROUP]);
  }

  public function showGroup(MMTree $mm_tree) {
    $headers = _mm_ui_userlist_get_headers();
    array_pop($headers);

    foreach ($headers as $key => $value) {
      $headers[$key] = [
        'data' => $value,
        'attributes' => empty($value) ? ['class' => ['no-sort']] : [],
      ];
    }
    $body = [[
      '#type' => 'table',
      '#attributes' => ['class' => ['tablesorter']],
      '#id' => 'mm-user-datatable-members-display',
      '#header' => $headers,
      [
        '#attributes' => ['class' => ['dataTables_empty']],
        ['#wrapper_attributes' => ['colspan' => count($headers)], '#markup' => $this->t('Loading data from server')],
      ],
      '#cache' => ['max-age' => -1],
    ]];

    mm_static($body, 'show_group', $mm_tree->id(), array_fill(0, count($headers), NULL));
    mm_add_library($body, 'dataTables');
    return mm_page_wrapper($this->t('Group Members'), $body, ['class' => ['mm-dialog']]);
  }

  static public function menuAccessUserCan(MMTree $mm_tree, $mode = '', AccountInterface $account = NULL) {
    return AccessResult::allowedIf(mm_content_user_can($mm_tree->id(), $mode, $account));
  }

  public function renderNodesOnPage(MMTree $mm_tree, $per_page) {
    $item = mm_content_get($mm_tree->id(), Constants::MM_GET_ARCHIVE);
    $perms = mm_content_user_can($mm_tree->id());
    $no_read = $ok = 0;
    $output = [];
    // set $_GET['page'] to control the page number
    if (_mm_render_nodes_on_page($item, $perms, (int) $per_page, [], FALSE, $output, $ok, $no_read, $pager_elem, $archive_tree, $archive_date_int, $rss_link)) {
      return $output;
    }
    return [];
  }

  static public function menuAccessAdd(AccountInterface $account, MMTree $mm_tree = NULL) {
    if ($mm_tree) {
      $perms = mm_content_user_can($mm_tree->id(), '', $account);
      if ($perms[Constants::MM_PERMS_APPLY] && !$perms[Constants::MM_PERMS_IS_GROUP] && !$perms[Constants::MM_PERMS_IS_RECYCLED] && !mm_content_is_archive($mm_tree->id())) {
        // Make sure the user can create at least one type of content here
        $allowed_node_types = mm_content_resolve_cascaded_setting('allowed_node_types', $mm_tree->id(), $types_at, $types_parent);
        $types = NodeType::loadMultiple();
        if (!$account->hasPermission('administer all menus')) {
          $types = array_intersect_key($types, array_flip($allowed_node_types));
        }
        /** @var NodeType $type */
        foreach ($types as $type) {
          if (mm_node_access_create($type->id(), $account)) {
            return AccessResult::allowed();
          }
        }
      }
    }
    return AccessResult::forbidden();
  }

  public function addNode(MMTree $mm_tree) {
    return $this->nodeAdd($mm_tree->id(), '');
  }

  public function addNodeWithType(MMTree $mm_tree, $node_type) {
    return $this->nodeAdd($mm_tree->id(), $node_type);
  }

  public function addNodeWithTypeGetTitle($node_type) {
    return $this->t('Add %type Content', ['%type' => static::getNodeTypeLabel($node_type)]);
  }

  /**
   * Present a node submission form or a set of links to such forms.
   *
   * This code is lifted from node_add() in node.module and modified to use
   * callback arguments
   *
   * @param int $mmtid
   *   MM Tree ID to add the node to
   * @param string $type
   *   Type of node to create(optional)
   * @return string|array
   *   The HTML code for the results
   */
  private function nodeAdd($mmtid, $type = '') {
    $user = $this->currentUser();

    if (!mm_content_user_can($mmtid, Constants::MM_PERMS_APPLY)) {
      return t('You are not allowed to assign the page %cat to content.', array('%cat' => mm_content_get_name($mmtid)));
    }
    $allowed_node_types = mm_content_resolve_cascaded_setting('allowed_node_types', $mmtid, $types_at, $types_parent);

    // If a node type has been specified, validate its existence.
    /** @var NodeType[] $types */
    $types = NodeType::loadMultiple();
    $type = isset($type) ? str_replace('-', '_', $type) : NULL;
    if (isset($types[$type]) && mm_node_access_create($type) && ($this->currentUser()->hasPermission('administer all menus') || in_array($type, $allowed_node_types))) {
      $node = Node::create(['type' => $type])
        ->setOwnerId($user->id());
      return $this->entityFormBuilder()->getForm($node);
    }
    else {
      // If no (valid) node type has been provided, display a node type overview.
      $hidden_types = mm_get_node_info(Constants::MM_NODE_INFO_ADD_HIDDEN);
      $admin_only_item = $item = array();
      foreach ($types as $type) {
        $type_url_str = $type->id();
        $direct_link = Url::fromRoute('monster_menus.add_node_with_type', ['mm_tree' => $mmtid, 'node_type' => $type_url_str]);
        if (mm_node_access_create($type_url_str) && !in_array($type_url_str, $hidden_types) && in_array($type_url_str, $allowed_node_types)) {
          $item[$type->label()] = $type;
          $sole_direct_link = $direct_link;
        }
        elseif ($this->currentUser()->hasPermission('administer all menus') && !in_array($type_url_str, $allowed_node_types)) {
          $admin_only_item[$type->label()] = $type;
        }
      }

      if ($item || $admin_only_item) {
        if (count($item) == 1 && !$this->currentUser()->hasPermission('administer all menus')) {
          return new RedirectResponse($sole_direct_link->toString());
        }
        uksort($item, 'strnatcasecmp');
        uksort($admin_only_item, 'strnatcasecmp');
        $output = '';

        if ($mmtid && mm_get_setting('pages.hide_empty_pages_in_menu')) {
          $or = new Condition('OR');
          $select = $this->database->select('mm_node2tree', 't');
          $select->join('node', 'n', 't.nid = n.nid');
          $select->condition('t.mmtid', $mmtid)
            ->condition($or
              ->condition('n.status', 1)
              ->condition('n.uid', $user->id())
            );
          $count = $select->countQuery()->execute()->fetchField();
          if (!$count) {
            \Drupal::messenger()->addWarning(t('Until you have added some content to this page, it will not appear in the menus for anyone who does not also have the ability to add content.'));
          }
        }
        $admin_ok = $this->currentUser()->hasPermission('administer all menus') && $admin_only_item;
        $output = $admin_ok && !$item ? array() : array(
          array('#markup' => $output . t('Choose the type of content to create using this page:')),
          array(
            '#theme' => 'node_add_list',
            '#content' => $item,
          ),
        );
        if ($admin_ok) {
          $output[] = array(
            array('#markup' => '<br /><div class="mm-admin-types">' . t('The following content type(s) will only be displayed to admin users:')),
            array(
              '#theme' => 'node_add_list',
              '#content' => $admin_only_item,
            ),
            array('#markup' => '</div>'),
          );
        }
      }
      else {
        $output = [['#markup' => t('You are not allowed to create content here.')]];
      }

      $output['#title'] = t('Add content');
      return $output;
    }
  }

  static public function menuAccessPageSettings(MMTree $mm_tree, AccountInterface $user) {
    $perms = mm_content_user_can($mm_tree->id(), '', $user);
    return AccessResult::allowedIf($perms[Constants::MM_PERMS_WRITE] || ($perms[Constants::MM_PERMS_SUB] || $perms[Constants::MM_PERMS_APPLY] && (!isset($user->user_portal_mmtid) || $mm_tree->id() != $user->user_portal_mmtid) && (!$perms[Constants::MM_PERMS_IS_GROUP] || $perms[Constants::MM_PERMS_READ]) && !$perms[Constants::MM_PERMS_IS_RECYCLED]));
  }

  /**
   * Perform an operation on a tree entry.
   *
   * @param MMTree $mm_tree
   *   MMTree of entry to modify.
   * @param string $op
   *   Operation to perform
   * @throws NotFoundHttpException|AccessDeniedHttpException
   *   NotFoundHttpException if the entry does not exist or if the entry is a
   *   bin that cannot be emptied by the current user; AccessDeniedHttpException
   *   if the current user does not have permission to perform the requested
   *   action.
   * @return string|array
   *   The HTML code for the results.
   */
  public function handlePageSettings(MMTree $mm_tree, $op = '') {
    $user = $this->currentUser();

    $perms = mm_content_user_can($mm_tree->id());
    if (empty($op)) {
      if ($perms[Constants::MM_PERMS_IS_RECYCLE_BIN]) {
        $op = $this->currentUser()->hasPermission('delete permanently') ? 'empty' : 'search';
      }
      elseif ($perms[Constants::MM_PERMS_WRITE]) {
        $op = 'edit';
      }
      else {
        \Drupal::messenger()->addStatus($this->t('You do not have permission to change the settings. Please choose another option from the menu.'));
        return ' ';
      }

      if ($op != 'edit') {
        return mm_goto(Url::fromRoute("monster_menus.page_settings_$op", ['mm_tree' => $mm_tree->id()]));
      }
    }

    $params = [
      Constants::MM_GET_TREE_DEPTH => $op == 'delete' || $op == 'empty' ? -1 : 0,
      Constants::MM_GET_TREE_RETURN_FLAGS => TRUE,
      Constants::MM_GET_TREE_RETURN_PERMS => TRUE,
      Constants::MM_GET_TREE_RETURN_MTIME => TRUE,
    ];
    $tree = mm_content_get_tree($mm_tree->id(), $params);

    if (!$tree) {
      throw new NotFoundHttpException();
    }

    if (!$tree[0]->perms[Constants::MM_PERMS_WRITE] && !$tree[0]->perms[Constants::MM_PERMS_SUB] && !$tree[0]->perms[Constants::MM_PERMS_APPLY]) {
      throw new AccessDeniedHttpException();
    }

    $x = mm_ui_strings($is_group = $tree[0]->is_group);
    $x['%name'] = mm_content_get_name($tree[0]);

    switch ($op) {
      case 'edit':
        _mm_ui_is_user_home($tree[0]);
        /** @noinspection PhpMethodParametersCountMismatchInspection */
        $form = \Drupal::formBuilder()->getForm('\Drupal\monster_menus\Form\EditContentForm', $tree[0], $mm_tree->id(), $is_group);
        $form['#title'] = $this->t('Settings for %name', $x);
        return $form;

      case 'copymove':
        _mm_ui_is_user_home($tree[0]);
        /** @noinspection PhpMethodParametersCountMismatchInspection */
        $form = \Drupal::formBuilder()->getForm('\Drupal\monster_menus\Form\CopyMoveContentForm', $tree[0]);
        $form['#title'] = $this->t('Copy/move %name', $x);
        return $form;

      case 'restore':
        $next_par = FALSE;
        foreach (array_reverse(mm_content_get_parents($tree[0]->mmtid)) as $t) {
          if ($t < 0) {
            // virtual user dir
            continue;
          }
          elseif ($next_par) {
            $par = mm_content_get($t);
            $pperms = mm_content_user_can($t);
            break;
          }
          elseif (mm_content_user_can($t, Constants::MM_PERMS_IS_RECYCLE_BIN)) {
            $next_par = TRUE;
          }
        }

        if (!empty($par) && !$pperms[Constants::MM_PERMS_SUB]) {
          $x['@name'] = mm_content_get_name($par);
          $x[':link'] = mm_content_get_mmtid_url($par->mmtid);
          return $this->t('You cannot restore this @thing because you do not have permission to add to the parent @thing, <a href=":link">@name</a>. You may be able to copy or move this @thing to another location, however.', $x);
        }
        elseif (!$pperms[Constants::MM_PERMS_SUB]) {
          return $this->t('You cannot restore this @thing because you do not have permission to add to the parent @thing. You may be able to copy or move this @thing to another location, however.', $x);
        }
        module_load_include('inc', 'monster_menus', 'mm_ui_content_restore');
        /** @noinspection PhpMethodParametersCountMismatchInspection */
        return \Drupal::formBuilder()->getForm('\Drupal\monster_menus\Form\RestoreContentConfirmForm', $tree[0], $par->mmtid, $x);

      case 'empty':
        if (!mm_content_user_can_recycle($mm_tree->id(), Constants::MM_PERMS_IS_EMPTYABLE)) {
          throw new NotFoundHttpException();
        }
      // intentionally fall through to 'delete'

      case 'delete':
        return $this->contentDelete($tree, $x, $is_group);

      case 'sub':
        $sub = clone $tree[0];
        $sub->name = $is_group ? $this->t('(new group)') : ($mm_tree->id() != 1 ? $this->t('(new page)') : $this->t('(new site)'));
        $sub->alias = '';
        $sub->uid = $user->id();
        $sub->theme = '';
        $sub->hidden = FALSE;
        $sub->flags = [];
        foreach (\Drupal::moduleHandler()->invokeAll('mm_tree_flags') as $flag => $elem) {
          if (isset($elem['#flag_inherit']) && $elem['#flag_inherit'] === TRUE && isset($tree[0]->flags[$flag])) {
            $sub->flags[$flag] = $tree[0]->flags[$flag];
          }
        }

        /** @noinspection PhpMethodParametersCountMismatchInspection */
        $form = \Drupal::formBuilder()->getForm('\Drupal\monster_menus\Form\EditContentForm', $sub, $mm_tree->id(), $is_group, TRUE);
        $form['#title'] = $mm_tree->id() != 1 ? $this->t('Create a new @subthing of %name', $x) : $this->t('Create a new site', $x);
        return $form;

      case 'search':
        return SearchReplaceForm::getForm($mm_tree->id());

      case 'search result':
        return SearchReplaceForm::getResults();

      case 'search result csv':
        return SearchReplaceForm::getResultsCSV();

      default:
        throw new NotFoundHttpException();
    }
  }

  private function contentDelete($tree, $x, $is_group) {
    if (isset($tree[0]->flags['limit_delete']) && !$this->currentUser()->hasPermission('administer all menus')) {
      throw new AccessDeniedHttpException();
    }

    if ($tree[0]->mmtid == mm_home_mmtid()) {
      return ['#markup' => $this->t('The @thing %name cannot be deleted.', $x)];
    }

    $del_perm = !mm_content_recycle_enabled() ||
      $tree[0]->perms[Constants::MM_PERMS_IS_RECYCLED] || $tree[0]->perms[Constants::MM_PERMS_IS_GROUP];

    $mmtids = array();
    $kids = 0;
    $x['%sub'] = '';
    foreach ($tree as $t) {
      if (!$t->perms[Constants::MM_PERMS_IS_RECYCLE_BIN]) {
        if ($t != $tree[0]) $kids++;
        if ($t->level == 1 && $x['%sub'] == '') $x['%sub'] = mm_content_get_name($t);
      }

      if ($del_perm && !$t->perms[Constants::MM_PERMS_WRITE] && !$t->perms[Constants::MM_PERMS_IS_RECYCLE_BIN] || isset($t->flags['limit_delete']) && !$this->currentUser()->hasPermission('administer all menus')) {
        $x['%name'] = mm_content_get_name($t);
        return ['#markup' => $this->t('You cannot delete this @thing because you do not have permission to delete the @subthing %name', $x)];
      }

      $mmtids[] = $t->mmtid;
    }
    $x['@num'] = $kids;

    $nodes = $excl_nodes = array();
    if (!$is_group) {
      $nodes = mm_content_get_nids_by_mmtid($mmtids);
      $excl_nodes = mm_content_get_nids_by_mmtid($mmtids, 0, TRUE);
    }

    /** @noinspection PhpMethodParametersCountMismatchInspection */
    return \Drupal::formBuilder()->getForm('\Drupal\monster_menus\Form\DeleteContentConfirmForm', $tree, $x, $is_group, $mmtids, $del_perm, $kids, $nodes, $excl_nodes);
  }

  static public function menuAccessEmptyBin(MMTree $mm_tree, AccountInterface $account) {
    $perms = mm_content_user_can($mm_tree->id(), NULL, $account);
    return AccessResult::allowedIf($perms[Constants::MM_PERMS_IS_RECYCLE_BIN] && mm_content_user_can_recycle($mm_tree->id(), Constants::MM_PERMS_IS_EMPTYABLE));
  }

  static public function menuAccessEdit(MMTree $mm_tree, AccountInterface $account) {
    $perms = mm_content_user_can($mm_tree->id(), NULL, $account);
    return AccessResult::allowedIf(!$perms[Constants::MM_PERMS_IS_RECYCLE_BIN] && $perms[Constants::MM_PERMS_WRITE]);
  }

  static public function menuAccessCopy(MMTree $mm_tree, AccountInterface $account) {
    $perms = mm_content_user_can($mm_tree->id(), NULL, $account);
    return AccessResult::allowedIf(!$perms[Constants::MM_PERMS_IS_RECYCLE_BIN] && ($perms[Constants::MM_PERMS_WRITE] || $perms[Constants::MM_PERMS_SUB] || $perms[Constants::MM_PERMS_APPLY]) && !mm_content_is_archive($mm_tree->id()) && \Drupal::currentUser()->hasPermission('use tree browser'));
  }

  static public function menuAccessRestore(MMTree $mm_tree, AccountInterface $account) {
    $parent = mm_content_get_parent($mm_tree->id());
    $perms = mm_content_user_can($mm_tree->id(), NULL, $account);
    return AccessResult::allowedIf($perms[Constants::MM_PERMS_WRITE] && $perms[Constants::MM_PERMS_IS_RECYCLED] && mm_content_user_can($parent, Constants::MM_PERMS_IS_RECYCLE_BIN, $account));
  }

  static public function menuGetTitleSettingsDelete(MMTree $mm_tree) {
    $perms = mm_content_user_can($mm_tree->id());
    return $perms[Constants::MM_PERMS_IS_RECYCLED] || $perms[Constants::MM_PERMS_IS_GROUP] ? 'Delete permanently' : 'Delete';
  }

  static public function menuAccessDelete(MMTree $mm_tree, AccountInterface $account) {
    $tree = mm_content_get_tree($mm_tree->id(), [
      Constants::MM_GET_TREE_RETURN_PERMS => TRUE,
      Constants::MM_GET_TREE_RETURN_FLAGS => TRUE,
      Constants::MM_GET_TREE_DEPTH => 0,
    ]);
    return AccessResult::allowedIf(isset($tree[0]) && $tree[0]->perms[Constants::MM_PERMS_WRITE] &&
      ($account->hasPermission('administer all menus') || !isset($tree[0]->flags['limit_delete'])) &&
      !$tree[0]->perms[Constants::MM_PERMS_IS_RECYCLE_BIN] &&
      (!$tree[0]->perms[Constants::MM_PERMS_IS_RECYCLED] || $account->hasPermission('delete permanently')));
  }

  static public function menuGetTitleSettingsSub(MMTree $mm_tree) {
    return t('Add @subthing', mm_ui_strings(mm_content_is_group($mm_tree->id())));
  }

  static public function menuAccessSub(MMTree $mm_tree, AccountInterface $account) {
    $perms = mm_content_user_can($mm_tree->id(), NULL, $account);
    return AccessResult::allowedIf($perms[Constants::MM_PERMS_SUB] && !mm_content_is_node_content_block($mm_tree->id()) && $perms[Constants::MM_PERMS_SUB] && !$perms[Constants::MM_PERMS_IS_RECYCLED]);
  }

  public function showRevisions(MMTree $mm_tree) {
    $access_modes = [
      Constants::MM_PERMS_WRITE => $this->t('delete/edit'),
      Constants::MM_PERMS_APPLY => $this->t('add content'),
      Constants::MM_PERMS_SUB => $this->t('add sub-pages'),
      Constants::MM_PERMS_READ => $this->t('read'),
    ];
    $is_group = mm_content_is_group($mm_tree->id());
    if ($is_group) {
      $access_modes[Constants::MM_PERMS_APPLY] = $this->t('apply');
      $access_modes[Constants::MM_PERMS_SUB] = $this->t('add sub-groups');
      $access_modes[Constants::MM_PERMS_READ] = $this->t('see members');
    }

    $yesno = function ($data) {
      return empty($data) ? $this->t('no') : $this->t('yes');
    };
    $from_list = function ($modes, $data) {
      if (is_null($data) || $data === '') {
        return '';
      }
      if (isset($modes[$data])) {
        return $modes[$data];
      }
      return $this->t('Unknown value "@value"', ['@value' => $data]);
    };
    $header = [
      [
        'data' => $this->t('Date'),
        '#field' => 'mtime',
        '#fmt' => function ($data) {
          return $data ? mm_format_date($data, 'short') : t('(Unknown)');
        },
        'field' => 'mtime',
        'sort' => 'desc',
      ],
      [
        'data' => $this->t('Modified By'),
        '#field' => 'muid',
        '#fmt' => 'mm_content_uid2name',
      ],
      [
        'data' => $is_group ? $this->t('Group Name') : $this->t('Page Name'),
        '#field' => 'name',
        '#fmt' => ['\Drupal\Component\Utility\Xss', 'filter'],
      ],
      [
        'data' => $this->t('Page URL'),
        '#field' => 'alias',
        '#fmt' => ['\Drupal\Component\Utility\Xss', 'filter'],
      ],
      [
        'data' => $this->t('Parent'),
        '#field' => 'parent',
        '#fmt' => function ($data) {
          return $data ? Link::fromTextAndUrl(mm_content_get_name($data), Url::fromRoute('entity.mm_tree.version_history', ['mm_tree' => $data]))->toString() : '';
        },
      ],
      [
        'data' => $this->t('All Users'),
        '#field' => 'default_mode',
        '#fmt' => function ($data) use ($from_list, $access_modes) {
          $out = array();
          $data = explode(',', $data);
          sort($data);
          foreach ($data as $mode) {
            $out[] = $from_list($access_modes, $mode);
          }
          return empty($out) ? $this->t('(none)') : implode(', ', $out);
        },
      ],
      [
        'data' => $this->t('Owner'),
        '#field' => 'uid',
        '#fmt' => 'mm_content_uid2name',
      ],
      [
        'data' => $this->t('Hidden'),
        '#field' => 'hidden',
        '#fmt' => $yesno,
      ],
      ['data' => $this->t('Theme'), '#field' => 'theme', '#fmt' => ['\Drupal\Component\Utility\Xss', 'filter']],
      [
        'data' => $this->t('Attributions'),
        '#field' => 'node_info',
        '#fmt' => function ($data) use ($from_list) {
          static $modes;
          if (!$modes) {
            $dummy = [];
            $modes = _mm_ui_node_info_values($dummy);
          }
          return $from_list($modes, $data);
        },
      ],
      [
        'data' => $this->t('Summaries'),
        '#field' => 'previews',
        '#fmt' => $yesno,
      ],
      [
        'data' => $this->t('Comments'),
        '#field' => 'comment',
        '#fmt' => function ($data) use ($from_list) {
          static $modes;
          if (!$modes) {
            $modes = _mm_ui_comment_write_setting_values();
          }
          return $from_list($modes, $data);
        },
      ],
      [
        'data' => $this->t('RSS'),
        '#field' => 'rss',
        '#fmt' => $yesno,
      ],
      ['data' => $this->t('Hover'), '#field' => 'hover', '#fmt' => ['\Drupal\Component\Utility\Xss', 'filter']],
    ];
    if ($is_group) {
      $header = array_intersect_key($header, array(0 => 0, 1 => 0, 2 => 0, 4 => 0, 5 => 0, 6 => 0, 13 => 0));
    }

    $rows = [];
    $saved = [];
    $query = $this->database->select('mm_tree_revision', 'r')
      ->fields('r')
      ->condition('mmtid', $mm_tree->id())
      ->extend('\Drupal\Core\Database\Query\TableSortExtender')
      ->orderByHeader($header)
      ->extend('\Drupal\Core\Database\Query\PagerSelectExtender')->limit(20)
      ->execute();
    foreach ($query as $result) {
      $row = [];
      foreach ($header as $key => &$col) {
        if (isset($saved[$key])) {
          list($_field, $func) = $saved[$key];
        }
        else {
          $_field = $col['#field'];
          $func = $col['#fmt'];
          $saved[$key] = [$_field, $func];
          unset($col['#field'], $col['#fmt']);
        }
        $data = $result->{$_field} = $func($result->{$_field});
        $class = 'mm-revisions-changed';
        if (!isset($col['field']) && !empty($last)) {
          $old = $last->{$_field};
          if ($old === $data) {
            $class = 'mm-revisions-same';
          }
          else {
            if (is_null($data) || $data === '') {
              $class = 'mm-revisions-deleted';
              $data = $old;
            }
            else {
              if (is_null($old) || $old === '') {
                $class = 'mm-revisions-added';
              }
            }
          }
        }
        $row[] = ['data' => $data, 'class' => [$class]];
      }
      $last = $result;
      $rows[] = $row;
    }

    $build['revisions'] = [
      '#theme' => 'table',
      '#header' => $header,
      '#rows' => $rows,
      '#attached' => ['library' => ['monster_menus/mm_css']],
    ];
    $build['pager'] = ['#type' => 'pager'];
    $build['#cache']['tags'] = $mm_tree->getCacheTags();

    return $build;
  }

  static public function menuAccessSolver(MMTree $mm_tree, AccountInterface $account = NULL) {
    return AccessResult::allowedIf(static::menuAccessSolverByMMTID($mm_tree->id(), $account));
  }

  static public function menuAccessSolverByMMTID($mmtid, AccountInterface $account = NULL) {
    if (empty($account)) {
      $account = \Drupal::currentUser();
    }
    return $mmtid && $account->hasPermission('use permissions solver') && $account->hasPermission('access user profiles') && mm_content_user_can($mmtid, Constants::MM_PERMS_APPLY, $account);
  }

  public function showSolver(MMTree $mm_tree, User $user = NULL) {
    $result = ['#markup' => $this->t('<p>Unknown user</p>')];

    if ($user) {
      module_load_include('inc', 'monster_menus', 'mm_ui_solver');
      $result = mm_ui_solver_table($mm_tree->id(), $user);
    }
    return new AjaxResponse((string) \Drupal::service('renderer')->renderRoot($result));
  }

  public function exportGroupAsCSV(MMTree $mm_tree) {
    $headers = [
      'Content-type' => 'text/csv',
      'Content-Disposition' => 'attachment; filename=mm_group_' . $mm_tree->id() . '.csv',
      'Pragma' => 'no-cache',
      'Expires' => '0',
    ];
    $output = fopen('php://output', 'w');
    ob_start();
    foreach (mm_content_get_users_in_group($mm_tree->id(), NULL, FALSE, 0, FALSE) as $uid => $name) {
      $query = $this->database->select('users_field_data', 'u')->fields('u', ['name']);
      $username = $query->condition('uid', $uid)->execute()->fetchField();
      fputcsv($output, [$username, $name]);
    }
    fclose($output);
    $response = ob_get_clean();
    return new Response($response, 200, $headers);
  }

  private function getGroupToken($_get = NULL) {
    if (empty($_get)) {
      $_get = \Drupal::request()->query;
    }
    if (empty($form_token = $_get->get('token'))) {
      throw new AccessDeniedHttpException();
    }
    return $form_token;
  }

  /**
   * Get a list of users in a group, as JSON.
   *
   * @param MMTree $mm_tree
   *   The MMTree object of the group that is being queried
   * @param string $element
   *   The name of the triggering form element
   * @return JsonResponse
   */
  public function getGroupUsersJson(MMTree $mm_tree, $element) {
    $clean_element = str_replace('-', '_', $element);
    $is_temp = $clean_element != 'mm_user_datatable_members_display';
    $_get = \Drupal::request()->query;
    $form_token = $is_temp ? $this->getGroupToken($_get) : '';
    $mmtid = $mm_tree->id();
    $users_array = mm_module_invoke_all_array('mm_large_group_get_users', [
      'mmtid' => $mmtid,
      'element' => $clean_element,
      'form_token' => $form_token,
    ]);
    if (empty($users_array)) {
      $query = $this->database->select('users_field_data', 'u')
        ->fields('u', ['uid', 'name']);
      if ($is_temp) {
        $query->join('mm_group_temp', 'm', 'u.uid = m.uid');
        $query->condition('m.gid', $mmtid)
          ->condition('m.sessionid', session_id())
          ->condition('m.token', $form_token);
      }
      else {
        $query_virtual_group = $this->database->select('mm_group', 'm')
          ->fields('m', ['vgid']);
        $query_virtual_group->condition('m.gid', $mmtid)
          ->condition('m.vgid', 0, '<>');
        $query_virtual_group->groupBy('m.gid');
        $query_virtual_group->groupBy('m.vgid');
        $results = $query_virtual_group->execute();
        if (($vgid = $results->fetchField()) > 0) {
          $query->join('mm_virtual_group', 'm', 'u.uid = m.uid');
          $query->condition('m.vgid', $vgid);
        }
        else {
          $query->join('mm_group', 'm', 'u.uid = m.uid');
          $query->condition('m.gid', $mmtid);
        }
      }
      $sort_array = ['name'];
      $unfiltered_query = $query;
      if (!empty($temp = $_get->get('sSearch', ''))) {
        $query->condition('u.name', '%' . $temp . '%', 'LIKE');
      }
      if ($_get->get('iSortCol_0', FALSE) !== FALSE) {
        $cols = $cal = $_get->getInt('iSortingCols', 0);
        for ($i = 0; $i < $cols; $i++) {
          $direction = $_get->get('sSortDir_' . $i, '') === 'desc' ? 'DESC' : 'ASC';
          if (($temp = $_get->getInt('iSortCol_' . $i, -1)) >= 0) {
            $query->orderBy($sort_array[$temp], $direction);
          }
        }
      }

      $total_unfiltered_rows = $unfiltered_query->countQuery()->execute()->fetchField();
      $total_rows = $query->countQuery()->execute()->fetchField();
      if (($temp = $_get->getInt('iDisplayStart', FALSE)) !== FALSE) {
        $query->range($temp, $_get->getInt('iDisplayLength'));
      }
      $results = $query->execute();

      $users = [
        'sEcho' => $_get->getInt('sEcho'),
        'iTotalRecords' => $total_rows,
        'iTotalDisplayRecords' => $total_unfiltered_rows,
        'aaData' => [],
      ];
      foreach ($results as $item) {
        if (!$is_temp) {
          $users['aaData'][] = [$item->name];
        }
        elseif ($clean_element == 'members') {
          $users['aaData'][] = [
            $item->name,
            '<a href="Javascript:Drupal.mmGroupRemoveUser(' . $item->uid . ',\'' . $clean_element . '\')">' . $this->t('Delete') . '</a>',
          ];
        }
      }
    }
    else {
      $users = $users_array;
    }

    return mm_json_response($users);
  }

  /**
   * Delete a user from the editing form temporary table.
   *
   * @param MMTree $mm_tree
   *   The MMTree object of the group from which the user is being removed
   * @param User $user
   *   User object of the user to be deleted
   * @return JsonResponse
   */
  public function deleteGroupUser(MMTree $mm_tree, User $user) {
    $this->database->delete('mm_group_temp')
      ->condition('gid', $mm_tree->id())
      ->condition('uid', $user->id())
      ->condition('sessionid', session_id())
      ->condition('token', $this->getGroupToken())
      ->execute();
    return mm_json_response([]);
  }

  /**
   * Add one or more users to the editing form temporary table.
   *
   * @param MMTree $mm_tree
   *   The MMTree object of the group to which the user is being added
   * @param string $uids
   *   A comma-separated list of uids to be added
   * @return JsonResponse
   */
  public function addGroupUser(MMTree $mm_tree, $uids) {
    $form_token = $this->getGroupToken();
    foreach (explode(',', $uids) as $uid) {
      if (!empty($uid) && User::load($uid)) {
        try {
          $this->database->merge('mm_group_temp')
            ->keys([
              'gid' => $mm_tree->id(),
              'uid' => $uid,
              'sessionid' => session_id(),
              'token' => $form_token,
            ])
            ->fields([
              'expire' => mm_request_time() + 24 * 60 * 60,
            ])
            ->execute();
        }
        catch (\Exception $e) {
        }
      }
    }
    return mm_json_response([]);
  }

  /**
   * Allow the user to edit a node.
   *
   * @param NodeInterface $node
   *   Node to edit
   * @return RedirectResponse|Form|array
   *   The form array or a RedirectResponse
   */
  public function editNode(NodeInterface $node) {
    return $this->entityFormBuilder()->getForm($node);
  }

  public function editNodeGetTitle(NodeInterface $node) {
    return $this->t('Edit %title', ['%title' => $node->label()]);
  }

  static public function accessAnyAdmin(AccountInterface $account) {
    return AccessResult::allowedIf(
      $account->hasPermission('administer all groups') ||
      $account->hasPermission('administer all users') ||
      $account->hasPermission('administer all menus'));
  }

  /**
   * Display a list of links to popup tree browsers
   *
   * @return array
   *   The HTML code for the links
   */
  public function showAdminBrowseLinks() {
    $list = [
      (string) $this->t('Permission groups') => [
        Groups::BROWSER_MODE_ADMIN_GROUP,
        $this->currentUser()->hasPermission('administer all groups'),
        mm_content_groups_mmtid(),
      ],
      (string) $this->t('Top-level menus') => [
        Fallback::BROWSER_MODE_ADMIN_PAGE,
        $this->currentUser()->hasPermission('administer all menus'),
        mm_home_mmtid(),
      ],
      (string) $this->t('User menus') => [
        Users::BROWSER_MODE_ADMIN_USER,
        $this->currentUser()->hasPermission('administer all users'),
        mm_content_users_mmtid(),
      ],
      (string) $this->t('Entire tree') => [
        Fallback::BROWSER_MODE_ADMIN_PAGE,
        $this->accessAllAdmin(),
        1,
      ],
    ];

    $links = [];
    foreach ($list as $text => $item) {
      if ($item[1]) {
        $links[] = [
          'attributes' => ['title' => $text],
          'url' => Url::fromRoute('monster_menus.browser_load', [], ['query' => ['_path' => $item[2] . '-' . $item[0] . '-0--' . Constants::MM_PERMS_APPLY . '/' . $item[2]]]),
          'title' => $text,
        ];
      }
    }

    $arr = [
      '#theme' => 'links',
      '#links' => $links,
      '#attributes' => [],
    ];
    mm_static($arr, 'admin_browse');
    return $arr;
  }

  static public function accessAllAdmin(AccountInterface $account = NULL) {
    if (empty($account)) {
      $account = \Drupal::currentUser();
    }
    return AccessResult::allowedIf(
      $account->hasPermission('administer all groups') &&
      $account->hasPermission('administer all users') &&
      $account->hasPermission('administer all menus'));
  }

  /**
   * Print a CSV dump of the entire MM tree
   */
  function getTreeAsCSV($start = 1) {
    \Drupal::service('monster_menus.dump_csv')->dump($start);
  }

  public function updateVgroupView($mmtid = NULL) {
    mm_content_update_vgroup_view($mmtid);
    return ['#markup' => $this->t('All virtual groups have been marked for update during the next cron run.')];
  }

  public function regenerateVgroup() {
    mm_regenerate_vgroup();
    return ['#markup' => $this->t('All virtual groups have been regenerated.')];
  }

  public function validateSortIndex($fix = FALSE) {
    $result = \Drupal::service('monster_menus.validate_sort_index')->setOutputMode()->validate($fix);
    return is_array($result) ? $result : ['#markup' => $result];
  }

  public function findOrphanNodes(Request $request) {
    return \Drupal::service('monster_menus.check_orphan_nodes')->setOutputMode()->check($request->query->get('_fix'), 'table');
  }

  public function editSite() {
    return $this->handlePageSettings(MMTree::load(1), 'sub');
  }

  /**
   * Redirect to a specific page. This is used internally when the incoming URL
   * looks like /NUMBER or /mm/NUMBER, so that the browser gets the full path of
   * the page.
   *
   * @param Request $request
   *   The incoming request
   * @param int $mmtid
   *   ID of the entry
   * @return RedirectResponse
   */
  public function redirectToMMTID(Request $request, $mmtid) {
    return new RedirectResponse(mm_content_get_mmtid_url($mmtid, ['query' => $request->query->all()])->toString());
  }

  public function menuAccessNode(NodeInterface $node, AccountInterface $account) {
    return AccessResult::allowedIf($node->access('view', $account));
  }

  public function redirectToNode(Request $request, NodeInterface $node, $mode = NULL) {
    if ($mmtids = mm_content_get_by_nid($node->id())) {
      $params = ['mm_tree' => $mmtids[0], 'node' => $node->id()];
      $options = ['query' => $request->query->all()];
    }

    switch ($mode) {
      case 'edit':
        if ($mmtids) {
          return new RedirectResponse(Url::fromRoute('entity.node.edit_form', $params, $options)->toString());
        }
        return $this->editNode($node);

      case 'delete':
        if ($mmtids) {
          return new RedirectResponse(Url::fromRoute('entity.node.delete_form', $params, $options)->toString());
        }
        /** @noinspection PhpMethodParametersCountMismatchInspection */
        return \Drupal::formBuilder()->getForm('\Drupal\monster_menus\Form\DeleteNodeConfirmForm', NULL, $node);

      default:
        if ($mmtids) {
          return new RedirectResponse(Url::fromRoute('entity.node.canonical', $params, $options)->toString());
        }
        return node_view($node);
    }
  }

}
