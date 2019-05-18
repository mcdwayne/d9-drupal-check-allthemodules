<?php

namespace Drupal\civicrm_tools;

use Drupal\civicrm\Civicrm;

/**
 * Class CiviCrmContact.
 */
class CiviCrmContact implements CiviCrmContactInterface, CiviCrmEntityFormatInterface {

  /**
   * Drupal\civicrm\Civicrm definition.
   *
   * @var \Drupal\civicrm\Civicrm
   */
  protected $civicrm;

  /**
   * Drupal\civicrm_tools\CiviCrmApiInterface definition.
   *
   * @var \Drupal\civicrm_tools\CiviCrmApiInterface
   */
  protected $civiCrmApi;

  /**
   * Constructs a new CiviCrmContact object.
   */
  public function __construct(Civicrm $civicrm, CiviCrmApiInterface $civicrm_api) {
    $this->civicrm = $civicrm;
    $this->civiCrmApi = $civicrm_api;
  }

  /**
   * {@inheritdoc}
   */
  public function getFromSmartGroup($group_id, array $params) {
    // @todo fix method naming with single or multiple values, not both
    // Set as SmartGroup with value 1.
    $params['group'] = [$group_id => 1];
    return $this->civiCrmApi->getAll('Contact', $params);
  }

  /**
   * {@inheritdoc}
   */
  public function getFromGroups(array $groups) {
    $params['group'] = [
      'IN' => $groups,
    ];
    return $this->civiCrmApi->getAll('Contact', $params);
  }

  /**
   * {@inheritdoc}
   *
   * @todo test
   */
  public function getFromTags(array $tags) {
    $params['tag'] = [
      'IN' => $tags,
    ];
    return $this->civiCrmApi->getAll('Contact', $params);
  }

  /**
   * {@inheritdoc}
   */
  public function getFromUserId($uid, $domain_id) {
    $result = [];
    $matches = $this->civiCrmApi->get('UFMatch', ['uf_id' => $uid, 'domain_id' => $domain_id]);
    // @todo review get single contact
    if (!empty($matches)) {
      reset($matches);
      $contactId = $matches[key($matches)]['contact_id'];
      $contact = $this->civiCrmApi->get('Contact', ['contact_id' => $contactId]);
      if (!empty($contact)) {
        reset($contact);
        $result = $contact[key($contact)];
      }
    }
    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function getFromLoggedInUser($domain_id) {
    $uid = \Drupal::currentUser()->id();
    return $this->getFromUserId($uid, $domain_id);
  }

  /**
   * {@inheritdoc}
   */
  public function getUserFromContactId($cid, $domain_id) {
    $result = NULL;
    $matches = $this->civiCrmApi->get('UFMatch', ['contact_id' => $cid, 'domain_id' => $domain_id]);
    if (!empty($matches)) {
      reset($matches);
      $userId = $matches[key($matches)]['uf_id'];
      /** @var \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager */
      $entityTypeManager = \Drupal::service('entity_type.manager');
      $result = $entityTypeManager->getStorage('user')->load((int) $userId);
    }
    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function labelFormat(array $values) {
    $result = [];
    foreach ($values as $key => $value) {
      $result[$key] = $value['display_name'];
    }
    return $result;
  }

}
