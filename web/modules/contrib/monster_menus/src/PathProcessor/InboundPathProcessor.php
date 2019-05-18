<?php
namespace Drupal\monster_menus\PathProcessor;

use Drupal\Core\Database\Database;
use Drupal\Core\PathProcessor\InboundPathProcessorInterface;
use Drupal\Core\Url;
use Drupal\monster_menus\Constants;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;

/**
 * Defines a path processor to rewrite URLs in the MM tree.
 *
 * As the route system does not allow an arbitrary number of parameters, convert
 * the path to a set of query parameters on the request.
 */
class InboundPathProcessor implements InboundPathProcessorInterface {

  const OARGS_KEY = '_oargs';
  protected $cache, $cache_oargs;

  /**
   * {@inheritdoc}
   */
  public function processInbound($path, Request $request) {
    $original = $path;
    $path = $this->processInboundPath($path, $request->getPathInfo(), TRUE, $oargs);
    // If the new path looks like /mm/MMTID ...
    if (preg_match('{^/mm/(-?\d+)(?:/|$)}', $path, $path_matches)) {
      // ... and it's a pseudo-MMTID (integer < 0), show an alpha list of users.
      if ($path_matches[1] < 0) {
        return Url::fromRoute('monster_menus.userlist', ['mmtid' => $path_matches[1]], ['base_url' => ''])
          ->toString();
      }

      if (!$request->query->has(self::OARGS_KEY)) {
        $request->query->set(self::OARGS_KEY, []);
      }
      $orig_is_mm = preg_match('{^/(mm/)?(-?\d+)(?:/|$)}', $original, $original_matches);
      // ... and there are arguments after the MMTID that do not resolve to a
      // valid path, then move the args into the query.
      if ($oargs && !$orig_is_mm) {
        try {
          \Drupal::service('router.no_access_checks')->match($path);
        }
        catch (ResourceNotFoundException $e) {
          $parts = explode('/', $path);
          $path = implode('/', array_slice($parts, 0, count($parts) - count($oargs)));
          $request->query->set(self::OARGS_KEY, $oargs);
        }
      }
      // ... and the original path looks like /MMTID or /mm/MMTID, and the MMTID
      // does not refer to a page without an alias, do a redirect to the full
      // path so it gets set in the browser. Testing $request->getScriptName()
      // verifies that the request originated from a client and was not
      // internally generated.
      else if (!$oargs && $orig_is_mm && $original_matches[2] == $path_matches[1] && $request->getScriptName() && ($tree = mm_content_get($path_matches[1])) && ($tree->alias || $tree->parent != 1 && $tree->parent != mm_home_mmtid())) {
        return Url::fromRoute('monster_menus.redirect', ['mmtid' => $path_matches[1]], ['base_url' => ''])
          ->toString();
      }
    }
    return $path;
  }

  public function processInboundPath($path, $original_path, $assume_home = TRUE, &$oargs = []) {
    if (preg_match('/^\w+:/', $original_path)) {
      // Ignore URLs starting with "proto:".
      return $path;
    }

    // remove empty '//' elements
    $original_path = preg_replace('{/+}', '/', $original_path);
    $original_path = trim($original_path, '/');
    $path = $original_path;

    if (!$this->cache) {
      $home_path = mm_home_path();
      $this->cache[''] = $this->cache[$home_path] = "/$home_path";
      $this->cache['feed'] = "/$home_path/feed";  // top-level /feed
      $this->cache_oargs[''] = $this->cache_oargs[$home_path] = $this->cache_oargs['feed'] = [];
    }

    if (isset($this->cache[$original_path])) {
      $oargs = $this->cache_oargs[$original_path];
      return $this->cache[$original_path];
    }

    $elems = explode('/', $original_path);
    $in_mm = FALSE;
    if (isset($this->cache[$elems[0]]) && $this->cache[$elems[0]] === FALSE) {
      // This top level element was previously proven to not be in MM.
      $oargs = [];
      return '/' . $original_path;
    }

    for ($i = count($elems); $i > 0; $i--) {
      $temp_path = implode('/', array_slice($elems, 0, $i));
      if (isset($this->cache[$temp_path])) {
        if ($this->cache[$temp_path] === FALSE) {
          $oargs = [];
          return '/' . $original_path;
        }
        $elems = array_merge(array_slice(explode('/', $this->cache[$temp_path]), 1), array_slice($elems, $i));
        break;
      }
    }

    if ($elems[0] == 'mm' && count($elems) >= 2 && is_numeric($elems[1])) {
      $in_mm = TRUE;
      $this_mmtid = $parent = $elems[1];
      array_shift($elems);
      array_shift($elems);
    }
    else if ($elems[0] == mm_home_mmtid()) {
      $in_mm = TRUE;
      $this_mmtid = $parent = mm_home_mmtid();
      array_shift($elems);
    }
    else if (count($elems) >= 2 && ($elems[0] == mm_content_users_alias() || is_numeric($elems[0]) && $elems[0] == mm_content_users_mmtid()) && strlen($elems[1]) == 1 && mm_get_setting('user_homepages.virtual')) {
      $in_mm = TRUE;
      $alias = ctype_alpha($elems[1][0]) ? strtoupper($elems[1][0]) : '~';
      array_splice($elems, 0, 2);
      $this_mmtid = $parent = count($elems) ? mm_content_users_mmtid() : -ord($alias);
    }

    $joins = $wheres = $numeric = $args = $args_at_level = array();
    $a = 0;
    $reserved = mm_content_reserved_aliases();
    $max = min(count($elems), \Drupal::state()->get('monster_menus.mysql_max_joins', Constants::MM_CONTENT_MYSQL_MAX_JOINS));
    for ($i = 0; $i < $max; $i++) {
      $elem = $elems[$i];
      $numeric[$i] = FALSE;
      if (!$in_mm && $i == 0 && $assume_home && ($elem == 'settings' || $elem == 'contents')) {
        $in_mm = TRUE;
        $this_mmtid = mm_home_mmtid();
        break;
      }
      elseif (!in_array($elem, $reserved)) {
        $n = count($joins);
        $nprev = $n - 1;
        $joins[] = "{mm_tree} t$n" . ($n ? " ON t$n.parent = t$nprev.mmtid" : '');
        $prefix = $n ? '' : (empty($parent) ? '' : "t0.parent = $parent AND ");
        $middle = $n ? '' : (empty($parent) ? 't0.parent IN(1, ' . mm_home_mmtid() . ') AND ' : '');
        $numeric[$i] = is_numeric($elem) && intval($elem) == $elem && $elem != 0;
        $args[':a' . $a++] = $elem;
        if ($numeric[$i] && $elem > 0) {
          $wheres[] = "{$prefix}(t$n.mmtid = :a" . ($a - 1) . " OR {$middle}t$n.alias = :a$a)";
          $args[':a' . $a++] = $elem;
          $args_at_level[$i] = 2;
        }
        else {
          $wheres[] = "{$prefix}{$middle}t$n.alias = :a" . ($a - 1);
          $args_at_level[$i] = 1;
        }
      }
      else {
        break;
      }
    }

    $this->cache_oargs[$original_path] = [];
    while ($joins) {
      $n = count($joins) - 1;
      $new_mmtid = Database::getConnection()->query("SELECT t$n.mmtid FROM " . join(' INNER JOIN ', $joins) . ' WHERE ' . join(' AND ', $wheres) . " ORDER BY t$n.alias LIMIT 1", $args)->fetchField();
      array_pop($joins);
      array_pop($wheres);
      $was_numeric = array_pop($numeric);
      if ($new_mmtid) {
        $in_mm = TRUE;
        $this_mmtid = $new_mmtid;
        break;
      }

      if ($was_numeric) {
        if ($elems[0] < 0) {
          $this_mmtid = $elems[0];
          $in_mm = TRUE;
          break;
        }
        elseif (!$joins) {
          $site_404 = \Drupal::config('system.site')->get('page.404');
          if ($site_404 && $site_404 != $original_path) {
            // FIXME
//            mm_module_invoke_all_array('url_inbound_alter', array(&$path, $site_404, $path_language));
            return $path;
          }
          break;
        }
      }
      // There can't be any extra args, so remove what's not needed
      if ($popped = array_pop($args_at_level)) {
        array_splice($args, -$popped);
      }
      $i--;
    }

    if ($in_mm && !empty($this_mmtid)) {
      $elems = array_slice($elems, $i);
      $oargs = $this->cache_oargs[$original_path] = $elems;
      array_unshift($elems, "mm/$this_mmtid");
      $path = implode('/', $elems);
    }
    else {
      // Path was in no way part of MM, so mark the topmost path element in
      // cache to prevent future attempts to match.
      if ($elems) {
        $this->cache[$elems[0]] = FALSE;
        $this->cache_oargs[$elems[0]] = [];
        return '/' . $path;
      }
    }

    return $this->cache[$original_path] = '/' . $path;
  }

}
