<?php

/**
 * @file
 * Contains \Drupal\monster_menus\Routing\RouteSubscriber.
 */

namespace Drupal\monster_menus\Routing;

use Drupal\Core\Database\Database;
use Drupal\Core\Routing\RouteSubscriberBase;
use Drupal\monster_menus\Constants;
use Symfony\Component\Routing\RouteCollection;
use Drupal\Core\Routing\RoutingEvents;
use Drupal\Core\Url;
use Drupal\Component\Serialization\Yaml;

/**
 * Listens to dynamic route events. Alters existing menu routes to include
 * special handing for MM.
 */
class RouteSubscriber extends RouteSubscriberBase {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[RoutingEvents::ALTER] = ['onAlterRoutes', -9999];  // negative value means "late"
    return $events;
  }

  /**
   * {@inheritdoc}
   */
  public function alterRoutes(RouteCollection $collection) {
    mm_module_invoke_all_array('mm_routing_alter', array(&$collection));

    // Alter routes based on transformations found in ::alterations().
    $alterations = Yaml::decode($this->alterations());###$x=$collection->get('diff.revisions_diff');print_r($x->getOptions());print_r($x->getDefaults());
    foreach ($alterations as $name => $changes) {
      if ($route = $collection->get($name)) {
        // Read current options and hold for future alterations.
        $options = $route->getOptions();
        if (isset($changes['options'])) {
          foreach (array_keys($options) as $key) {
            if ($key[0] != '_') {
              unset($options[$key]);
            }
          }
        }

        // Alter defaults.
        if (isset($changes['defaults'])) {
          $route->setDefaults($changes['defaults']);
        }

        // Alter requirements.
        $bare_path = $route->getPath();
        if (isset($changes['requirements'])) {
          $changes['requirements']['_method'] = 'GET|POST';
          if (strpos($bare_path, '{node}') !== FALSE) {
            $changes['requirements']['node'] = '\d+';
          }
          $route->setRequirements($changes['requirements']);
        }

        // Alter options.
        if (isset($changes['options'])) {
          $options += $changes['options'];
        }

        // Alter path, adding MM's prefix.
        $route->setPath('/mm/{mm_tree}' . $bare_path);
        // Add parameter converters, as needed.
        $params = isset($options['parameters']) ? $options['parameters'] : [];
        $params['mm_tree'] = ['type' => 'entity:mm_tree', 'converter' => 'paramconverter.entity'];
        if (strpos($bare_path, '{node}') !== 0 && !isset($params['node'])) {
          $params['node'] = ['type' => 'entity:node', 'converter' => 'paramconverter.entity'];
          $route->setRequirement('node', '\d+');
        }
        $options['parameters'] = $params;

        // Set final options.
        $route->setOptions($options);
        // Set the parameter requirement for {mm_tree}.
        $route->setRequirement('mm_tree', '-?\d+');
      }
    }

    // Alter remaining routes which contain {node}, prefixing them with
    // "/mm/{mm_tree}".
    foreach ($collection->all() as $name => $route) {
      // Make sure this isn't already one of our routes.
      if (strncmp($name, 'monster_menus.', 14)) {
        if (!isset($alterations[$name]) && strpos($route->getPath(), '{node}') !== FALSE && strpos($route->getPath(), '/mm/{mm_tree}') !== 0) {
          $route->setPath('/mm/{mm_tree}' . $route->getPath());
        }
      }
    }

    // Modify some built-in routes to point to MM's equivalent.
    $aliases = [
      'node.add_page' => 'monster_menus.add_node',
      'node.add' => 'monster_menus.add_node_with_type',
    ];
    foreach ($aliases as $from => $to) {
      if (($from_route = $collection->get($from)) && ($to_route = $collection->get($to))) {
        $collection->remove($from);
        $collection->add($from, clone $to_route);
      }
    }

    // Generate the list of keywords that are not allowed in URL aliases, and
    // give an error message if there already is something in mm_tree using one
    // of the menu keywords.
    $checked = $reserved_alias = $top_level_reserved = array();
    foreach ($collection->all() as $name => $route) {
      // Remove leading or trailing slashes, then squish any multiple slashes in
      // a row.
      $path = $route->getPath();
      $elems = explode('/', preg_replace('{//+}', '/', trim($path, '/')));

      if ($elems[0] && $elems[0][0] != '{') {
        $top_level_reserved[$elems[0]] = TRUE;
      }

      if (count($elems) >= 3 && $elems[0] == 'mm' && $elems[1] == '%mm_mmtid') {
        $failed_elems = array();
        for ($i = 2; $i < count($elems); $i++) {
          // Only reserve the first non-token after mm/%mm_mmtid
          if ($elems[$i][0] != '{') {
            if (empty($reserved_alias[$elems[$i]])) {
              if (!isset($checked[$elems[$i]])) {
                $checked[$elems[$i]] = mm_content_get(array('alias' => $elems[$i]), array(), 10);
              }

              if (!empty($checked[$elems[$i]])) {
                $failed_elems[] = $elems[$i];
              }
              $reserved_alias[$elems[$i]] = TRUE;
            }
            break;
          }
        }

        foreach ($failed_elems as $elem) {
          $list = array();
          $error = 'The menu entry %entry contains the element %element. This conflicts with the URL names that are already assigned to these MM pages:<br />';
          foreach ($checked[$elem] as $index => $tree) {
            $error .= '<a href=":link' . $index . '">@title' . $index . '</a>' . ($index > 0 ? '<br />' : '');
            $list['@title' . $index] = '&nbsp;&nbsp;' . mm_content_get_name($tree);
            $list[':link' . $index] = Url::fromRoute('monster_menus.handle_page_settings', ['mm_tree' => $tree->mmtid])->toString();
          }
          $error .= '<br />The menu entry has been disabled. You must change the URL name(s) and rebuild the menus.';
          $err_arr = array_merge(array(
            '%entry' => $path,
            '%element' => $elem,
          ), $list);
          if (\Drupal::currentUser()->hasPermission('administer all menus')) {
            \Drupal::messenger()->addError(t($error, $err_arr));
          }
          \Drupal::logger('mm')->error($error, $err_arr);
          $collection->remove($name);
        }
      }
    }
    \Drupal::state()->set('monster_menus.reserved_alias', array_merge(array_keys($reserved_alias), mm_content_reserved_aliases_base()));
    \Drupal::state()->set('monster_menus.top_level_reserved', array_keys($top_level_reserved));

    // Emit an error message if there already is something in mm_tree that would
    // match one of the system menu entries.
    // First, get the position of the homepage within the tree.
    //   SELECT depth FROM mm_tree_parents WHERE parent = [mm_home_mmtid()] LIMIT 1
    $db = Database::getConnection();
    $home_depth = $db->select('mm_tree_parents', 'p')
      ->condition('parent', mm_home_mmtid())
      ->fields('p', array('depth'))
      ->range(0, 1)
      ->execute()
      ->fetchField();
    if (isset($home_depth)) {
      foreach ($collection->all() as $name => $route) {
        // Remove leading or trailing slashes, then squish any multiple slashes in
        // a row.
        $path = $route->getPath();
        $elems = explode('/', preg_replace('{//+}', '/', trim($path, '/')));
        if (count($elems) >= 2 && $elems[0] == 'mm' && $elems[1] == '%mm_mmtid') {
          continue;
        }
        $where = '';
        $compare_total = 0;
        // SELECT COUNT(*) FROM mm_tree t
        //   INNER JOIN mm_tree_parents p ON p.mmtid = t.mmtid
        //   INNER JOIN mm_tree t2 ON t2.mmtid = p.parent
        // WHERE t.alias = '[level 3 alias]' AND (
        //   SELECT COUNT(*) FROM mm_tree_parents WHERE mmtid = t.mmtid) = 4 AND (
        //     t2.alias = '[level 2 alias]' AND p.depth = 3
        //     OR t2.alias = '[level 1 alias]' AND p.depth = 2
        //     OR p.depth = 1 AND t2.mmtid = [mm_home_mmtid()] )     (etc.)
        // GROUP BY t.mmtid HAVING COUNT(*) = [overall depth]
        $ors = array('p.depth = :home_depth AND t2.mmtid = :home_mmtid');
        $args = array(':home_depth' => $home_depth, ':home_mmtid' => mm_home_mmtid());
        foreach (array_reverse($elems, TRUE) as $depth => $elem) {
          if ($elem && $elem[0] != '{') {
            $args[":alias$depth"] = $elem;
            $args[":depth$depth"] = $depth + $home_depth + 1;
            if (empty($where)) {
              $where = "t.alias = :alias$depth AND (SELECT COUNT(*) FROM {mm_tree_parents} WHERE mmtid = t.mmtid) = :depth$depth";
            }
            else {
              $ors[] = "t2.alias = :alias$depth AND p.depth = :depth$depth";
            }
            $compare_total++;
          }
        }

        if ($where) {
          $args[':compare'] = $compare_total;
          if ($ors) {
            $where .= ' AND (' . join(' OR ', $ors) . ')';
          }
          $result = $db->query('SELECT t.mmtid, t.name FROM {mm_tree} t ' .
            'INNER JOIN {mm_tree_parents} p ON p.mmtid = t.mmtid ' .
            "INNER JOIN {mm_tree} t2 ON t2.mmtid = p.parent WHERE $where " .
            'GROUP BY t.mmtid, t.name HAVING COUNT(*) = :compare LIMIT 10', $args);
          $list = array();
          $error = 'The menu entry %entry conflicts with these MM pages:<br />';
          foreach ($result as $index => $tree) {
            $error .= '<a href=":link' . $index . '">@title' . $index . '</a>' . ($index > 0 ? '<br />' : '');
            $list['@title' . $index] = '&nbsp;&nbsp;' . mm_content_get_name($tree);
            $list[':link' . $index] = Url::fromRoute('monster_menus.handle_page_settings', ['mm_tree' => $tree->mmtid])->toString();
          }
          $error .= '<br />The menu entry has been disabled. Change either the URL name(s) or the menu path and rebuild the menus.';
          if ($list) {
            $err_arr = array_merge(array('%entry' => $path), $list);
            if (\Drupal::currentUser()->hasPermission('administer all menus')) {
              \Drupal::messenger()->addError(t($error, $err_arr));
            }
            \Drupal::logger('mm')->error($error, $err_arr);
            $collection->remove($name);
          }
        }
      }
    }

    // Regenerate the list of MM tree entry names to hide from non-admin users
    $hidden_names = array(Constants::MM_ENTRY_NAME_DEFAULT_USER, Constants::MM_ENTRY_NAME_DISABLED_USER);
    $hidden_names = array_merge($hidden_names, mm_module_invoke_all('mm_hidden_user_names'));

    // Find the path of the contextual.render route, for processing in
    // mm_active_menu_item().
    $path = '';
    if ($route = $collection->get('contextual.render')) {
      $path = $route->getPath();
    }
    \Drupal::state()->set('monster_menus.contextual_render_path', $path);

    // Regenerate the custom page display list
    _mm_showpage_router(TRUE);
  }

  private function alterations() {
    return <<<YAML
entity.node.edit_form:
  defaults:
    _title_callback: '\Drupal\monster_menus\Controller\DefaultController::editNodeGetTitle'
    _controller: '\Drupal\monster_menus\Controller\DefaultController::editNode'
entity.node.delete_form:
  defaults:
    _title_callback: '\Drupal\monster_menus\Form\DeleteNodeConfirmForm::getMenuTitle'
    _form: \Drupal\monster_menus\Form\DeleteNodeConfirmForm
  requirements:
    _custom_access: '\Drupal\monster_menus\Form\DeleteNodeConfirmForm::access'
entity.node.preview: {}
entity.node.version_history:
  defaults:
    _title: Revisions
    _controller: '\Drupal\monster_menus\Controller\NodeRevisionsController::revisionOverview'
  requirements:
    _custom_access: '\Drupal\monster_menus\Controller\NodeRevisionsController::menuAccessNodeRevisions'
node.revision_revert_confirm:
  defaults:
    _title: 'Revert to earlier revision'
    _controller: '\Drupal\monster_menus\Controller\NodeRevisionsController::revisionRevertConfirm'
  requirements:
    _custom_access: '\Drupal\monster_menus\Controller\NodeRevisionsController::menuAccessNodeRevisions'
node.revision_delete_confirm:
  defaults:
    op: delete
    _title: 'Delete earlier revision'
    _controller: '\Drupal\monster_menus\Controller\NodeRevisionsController::revisionDeleteConfirm'
  requirements:
    _custom_access: '\Drupal\monster_menus\Controller\NodeRevisionsController::menuAccessNodeRevisions'
entity.node.revision:
  defaults:
    _title_callback: '\Drupal\\node\Controller\NodeController::revisionPageTitle'
    _controller: '\Drupal\monster_menus\Controller\NodeRevisionsController::revisionShow'
  requirements:
    _custom_access: '\Drupal\monster_menus\Controller\NodeRevisionsController::menuAccessNodeRevisions'
diff.revisions_diff:
  defaults:
    op: compare
    _title: 'Compare revisions'
    _controller: '\Drupal\monster_menus\Controller\NodeRevisionsController::compareRevisions'
  requirements:
    _custom_access: '\Drupal\monster_menus\Controller\NodeRevisionsController::menuAccessNodeRevisions'
entity.comment.canonical: {}
entity.comment.delete_form:
  defaults:
    _title: 'Delete comment'
    _entity_form: 'comment.delete'
entity.comment.edit_form:
  defaults:
    _title: 'Edit comment'
    _entity_form: 'comment.default'
comment.reply:
  defaults:
    _title: 'Reply to comment'
    _controller: '\Drupal\comment\Controller\CommentController::getReplyForm'
    _entity_form: 'comment.default'
    pid: null
YAML;
  }

}
