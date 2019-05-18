<?php

namespace Drupal\Tests\monster_menus\Functional\Permissions;

use Drupal\Core\Database\Database;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Serialization\Yaml;
use Drupal\monster_menus\Constants;
use Drupal\monster_menus\MMCreatePath\MMCreatePath;
use Drupal\monster_menus\MMCreatePath\MMCreatePathCat;
use Drupal\monster_menus\MMCreatePath\MMCreatePathGroup;
use Drupal\node\Entity\Node;
use Drupal\Tests\BrowserTestBase;
use Drupal\user\Entity\Role;
use Drupal\user\Entity\User;

/**
 * @group MonsterMenus
 */
class PermissionsTest extends BrowserTestBase {

  protected static $modules = ['monster_menus'];

  /**
   * If TRUE, generate and save the baseline file.
   */
  const generateBaseline = FALSE;
  /**
   * If TRUE, test node permissions.
   */
  const testNodes = TRUE;

  const baselineFilename = 'PermissionsTestBaseline.yml.gz';

  private $uids = [];

  /** @var MMCreatePath $mmCreatePath */
  private $mmCreatePath;

  private $testNodes;

  public $baseline;

  /**
   * @inheritDoc
   */
  protected function setUp() {
    parent::setUp();

    // Prevent mm_content_delete() from printing deletion stats.
    $_SERVER['SERVER_SOFTWARE'] = 'test';

    $setup_user = function($access_modes) {
      $label = join('+', $access_modes);
      $role = Role::create(['id' => $this->randomMachineName(8), 'label' => "$label Role"]);
      foreach ($access_modes as $access_mode) {
        $role->grantPermission($access_mode);
      }
      $role->save();
      $user = User::create(['name' => "Can $label", 'status' => 1]);
      $user->addRole($role->id());
      $user->save();
      $this->uids[$label] = $user->id();
    };
    $this->testNodes = self::testNodes && mm_module_exists('node');

    $this->mmCreatePath = $this->container->get('monster_menus.mm_create_path');

    $this->uids = ['anonymous' => 0, 'admin' => 1];
    $access_modes = ['administer all menus', 'administer all users', 'administer all groups', 'view all menus', 'bypass node access'];
    foreach ($access_modes as $access_mode) {
      if ($access_mode != 'bypass node access' || $this->testNodes) {
         $setup_user([$access_mode]);
      }
    }
    if ($this->testNodes) {
      $setup_user(['administer all menus', 'bypass node access']);
    }
    $user = User::create(['name' => 'No roles', 'roles' => [], 'status' => 1]);
    $user->save();
    $this->uids['no roles'] = $user->id();

    if (!self::generateBaseline) {
      try {
        $this->baseline = Yaml::decode(join("\n", gzfile(__DIR__ . '/' . self::baselineFilename)));
      }
      catch (\Exception $e) {
        print('Could not open baseline file ' . __DIR__ . '/' . self::baselineFilename . ': ' . $e->getMessage());
        throw $e;
      }
    }
  }

  /**
   * @throws \Exception
   */
  public function testPermissions() {
    // The following will turn off devel query logging, to make this test as
    // fast as possible.
    Database::getLog('devel');

    $database = Database::getConnection();

    $test_node = function ($usr, $print_path, Node $node, &$stats, $pass, $curr, $secondary) {
      $label = $node->label();
      $new = mm_content_user_can_node($node, '', $usr);
      foreach ([Constants::MM_PERMS_READ, Constants::MM_PERMS_WRITE] as $mode) {
        $stats['count']++;
        if (self::generateBaseline) {
          $stats['baseline'][$print_path]['node'][$label][$mode][$pass] = !empty($new[$mode]);
        }
        else if (!isset($this->baseline[$stats['label']][$print_path]['node'][$label][$mode][$pass])) {
          $stats['fail'][] = $this->failed("Undefined baseline entry ['" . $stats['label'] . "']['$print_path']['node']['$label']['$mode'][$pass]. Re-run this test using PermissionsTest::wantBaseline=TRUE and use the output to rewrite " . self::baselineFilename . '.');
        }
        else {
          $old = $this->baseline[$stats['label']][$print_path]['node'][$label][$mode][$pass];
          if ($new[$mode] != $old) {
            if (!$curr) {
              $stats['fail'][] = $this->failed("$print_path: node '$label' pass $pass: $mode", $old);
            }
            else if ($pass) {
              $stats['fail'][] = $this->failed("($curr->mmtid) and ($secondary->mmtid) $print_path: node '$label': $mode", $old);
            }
            else {
              $stats['fail'][] = $this->failed("($curr->mmtid) $print_path: node '$label': $mode", $old);
            }
          }
          else if ($curr) {
            $this->assertTrue(TRUE, "($curr->mmtid) $print_path: node '$label'");
          }
          else {
            $this->assertTrue(TRUE, "$print_path: node '$label'");
          }
        }
      }
    };

    $test_page = function ($usr, $path, $nodes, $secondaries, &$stats) use ($test_node) {
      $item = $path[count($path) - 1];
      $print_path = [];
      foreach ($path as $p) {
        $print_path[] = $p->name;
      }
      $print_path = join('/', $print_path);

      $new = mm_content_user_can($item->mmtid, '', $usr);
      $modes = [
        Constants::MM_PERMS_WRITE,
        Constants::MM_PERMS_SUB,
        Constants::MM_PERMS_APPLY,
        Constants::MM_PERMS_READ,
        Constants::MM_PERMS_IS_USER,
        Constants::MM_PERMS_IS_GROUP,
        Constants::MM_PERMS_IS_RECYCLE_BIN,
        Constants::MM_PERMS_IS_RECYCLED,
      ];
      foreach ($modes as $mode) {
        if ($mode != Constants::MM_PERMS_APPLY || $path[0]->name != Constants::MM_ENTRY_NAME_GROUPS) {
          $stats['count']++;
          if (self::generateBaseline) {
            $stats['baseline'][$print_path]['page'][$mode] = !empty($new[$mode]);
          }
          else if (!isset($this->baseline[$stats['label']][$print_path]['page'][$mode])) {
            $stats['fail'][] = $this->failed("Undefined baseline entry ['" . $stats['label'] . "']['$print_path']['page'][$mode]. Re-run this test using PermissionsTest::wantBaseline=TRUE and use the output to rewrite " . self::baselineFilename . '.');
          }
          else {
            $old = $this->baseline[$stats['label']][$print_path]['page'][$mode];
            if ($new[$mode] !== $old) {
              $stats['fail'][] = $this->failed("($item->mmtid) $print_path: $mode", $old);
            }
            else {
              $this->assertTrue(TRUE, "($item->mmtid) $print_path: $mode");
            }
          }
        }
      }

      /** @var $node Node */
      foreach ($nodes as $node) {
        $node->mm_catlist = [$item->mmtid => ''];
        $node->save();
        $test_node($usr, $print_path, $node, $stats, 0, $item, NULL);
        $bin = mm_content_move_to_bin(NULL, $node->id());
        mm_content_update_sort_queue();
        $label = $node->label();
        if (!is_numeric($bin)) {
          $stats['fail'][] = $this->failed("Could not recycle the node '$label' at $print_path: $bin");
        }
        else {
          $pp = $print_path . '/[recycled]';
          $test_node($usr, $pp, $node, $stats, 0, NULL, NULL);
          $err = mm_content_move_from_bin(NULL, $node, $bin, FALSE);
          if (is_string($err)) {
            $stats['fail'][] = $this->failed("Could not move '$label' out of recycle bin at $pp: $err");
          }
          else if ($secondaries) {
            foreach ($secondaries as $pass => $secondary) {
              $node->mm_catlist = [$item->mmtid => '', $secondary->mmtid => ''];
              $node->save();
              $test_node($usr, $print_path, $node, $stats, $pass + 1, $item, $secondary);
              $bin = mm_content_move_to_bin(NULL, [$node->id() => [$item->mmtid]]);
              mm_content_update_sort_queue();
              if (!is_numeric($bin)) {
                $stats['fail'][] = $this->failed("Could not recycle the node '$label' at $print_path: $bin");
              }
              else {
                $test_node($usr, $pp, $node, $stats, $pass + 1, NULL, NULL);
                $err = mm_content_move_from_bin(NULL, $node, $bin, FALSE);
                if (is_string($err)) {
                  $stats['fail'][] = $this->failed("Could not move '$label' out of recycle bin at $pp: $err");
                }
                else {
                  $this->assertTrue(TRUE, "Moved '$label' out of recycle bin at $pp");
                }
              }
            }
          }
        }
      }
      if ($nodes) {
        // Flush the cache now instead of waiting until there are tons of entries
        _mm_content_clear_access_cache();
      }
    };

    $test_tree = function ($usr, $path, &$stats) {
      $iter = new PermissionsTestIter([$path[0]->name, $path[1]->name], $stats, $this);
      $params = [
        Constants::MM_GET_TREE_ITERATOR => $iter,
        Constants::MM_GET_TREE_FILTER_HIDDEN => TRUE,
        Constants::MM_GET_TREE_RETURN_PERMS => TRUE,
        Constants::MM_GET_TREE_USER => $usr,
      ];
      mm_content_get_tree($path[1]->mmtid, $params);
    };

    $user = User::load(1);
    if (!$user) {
      throw new \Exception('Could not load the admin user.');
    }
    $this->container->set('current_user', $user);

    $dummy_uid = 99999;   // to keep mm_create_path() from using uid=1 by default

    $roots = [mm_home_mmtid() => '[home]', mm_content_users_mmtid() => Constants::MM_ENTRY_NAME_USERS];
    $stats = [];
    foreach ($this->uids as $label => $test_uid) {
      $stats[$test_uid] = ['label' => $label, 'count' => 0, 'fail' => [], 'baseline' => []];
      $usr = User::load($test_uid);

      $grp = [
        new MMCreatePathGroup([
          'name' => Constants::MM_ENTRY_NAME_GROUPS,
          'mmtid' => mm_content_groups_mmtid(),
        ]),
        new MMCreatePathGroup([
          'name' => '~MM TEST',
          'members' => $test_uid ? [$test_uid] : [],
        ]),
      ];
      if (!$this->mmCreatePath->createPath($grp)) {
        throw new \Exception('Create group failed');
      }
      $gid = $grp[1]->mmtid;

      if ($test_uid) {
        $vgrp = [
          &$grp[0],
          new MMCreatePathGroup([
            'name' => Constants::MM_ENTRY_NAME_VIRTUAL_GROUP,
          ]),
          new MMCreatePathGroup([
            'name' => '~MM TEST',
            'vgroup' => TRUE,
            'qfield' => $test_uid,
            'qfrom' => '',
          ]),
        ];
        if (!$this->mmCreatePath->createPath($vgrp)) {
          throw new \Exception('Create vgroup failed');
        }
        $q = $database->select('mm_group');
        $q->addField('mm_group','vgid');
        $q->condition('gid', $vgrp[2]->mmtid);
        $vgid = $q->execute()->fetchField();
        $database->delete('mm_virtual_group')->condition('vgid', $vgid)->execute();
        $database->insert('mm_virtual_group')
          ->fields(['vgid' => $vgid, 'uid' => $test_uid, 'preview' => 0])
          ->execute();
      }

      $nodes = [];
      if ($this->testNodes) {
        // Create a bunch of nodes, initially not on any page.
        // This section requires $user->id() == 1.
        if (!self::saveNode($user, [], [], $nodes, 'owned by admin') ||
          !self::saveNode($usr, [], [], $nodes, 'owned by user') ||
          !self::saveNode($user, [], [], $nodes, 'writable by everyone', TRUE) ||
          $test_uid &&
          (!self::saveNode($user, [$gid => ''], [], $nodes, 'writable by user in group') ||
            !self::saveNode($user, [], [$test_uid => ''], $nodes, 'writable by user in ad hoc group') ||
            !self::saveNode($user, [$vgid = ''], [], $nodes, 'writable by user in virtual group'))) {
          return;
        }
      }

      $parents = [
        new MMCreatePathCat([
          'name' => 'unreadable parent',
          'alias' => 'xparent',
          'default_mode' => '',
          'uid' => $dummy_uid,
        ]),
        new MMCreatePathCat([
          'name' => 'parent readable by everyone',
          'alias' => 'rparent',
          'uid' => $dummy_uid,
        ]),
      ];
      if ($test_uid) {
        $parents[] = new MMCreatePathCat([
          'name' => 'parent readable by regular group',
          'alias' => 'rreggroupparent',
          'perms' => [Constants::MM_PERMS_READ => ['groups' => [&$grp]]],
          'uid' => $dummy_uid,
        ]);
        $parents[] = new MMCreatePathCat([
          'name' => 'parent readable by ad hoc group',
          'alias' => 'radhocgroupparent',
          'perms' => [Constants::MM_PERMS_READ => ['users' => [$test_uid]]],
          'uid' => $dummy_uid,
        ]);
        $parents[] = new MMCreatePathCat([
          'name' => 'parent readable by virtual group',
          'alias' => 'rvirtgroupparent',
          'perms' => [Constants::MM_PERMS_READ => ['groups' => [&$vgrp]]],
          'uid' => $dummy_uid,
        ]);
      }

      foreach ($roots as $root => $root_name) {
        $this->deleteIfExists(['parent' => $root, 'alias' => '~mmtest']);
        foreach ($parents as $parent) {
          $path = [
            new MMCreatePathCat([
              'mmtid' => $root,
              'name' => $root_name,
            ]),
            new MMCreatePathCat([
              'name' => '~MM TEST',
              'alias' => '~mmtest',
              'uid' => $dummy_uid,
            ]),
            clone($parent),
            new MMCreatePathCat([
              'name' => 'no read',
              'alias' => 'noread',
              'default_mode' => '',
              'uid' => $dummy_uid,
            ]),
          ];
          $this->mmCreatePath->createPath($path);
          // Test nodes first on the page by themselves, then also on a
          // world-readable page and an unreadable page.
          $secondaries = [$path[1], $path[3]];
          $test_page($usr, $path, $nodes, $secondaries, $stats[$test_uid]);

          $path[3] = new MMCreatePathCat([
            'name' => 'owns',
            'alias' => 'owns',
            'default_mode' => '',
            'uid' => $test_uid,
          ]);
          $this->mmCreatePath->createPath($path);
          $test_page($usr, $path, $nodes, $secondaries, $stats[$test_uid]);

          $path[3] = new MMCreatePathCat([
            'name' => 'inaccessible',
            'alias' => 'inaccessible',
            'default_mode' => '',
            'uid' => $dummy_uid,
          ]);
          $this->mmCreatePath->createPath($path);
          $test_page($usr, $path, $nodes, $secondaries, $stats[$test_uid]);

          foreach (['read', 'write', 'add sub', 'use'] as $long) {
            $short = $long[0];
            $path[3] = new MMCreatePathCat([
              'name' => $long . ' by everyone',
              'alias' => $short . 'everyone',
              'default_mode' => $short,
              'uid' => $dummy_uid,
            ]);
            $this->mmCreatePath->createPath($path);
            $test_page($usr, $path, $nodes, $secondaries, $stats[$test_uid]);

            if ($test_uid) {
              $path[3] = new MMCreatePathCat([
                'name' => $long . ' by regular group',
                'alias' => $short . 'reggroup',
                'default_mode' => '',
                'perms' => [$short => ['groups' => [&$grp]]],
                'uid' => $dummy_uid,
              ]);
              $this->mmCreatePath->createPath($path);
              $test_page($usr, $path, $nodes, $secondaries, $stats[$test_uid]);

              $path[3] = new MMCreatePathCat([
                'name' => $long . ' by ad hoc group',
                'alias' => $short . 'adhocgroup',
                'default_mode' => '',
                'perms' => [$short => ['users' => [$test_uid]]],
                'uid' => $dummy_uid,
              ]);
              $this->mmCreatePath->createPath($path);
              $test_page($usr, $path, $nodes, $secondaries, $stats[$test_uid]);

              $path[3] = new MMCreatePathCat([
                'name' => $long . ' by virtual group',
                'alias' => $short . 'virtgroup',
                'default_mode' => '',
                'perms' => [$short => ['groups' => [&$vgrp]]],
                'uid' => $dummy_uid,
              ]);
              $this->mmCreatePath->createPath($path);
              $test_page($usr, $path, $nodes, $secondaries, $stats[$test_uid]);
            }
          }
        }

        // recycle bin
        $bin = mm_content_move_to_bin($path[1]->mmtid);
        mm_content_update_sort_queue();
        if (!is_numeric($bin)) {
          throw new \Exception("Error while moving '" . $path[1]->name . "' (" . $path[1]->mmtid . ") to recycle bin: $bin");
        }
        $path2 = $path;
        $path2[1] = new MMCreatePathCat([
          'name' => $path[1]->name . ' [recycled]',
          'mmtid' => $path[1]->mmtid,
        ]);
        unset($path2[3]);
        $test_page($usr, $path2, [], [], $stats[$test_uid]);  // subpage
        unset($path2[2]);
        $test_page($usr, $path2, [], [], $stats[$test_uid]);  // page
        $test_page($usr, [
          new MMCreatePathCat([
            'name' => $root_name . '/[recycle bin]',
            'mmtid' => $bin,
          ])
        ], [], [], $stats[$test_uid]);  // bin itself
        $err = mm_content_move_from_bin($path2[1]->mmtid, NULL, NULL, FALSE);
        mm_content_update_sort_queue();
        if (is_string($err)) {
          throw new \Exception("Error while moving '" . $path2[1]->name . "' (" . $path2[1]->mmtid . ") out of recycle bin: $err");
        }

        if (!self::generateBaseline) {
          $test_tree($usr, [$path[0], $path[1]], $stats[$test_uid]);
        }
      }

      // groups
      $tgrp = [
        &$grp[0],
        new MMCreatePathGroup([
          'name' => '~MM TEST',
        ]),
        new MMCreatePathGroup([
          'name' => 'owns',
          'alias' => 'owns',
          'default_mode' => '',
          'uid' => $test_uid,
        ]),
      ];
      $this->mmCreatePath->createPath($tgrp);
      $test_page($usr, $tgrp, [], [], $stats[$test_uid]);

      $tgrp[2] = new MMCreatePathGroup([
        'name' => 'inaccessible',
        'alias' => 'inaccessible',
        'default_mode' => Constants::MM_PERMS_APPLY,
        'uid' => $dummy_uid,
      ]);
      $this->mmCreatePath->createPath($tgrp);
      $test_page($usr, $tgrp, [], [], $stats[$test_uid]);

      $tgrp[3] = new MMCreatePathGroup([
        'name' => 'read by everyone child of inaccessible',
        'default_mode' => 'u,r',
        'uid' => $dummy_uid,
      ]);
      $this->mmCreatePath->createPath($tgrp);
      $test_page($usr, $tgrp, [], [], $stats[$test_uid]);
      unset($tgrp[3]);

      foreach (['read', 'write', 'add sub'] as $long) {
        $short = $long[0];
        $tgrp[2] = new MMCreatePathGroup([
          'name' => $long . ' by everyone',
          'alias' => $short . 'everyone',
          'default_mode' => "u,$short",
          'uid' => $dummy_uid,
        ]);
        $this->mmCreatePath->createPath($tgrp);
        $test_page($usr, $tgrp, [], [], $stats[$test_uid]);

        if ($test_uid) {
          $tgrp[2] = new MMCreatePathGroup([
            'name' => $long . ' by regular group',
            'alias' => $short . 'reggroup',
            'default_mode' => Constants::MM_PERMS_APPLY,
            'perms' => [$short => ['groups' => [&$grp]]],
            'uid' => $dummy_uid,
          ]);
          $this->mmCreatePath->createPath($tgrp);
          $test_page($usr, $tgrp, [], [], $stats[$test_uid]);

          $tgrp[2] = new MMCreatePathGroup([
            'name' => $long . ' by ad hoc group',
            'alias' => $short . 'adhocgroup',
            'default_mode' => Constants::MM_PERMS_APPLY,
            'perms' => [$short => ['users' => [$test_uid]]],
            'uid' => $dummy_uid,
          ]);
          $this->mmCreatePath->createPath($tgrp);
          $test_page($usr, $tgrp, [], [], $stats[$test_uid]);

          $tgrp[2] = new MMCreatePathGroup([
            'name' => $long . ' by virtual group',
            'alias' => $short . 'virtgroup',
            'default_mode' => Constants::MM_PERMS_APPLY,
            'perms' => [$short => ['groups' => [&$vgrp]]],
            'uid' => $dummy_uid,
          ]);
          $this->mmCreatePath->createPath($tgrp);
          $test_page($usr, $tgrp, [], [], $stats[$test_uid]);
        }
      }

      if (!self::generateBaseline) {
        mm_content_update_sort_queue();
        $test_tree($usr, [$tgrp[0], $tgrp[1]], $stats[$test_uid]);
      }

      // various standard locations
      $list = [
        1 => '[root]',
        mm_home_mmtid() => '[home]',
        mm_content_users_mmtid() => Constants::MM_ENTRY_NAME_USERS,
        mm_content_groups_mmtid() => Constants::MM_ENTRY_NAME_GROUPS,
        -65 => Constants::MM_ENTRY_NAME_USERS . '/A',
        1234567890 => '[non-existent]'
      ];
      foreach ($list as $tid => $label) {
        $path = [
          new MMCreatePathCat([
            'mmtid' => $tid,
            'name' => $label,
          ])
        ];
        $test_page($usr, $path, [], [], $stats[$test_uid]);
      }

      if ($nodes) {
        $node = Node::create(['type' => 'khsgkjhdsg']);
        $node->setTitle('unsaved node');
        $test_node($usr, '[unsaved node]', $node, $stats[$test_uid], 0, NULL, NULL);
      }

      $this->mmCreatePath->clearCaches();

      // delete nodes, so they get re-created for next user
      foreach ($nodes as $node) {
        $node->delete();
      }
    }

    if (self::generateBaseline) {
      $baseline = [];
      foreach ($stats as $stat) {
        $baseline[$stat['label']] = $stat['baseline'];
      }

      if ($fp = gzopen('/tmp/' . self::baselineFilename, 'w')) {
        gzputs($fp, Yaml::encode($baseline));
        gzclose($fp);
        fwrite(STDOUT, 'The baseline file was saved as /tmp/' . self::baselineFilename . "\n");
      }
      else {
        die('Could not create ' . self::baselineFilename . "\n");
      }
    }
    else {
      foreach ($stats as $uid => $stat) {
        if ($stat['fail']) {
          fwrite(STDOUT, sprintf("uid = %s (%s): %d of %d tests failed\n", $uid < 0 ? 'other' : $uid, $stat['label'], count($stat['fail']), $stat['count']));
          fwrite(STDOUT, join("\n", $stat['fail']) . "\n\n");
        }
      }
    }
  }

  public function failed($msg, $truth = NULL) {
    if (!is_null($truth)) {
      $msg = ($truth ? 'T' : 'F') . ": $msg";
    }
    $this->assertTrue(FALSE, $msg);
    return $msg;
  }

  /**
   * Create a node belonging to a particular user.
   *
   * @param AccountInterface $usr
   *   The node's owner.
   * @param $groups_w
   *   The node's group permissions.
   * @param $users_w
   *   The node's user permissions.
   * @param $nodes
   *   The list of all created nodes.
   * @param $title
   *   The node's title.
   * @param bool $everyone
   *   The world-writable flag.
   * @return bool
   *   Success or failure.
   * @throws \Exception
   */
  private static function saveNode(AccountInterface $usr, $groups_w, $users_w, &$nodes, $title, $everyone = FALSE) {
    $node = Node::create([
      'type' => 'story',
      'uid' => $usr->id(),
      'name' => $usr->getAccountName(),
      'title' => $title,
      'body' => 'body',
      'status' => 1,
      'comment' => 0,
      'mm_catlist_restricted' => [],
      'mm_catlist' => [],
      'owner' => $usr->id(),
      'groups_w' => $groups_w,
      'users_w' => $users_w,
      'others_w' => $everyone,
      'show_node_info' => 3,
      'revision' => FALSE,
      'mm_others_w_force' => TRUE,  // always allow "writable by everyone"
    ]);
    try {
      $node->save();
    }
    catch (\Exception $e) {
      print($e->getMessage());
      return FALSE;
    }
    $nodes[$node->id()] = Node::load($node->id());
    if (!$nodes[$node->id()]) {
      throw new \Exception('Could not reload node');
    }
    return TRUE;
  }

  private function deleteIfExists($arr) {
    if ($existing = mm_content_get($arr)) {
      mm_content_delete($existing[0]->mmtid);
      mm_content_update_sort_queue();
      $this->mmCreatePath->clearCaches();
    }
  }

};