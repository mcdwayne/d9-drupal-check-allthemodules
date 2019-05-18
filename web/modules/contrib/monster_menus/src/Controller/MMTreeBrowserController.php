<?php
/**
 * @file
 * Contains \Drupal\monster_menus\Controller\MMTreeBrowserController.
 */

namespace Drupal\monster_menus\Controller;

use Drupal\Component\Plugin\PluginManagerInterface;
use Drupal\Component\Utility\Html;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Database\Connection;
use Drupal\Core\Link;
use Drupal\Core\Render\HtmlResponse;
use Drupal\Core\Render\Renderer;
use Drupal\Core\Url;
use Drupal\filter\Render\FilteredMarkup;
use Drupal\monster_menus\Constants;
use Drupal\monster_menus\Entity\MMTree;
use Drupal\monster_menus\MMTreeBrowserDisplay\MMTreeBrowserDisplayInterface;
use Drupal\monster_menus\Plugin\MMTreeBrowserDisplay\Fallback;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Default controller for the monster_menus module.
 */
class MMTreeBrowserController extends ControllerBase {

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * The current page request.
   *
   * @var Request
   */
  protected $request;

  /**
   * The renderer service.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * The plugin service.
   *
   * @var \Drupal\Component\Plugin\PluginManagerInterface
   */
  protected $plugins;

  /**
   * The display plugin.
   *
   * @var MMTreeBrowserDisplayInterface
   */
  protected $plugin;

  /**
   * Constructs a MMTreeBrowserController object.
   *
   * @param \Drupal\Core\Database\Connection $database
   *   The database connection.
   * @param Request $request
   *   The parameters of the current request.
   */
  public function __construct(Connection $database, Request $request, Renderer $renderer, PluginManagerInterface $plugins) {
    $this->database = $database;
    $this->request = $request;
    $this->renderer = $renderer;
    $this->plugins = $plugins;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('database'),
      $container->get('request_stack')->getCurrentRequest(),
      $container->get('renderer'),
      $container->get('plugin.manager.mm_tree_browser_display')
    );
  }

  private function plugin($mode = '') {
    if (empty($this->plugin)) {
      $this->plugin = $this->plugins->getInstance(['mode' => $mode ?: $this->request->query->getAlnum('browserMode')]);
    }
    return $this->plugin;
  }

  /**
   * Get a portion of the MM Tree as JSON.
   *
   * @param \Drupal\monster_menus\Entity\MMTree $mm_tree
   *   Starting point
   * @return JsonResponse
   */
  public function getTreeJson(MMTree $mm_tree) {
    if (($id = $this->request->query->getInt('_vusr', 1)) > 0) {
      $id = $mm_tree->id();
    }
    return mm_json_response($this->getLeft($id), ['Pragma' => 'no-cache']);
  }

  /**
   * Get the render array for the outer tree structure.
   *
   * @return HtmlResponse
   */
  public function getWrapperRenderable() {
    $path = explode('/', preg_replace('{//+}', '/', $this->request->query->get('_path')));
    $start = array_shift($path);
    $params = explode('-', $start);
    if (isset($path[0]) && $path[0] != $params[0]) {
      array_unshift($path, $params[0]);
    }
    elseif (!count($path)) {
      $path[] = 1;
    }

    for ($i = 0; $i < 7; $i++) {
      if (!isset($params[$i])) {
        $params[$i] = '';
      }
    }

    return mm_page_wrapper(
      $this->t('Tree Browser'),
      $this->getWrapper($path[count($path) - 1], $path[0], $params[1], $params[3], $params[4], $params[5], $params[6]),
      ['id' => 'mm-media-assist-load', 'class' => ['mm-media-assist']]
    );
  }

  /**
   * Get the right hand pane's contents as JSON.
   *
   * @return JsonResponse
   */
  public function getRightJSON() {
    $get = $this->request->query->all();
    if (empty($get['id']) || !($mmtid = intval(substr($get['id'], 5)))) {
      return mm_json_response([]);
    }

    $mode = $get['browserMode'];
    $actions = [];
    $dialogs = [];
    if (!$this->isBookmarked($mmtid, $mode)) {
      $actions['bkmark'] = [
        '#type' => 'button',
        '#id' => mm_ui_modal_dialog([], $dialogs),
        '#value' => $this->t('Bookmark'),
        '#attributes' => [
          'title' => $this->t('Bookmark this location'),
          'rel' => Url::fromRoute('monster_menus.browser_bookmark_add',
            ['mm_tree' => $mmtid],
            ['query' => array('browserMode' => $mode)]
          )->toString(),
        ]
      ];
    }

    $item = mm_content_get($mmtid, Constants::MM_GET_FLAGS);
    $perms = $item->perms = mm_content_user_can($mmtid);
    $item->is_group = $perms[Constants::MM_PERMS_IS_GROUP];
    $item->is_user = $perms[Constants::MM_PERMS_IS_USER];

    $this->plugin()->alterRightButtons($mode, $this->request->query, $item, $perms, $actions, $dialogs);
    $actions['close'] = [
      '#type' => 'button',
      '#value' => $this->t('Close window'),
      '#attributes' => ['rel' => '#close'],
    ];
    mm_module_invoke_all_array('mm_browser_buttons_alter', [$mode, $item, &$actions, &$dialogs]);

    $content = $this->plugin()->viewRight($mode, $this->request->query, $perms, $item, $this->database);

    if ($content instanceof JsonResponse) {
      // View function returned JSON, so don't do any further processing.
      return $content;
    }

    if (is_array($content)) {
      $content = $this->renderer->renderRoot($content);
    }

    // Get the last viewed item
    $lastviewed = '';
    if ($last_mmtid = $this->getLastViewed($mode)) {
      $lastviewed = $this->getRelativePath($last_mmtid, $get['browserTop']);
    }
    if (isset($get['id'])) {
      $this->setLastViewedMMTID(intval(substr($get['id'], 5)));
    }

    $actions = ['#type' => 'actions', 'actions' => $actions];
    return mm_json_response([
      'title' => mm_content_get_name($item),
      'links' => render($actions),
      'body' => $content,
      'lastviewed' => $lastviewed,
      'dialogs' => isset($dialogs['#attached']['drupalSettings']['MM']['MMDialog']) ? $dialogs['#attached']['drupalSettings']['MM']['MMDialog'] : [],
    ]);
  }

  /**
   * Determine whether or not a bookmark already exists.
   *
   * @return JsonResponse
   */
  public function bookmarkExistsJSON() {
    return mm_json_response([
      'exists' => $this->isBookmarked($this->request->query->getInt('id'), $this->request->query->get('browserMode', ''))
    ]);
  }

  /**
   * Get all bookmarks as JSON.
   *
   * @return JsonResponse
   */
  public function getBookmarksJSON() {
    // Display bookmarks.
    $bookmarks = [];
    $result = $this->getBookmarks($this->request->query->get('browserMode', ''));
    while ($row = $result->fetchAssoc()) {
      $ds_data = unserialize($row['data']);
      $bookmarks[] = [
        $ds_data['mmtid'],
        $ds_data['title'],
        $this->getRelativePath($ds_data['mmtid'], $this->request->query->get('browserTop', '')),
      ];
    }
    return mm_json_response($bookmarks);
  }

  /**
   * Get the page last viewed by the user, if any, as JSON.
   *
   * @return null|JsonResponse
   */
  public function getLastViewedJSON() {
    $query = $this->request->query;
    $return = NULL;
    if ($last_mmtid = $this->getLastViewed($query->get('browserMode', ''))) {
      $return = mm_json_response(['path' => $this->getRelativePath($last_mmtid, $query->get('browserTop'))]);
    }
    if ($get_id = $query->get('id', '')) {
      $this->setLastViewedMMTID(intval(substr($get_id, 0, 5)));
    }
    return $return;
  }

  /**
   * Set the last viewed page for the current user.
   *
   * @param MMTree $mm_tree
   */
  public function setLastViewed(MMTree $mm_tree) {
    $this->setLastViewedMMTID($mm_tree->id());
  }

  /**
   * Set the last viewed page for the current user.
   *
   * @param int $mmtid
   */
  private function setLastViewedMMTID($mmtid) {
    $uid = $this->currentUser()->id();
    $type = $this->plugin()->getBookmarksType($this->request->query->get('browserMode', '')) . '_last';
    $this->database->merge('mm_tree_bookmarks')
      ->keys(array(
        'uid' => $uid,
        'type' => $type,
        'weight' => 0,
      ))
      ->fields(['data' => $mmtid])
      ->execute();
  }

  /**
   * Get the UI form to add a bookmark, or submit the form to actually add the
   * bookmark.
   *
   * @param MMTree $mm_tree
   *   MMTree entity of the page to be added.
   * @return \Symfony\Component\HttpFoundation\Response
   *   Response object.
   * @throws \Exception
   */
  public function getAddBookmarkForm(MMTree $mm_tree) {
    $user_uid = $this->currentUser()->id();
    $mmtid = $mm_tree->id();
    $name = mm_content_get_name($mmtid);
    $mode = $this->request->query->get('browserMode', '');

    if ($this->isBookmarked($mmtid, $mode)) {
      $output = [
        '#theme' => 'mm_browser_bookmark_add',
        '#name' => '',
        '#mmtid' => '',
      ];
    }
    elseif ($this->request->get('linktitle')) {
      $mm_bookmark_serialized = serialize([
        'title' => $this->request->get('linktitle', ''),
        'mmtid' => $mmtid,
      ]);
      $type = $this->plugin()->getBookmarksType($mode);
      $transaction = $this->database->startTransaction();
      $select = $this->database->select('mm_tree_bookmarks', 'b')
        ->condition('b.uid', $user_uid)
        ->condition('b.type', $type);
      $select->addExpression('IFNULL(MAX(b.weight), -1) + 1', 'max_weight');
      $max_weight = $select->execute()->fetchField();
      $this->database->insert('mm_tree_bookmarks')
        ->fields([
          'uid' => $user_uid,
          'weight' => $max_weight,
          'type' => $type,
          'data' => $mm_bookmark_serialized,
        ])
        ->execute();
      unset($transaction);
      return mm_json_response([]);
    }
    else {
      $output = [
        '#theme' => 'mm_browser_bookmark_add',
        '#name' => $name,
        '#mmtid' => $mmtid,
      ];
    }

    return new HtmlResponse((string) \Drupal::service('renderer')->renderRoot($output));
  }

  /**
   * Get the bookmarks overview form.
   *
   * @return array
   */
  public function getManageBookmarksForm() {
    $mode = $this->plugin()->getBookmarksType($this->request->query->get('browserMode', ''));
    $num_rows = $this->database->select('mm_tree_bookmarks', 'b')
      ->condition('b.uid', $this->currentUser()->id())
      ->condition('b.type', $mode)
      ->countQuery()->execute()->fetchField();

    $body = [];
    if ($num_rows < 1) {
      $body[] = ['#markup' => '<div id="message"><p>' . $this->t('No bookmarks found.') . '</p><p><input type="button" onclick="Drupal.mmDialogClose(); return false;" value="' . $this->t('Cancel') . '"></p></div>'];
    }
    else {
      mm_static($body, 'mm_browser_bookmark_manage', TRUE);

      $body[] = ['#markup' => '<div id="tb-manage-body"><form id="manage-bookmarks-form"><div id="manage-bookmarks-div"><ul class="sortable">'];

      $result = $this->getBookmarks($mode);
      while ($row = $result->fetchAssoc()) {
        $ds_data = unserialize($row['data']);
        $escaped_title = mm_ui_js_escape($ds_data['title']);
        $edit_js = "return Drupal.mmBrowserEditBookmarkEdit(" . $ds_data['mmtid'] . ", '" . $escaped_title . "', document)";
        $body[] = ['#markup' => FilteredMarkup::create('<li class="ui-state-default" id="li_' . $ds_data['mmtid'] . '" name="' . $ds_data['mmtid'] . '"><table class="manage-bookmarks-table"><tr id=' . $ds_data['mmtid'] . '><td class="tb-manage-name" ondblclick="' . $edit_js . '"><span class="ui-icon ui-icon-arrowthick-2-n-s"></span>' . Html::escape($ds_data['title']) . '</td><td><a href="#" onclick="return Drupal.mmBrowserDeleteBookmarkConfirm(' . $ds_data['mmtid'] . ", '" . $escaped_title . '\', document)">' . $this->t('Delete') . '</a></td><td><a href="#" onclick="' . $edit_js . '">' . $this->t('Edit') . '</a></td></tr></table></li>')];
      }
      $body[] = ['#markup' => '</ul></div></form>'];
    }

    return mm_page_wrapper('', $body, [], FALSE, FALSE);
  }

  /**
   * Delete a bookmark.
   *
   * @param MMTree $mm_tree
   *   Entity of the bookmarked page to delete.
   * @return JsonResponse
   *   The deleted bookmark's ID.
   */
  public function deleteBookmarkJSON(MMTree $mm_tree) {
    $mode = $this->request->query->get('browserMode', '');
    $result = $this->getBookmarks($mode);
    $type = $this->plugin()->getBookmarksType($mode);
    while ($row = $result->fetchAssoc()) {
      $ds_data = unserialize($row['data']);
      if ($ds_data['mmtid'] == $mm_tree->id()) {
        $this->database->delete('mm_tree_bookmarks')
          ->condition('uid', $this->currentUser()->id())
          ->condition('type', $type)
          ->condition('weight', $row['weight'])
          ->execute();
      }
    }
    return mm_json_response(['mmtid' => $mm_tree->id()]);
  }

  /**
   * Change the title of a bookmark.
   *
   * @param MMTree $mm_tree
   *   Entity of the bookmarked page to set the title for.
   * @return JsonResponse
   *   The new title and the bookmark's ID.
   */
  public function setBookmarkJSON(MMTree $mm_tree) {
    $title = $this->request->get('title', '');

    $mode = $this->request->query->get('browserMode', '');
    $result = $this->getBookmarks($mode);
    $type = $this->plugin()->getBookmarksType($mode);
    while ($row = $result->fetchAssoc()) {
      $ds_data = unserialize($row['data']);
      if ($ds_data['mmtid'] == $mm_tree->id()) {
        $ds_data['title'] = $title;
        $ds_done = serialize($ds_data);
        $this->database->update('mm_tree_bookmarks')
          ->fields(['data' => $ds_done])
          ->condition('uid', $this->currentUser()->id())
          ->condition('type', $type)
          ->condition('weight', $row['weight'])
          ->execute();
      }
    }
    return mm_json_response(['title' => $title, 'mmtid' => $mm_tree->id()]);
  }

  /**
   * Change the order of the list of bookmarks.
   *
   * @return JsonResponse
   *   The new order.
   * @throws \Exception
   */
  public function sortBookmarksJSON() {
    $neworder = explode('|', $this->request->get('neworder', ''));
    $mode = $this->request->query->get('browserMode', '');
    $result = $this->getBookmarks($mode);
    $type = $this->plugin()->getBookmarksType($mode);
    $uid = $this->currentUser()->id();
    $bookmarks = [];
    while ($row = $result->fetchAssoc()) {
      $ds_data = unserialize($row['data']);
      $bookmarks[$ds_data['mmtid']] = $ds_data;
    }

    $this->database->delete('mm_tree_bookmarks')
      ->condition('uid', $uid)
      ->condition('type', $type)
      ->execute();
    $weight = 0;
    foreach ($neworder as $weight => $mmtid) {
      if (isset($bookmarks[$mmtid])) {
        $this->database->insert('mm_tree_bookmarks')
          ->fields([
            'weight' => $weight,
            'uid' => $uid,
            'type' => $type,
            'data' => serialize($bookmarks[$mmtid]),
          ])->execute();
        unset($bookmarks[$mmtid]);
      }
    }
    // Ensure any remaining bookmarks not in $neworder are preserved.
    foreach ($bookmarks as $mmtid => $ds_data) {
      $this->database->insert('mm_tree_bookmarks')
        ->fields([
          'weight' => $weight++,
          'uid' => $uid,
          'type' => $type,
          'data' => serialize($ds_data)])
        ->execute();
    }
    return mm_json_response(['neworder' => $neworder]);
  }

  /**
   * Check to ensure a bookmark does not already exist prior to adding it.
   *
   * @param int $mmtid
   *   MM Tree ID of the bookmark.
   * @param string $mode
   *   Display mode constant.
   * @return bool
   */
  private function isBookmarked($mmtid, $mode) {
    $already_exists = FALSE;
    $result = $this->getBookmarks($mode);
    foreach ($result as $row) {
      $ds_data = unserialize($row->data);
      if ($ds_data['mmtid'] == $mmtid) {
        $already_exists = TRUE;
        break;
      }
    }
    return $already_exists;
  }

  /**
   * Get the outer structure of the tree browser.
   *
   * @param int $selected
   *   MM Tree ID of the initially selected page.
   * @param int $top_mmtid
   *   MM Tree ID of the topmost page shown in the tree.
   * @param string $mode
   *   Display mode constant.
   * @param string $enabled
   *   List of permissions a page can have in order to be visible.
   * @param string $selectable
   *   List of permissions a page can have in order to be selectable.
   * @param string $title
   *   Title to appear above the browser.
   * @param string $field_id
   *   The field name and bundle to display in the right hand pane.
   * @param string $file_types
   *   List of allowed MIME types to display in the right hand pane.
   * @param string $min_wh
   *   Minimum width/height, in pixels, of images that are selectable by the
   *   user.
   * @return array
   *   Render array.
   */
  private function getWrapper($selected, $top_mmtid = 1, $mode = Fallback::BROWSER_MODE_PAGE, $enabled = Constants::MM_PERMS_READ, $selectable = '', $title = '', $field_id = NULL, $file_types = NULL, $min_wh = NULL) {
    if (empty($title)) {
      $title = $this->plugin($mode)->label($mode);
    }

    if (!$top_mmtid) {
      $show_root = $top_mmtid === '0';
      $top_mmtid = 1;
    }

    $out = [];
    $instance_id = $this->request->query->getInt('instanceId', 0);
    $settings = [
      'browserInstanceId' => $instance_id,
      'libraryPath'       => drupal_get_path('module', 'monster_menus') . '/libraries',
      'browserDots'       => $this->plugin()->showReservedEntries($mode),
      'browserEnabled'    => $enabled,
      'browserMode'       => $mode,
      'browserSelectable' => $selectable,
      'browserTop'        => $top_mmtid,
      'browserShowRoot'   => (int) !empty($show_root),
      'lastBrowserPath'   => $this->getRelativePath($this->getLastViewed($mode), $top_mmtid),
      'startBrowserPath'  => $this->getRelativePath($selected, $top_mmtid),
    ];

    if (!empty($field_id)) {
      $settings['browserFieldID'] = $field_id;
    }
    if (!empty($file_types)) {
      $settings['browserFileTypes'] = $file_types;
    }
    if (!empty($min_wh)) {
      $settings += ['browserMinW' => $min_wh[0], 'browserMinH' => $min_wh[1]];
    }
    mm_add_js_setting($out, 'mmBrowser', $settings);
    mm_add_library($out, 'back_in_history');
    mm_ui_modal_dialog([], $out);
    mm_add_library($out, 'mm_browser');
    mm_add_library($out, 'jsTree');

    // Suppress the admin module
    \Drupal::moduleHandler()->invokeAll('suppress');

    $out[] = [
      '#prefix' => '<div id="mmtree-browse"><div id="mmtree-browse-nav">',
      $this->getURLs($mode, $top_mmtid),
      '#suffix' => <<<HTML
    <h2 class="mmtree-assist-title">$title</h2>
  </div>
  <div id="mmtree-browse-browser">
    <div id="mmtree-browse-tree-wrapper">
      <div id="mmtree-browse-tree"></div>
    </div>
    <div id="mmtree-browse-items">
      <div id="mmtree-browse-header">
        <h4 id="mmtree-assist-title"></h4>
        <div id="mmtree-assist-links"></div>
      </div>
      <div id="mmtree-assist-content"></div>
    </div>
  </div>
</div>
HTML
    ];
    return $out;
  }

  /**
   * Create the list of URLs at the top of the tree browser.
   *
   * @param int $mode
   *   Display mode constant.
   * @param int $top_mmtid
   *   MM Tree ID of the topmost page shown in the tree.
   * @return array
   *   Render array.
   */
  private function getURLs($mode, $top_mmtid) {
    $urls = [];
    $allowed_top = $this->plugin()->getTreeTop($mode);

    if ($top_mmtid != $allowed_top) {
      $urls[] = '<button onclick="Drupal.mm_browser_goto_top(\'' . $allowed_top . '\');" class="ui-button ui-widget ui-corner-all">' . t('View entire tree') . '</button>';
    }
    $urls[] = '<button onclick="Drupal.mm_browser_last_viewed();" id="last-viewed-link" class="ui-button ui-widget ui-corner-all">' . t('Last location') . '</button>';
    $urls[] = join('', mm_module_invoke_all('mm_browser_navigation', $mode, $top_mmtid));

    // Display bookmarks.
    $bookmarks = '<select id="bookmarks-link" class="ui-widget ui-corner-all mm-browser-button"><option selected="1" value="" disabled="1">' . t('Bookmarks') . '</option>';
    mm_ui_modal_dialog('init', $arr);
    $generated_url = Url::fromRoute('monster_menus.browser_bookmark_manage', [], ['query' => ['browserMode' => $mode]])->toString(TRUE);
    $bookmarks .= '<option id="bookmarks-manage" value="#' . $generated_url->getGeneratedUrl() . '">' . t('Organize Bookmarks...') . '</option>';

    $result = $this->getBookmarks($mode);
    while ($row = $result->fetchAssoc()) {
      $ds_data = unserialize($row['data']);
      $bookmarks .= '<option value="' . $this->getRelativePath($ds_data['mmtid'], $top_mmtid) . '">' . Html::escape($ds_data['title']) . '</option>';
    }
    $bookmarks .= '</select>';
    $urls[] = $bookmarks;
    mm_module_invoke_all_array('mm_browser_menu_alter', [$mode, &$urls]);
    $arr[] = ['#markup' => FilteredMarkup::create('<div class="ui-widget">' . implode('', $urls) . '</div>'), '#cache' => [
      '#max_age' => $generated_url->getCacheMaxAge(),
    ]];
    return $arr;
  }

  /**
   * @param array $mmtid
   *   0 to get just the root, otherwise the MMTID to fetch the children of.
   * @return array
   *   An array of entries, where any sub-entries are nested in 'children'.
   */
  private function getLeft($mmtid) {
    $mm_children = array();
    $get = $this->request->query->all();

    $depth = !$mmtid ? 0 : 1;
    if ($mmtid == 0 || $mmtid == 1) {
      $mmtid = $this->plugin()->getTreeTop($get['browserMode']);
    }
    $params = array(
      Constants::MM_GET_TREE_ADD_TO_CACHE =>     TRUE,
      Constants::MM_GET_TREE_FILTER_BINS =>      FALSE,
      Constants::MM_GET_TREE_FILTER_DOTS =>      $get['browserDots'] == 'true',
      Constants::MM_GET_TREE_FILTER_HIDDEN =>    TRUE,
      Constants::MM_GET_TREE_DEPTH =>            $depth,
      Constants::MM_GET_TREE_RETURN_KID_COUNT => TRUE,
      Constants::MM_GET_TREE_RETURN_PERMS =>     TRUE,
    );
    $this->plugin()->alterLeftQuery($get['browserMode'], $this->request->query, $params);

    $list = mm_content_get_tree($mmtid, $params);

    if ($depth) {
      array_shift($list);
    }
    foreach ($list as $item) {
      $class = [];
      $children = TRUE;
      $state = [ 'opened' => FALSE, 'disabled' => FALSE, 'selected' => FALSE ];
      $attributes = [];

      $name = mm_content_get_name($item);
      $hidden = $item->state & Constants::MM_GET_TREE_STATE_HIDDEN;
      if ($hidden) {
        $name .= ' ' . t('(hidden)');
        $class['mmtree-hidden'] = 1;
      }
      $text = $name;
      if (!empty($item->fid_list)) {
        // We can't get the count in SQL, so count the unique fids here.
        $fid_list = array_unique(explode(',', $item->fid_list));
        $item->nodecount = count($fid_list);
      }
      if (isset($item->nodecount) && $item->nodecount > 0) {
        $text = t('@name <span class="mmtree-browse-filecount">@count</span>', array('@name' => $name, '@count' => $this->formatPlural($item->nodecount, '(1 item)', '(@count items)')));
      }

      if ($item->state & Constants::MM_GET_TREE_STATE_LEAF) {
        $class['leaf'] = 1;
        $children = FALSE;
      }

      $denied = $item->state & Constants::MM_GET_TREE_STATE_DENIED || !empty($get['browserEnabled']) && !$item->perms[$get['browserEnabled']];
      if ($denied) {
        $class['disabled'] = $class['leaf'] = 1;
        $state['disabled'] = TRUE;
        $children = FALSE;
      }

      $attributes['class'] = join(' ', array_keys($class));

      $mm_children[] = array(
        'id' =>       'mmbr-' . $item->mmtid,
        'text' =>     $text,
        'state' =>    $state,
        'children' => $children,
        'a_attr' =>   $attributes,
      );
    }
    return $mm_children;
  }

  /**
   * Get a path that is relative to the displayed tree's root.
   *
   * @param int $mmtid
   *   MM Tree ID of the page in question.
   * @param int $top
   *   MM Tree ID of the tree's root.
   * @return string
   *   The relative path.
   */
  private function getRelativePath($mmtid, $top) {
    $path = mm_content_get_full_path($mmtid);
    if (preg_match('{\b' . $top . '(/|$)(.*)}', $path, $matches)) {
      return $matches[0];
    }
    return $path;
  }

  /**
   * Get the MMTID of the page last viewed by the current user.
   *
   * @param $mode
   *   Display mode constant.
   * @return int|null
   *   MM Tree ID of the last viewed page.
   */
  private function getLastViewed($mode) {
    return $this->database->select('mm_tree_bookmarks', 'b')
      ->fields('b', array('data'))
      ->condition('b.uid', $this->currentUser()->id())
      ->condition('b.type', $this->plugin()->getBookmarksType($mode) . '_last')
      ->execute()->fetchField();
  }

  /**
   * Get a database object for the query containing the list of bookmarks for
   * the current user.
   *
   * @param $mode
   *   Display mode constant.
   * @return \Drupal\Core\Database\Query\Select
   *   The database object.
   */
  private function getBookmarks($mode) {
    return $this->database->select('mm_tree_bookmarks', 'b')
      ->fields('b')
      ->condition('b.uid', $this->currentUser()->id())
      ->condition('b.type', $this->plugin()->getBookmarksType($mode))
      ->orderBy('b.weight')
      ->execute();
  }

}
