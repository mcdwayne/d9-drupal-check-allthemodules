<?php

namespace Drupal\monster_menus\Commands;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Session\UserSession;
use Drupal\monster_menus\CheckOrphanNodes;
use Drupal\monster_menus\Constants;
use Drupal\monster_menus\ValidateSortIndex;
use Drupal\user\Entity\User;
use Drupal\user\UserInterface;
use Drush\Commands\DrushCommands;

/**
 * A Drush commandfile.
 *
 * In addition to this file, you need a drush.services.yml
 * in root of your module, and a composer.json file that provides the name
 * of the services file to use.
 *
 * See these files for an example of injecting Drupal services:
 *   - http://cgit.drupalcode.org/devel/tree/src/Commands/DevelCommands.php
 *   - http://cgit.drupalcode.org/devel/tree/drush.services.yml
 */
class MonsterMenusCommands extends DrushCommands {

  /**
   * Adds a cache clear option for views data.
   *
   * @hook on-event cache-clear
   */
  public function cacheClear(&$types, $include_bootstrapped_types) {
    if ($include_bootstrapped_types && \Drupal::moduleHandler()->moduleExists('views')) {
      $types['views-data'] = function () {
        Cache::invalidateTags(['views_data']);
      };
    }
  }

  /**
   * Check for nodes not assigned to any MM page. See also mm-fix-orphan-nodes
   *
   * @command mm:check-orphan-nodes
   * @aliases mm-orphans,mmcon,mm-check-orphan-nodes
   */
  public function checkOrphanNodes() {
    \Drupal::service('monster_menus.check_orphan_nodes')->setOutputMode(CheckOrphanNodes::OUTPUT_MODE_DRUSH)->check(FALSE);
  }

  /**
   * Check for problems in the way menu entries are sorted. See also mm-fix-sort.
   *
   *
   * @command mm:check-sort
   * @aliases mm-check,mmcs,mm-check-sort
   */
  public function checkSort() {
    \Drupal::service('monster_menus.validate_sort_index')->setOutputMode(ValidateSortIndex::OUTPUT_MODE_DRUSH)->validate(FALSE);
  }

  /**
   * Create a user homepage or restore an existing one.
   *
   * @param $name_uid
   *   The name or UID of the user to create the homepage for
   *
   * @command mm:create-homepage
   * @aliases mmch,mm-create-homepage
   */
  public function createHomepage($name_uid) {
    /** @var UserInterface $account */
    if (!($account = is_numeric($name_uid) ? User::load((int)$name_uid) : user_load_by_name($name_uid))) {
      throw new \Exception(dt('Could not find the user @user', array('@user' => $name_uid)));
    }

    if (!$account->isActive()) {
      throw new \Exception(dt('User @name (@uid) is disabled.', array('@name' => $account->getAccountName(), '@uid' => $account->id())));
    }

    if (!empty($account->user_mmtid) && ($exists = mm_content_get($account->user_mmtid, Constants::MM_GET_FLAGS))) {
      $perms = mm_content_user_can($exists->mmtid);
      if ($perms[Constants::MM_PERMS_IS_RECYCLED]) {
        // Restore a page that is in the recycle bin.
        mm_content_move_from_bin($account->user_mmtid);
        $this->logger()->success(dt('Restored @name (@uid) from recycle bin to @path.', array('@name' => $account->getAccountName(), '@uid' => $account->id(), '@path' => mm_content_get_mmtid_url($account->user_mmtid)->toString())));
        return;
      }

      if (($parent = mm_content_get($exists->parent)) && $parent->name == Constants::MM_ENTRY_NAME_DISABLED_USER) {
        // Restore a homepage that is in the "disabled" tree?
        if ($this->io()->ask(dt('User @name (@uid) already has a disabled homepage at @path. Restore it or create a new one? (r/n)', array('@name' => $account->getAccountName(), '@uid' => $account->id(), '@path' => mm_content_get_mmtid_url($account->user_mmtid)->toString()))) == 'r') {
          mm_content_move($account->user_mmtid, mm_content_users_mmtid());
          $this->logger()->success(dt('Restored @name (@uid) from the disabled section to @path.', array('@name' => $account->getAccountName(), '@uid' => $account->id(), '@path' => mm_content_get_mmtid_url($account->user_mmtid)->toString())));
          return;
        }
        unset($exists->flags['user_home']);
        mm_content_set_flags($exists->mmtid, $exists->flags);
        $account->user_mmtid = NULL;
      }
      else {
        throw new \Exception(dt('User @name (@uid) already has a homepage at @path.', array('@name' => $account->getAccountName(), '@uid' => $account->id(), '@path' => mm_content_get_mmtid_url($account->user_mmtid)->toString())));
      }
    }
    // Create from scratch.
    mm_content_add_user($account);
    $this->logger()->success(dt('Created a homepage for @name (@uid) at @path.', array('@name' => $account->getAccountName(), '@uid' => $account->id(), '@path' => mm_content_get_mmtid_url($account->user_mmtid)->toString())));
  }

  /**
   * Move a user homepage to the disabled section.
   *
   * @param $name_uid
   *   The name or UID of the user to disable
   *
   * @command mm:disable-homepage
   * @aliases mmdh,mm-disable-homepage
   */
  public function disableHomepage($name_uid) {
    /** @var UserInterface $account */
    if (!($account = is_numeric($name_uid) ? User::load((int)$name_uid) : user_load_by_name($name_uid))) {
      throw new \Exception(dt('Could not find the user @user', array('@user' => $name_uid)));
    }

    if (empty($account->user_mmtid) || !($exists = mm_content_get($account->user_mmtid))) {
      throw new \Exception(dt('User @name (@uid) does not have a valid homepage.', array('@name' => $account->getAccountName(), '@uid' => $account->id())));
    }

    if (($parent = mm_content_get($exists->parent)) && $parent->name == Constants::MM_ENTRY_NAME_DISABLED_USER) {
      throw new \Exception(dt('User @name (@uid) already has a disabled homepage at @path.', array('@name' => $account->getAccountName(), '@uid' => $account->id(), '@path' => mm_content_get_mmtid_url($account->user_mmtid)->toString())));
    }

    if (!($err = mm_content_move_to_disabled($account->user_mmtid))) {
      $this->logger()->success(dt('Moved the homepage of @name (@uid) to @path.', array('@name' => $account->getAccountName(), '@uid' => $account->id(), '@path' => mm_content_get_mmtid_url($account->user_mmtid)->toString())));
      return;
    }
    throw new \Exception($err);
  }

  /**
   * Dump a section of the tree as CSV.
   *
   * @param $mmtid
   *   The Tree ID of the page to start at
   *
   * @command mm:dump
   * @aliases mmd,mm-dump
   */
  public function dump($mmtid) {
    \Drupal::service('monster_menus.dump_csv')->dump($mmtid);
  }

  /**
   * Export a section of the tree in a format that can be re-imported using
   * admin/mm/import.
   *
   * @param $mmtid
   *   The Tree ID of the page to start at
   * @param array $options
   *   An associative array of options whose values come from cli, aliases,
   *   config, etc.
   * @option nodes
   *   Include nodes
   *
   * @command mm:export
   * @aliases mmx,mm-export
   */
  public function export($mmtid, array $options = ['nodes' => null]) {
    module_load_include('inc', 'monster_menus', 'mm_import_export');
    // In case of error, don't save session as wrong user.
    $accountSwitcher = \Drupal::service('account_switcher');
    $accountSwitcher->switchTo(new UserSession(array('uid' => 1)));
    print mm_export($mmtid, $options['nodes']);
    // Re-enable session saving.
    $accountSwitcher->switchBack();
  }

  /**
   * List user homepages that can probably be deleted.
   *
   * @command mm:find-unmodified-homepages
   * @aliases mm-unmodified,mmfuh,mm-find-unmodified-homepages
   */
  public function findUnmodifiedHomepages() {
    $accountSwitcher = \Drupal::service('account_switcher');
    $accountSwitcher->switchTo(new UserSession(array('uid' => 1)));

    mm_content_find_unmodified_homepages(function ($item) {
      $this->output()->writeln($item->mmtid . ' ' . mm_content_get_mmtid_url($item->mmtid)->toString());
    });
  }

  /**
   * Check for nodes not assigned to any MM page and associate them with a page.
   *
   * @command mm:fix-orphan-nodes
   * @aliases mm-fix-orphans,mmfon,mm-fix-orphan-nodes
   */
  public function fixOrphanNodes() {
    \Drupal::service('monster_menus.check_orphan_nodes')->setOutputMode(CheckOrphanNodes::OUTPUT_MODE_DRUSH)->check(TRUE);
  }

  /**
   * Check for and fix any problems in the way menu entries are sorted.
   *
   * @command mm:fix-sort
   * @aliases mm-fix,mmfs,mm-fix-sort
   */
  public function fixSort() {
    \Drupal::service('monster_menus.validate_sort_index')->setOutputMode(ValidateSortIndex::OUTPUT_MODE_DRUSH)->validate(TRUE);
    mm_content_update_sort_queue();
  }

  /**
   * Mark all virtual groups as "dirty", so that they are regenerated during the
   * next cron run.
   *
   * @command mm:mark-vgroups
   * @aliases mm-mark,mmmv,mm-mark-vgroups
   */
  public function markVgroups() {
    mm_content_update_vgroup_view();
  }

  /**
   * Delete or recycle user homepages that have not changed.
   *
   * @command mm:purge-unmodified-homepages
   * @aliases mmpuh,mm-purge-unmodified-homepages
   */
  public function purgeUnmodifiedHomepages() {
    $accountSwitcher = \Drupal::service('account_switcher');
    $accountSwitcher->switchTo(new UserSession(array('uid' => 1)));

    $count = mm_content_find_unmodified_homepages(function ($item) {
      static $count = 0;
      print dt("Purged: @count\r", ['@count' => ++$count]);
      mm_content_recycle_enabled() ? mm_content_move_to_bin($item->mmtid) : mm_content_delete($item->mmtid);
    });
    $this->logger()->info(dt(mm_content_recycle_enabled() ? 'Moved @count homepage(s) to the recycle bin.' : 'Deleted @count homepage(s).', [
      '@count' => $count]));
  }

  /**
   * Update the membership of any "dirty" virtual groups immediately, instead of
   * during cron.
   *
   * @command mm:update-vgroups
   * @aliases mm-update,mmuv,mm-update-vgroups
   */
  public function updateVgroups() {
    mm_regenerate_vgroup();
  }

  /**
   * Update the membership of all virtual groups immediately.
   *
   * @command mm:regenerate-vgroups
   * @aliases mm-regen,mmrv,mm-regenerate-vgroups
   */
  public function regenerateVgroups() {
    mm_content_update_vgroup_view();
    mm_regenerate_vgroup();
  }

}
