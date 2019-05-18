<?php

/**
 * @file
 * Contains \Drupal\monster_menus\Controller\MMTreeViewController.
 */

namespace Drupal\monster_menus\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Database\Connection;
use Drupal\Core\Session\AccountProxy;
use Drupal\Core\Url;
use Drupal\monster_menus\Constants;
use Drupal\monster_menus\Entity\MMTree;
use Drupal\monster_menus\PathProcessor\InboundPathProcessor;
use Drupal\views\ViewExecutable;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Default controller for the monster_menus module.
 */
class MMTreeViewController extends ControllerBase {

  /**
   * The database connection.
   *
   * @var Connection
   */
  protected $database;

  /**
   * Constructs a DefaultController object.
   *
   * @param Connection $database
   *   The database connection.
   */
  public function __construct(Connection $database) {
    $this->database = $database;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('database')
    );
  }

  public function title(MMTree $mm_tree) {
    return mm_content_get_name($mm_tree->toObject());
  }

  public static function getCacheContexts() {
    return ['url.query_args:_date', 'url.query_args:_oargs', 'route'];
  }

  /**
   * Return TRUE if there is an error condition in the request.
   *
   * @param Request $request
   *   Request object
   * @return bool
   *   TRUE if there is an error.
   */
  public static function hasError(Request $request) {
    return $request->query->get('_exception_statuscode') > 200;
  }

  /**
   * Get the arguments after the last found MMTID, but only if this isn't an
   * error page (403/404).
   *
   * @param Request $request
   *   Request object
   * @return array
   *   The list of other arguments
   */
  public static function getOargList(Request $request) {
    return !static::hasError($request) ? $request->query->get(InboundPathProcessor::OARGS_KEY, []) : [];
  }

  /**
   * Show a page.
   *
   * @param MMTree $mm_tree
   *   MMTree entity to display
   * @param Request $request
   *   Request object
   * @param string $display_mode
   *   Display mode to use
   * @return array|Response
   *   Render array for the page
   */
  public function view(MMTree $mm_tree, Request $request, $display_mode = NULL) {
    $mmtids = mm_content_get_parents_with_self($this_mmtid = $mm_tree->id());
    array_shift($mmtids);
    $oarg_list = static::getOargList($request);
    if ($display_mode && !static::hasError($request)) {
      array_unshift($oarg_list, $display_mode);
    }
    $perms = mm_content_user_can($this_mmtid);

    $output = _mm_render_pages($mmtids, $page_title, $oarg_list, $err);
    $output['#cache']['tags'] = $mm_tree->getCacheTags();
    $output['#cache']['contexts'] = static::getCacheContexts();
    if (isset($output['view'])) {
      /** @var ViewExecutable $view */
      $view = $output['view'];
      $output['view'] = $view->preview();
      $output = render($output);
      $response = $view->getResponse();
      $response->setContent($output);
      return $response;
    }
    $links = [];
    $recyc_msg = '';

    if ($perms[Constants::MM_PERMS_IS_RECYCLED]) {
      if ($perms[Constants::MM_PERMS_IS_RECYCLE_BIN]) {
        $recyc_msg = $this->t('The contents below are in the recycle bin.');
      }
      else {
        $recyc_msg = $this->t('This page is in the recycle bin.');

        if (mm_content_recycle_enabled()) {
          $when = $this->database->select('mm_recycle', 'r')
            ->fields('r', ['recycle_date'])
            ->condition('r.type', 'cat')
            ->condition('r.id', $this_mmtid)
            ->execute()
            ->fetchField();
          $autodel = mm_content_get_recycle_autodel_time($when, NULL, $this_mmtid, $this->t(' It'));
          $recyc_msg = $this->t('@prefix@autodel', ['@prefix' => $recyc_msg, '@autodel' => $autodel]);
        }

        if ($perms[Constants::MM_PERMS_WRITE]) {
          if (count($mmtids) >= 2 && mm_content_user_can($mmtids[count($mmtids) - 2], Constants::MM_PERMS_IS_RECYCLE_BIN)) {
            $msg = $this->currentUser()
              ->hasPermission('delete permanently') ? '@prefix You can restore or permanently delete it using the %settings tab.' : '@prefix You can restore it using the %settings tab.';
            $recyc_msg = $this->t($msg, ['@prefix' => $recyc_msg, '%settings' => $this->t('Settings')]);
          }
          else {
            foreach (array_reverse($mmtids) as $t) {
              if (mm_content_user_can($t, Constants::MM_PERMS_IS_RECYCLE_BIN)) {
                if (!empty($last_t) && ($tree = mm_content_get($last_t))) {
                  $pg = array(
                    '@title' => mm_content_get_name($tree),
                    ':link' => mm_content_get_mmtid_url($last_t)
                  );
                  break;
                }
              }
              else {
                $last_t = $t;
              }
            }

            if (!empty($pg)) {
              $recyc_msg = $this->t('@prefix<p>This page cannot be restored by itself. You must restore the topmost parent page in the recycle bin, <a href=":link">@title</a>.</p>', [
                '@prefix' => $recyc_msg,
                ':link' => $pg[':link'],
                '@title' => $pg['@title']
              ]);
            }
          }
        }
      }
    }

    if (!$err) {
      if ($perms[Constants::MM_PERMS_IS_RECYCLED]) {
        \Drupal::messenger()->addStatus($recyc_msg);
        if ($perms[Constants::MM_PERMS_IS_RECYCLE_BIN]) {
          $output['bin'] = _mm_show_bin_contents($this_mmtid);
        }
      }
    }
    elseif ($err == 'no read') {
      $output = [
        '#type' => 'item',
        '#input' => FALSE,
        '#markup' => $perms[Constants::MM_PERMS_IS_GROUP] ? $this->t('You do not have permission to see the members of this group.') : mm_access_denied(),
      ];
    }
    else {
      // $err=='no content' or 'missing homepage'
      $empty_msg = $this->t('<h2>Welcome</h2><p>This is a personal homepage that has not been modified yet.</p>');
      if ($err == 'missing homepage') {
        $list = [$empty_msg];
        /** @var AccountProxy $usr */
        $usr = user_load_by_name($oarg_list[0]);
        if (DefaultController::menuAccessCreateHomepage($usr)) {
          $links[] = [
            'title' => $this->t('Create a homepage'),
            'url' => Url::fromRoute('monster_menus.create_homepage', ['user' => $usr->id()]),
          ];
        }
        mm_module_invoke_all_array('mm_missing_homepage_alter', [$usr, &$list, &$links]);
        $nada = join('', $list);
      }
      else {
        if (!$perms[Constants::MM_PERMS_IS_GROUP] && !mm_get_setting('pages.hide_empty_pages_in_menu')) {
          $list = [];
          $entry = mm_content_get($this_mmtid, [Constants::MM_GET_FLAGS, Constants::MM_GET_PARENTS]);
          if ($perms[Constants::MM_PERMS_IS_USER] && isset($entry->flags['user_home'])) {
            if ($entry->flags['user_home'] == $this->currentUser()->id()) {
              $list[0] = mm_get_setting('pages.default_homepage');
            }
            if (empty($list[0])) {
              $list[0] = $empty_msg;
            }
          }
          elseif ($perms[Constants::MM_PERMS_IS_RECYCLE_BIN]) {
            $list[0] = ' ';
          }
          else {
            $list[0] = $this->t('<p>This page does not yet have any content.</p>');
          }

          $entry->perms = $perms;
          mm_module_invoke_all_array('mm_empty_page_alter', array(
            $entry,
            &$list,
            &$links
          ));
          $nada = join('', $list);
        }
        else {
          $nada = ' ';
        }

        if ($perms[Constants::MM_PERMS_APPLY] && !$perms[Constants::MM_PERMS_IS_RECYCLED] && !$perms[Constants::MM_PERMS_IS_GROUP]) {
          $link = Url::fromRoute('monster_menus.add_node', ['mm_tree' => $this_mmtid]);
          if (!$perms[Constants::MM_PERMS_IS_GROUP] && !mm_get_setting('pages.hide_empty_pages_in_menu')) {
            $links[] = array(
              'title' => $this->t('Add content'),
              'url' => $link,
            );
          }
          else {
            return mm_goto($link);
          }
        }
        elseif ($perms[Constants::MM_PERMS_IS_GROUP]) {
          $output = [
            [
              '#type' => 'html_tag',
              '#tag' => 'h2',
              '#attributes' => [],
            ]
          ];
          $users = mm_content_get_users_in_group($this_mmtid, NULL, FALSE, 100, TRUE, $output);

          if (!count($users)) {
            $msg = $this->t('There are no users in this group.');
          }
          elseif (isset($users['']) && $users[''] == '...') {
            $msg = $this->t('A partial list of users in this group:');
          }
          else {
            $msg = $this->t('All users in this group:');
          }
          $output[0]['#value'] = $msg;

          $output[] = [
            '#type' => 'item',
            '#input' => FALSE,
            '#markup' => join('<br />', $users),
          ];
          return $output;
        }
        elseif ($perms[Constants::MM_PERMS_WRITE] || $perms[Constants::MM_PERMS_SUB]) {
          if (!$perms[Constants::MM_PERMS_IS_GROUP] && !mm_get_setting('pages.hide_empty_pages_in_menu')) {
            if (!$perms[Constants::MM_PERMS_IS_RECYCLED]) {
              $nada .= $this->t('<p>You do not have permission to add content, however you can use the %settings tab to make changes to the page itself.</p>', array('%settings' => $this->t('Settings')));
            }
          }
          else {
            return mm_goto(Url::fromRoute('monster_menus.handle_page_menu', ['mm_tree' => $this_mmtid]));
          }
        }

        if ($perms[Constants::MM_PERMS_IS_RECYCLED]) {
          \Drupal::messenger()->addStatus($recyc_msg);
          if ($perms[Constants::MM_PERMS_IS_RECYCLE_BIN]) {
            $output['bin'] = _mm_show_bin_contents($this_mmtid);
          }
        }
      } // $err != 'missing homepage'

      if (empty($output)) {
        $output['empty'] = [
          '#type' => 'item',
          '#input' => FALSE,
          '#markup' => $nada,
        ];
      }
    } // $err=='no content'

    // If the no_index flag is set, include a noindex meta tag, asking nice
    // crawlers not to index the page.
    if (!isset($entry)) {
      $entry = mm_content_get($this_mmtid, Constants::MM_GET_FLAGS);
    }
    if (isset($entry->flags['no_index'])) {
      $output['#attached']['html_head'][] = [
        [
          '#tag' => 'meta',
          '#attributes' => [
            'name' => 'robots',
            'content' => 'noindex',
          ],
        ],
        'no_index'
      ];
    }

    if (isset($links)) {
      $output['links'] = [
        '#theme' => 'links',
        '#links' => $links,
      ];
    }
    // FIXME: verify that this works. It will primarily be set for the /users form.
    $output['#title'] = $page_title;

    return $output;
  }

}