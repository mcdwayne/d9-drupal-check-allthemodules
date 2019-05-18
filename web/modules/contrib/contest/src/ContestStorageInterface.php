<?php

namespace Drupal\contest;

use Drupal\Core\Entity\EntityStorageInterface;

/**
 * Defines a common interface for contest entity controller classes.
 */
interface ContestStorageInterface extends EntityStorageInterface {

  /**
   * Clear the winner flag on a contest entry.
   *
   * @param int $cid
   *   The contest's ID.
   * @param int $uid
   *   The user's ID.
   *
   * @return \Drupal\Core\Database\Query\Update
   *   A new Update object for this connection.
   */
  public function clearWinner($cid, $uid);

  /**
   * Clear all winning flags for a contest.
   *
   * @param int $cid
   *   The contest's ID.
   *
   * @return \Drupal\Core\Database\Query\Update
   *   A new Update object for this connection.
   */
  public function clearWinners($cid);

  /**
   * Delete all the entries for a contest.
   *
   * @param ContestInterface $contest
   *   The contest entity.
   *
   * @return DeleteQuery
   *   The DeleteQuery object returned by db_delete().
   */
  public function deleteEntries(ContestInterface $contest);

  /**
   * Retrieve an array of contestant entry data for a contest.
   *
   * @param int $id
   *   The contest's ID.
   *
   * @return object
   *   An array of database objects with the following properties:
   *   - uid (int) The user's ID.
   *   - name (string) The user's name.
   *   - mail (string) The user's email address.
   *   - qty (int) The number of entries.
   *   - created (int) An entry creation date.
   *   - winner (int) A winning entry flag.
   */
  public static function getContestants($id);

  /**
   * Retrieve a uid for every entry into a contest.
   *
   * @param int $id
   *   The contest's ID.
   *
   * @return array
   *   An array of objects with the uid property, (with duplicates).
   */
  public function getEntries($id);

  /**
   * Retrieve a list of unique user IDs entered into a contest.
   *
   * @param int $id
   *   The contest's ID.
   *
   * @return array
   *   An array of objects with the uid property, (no duplicates).
   */
  public function getUniqueEntries($id);

  /**
   * Retrieve the contest winners in order.
   *
   * @param int $id
   *   The contest's ID.
   *
   * @return array
   *   An ordered array of user IDs to winning place.
   */
  public function getWinners($id);

  /**
   * Fetch a list of prior winners within the DQ time, (default is 89 days).
   *
   * @param int $created
   *   The maximum creation date of a winning entry.
   *
   * @return array
   *   An array of objects with the uid property.
   */
  public function getDqs($created);

  /**
   * Retrieve the contest end date.
   *
   * @param ContestInterface $contest
   *   The contest entity.
   *
   * @return int
   *   A Unix timestamp of the contest end date.
   */
  public function getEndDate(ContestInterface $contest);

  /**
   * Retrieve the number of contest entries.
   *
   * @param ContestInterface $contest
   *   The contest entity.
   *
   * @return int
   *   The number of contest entries.
   */
  public function getEntryCount(ContestInterface $contest);

  /**
   * Retrieve the highest winning place.
   *
   * @param int $cid
   *   The contest's ID.
   *
   * @return int
   *   The highest winning place.
   */
  public function getMaxPlace($cid);

  /**
   * Retrieve a list of running contests ordered by end date ascending.
   *
   * @return array
   *   An array of contest entities.
   */
  public function getMostRecentContest();

  /**
   * Retrieve the period, (entry frequency) for the contest.
   *
   * @param int $id
   *   The contest's ID.
   *
   * @return int
   *   The days between an entry in seconds.
   */
  public function getPeriod($id);

  /**
   * Retrieve the winners publishing status.
   *
   * @param int $id
   *   The contest's ID.
   *
   * @return bool
   *   True if the winners are published.
   */
  public static function getPublished($id);

  /**
   * Retrieve a random entry for a contest.
   *
   * @param int $cid
   *   The contest's ID.
   * @param array $wids
   *   An array of user IDs from recent winners.
   *
   * @return object
   *   An object with the uid and created properties.
   */
  public function getRandomEntry($cid, array $wids);

  /**
   * Retrieve a random entry for a particular user entered into a contest.
   *
   * @param int $cid
   *   The contest's ID.
   * @param int $uid
   *   The user's ID.
   *
   * @return object
   *   An object with the uid and created properties.
   */
  public function getRandomUserEntry($cid, $uid);

  /**
   * Retrieve the contest start date.
   *
   * @param ContestInterface $contest
   *   The contest entity.
   *
   * @return int
   *   A Unix timestamp of the contest start date.
   */
  public function getStartDate(ContestInterface $contest);

  /**
   * Retrieve the number of winners that have been selected for a contest.
   *
   * @param int $cid
   *   The contest's ID.
   *
   * @return int
   *   The number of winners for a particular contest.
   */
  public function getWinnerCount($cid);

  /**
   * Retrieve the date of the last entry for a user.
   *
   * @param int $cid
   *   The contest's ID.
   * @param int $uid
   *   The user's ID.
   *
   * @return int
   *   The Unix timstamp of a user's last entry.
   */
  public function latestUsrEntryDate($cid, $uid);

  /**
   * Set the publish_winners flag on a contest to 1.
   *
   * @param int $id
   *   The contest's ID.
   *
   * @return \Drupal\Core\Database\Query\Update
   *   A new Update object for this connection.
   */
  public function publishWinners($id);

  /**
   * Insert an entry for a contest.
   *
   * @param array $fields
   *   An array of entry fields
   *   - cid (int) The contest's ID.
   *   - uid (int) The user's ID
   *   - created (int) The current time.
   *   - ip (string) The IP the entry was submitted by.
   *
   * @return \Drupal\Core\Database\Query\InsertQuery
   *   An InsertQuery object for the active database.
   */
  public function saveEntry(array $fields);

  /**
   * Set the place in the winner field.
   *
   * @param int $cid
   *   The contest's ID.
   * @param int $uid
   *   The user's ID.
   * @param int $place
   *   The winner's place, (1, 2, 3). Sequentialwith gaps, (1st, 3rd, 6th).
   * @param int $created
   *   The timestamp of the winning entry.
   *
   * @return \Drupal\Core\Database\Query\Update
   *   A new Update object for this connection.
   */
  public function setWinner($cid, $uid, $place, $created);

  /**
   * Set the publish_winners flag on a contest to 0.
   *
   * @param int $id
   *   The contest's ID.
   *
   * @return \Drupal\Core\Database\Query\Update
   *   A new Update object for this connection.
   */
  public function unpublishWinners($id);

  /**
   * Determine if the user has an entry in a contest.
   *
   * @param int $cid
   *   The contest's ID.
   * @param int $uid
   *   The user's ID.
   *
   * @return bool
   *   True of the user has an entry in the contest.
   */
  public function usrEnteredOnce($cid, $uid);

  /**
   * Determine if the user name exists.
   *
   * @param string $name
   *   A prospective user name.
   *
   * @return bool
   *   True of the user name exists.
   */
  public static function usrNameExists($name);

  /**
   * Fetch data relavent to deciding available actions that can be performed.
   *
   * @param int $id
   *   The contest's ID.
   *
   * @return object
   *   All the data for a contest including:
   *   - end (int) A Unix timestamp of the end of the contest.
   *   - places (int) The number of winning places in a contest.
   *   - publish_winners (int) The published winners flag.
   */
  public function winnerPreSelect($id);

}
