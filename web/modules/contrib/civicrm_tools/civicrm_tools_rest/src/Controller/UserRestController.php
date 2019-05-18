<?php

namespace Drupal\civicrm_tools_rest\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\user\Entity\User;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\civicrm_tools\CiviCrmContactInterface;
use Drupal\Core\Cache\CacheableJsonResponse;
use Drupal\Core\Cache\CacheableMetadata;

/**
 * Class UserRestController.
 */
class UserRestController extends ControllerBase {

  /**
   * Drupal\civicrm_tools\CiviCrmContactInterface definition.
   *
   * @var \Drupal\civicrm_tools\CiviCrmContactInterface
   */
  protected $civicrmToolsContact;

  /**
   * Constructs a new UserRestController object.
   */
  public function __construct(CiviCrmContactInterface $civicrm_tools_contact) {
    $this->civicrmToolsContact = $civicrm_tools_contact;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('civicrm_tools.contact')
    );
  }

  /**
   * Get users by CiviCRM Group.
   *
   * @param int $group_id
   *   The CiviCRM group id.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   Users list JSON response.
   */
  public function getUsersByGroup($group_id) {
    $result = [
      'data' => [],
      'message' => $this->t('No users for group id @group_id.', [
        '@group_id' => $group_id,
      ]),
    ];

    $config = \Drupal::configFactory()->get('civicrm_tools_rest.settings');
    $groupLimit = $config->get('group_limit');

    if (empty($groupLimit) || in_array($group_id, $groupLimit)) {
      $contacts = [];
      $groupType = '';

      // Try to get contacts from a Group.
      try {
        $contacts = $this->civicrmToolsContact->getFromGroups([$group_id]);
        $groupType = 'group';
      }
      catch (\Exception $exception) {
        $result['message'] = $exception->getMessage();
      }

      // If not contacts were found, look for contacts in a Smart Group.
      if (empty($contacts)) {
        try {
          $contacts = $this->civicrmToolsContact->getFromSmartGroup($group_id, []);
          $groupType = 'smart group';
        }
        catch (\Exception $exception) {
          $result['message'] = $exception->getMessage();
        }
      }

      if (!empty($contacts)) {
        foreach ($contacts as $cid => $contact) {
          $user = $this->civicrmToolsContact->getUserFromContactId((int) $cid, CIVICRM_DOMAIN_ID);
          // A contact match could not exist for a user.
          if (!empty($user) && $user instanceof User) {
            $resultUser = $user->toArray();
            // Remove password.
            unset($resultUser['pass']);
            $result['data'][] = $resultUser;
          }
        }
        $result['message'] = $this->t('@count users fetched from the @group_type @group_id', [
          '@count' => count($result['data']),
          '@group_type' => $groupType,
          '@group_id' => $group_id,
        ]);
      }
    }

    if (!in_array($group_id, $groupLimit)) {
      $result['message'] = $this->t('Access to the group @group_id has been restricted via the configuration.', [
        '@group_id' => $group_id,
      ]);
    }

    // Add the user_list cache tag to update when users are updated.
    $cacheMetadata = new CacheableMetadata();
    // @todo needs to know modification to groups or set at least a max age.
    $cacheMetadata->setCacheTags(['user_list']);
    // User url.path for group id.
    $cacheMetadata->setCacheContexts(['url.path']);
    $response = new CacheableJsonResponse($result);
    $response->addCacheableDependency($cacheMetadata);

    return $response;
  }

}
