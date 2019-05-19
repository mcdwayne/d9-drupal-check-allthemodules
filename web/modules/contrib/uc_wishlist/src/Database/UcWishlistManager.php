<?php

namespace Drupal\uc_wishlist\Database;

use Drupal\Core\Database\Connection;

/**
 * Defines an UcWishlistManager service.
 */
class UcWishlistManager {

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * Constructs an UcWishlistManager object.
   *
   * @param \Drupal\Core\Database\Connection $connection
   *   The connection object.
   */
  public function __construct(Connection $connection) {
    $this->connection = $connection;
  }

  /**
   * This function retrieves a list of wish lists created by
   * a specific user based on the wid, uid, wish list title
   * and expiration status.
   *
   * @return \Drupal\Core\Database\StatementInterface[]
   *   List of wish lists created by any particular user.
   */
  public function getAllWishlist() {
    $query = $this->connection->select('uc_wishlists', 'w');
    $query->leftJoin('users', 'u', 'w.uid = u.uid');
    $query->fields('w', [
      'wid',
      'uid',
      'title',
      'expiration',
    ]);
    $result = $query->execute();
    /**
     * @todo
     * extend default pager limit
     * $query->extend('PagerDefault')->limit(25)->execute();
     */
    return $result;
  }

  /**
   * This function is invoked to create a particular user wish
   * list referring to the custom fields involved.
   *
   * @param $fields
   *   variable that contains all the custom fields for creating a
   *   wish list.
   *
   * @param $values
   *   refers to the values of the wish list product parameters for
   *   creating a specific user wish list.
   *
   * @return array
   *   creates a user wish list.
   */
  public function createWishlist($fields, $values) {
    $this->connection->insert('uc_wishlists')->fields($fields, $values)->execute();
  }

  /**
   * This function is invoked to create/add related wish list
   * products with reference to the set of custom fields involved.
   *
   * @param $fields
   *   variable that contains all the custom fields for creating a
   *   wish list.
   *
   * @param $values
   *   refers to the values of the wish list product parameters for
   *   creating a specific user wish list.
   *
   * @return array
   *   displays a list of wish list products.
   */
  public function createWishlistProduct($fields, $values) {
    $this->connection->insert('uc_wishlist_products')->fields($fields, $values)->execute();
  }

  /**
   * This function retrieves the id of a particular wish list
   * by referring to the corresponding user id.
   *
   * @param $uid
   *   refers to the id used for retrieving specific wish
   *    lists created by a specific user.
   *
   * @return int
   *   The id of the desired wish list.
   */
  public function getWishlistIdByUser($uid) {
    $this->connection->query('SELECT wid FROM {uc_wishlists} WHERE uid = :uid;', [':uid' => $uid])->fetchField();
  }

  /**
   * This function retrieves a specific wish list product with
   * reference to the parameters wid, nid and data provided as
   * input.
   *
   * @param $wid
   *   refers to a particular wish list id used to retrieve a
   *   specific wish list item.
   *
   * @param $nid
   *   refers to a particular node id used to retrieve a specific
   *   wish list item.
   *
   * @param $data
   *   refers to the data associated with a particular wish list
   *   item.
   *
   * @return string
   *   A specific wishlist product by referring to the
   *   corresponding wid, nid and associated data.
   */
  public function getWishlistItem($wid, $nid, $data) {
    $this->connection->query("SELECT * FROM {uc_wishlist_products} WHERE wid = :wid AND nid = :nid AND data = :data", [':wid' => $wid, ':nid' => $nid, ':data' => serialize($data)]);
  }

  /**
   * This function displays the list of wish lists created by
   * a user by providing specific matching keywords referring to
   * a user name, wish list title and address.
   *
   * @param $keywords
   *   refers to the keywords to make three queries and return
   *   a new DatabaseCondition.
   *
   * @return string
   *   displays a list of user wish lists.
   */
  public function searchUserWishlist($keywords) {
    if (!empty($keywords)) {
      // Check for user, wish list title, or address matches.
      $query = $this->connection->select('uc_wishlists', 'w');
      $query->join('users', 'u', 'w.uid = u.uid');
      $query->fields('w', [
        'wid',
        'title',
      ]);
      $query->distinct();
      $query->condition(db_or()
        ->condition('u.name', '%' . $keywords . '%', 'LIKE')
        ->condition('w.title', '%' . $keywords . '%', 'LIKE')
        ->condition('w.address', '%' . $keywords . '%', 'LIKE'));
    }
    else {
      $query = $this->connection->select('uc_wishlists', 'w');
      $query->fields('w', [
        'wid',
        'title',
      ]);
    }
    $query->condition('w.private', 0, '=');
    $result = $query->orderBy('w.title')->execute;
    /**
     * @todo
     * extend default pager limit
     * $query->extend('PagerDefault')->limit(25)->execute();
     */
    return $result;
  }

  /**
   * This function retrieves a specific wish list by passing
   * a particular wid as the function parameter.
   *
   * @param int $wid
   *   Displays a particular wish list by retrieving
   *   its wid.
   *
   * @return array
   *   displays a specific wish list to the user.
   */
  public function getWishlist($wid) {
    $this->connection->query("SELECT * FROM {uc_wishlists} WHERE wid = :wid", [':wid' => $wid]);
  }

  /**
   * @param $rid
   *   refers to the user role id for a specific user
   *   account.
   *
   * @param $created
   *   refers to the created user for a particular user
   *   account.
   */
  public function selectAccounts($rid, $created) {
    $query = $this->connection->select('users', 'u');
    $query->innerJoin('user_roles', 'ur', 'u.uid = ur.uid');
    $query->where('ur.rid = :rid AND u.created < :created', [
      ':rid' => $rid,
      ':created' => $created,
    ]);
    return $query->execute();
  }

  /**
   * Any specific wish list product can be selected by invoking
   * this function for altering/removing it.
   *
   * @param $wid
   *   refers to a particular wish list id.
   *
   * @return string
   *   select(s) a any specific wishlist product.
   */
  public function selectWishlistProducts($wid) {
    $query = $this->connection->select('node', 'n');
    $query->join('uc_wishlist_products', 'w', 'n.nid = w.nid');
    $query->fields('w');
    $query->addField('n', 'vid');
    $query->condition('w.wid', $wid);
    $query->addTag('node_access');
    $query->join('node_field_data', 'f', 'n.nid = f.nid');
    $query->addField('f', 'title');

    $result = $query->execute();
    return $result;
  }

  /**
   * The wish list gets updated on altering the allotted quantity
   * of any product within the list by invoking this function and
   * passing the wpid and qty variables as parameters.
   *
   * @param $wpid
   *   refers to a particular wish list product id.
   *
   * @param $qty
   *   refers to the quantity assigned to the corresponding
   *   wish list product.
   *
   * @return int
   *   An updated wish list product quantity.
   */
  public function updateWantedQuantity($wpid, $qty) {
    $this->connection->update('uc_wishlist_products')->fields(['qty' => $qty])->condition('wpid', $wpid, '=')->execute();
  }

  /**
   * This function is invoked to delete a specific user
   * wish list by passing the wid as a parameter.
   *
   * @param $wid
   *   points to a particular wish list id.
   *
   * @return string
   *   Deletes a particular wish list.
   */
  public function deleteWishlist($wid) {
    $this->connection->delete('uc_wishlists')->condition('wid', $wid)->execute();
    $this->connection->delete('uc_wishlist_products')->condition('wid', $wid)->execute();
  }

  /**
   * Checks the availability of a particular product within a wish
   * list. Returns true, otherwise returns false if product is not
   * found.
   *
   * @param $wid
   *   refers to a particular wish list id.
   *
   * @param $pid
   *   refers to a particular product id.
   *
   * @return bool
   *   Returns true if product within list, otherwise false.
   */
  public function isProductInWishlist($wid, $pid) {
    $this->connection->query("SELECT * FROM {uc_wishlist_products} WHERE nid = :pid AND wid = :wid", [':pid' => $pid, ':wid' => $wid]);
    return $this->connection;
  }

  /**
   * Any specific wish list product can be removed from the list by
   * passing the wpid as a parameter.
   *
   * @param $pid
   *   refers to the id of a particular wish list product.
   *
   * @return string
   *   Removes the selected item from the wishlist.
   */
  public function removeItem($wpid) {
    $this->connection->delete('uc_wishlist_products')->condition('wpid', $wpid)->execute();
    return $this->connection;
  }

  /**
   * This function is invoked to remove a specific product
   * using the pid.
   *
   * @param $pid
   *   refers to the id of a particular product.
   *
   * @return string
   *   Removes the desired product using the pid.
   */
  public function removeProduct($pid) {
    $this->connection->delete('uc_wishlist_products')->condition('nid', $pid)->execute();
    return $this->connection;
  }

}
