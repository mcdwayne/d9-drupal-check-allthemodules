<?php

namespace Drupal\entity_gallery\Tests;

use Drupal\Core\Session\AccountInterface;
use Drupal\entity_gallery\GalleryTypeCreationTrait;
use Drupal\entity_gallery\EntityGalleryCreationTrait;
use Drupal\entity_gallery\EntityGalleryInterface;
use Drupal\simpletest\WebTestBase;

/**
 * Sets up page and article content types.
 */
abstract class EntityGalleryTestBase extends WebTestBase {

  use GalleryTypeCreationTrait {
    createGalleryType as drupalCreateGalleryType;
  }
  use EntityGalleryCreationTrait {
    getEntityGalleryByTitle as drupalGetEntityGalleryByTitle;
    createEntityGallery as drupalCreateEntityGallery;
  }

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('entity_gallery', 'datetime', 'node');

  /**
   * The entity gallery access control handler.
   *
   * @var \Drupal\Core\Entity\EntityAccessControlHandlerInterface
   */
  protected $accessHandler;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Create Basic page and Article node types.
    if ($this->profile != 'standard') {
      $this->drupalCreateContentType([
        'type' => 'page',
        'name' => 'Basic page',
        'display_submitted' => FALSE,
      ]);
      $this->drupalCreateContentType(['type' => 'article', 'name' => 'Article']);
    }
    $this->accessHandler = \Drupal::entityManager()->getAccessControlHandler('node');

    // Create Basic page and Article entity gallery types.
    $this->drupalCreateGalleryType(array(
      'type' => 'page',
      'name' => 'Basic page',
      'gallery_type' => 'node',
      'gallery_type_bundles' => [
        'page' => 'page',
      ],
      'display_submitted' => FALSE,
    ));
    $this->drupalCreateGalleryType(array(
      'type' => 'article',
      'name' => 'Article',
      'gallery_type' => 'node',
      'gallery_type_bundles' => [
        'article' => 'article',
      ],
    ));
    $this->accessHandler = \Drupal::entityManager()->getAccessControlHandler('entity_gallery');
  }

  /**
   * Asserts that entity gallery access correctly grants or denies access.
   *
   * @param array $ops
   *   An associative array of the expected entity gallery access grants for the
   *   entity gallery and account, with each key as the name of an operation
   *   (e.g. 'view', 'delete') and each value a Boolean indicating whether
   *   access to that operation should be granted.
   * @param \Drupal\entity_gallery\EntityGalleryInterface $entity_gallery
   *   The entity gallery object to check.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user account for which to check access.
   */
  function assertEntityGalleryAccess(array $ops, EntityGalleryInterface $entity_gallery, AccountInterface $account) {
    foreach ($ops as $op => $result) {
      $this->assertEqual($result, $this->accessHandler->access($entity_gallery, $op, $account), $this->entityGalleryAccessAssertMessage($op, $result, $entity_gallery->language()->getId()));
    }
  }

  /**
   * Asserts that entity gallery create access correctly grants or denies
   * access.
   *
   * @param string $bundle
   *   The entity gallery bundle to check access to.
   * @param bool $result
   *   Whether access should be granted or not.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user account for which to check access.
   * @param string|null $langcode
   *   (optional) The language code indicating which translation of the entity
   *   gallery to check. If NULL, the untranslated (fallback) access is checked.
   */
  function assertEntityGalleryCreateAccess($bundle, $result, AccountInterface $account, $langcode = NULL) {
    $this->assertEqual($result, $this->accessHandler->createAccess($bundle, $account, array(
      'langcode' => $langcode,
    )), $this->entityGalleryAccessAssertMessage('create', $result, $langcode));
  }

  /**
   * Constructs an assert message to display which entity gallery access was
   * tested.
   *
   * @param string $operation
   *   The operation to check access for.
   * @param bool $result
   *   Whether access should be granted or not.
   * @param string|null $langcode
   *   (optional) The language code indicating which translation of the entity
   *   gallery to check. If NULL, the untranslated (fallback) access is checked.
   *
   * @return string
   *   An assert message string which contains information in plain English
   *   about the entity gallery access permission test that was performed.
   */
  function entityGalleryAccessAssertMessage($operation, $result, $langcode = NULL) {
    return format_string(
      'Entity gallery access returns @result with operation %op, language code %langcode.',
      array(
        '@result' => $result ? 'true' : 'false',
        '%op' => $operation,
        '%langcode' => !empty($langcode) ? $langcode : 'empty'
      )
    );
  }

}
