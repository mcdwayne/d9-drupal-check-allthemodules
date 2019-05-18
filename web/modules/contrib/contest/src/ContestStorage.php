<?php

namespace Drupal\contest;

use Drupal\Core\Entity\Sql\SqlContentEntityStorage;

/**
 * Storage class for contests.
 */
class ContestStorage extends SqlContentEntityStorage implements ContestStorageInterface {
  const ADDR_MAX = 100;
  const CITY_MAX = 50;
  const DAY = 86400;
  const INT_MAX = 2147483647;
  const NAME_MAX = 50;
  const PHONE_MAX = 20;
  const STRING_MAX = 255;
  const ZIP_MAX = 5;

  /**
   * Fetch a list of matching users.
   *
   * @param string $name
   *   The user's name.
   *
   * @return array
   *   An array of user names.
   */
  public function autocomplete($name) {
    return $this->database->query("SELECT name, name FROM {users} WHERE name LIKE :name", [':name' => $name])->fetchAllKeyed();
  }

  /**
   * Clear the winner flag on a contest entry.
   *
   * @param int $cid
   *   The contest's ID.
   * @param int $uid
   *   The user's ID.
   *
   * @return Drupal\Core\Database\Query\Update
   *   A new Update object for this connection.
   */
  public function clearWinner($cid, $uid) {
    return $this->database
      ->update('contest_entry')
      ->fields(['winner' => 0])
      ->condition('cid', $cid)
      ->condition('uid', $uid)
      ->execute();
  }

  /**
   * Clear all winning flags for a contest.
   *
   * @param int $cid
   *   The contest's ID.
   *
   * @return Drupal\Core\Database\Query\Update
   *   A new Update object for this connection.
   */
  public function clearWinners($cid) {
    return $this->database
      ->update('contest_entry')
      ->fields(['winner' => 0])
      ->condition('cid', $cid)
      ->execute();
  }

  /**
   * Delete all the entries for a contest.
   *
   * @param ContestInterface $contest
   *   The contest entity.
   *
   * @return Drupal\Core\Database\Query\DeleteQuery
   *   A new DeleteQuery object for this connection.
   */
  public function deleteEntries(ContestInterface $contest) {
    return $this->database->delete('contest_entry')->condition('cid', $contest->id())->execute();
  }

  /**
   * Determine if the contest exists.
   *
   * @param int $nid
   *   The contest's ID.
   *
   * @return bool
   *   True if the contest exists.
   */
  public function exists($nid) {
    return (bool) $this->database->query("SELECT 1 FROM {contest} WHERE nid = :nid", [':nid' => $nid])->fetchField();
  }

  /**
   * Flush the cache table.
   *
   * @param string $cid
   *   The contest's ID.
   *
   * @return Drupal\Core\Database\Query\DeleteQuery
   *   A new DeleteQuery object for this connection.
   */
  public static function flushCache($cid) {
    return db_delete('cache_default')->condition('cid', "drupal-$cid", 'LIKE')->execute();
  }

  /**
   * Retrieve an array of contestant entry data for a contest.
   *
   * @param int $cid
   *   The contest's ID.
   *
   * @return object
   *   An array of database objects with the following properties:
   *   - uid (int) The user's ID.
   *   - name (string) The user's name.
   *   - mail (string) The user's email address.
   *   - qty (int) The number of entries.
   *   - winner (int) A winning entry flag.
   */
  public static function getContestants($cid) {
    $stmt = "
      SELECT
        DISTINCT(u.uid) AS 'uid',
        u.name AS 'name',
        u.mail as 'mail',
        e.qty AS 'qty',
        e.winner AS 'winner'
      FROM
        (
          SELECT
            uid,
            COUNT(uid) AS 'qty',
            cid,
            MAX(winner) AS 'winner'
          FROM
            {contest_entry}
          GROUP BY
            uid,
            cid
          ORDER BY
            winner DESC
        ) e
        JOIN {users_field_data} u ON u.uid = e.uid
      WHERE
        e.cid = :cid
        AND u.status = 1
      ORDER BY
        e.qty DESC,
        u.name ASC
    ";
    return db_query($stmt, [':cid' => $cid])->fetchAll();
  }

  /**
   * Retrieve all the data for a contest.
   *
   * @param int $id
   *   The contest's ID.
   *
   * @return object
   *   A row of contest data.
   */
  public static function getContest($id) {
    return db_query("SELECT * FROM {contest_field_data} WHERE id = :id", [':id' => $id])->fetchObject();
  }

  /**
   * Retrieve a list of prior winners within the disqualification time.
   *
   * @param int $created
   *   The maximum creation date of a winning entry.
   *
   * @return array
   *   An array of objects with the uid property.
   */
  public function getDqs($created) {
    return $this->database->query("SELECT uid FROM {contest_entry} WHERE created > :created AND winner ORDER BY winner", [':created' => $created])->fetchAll();
  }

  /**
   * Retrieve the contest end date.
   *
   * @param ContestInterface $contest
   *   The contest entity.
   *
   * @return int
   *   A Unix timestamp of the contest end date.
   */
  public function getEndDate(ContestInterface $contest) {
    return $this->database->queryRange("SELECT end FROM {contest_field_data} WHERE id = :id", 0, 1, [':id' => $contest->id()])->fetchField();
  }

  /**
   * Retrieve a uid for every entry into a contest.
   *
   * @param int $id
   *   The contest's ID.
   *
   * @return array
   *   An array of objects with the uid property, (with duplicates).
   */
  public function getEntries($id) {
    return $this->database->query("SELECT uid FROM {contest_entry} WHERE cid = :cid", [':cid' => $id])->fetchAll();
  }

  /**
   * Retrieve the number of contest entries.
   *
   * @param ContestInterface $contest
   *   The contest entity.
   *
   * @return int
   *   The number of contest entries.
   */
  public function getEntryCount(ContestInterface $contest) {
    return $this->database->queryRange("SELECT COUNT(uid) FROM {contest_entry} WHERE cid = :cid", 0, 1, [':cid' => $contest->id()])->fetchField();
  }

  /**
   * Retrieve the highest winning place.
   *
   * @param int $cid
   *   The contest's ID.
   *
   * @return int
   *   The highest winning place.
   */
  public function getMaxPlace($cid) {
    return $this->database->queryRange("SELECT winner FROM {contest_entry} WHERE cid = :cid AND winner ORDER BY winner DESC", 0, 1, [':cid' => $cid])->fetchField();
  }

  /**
   * Retrieve a list of running contests ordered by end date ascending.
   *
   * @return array
   *   An array of contest entities.
   */
  public function getMostRecentContest() {
    $query = \Drupal::entityQuery('contest')
      ->condition('start', REQUEST_TIME, '<=')
      ->condition('end', REQUEST_TIME, '>=')
      ->sort('end', 'ASC');

    return $this->loadMultiple($query->execute());
  }

  /**
   * Fetch a list of contest IDs.
   *
   * @param int $start
   *   The offset.
   * @param int $limit
   *   The max rows.
   *
   * @return array
   *   An array of contest IDs.
   */
  public function getNids($start = NULL, $limit = 0) {
    $stmt = "
      SELECT
        c.nid
      FROM
        {contest} c
        JOIN {node} n ON n.nid = c.nid
      WHERE
        n.status = 1
      ORDER BY
        n.sticky DESC,
        c.end ASC,
        c.start DESC,
        n.title ASC
    ";
    if (is_numeric($start) && (int) $limit) {
      return $this->database->queryRange($stmt, $start, $limit)->fetchCol();
    }
    return $this->database->query($stmt)->fetchCol();
  }

  /**
   * Retrieve the period, (entry frequency) for the contest.
   *
   * @param int $id
   *   The contest's ID.
   *
   * @return int
   *   The days between an entry in seconds.
   */
  public function getPeriod($id) {
    $args = [
      ':id'    => $id,
      ':end'   => REQUEST_TIME,
      ':start' => REQUEST_TIME,
    ];
    return $this->database->queryRange("SELECT period FROM {contest_field_data} WHERE id = :id AND start < :start AND :end < end", 0, 1, $args)->fetchField();
  }

  /**
   * Create an array of contest entry options.
   *
   * @return array
   *   An array of entry period options.
   */
  public static function getPeriodFormats() {
    return [
      self::DAY       => 'Ymd',
      self::DAY * 7   => 'YW',
      self::DAY * 30  => 'Ym',
      self::DAY * 365 => 'Y',
      self::INT_MAX   => t('Once')->__toString(),
    ];
  }

  /**
   * Create an array of contest entry options.
   *
   * @return array
   *   An array of entry period options.
   */
  public static function getPeriodOptions() {
    return [
      self::DAY       => t('Daily')->__toString(),
      self::DAY * 7   => t('Weekly')->__toString(),
      self::DAY * 30  => t('Monthly')->__toString(),
      self::DAY * 365 => t('Yearly')->__toString(),
      self::INT_MAX   => t('Once')->__toString(),
    ];
  }

  /**
   * Retrieve the winners publishing status.
   *
   * @param int $id
   *   The contest's ID.
   *
   * @return bool
   *   True if the winners are published.
   */
  public static function getPublished($id) {
    $args = [
      ':end' => REQUEST_TIME,
      ':id'  => $id,
    ];
    return (bool) db_query_range("SELECT publish_winners FROM {contest_field_data} WHERE end <= :end AND id = :id", 0, 1, $args)->fetchField();
  }

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
  public function getRandomEntry($cid, array $wids = []) {
    $stmt = "
      SELECT
        e.uid,
        e.created
      FROM
        {contest_entry} e
        JOIN {users_field_data} u ON u.uid = e.uid
      WHERE
        e.cid = :cid
        AND e.uid NOT IN (:wids[])
        AND u.status = 1
      ORDER BY
        RAND()
    ";
    return $this->database->queryRange($stmt, 0, 1, [':cid' => $cid, ':wids[]' => $wids])->fetchObject();
  }

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
  public function getRandomUserEntry($cid, $uid) {
    $stmt = "
      SELECT
        e.uid,
        e.created
      FROM
        {contest_entry} e
        JOIN {users_field_data} u ON u.uid = e.uid
      WHERE
        e.cid = :cid
        AND e.uid = :uid
        AND u.status = 1
      ORDER BY
        RAND()
    ";
    return $this->database->queryRange($stmt, 0, 1, [':cid' => $cid, ':uid' => $uid])->fetchObject();
  }

  /**
   * Retrieve the contest start date.
   *
   * @param ContestInterface $contest
   *   The contest entity.
   *
   * @return int
   *   A Unix timestamp of the contest start date.
   */
  public function getStartDate(ContestInterface $contest) {
    return $this->database->queryRange("SELECT start FROM {contest_field_data} WHERE id = :id", 0, 1, [':id' => $contest->id()])->fetchField();
  }

  /**
   * Retrieve the contest token data.
   *
   * @param int $id
   *   The contest ID.
   *
   * @return object
   *   A stdClass with the token data.
   */
  public static function getTokenData($id) {
    return db_query("SELECT start, end, period, places, sponsor_uid, sponsor_url FROM {contest_field_data} WHERE id = :id", [':id' => $id])->fetchObject();
  }

  /**
   * Retrieve a list of unique user IDs entered into a contest.
   *
   * @param int $cid
   *   The contest's ID.
   *
   * @return array
   *   An array of objects with the uid property, (no duplicates).
   */
  public function getUniqueEntries($cid = 0) {
    if ($cid) {
      return $this->database->query("SELECT uid FROM {contest_entry} WHERE cid = :cid GROUP BY uid", [':cid' => $cid])->fetchAll();
    }
    return $this->database->query("SELECT DISTINCT(uid) FROM {contest_entry}")->fetchAll();
  }

  /**
   * Retrieve the number of winners that have been selected for a contest.
   *
   * @param int $cid
   *   The contest's ID.
   *
   * @return int
   *   The number of winners for a particular contest.
   */
  public function getWinnerCount($cid) {
    return $this->database->queryRange("SELECT COUNT(uid) FROM {contest_entry} WHERE cid = :cid AND winner", 0, 1, [':cid' => $cid])->fetchField();
  }

  /**
   * Retrieve the contest winners in order.
   *
   * @param int $id
   *   The contest's ID.
   *
   * @return array
   *   An ordered array of user IDs to winning place.
   */
  public function getWinners($id) {
    return $this->database->query("SELECT uid FROM {contest_entry} WHERE cid = :cid AND winner ORDER BY winner ASC", [':cid' => $id])->fetchCol();
  }

  /**
   * Retrieve the date of the last entry for a user.
   *
   * @param array $fields
   *   An array of the contest fields.
   *
   * @return object
   *   A new InsertQuery object for this connection.
   */
  public function insert(array $fields) {
    return $this->database->insert('contest')->fields($fields)->execute();
  }

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
  public function latestUsrEntryDate($cid, $uid) {
    return $this->database->queryRange("SELECT created FROM {contest_entry} WHERE uid = :uid AND cid = :cid ORDER BY created DESC", 0, 1, [':cid' => $cid, ':uid' => $uid])->fetchField();
  }

  /**
   * Fetch the contest and sponsor fields.
   *
   * @param int $id
   *   The contest's ID.
   *
   * @return object
   *   An object with the contest and sponsor fields.
   */
  public function loadData($id) {
    $stmt = "
      SELECT
        u.name AS 'sponsor',
        u.mail AS 'sponsor_email',
        c.sponsor_url,
        c.start,
        c.end,
        c.places,
        c.period,
        c.publish_winners
      FROM
        {contest_field_data} c
        LEFT JOIN {users_field_data} u ON u.uid =  c.sponsor_uid
      WHERE
        c.id = :id
    ";
    return $this->database->queryRange($stmt, 0, 1, [':id' => $id])->fetchObject();
  }

  /**
   * Set the publish_winners flag on a contest to 1.
   *
   * @param int $id
   *   The contest's ID.
   *
   * @return Drupal\Core\Database\Query\Update
   *   A new Update object for this connection.
   */
  public function publishWinners($id) {
    return $this->database->update('contest_field_data')->fields(['publish_winners' => 1])->condition('id', $id)->execute();
  }

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
   * @return Drupal\Core\Database\Query\InsertQuery
   *   An InsertQuery object for this connection.
   */
  public function saveEntry(array $fields) {
    return $this->database->insert('contest_entry')->fields($fields)->execute();
  }

  /**
   * Set the place in the winner field.
   *
   * @param int $cid
   *   The contest's ID.
   * @param int $uid
   *   The user's ID.
   * @param int $place
   *   Winner's place (1, 2, 3...) Sequential -w- gaps, (1st, 3rd, 6th, etc.).
   * @param int $created
   *   The timestamp of the winning entry.
   *
   * @return Drupal\Core\Database\Query\Update
   *   A new Update object for this connection.
   */
  public function setWinner($cid, $uid, $place, $created) {
    return db_update('contest_entry')
      ->fields(['winner' => $place])
      ->condition('cid', $cid)
      ->condition('uid', $uid)
      ->condition('created', $created)
      ->execute();
  }

  /**
   * Set the publish_winners flag on a contest to 0.
   *
   * @param int $id
   *   The contest's ID.
   *
   * @return \Drupal\Core\Database\Query\Update
   *   A new Update object for this connection.
   */
  public function unpublishWinners($id) {
    return $this->database->update('contest_field_data')->fields(['publish_winners' => 0])->condition('id', $id)->execute();
  }

  /**
   * Update the contest.
   *
   * @param int $vid
   *   The contest's version ID.
   * @param array $fields
   *   The contest's fields.
   *
   * @return \Drupal\Core\Database\Query\Update
   *   A new Update object for this connection.
   */
  public function update($vid, array $fields) {
    return $this->database->update('contest')->fields($fields)->condition('vid', $vid)->execute();
  }

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
  public function usrEnteredOnce($cid, $uid) {
    return (bool) $this->database->queryRange("SELECT 1 FROM {contest_entry} WHERE uid = :uid AND cid = :cid", 0, 1, [':cid' => $cid, ':uid' => $uid])->fetchField();
  }

  /**
   * Determine if the user name exists.
   *
   * @param string $mail
   *   A prospective user email address.
   *
   * @return bool
   *   True of the user email exists.
   */
  public static function usrMailExists($mail) {
    return (bool) db_query_range("SELECT 1 FROM {users_field_data} WHERE mail = :mail OR init = :mail", 0, 1, [':mail' => $mail])->fetchField();
  }

  /**
   * Determine if the user name exists.
   *
   * @param string $name
   *   A prospective user name.
   *
   * @return bool
   *   True of the user name exists.
   */
  public static function usrNameExists($name) {
    return (bool) db_query_range("SELECT 1 FROM {users_field_data} WHERE name = :name", 0, 1, [':name' => $name])->fetchField();
  }

  /**
   * Fetch data to deciding available actions.
   *
   * @param int $id
   *   The contest's ID.
   *
   * @return object
   *   All the data for a contest including:
   *   - end (int) A Unix timestamp of the end of the contest.
   *   - places (int) The number of winning places in a contest.
   *   - publish_winners (int) A flag to indicate if the winners are published.
   */
  public function winnerPreSelect($id) {
    return $this->database->query("SELECT end, places, publish_winners FROM {contest_field_data} WHERE id = :id", [':id' => $id])->fetchObject();
  }

}
