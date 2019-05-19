<?php

namespace Drupal\social_course;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\group\Entity\GroupInterface;
use Drupal\node\NodeInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\group\Entity\GroupContent;
use Drupal\Core\Extension\ModuleHandler;
use Drupal\social_course\Entity\CourseEnrollmentInterface;
use Symfony\Component\Validator\Exception\InvalidArgumentException;

/**
 * Class CourseWrapper.
 *
 * @package Drupal\social_course
 */
class CourseWrapper implements CourseWrapperInterface {

  /**
   * The group entity.
   *
   * @var \Drupal\group\Entity\GroupInterface
   */
  protected $group;

  /**
   * List of available to handle bundles.
   *
   * @var array
   */
  protected static $bundles = ['course_basic', 'course_advanced'];

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * Module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandler
   */
  protected $moduleHandler;

  /**
   * CourseWrapper constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   * @param \Drupal\Core\Extension\ModuleHandler $moduleHandler
   *   The module handler.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, AccountInterface $current_user, ModuleHandler $moduleHandler) {
    $this->entityTypeManager = $entity_type_manager;
    $this->currentUser = $current_user;
    $this->moduleHandler = $moduleHandler;
  }

  /**
   * {@inheritdoc}
   */
  public function setCourse(GroupInterface $group) {
    if (!in_array($group->bundle(), self::$bundles)) {
      throw new InvalidArgumentException(sprintf('%s bundle is not allowed. Allowed bundles: %s', $group->bundle(), implode(', ', self::$bundles)));
    }

    $this->group = $group;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getCourse() {
    return $this->group;
  }

  /**
   * {@inheritdoc}
   */
  public function getCoursePublishedStatus() {
    return $this->getCourse()->get('status')->value;
  }

  /**
   * {@inheritdoc}
   */
  public static function setAvailableBundles(array $bundles) {
    if (!$bundles) {
      throw new InvalidArgumentException('$bundles argument cannot be empty');
    }

    self::$bundles = $bundles;
  }

  /**
   * {@inheritdoc}
   */
  public static function getAvailableBundles() {
    return self::$bundles;
  }

  /**
   * {@inheritdoc}
   */
  public function courseIsSequential() {
    $value = $this->getCourse()->get('field_course_order')->value;

    return ((int) $value === SOCIAL_COURSE_SEQUENTIAL);
  }

  /**
   * {@inheritdoc}
   */
  public function getCourseStatus(AccountInterface $account) {
    $storage = $this->entityTypeManager->getStorage('course_enrollment');
    $entities = $storage->loadByProperties([
      'uid' => $account->id(),
      'gid' => $this->getCourse()->id(),
    ]);

    if (!$entities) {
      return CourseEnrollmentInterface::NOT_STARTED;
    }

    foreach ($entities as $entity) {
      if ($entity->getStatus() === CourseEnrollmentInterface::IN_PROGRESS) {
        return CourseEnrollmentInterface::IN_PROGRESS;
      }
    }

    return CourseEnrollmentInterface::FINISHED;
  }

  /**
   * {@inheritdoc}
   */
  public function getSectionStatus(NodeInterface $node, AccountInterface $account) {
    $storage = $this->entityTypeManager->getStorage('course_enrollment');
    $entities = $storage->loadByProperties([
      'uid' => $account->id(),
      'gid' => $this->getCourse()->id(),
      'sid' => $node->id(),
    ]);

    if (!$entities) {
      return CourseEnrollmentInterface::NOT_STARTED;
    }

    foreach ($entities as $entity) {
      /** @var \Drupal\social_course\Entity\CourseEnrollmentInterface $entity */
      if ($entity->getStatus() === CourseEnrollmentInterface::IN_PROGRESS) {
        return CourseEnrollmentInterface::IN_PROGRESS;
      }
    }

    return CourseEnrollmentInterface::FINISHED;
  }

  /**
   * {@inheritdoc}
   */
  public function sectionAccess(NodeInterface $node, AccountInterface $account, $op) {
    $access = AccessResult::neutral();

    switch ($op) {
      case 'start':
      case 'continue':
        // Only members can start a course.
        if (!$this->getCourse()->getMember($account)) {
          return AccessResult::forbidden()->cachePerUser();
        }

        if (!$this->getCourse()->get('field_course_opening_status')->value) {
          return AccessResult::forbidden()->addCacheTags($this->getCourse()->getCacheTags());
        }

        // Load all sections in course.
        $sections = $this->getSections();
        // Allow start first section if user has not started course yet.
        $section = current($sections);
        $access = $access->orIf(AccessResult::allowedIf($section->id() == $node->id() && !$section->get('field_course_section_content')->isEmpty()));
        break;

      case 'bypass':
        $access = $access->orIf(AccessResult::allowedIf($node->getOwnerId() === $account->id()));
        $access = $access->orIf(AccessResult::allowedIfHasPermissions($account, [
          'bypass node access',
          'administer nodes',
        ], 'OR'));
        break;

      case 'view':
        $storage = $this->entityTypeManager->getStorage('course_enrollment');
        $course_enrollments = $storage->loadByProperties([
          'uid' => $account->id(),
          'gid' => $this->getCourse()->id(),
          'sid' => $node->id(),
        ]);

        // Anonymous users can't ever view sections because we can't track
        // their progress.
        $access = $access->orIf(AccessResult::forbiddenIf($account->isAnonymous()));

        $access = $access->orIf(AccessResult::allowedIf($course_enrollments));
        $access = $access->orIf(AccessResult::allowedIf($node->getOwnerId() === $account->id()));
        $access = $access->orIf(AccessResult::allowedIf(!$this->courseIsSequential()));

        if (!$node->isPublished() || !$this->getCourse()->get('field_course_opening_status')->value) {
          $has_permission = AccessResult::allowedIfHasPermission($account, 'administer nodes');
          $access = $access->andIf(
            AccessResult::allowedIf(
              $node->getOwnerId() === $account->id() && $account->hasPermission('view own unpublished content')
            )->orIf($has_permission)
          );
        }
        break;
    }

    return $access
      ->cachePerUser()
      ->cachePerPermissions();
  }

  /**
   * {@inheritdoc}
   */
  public function materialAccess(NodeInterface $node, AccountInterface $account, $op) {
    $access = AccessResult::neutral();

    switch ($op) {
      case 'view':
        $section = $this->getSectionFromMaterial($node);
        $storage = $this->entityTypeManager->getStorage('course_enrollment');
        $course_enrollments = $storage->loadByProperties([
          'uid' => $account->id(),
          'gid' => $this->getCourse()->id(),
          'sid' => $section->id(),
          'mid' => $node->id(),
        ]);

        // Anonymous users can't ever view materials because we can't track
        // their progress.
        $access = $access->orIf(AccessResult::forbiddenIf($account->isAnonymous()));

        $access = $access->orIf(AccessResult::allowedIf($course_enrollments));
        $access = $access->orIf(AccessResult::allowedIf($node->getOwnerId() === $account->id()));
        $access = $access->orIf(AccessResult::allowedIf(!$this->courseIsSequential()));
        $access = $access->orIf(AccessResult::allowedIfHasPermissions($account, [
          'bypass node access',
          'administer nodes',
        ], 'OR'));
        break;
    }

    return $access;
  }

  /**
   * {@inheritdoc}
   */
  public function getSections() {
    if ($this->group) {
      $sections = $this->group->getContentEntities('group_node:course_section');
    }
    if (empty($sections)) {
      return [];
    }

    // Set id of section as key of array.
    foreach ($sections as $key => $section) {
      unset($sections[$key]);

      if ($section instanceof NodeInterface) {
        $sections[$section->id()] = $section;
      }
    }

    // Sort sections by weight to get first section.
    uasort($sections, function ($a, $b) {
      if ($a->get('field_course_section_weight')->value == $b->get('field_course_section_weight')->value) {
        return 0;
      }

      return $a->get('field_course_section_weight')->value > $b->get('field_course_section_weight')->value ? 1 : -1;
    });

    return $sections;
  }

  /**
   * {@inheritdoc}
   */
  public function getMaterials(NodeInterface $node = NULL) {
    $sections = $this->getSections();
    $nodes = [];

    if (!$node) {
      $nodes = $sections;
    }
    elseif (isset($sections[$node->id()])) {
      $nodes = [$node];
    }

    $ids = [];

    foreach ($nodes as $node) {
      foreach ($node->get('field_course_section_content')->getValue() as $item) {
        $ids[] = $item['target_id'];
      }
    }

    if (!$ids) {
      return [];
    }

    $this->moduleHandler->alter('social_course_materials', $ids);

    if (!$ids) {
      return [];
    }

    $storage = $this->entityTypeManager->getStorage('node');
    $materials = $storage->loadMultiple($ids);

    return $materials;
  }

  /**
   * {@inheritdoc}
   */
  public function setCourseFromSection(NodeInterface $node) {
    $entities = GroupContent::loadByEntity($node);

    if ($entity = current($entities)) {
      $group = $entity->getGroup();
      $this->setCourse($group);
    }

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setCourseFromMaterial(NodeInterface $node) {
    if ($entity = $this->getSectionFromMaterial($node)) {
      $this->setCourseFromSection($entity);
    }

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getSectionFromMaterial(NodeInterface $node) {
    $storage = $this->entityTypeManager->getStorage('node');
    $entitites = $storage->loadByProperties([
      'type' => 'course_section',
      'field_course_section_content' => $node->id(),
    ]);

    return current($entitites);
  }

  /**
   * {@inheritdoc}
   */
  public function getSection(NodeInterface $node, $offset) {
    $sections = $this->getSections();
    $number = array_search($node->id(), array_keys($sections));
    $element = array_slice($sections, $number + $offset, 1);

    return current($element);
  }

  /**
   * {@inheritdoc}
   */
  public function getMaterial(NodeInterface $node, $offset) {
    $section = $this->getSectionFromMaterial($node);
    $materials = $this->getMaterials($section);
    $number = array_search($node->id(), array_keys($materials));
    $element = array_slice($materials, $number + $offset, 1);

    return current($element);
  }

  /**
   * {@inheritdoc}
   */
  public function getSectionNumber(NodeInterface $node) {
    $sections = $this->getSections();
    $number = array_search($node->id(), array_keys($sections));

    return $number;
  }

  /**
   * {@inheritdoc}
   */
  public function getMaterialNumber(NodeInterface $node) {
    $section = $this->getSectionFromMaterial($node);
    $materials = $this->getMaterials($section);
    $number = array_search($node->id(), array_keys($materials));

    return $number;
  }

  /**
   * {@inheritdoc}
   */
  public function getFinishedMaterials(NodeInterface $node, AccountInterface $account) {
    $storage = $this->entityTypeManager->getStorage('course_enrollment');
    $materials = $storage->loadByProperties([
      'gid' => $this->group->id(),
      'sid' => $node->id(),
      'uid' => $account->id(),
      'status' => CourseEnrollmentInterface::FINISHED,
    ]);
    return $materials;
  }

}
