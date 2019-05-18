<?php

/**
 * @file
 * Hooks provided by the Achievements module and how to implement them.
 */

/**
 * Implements hook_entity_insert().
 */
function achievements_example_entity_insert(Drupal\Core\Entity\EntityInterface $entity) {
  if ($entity->getEntityType()->id() == 'comment') {
    /** @var \Drupal\comment\Entity\Comment $comment */
    $comment = $entity;

    // Most achievements measure some kind of statistical data that must be
    // aggregated over time. To ease the storage of this data, the achievement
    // module ships with achievement_storage_get() and _set(), which allow you
    // to store custom data on a per-user basis. In most cases, the storage
    // location is the same as your achievement ID but in situations where you
    // have progressive achievements (1, 2, 50 comments etc.), it's better to
    // share a single place like we do below. If you don't use the achievement
    // ID for the storage location, you must specify the new location in the
    // 'storage' key of hook_achievements_info().
    //
    // Here we're grabbing the number of comments that the current commenter has
    // left in the past (which might be 0), adding 1 (for the current insert),
    // and then saving the count back to the database. The saved data is
    // serialized so can be as simple or as complex as you need it to be.
    $current_count = achievements_storage_get('comment-count', $comment->getOwnerId()) + 1;
    achievements_storage_set('comment-count', $current_count, $comment->getOwnerId());

    // Note that we're not checking if the user has previously earned any of the
    // commenting achievements yet. There are two reasons: first, we might want
    // to add another commenting achievement for, say, 250 comments, and if we
    // had stopped the storage counter above at 100, someone who currently has
    // 300 comments wouldn't unlock the achievement until they added another 150
    // nuggets of wisdom to the site. Generally speaking, if you need to store
    // incremental data for an achievement, you should continue to store it even
    // after the achievement has been unlocked - you never know if you'll want
    // to add a future milestone that will unlock on higher increments.
    //
    // Secondly, the achievements_unlocked() function below automatically checks
    // if the user has unlocked the achievement already, and will not reward it
    // again if they have. This saves you a small bit of repetitive coding but
    // you're welcome to use achievements_unlocked_already() as needed.
    //
    // Knowing that we currently have 50 and 100 comment achievements, we simply
    // loop through each milestone and check if the current count value matches.
    foreach ([1, 50, 100] as $count) {
      if ($current_count == $count) {
        achievements_unlocked('comment_count_' . $count, $comment->getOwnerId());
      }
    }
  }
}

/**
 * Implements hook_node_insert().
 */
function example_node_insert($node) {
  // Sometimes, we don't need any storage at all.
  if (format_date(REQUEST_TIME, 'custom', 'D') == 'Mon') {
    achievements_unlocked('node-mondays', $node->uid);
  }
}

/**
 * Implements hook_achievements_info_alter().
 *
 * Modify achievements that have been defined in hook_achievements_info().
 * Note that achievement info is cached so if you add or modify this hook,
 * also clear said achievement cache in admin/config/people/achievements.
 *
 * @param &$achievements
 *   An array of defined achievements returned by hook_achievements_info().
 */
function example_achievements_info_alter(&$achievements) {
  $achievements['comment-count-100']['points'] = 200;
}

/**
 * Implements hook_achievements_unlocked().
 *
 * This hook is invoked after an achievement has been unlocked and all
 * the relevant information has been stored or updated in the database.
 *
 * @param $achievement
 *   An array of information about the achievement.
 * @param $uid
 *   The user ID who has unlocked the achievement.
 */
function example_achievements_unlocked($achievement, $uid) {
  // Post to twitter or facebook, unlock an additional reward, etc., etc.
}

/**
 * Implements hook_achievements_locked().
 *
 * This hook is invoked after an achievement has been removed from a user and
 * all relevant information has been stored or updated in the database. This
 * is currently only possible from the UI at admin/config/people/achievements.
 *
 * @param $achievement
 *   An array of information about the achievement.
 * @param $uid
 *   The user ID who is having the achievement taken away.
 */
function example_achievements_locked($achievement, $uid) {
  // React to achievement removal. bad user, BaAaDdd UUserrRR!
}

/**
 * Implements hook_achievements_leaderboard_alter().
 *
 * Allows you to tweak or even recreate the leaderboard as required. The
 * default implementation creates leaderboards as HTML tables and this hook
 * allows you to modify that table (new columns, tweaked values, etc.) or
 * replace it entirely with a new render element.
 *
 * @param &$leaderboard
 *   An array of information about the leaderboard. Available keys are:
 *   - achievers: The database results from the leaderboard queries.
 *     Results are keyed by leaderboard type (top, relative, first, and
 *     recent) and then by user ID, sorted in proper ranking order.
 *   - block: A boolean indicating whether this is a block-based leaderboard.
 *   - type: The type of leaderboard being displayed. One of: top (the overall
 *     leaderboard displayed on achievements/leaderboard), relative (the
 *     current-user-centric version with nearby ranks), first (the first users
 *     who unlocked a particular achievement), and recent (the most recent
 *     users who unlocked a particular achievement).
 *   - render: A render array for use with drupal_render(). Default rendering
 *     is with #theme => table, and you'll receive all the keys necessary
 *     for that implementation. You're welcome to insert your own unique
 *     render, bypassing the default entirely.
 */
function example_achievements_leaderboard_alter(&$leaderboard) {
  if ($leaderboard['type'] == 'first') {
    $leaderboard['render']['#caption'] = t('Congratulations to our first 10!');
  }
}

/**
 * Implements hook_query_alter().
 *
 * The following database tags have been created for hook_query_alter() and
 * the matching hook_query_TAG_alter(). If you need more than this, don't
 * hesitate to create an issue asking for them.
 *
 * achievement_totals:
 *   Find the totals of all users in ranking order.
 *
 * achievement_totals_user:
 *   Find the totals of the passed user.
 *
 * achievement_totals_user_nearby:
 *   Find users nearby the ranking of the passed user.
 */
function example_query_alter(QueryAlterableInterface $query) {
  // Futz with morbus' logic. insert explosions and singularities.
}

/**
 * Implements hook_achievements_access_earn().
 *
 * Allows you to programmatically determine if a user has access to earn
 * achievements. We do already have an "earn achievements" permission, but
 * this allows more complex methods of determining that privilege. For an
 * example, see the achievements_optout.module, which allows a user to opt-out
 * of earning achievements, even if you've already granted them permission to.
 *
 * @param $uid
 *   The user ID whose access is being questioned.
 *
 * @return
 *   TRUE if the $uid can earn achievements, FALSE if they can't,
 *   or NULL if there's no change to the user's default access.
 */
function example_achievements_access_earn($uid) {
  $account = \Drupal::entityManager()->getStorage('user')->load($uid);
  if (format_username($account) == 'Morbus Iff') {
    // always, mastah, alllwayyYAYsss.
    return TRUE;
  }
}
