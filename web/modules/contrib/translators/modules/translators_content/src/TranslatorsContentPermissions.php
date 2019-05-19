<?php

namespace Drupal\translators_content;

use Drupal\content_translation\ContentTranslationManagerInterface;
use Drupal\content_translation\ContentTranslationPermissions;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class TranslatorsContentPermissions.
 *
 * @package Drupal\translators_content
 */
class TranslatorsContentPermissions extends ContentTranslationPermissions {

  /**
   * Module name.
   */
  const MODULE_NAME = 'translators_content';
  /**
   * Permissions suffix.
   */
  const PERMISSIONS_SUFFIX = '(in translation skills)';
  /**
   * Config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * {@inheritdoc}
   */
  public function __construct(
    EntityManagerInterface $entity_manager,
    ContentTranslationManagerInterface $content_translation_manager,
    ConfigFactoryInterface $factory
  ) {
    parent::__construct($entity_manager, $content_translation_manager);
    $this->configFactory = $factory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.manager'),
      $container->get('content_translation.manager'),
      $container->get('config.factory')
    );
  }

  /**
   * Check if the permissions are enabled at the module config page.
   *
   * @return bool
   *   TRUE - enabled, FALSE otherwise.
   */
  private function isPermissionsEnabled() {
    $config = $this->configFactory->get('translators.settings');
    return (bool) $config->get('enable_translators_content_permissions');
  }

  /**
   * {@inheritdoc}
   */
  public function contentPermissions() {
    $permissions = [];

    if (!$this->isPermissionsEnabled()) {
      return $permissions;
    }

    $this->addStaticPermissions($permissions);

    // Create a translate permission for each enabled entity type
    // and (optionally) bundle.
    $definitions = $this->entityManager->getDefinitions();
    foreach ($definitions as $entity_type_id => $entity_type) {
      if ($permission_granularity = $entity_type->getPermissionGranularity()) {
        if ($permission_granularity === 'bundle') {
          $bundles_info = $this->entityManager
            ->getBundleInfo($entity_type_id);
          foreach ($bundles_info as $bundle => $bundle_info) {
            $this->addBundleGlobalPermissions($permissions, $bundle, $bundle_info['label']);
          }
        }
      }
    }
    return $permissions;
  }

  /**
   * Add static permissions.
   *
   * @param array &$permissions
   *   Permissions array to be used for putting in.
   */
  protected function addStaticPermissions(array &$permissions) {
    $permissions['translators_content create content translations'] = $this->t('Create translations (in translation skills)');
    $permissions['translators_content update content translations'] = $this->t('Edit translations (in translation skills)');
    $permissions['translators_content delete content translations'] = $this->t('Delete translations (in translation skills)');
  }

  /**
   * Add bundle global permissions.
   *
   * @param array &$permissions
   *   Permissions array.
   * @param string $bundle_id
   *   Entity bundle ID.
   * @param string $bundle_label
   *   Entity bundle label.
   */
  protected function addBundleGlobalPermissions(array &$permissions, $bundle_id, $bundle_label = NULL) {
    // Fallback for the cases if bundle has no label,
    // in this case we gonna use it's ID(machine name).
    if (empty($bundle_label)) {
      $bundle_label = $bundle_id;
    }
    // Prepare translation arguments array.
    $t = [
      '@bundle_label' => $bundle_label,
      '@suffix'       => static::PERMISSIONS_SUFFIX,
    ];
    // Add "global" permissions for the basic actions on the entity bundles.
    $permissions[static::MODULE_NAME . " create $bundle_id content"] = [
      'title' => $this->t('@bundle_label: Create new content @suffix', $t),
    ];
    $permissions[static::MODULE_NAME . " edit own $bundle_id content"] = [
      'title' => $this->t('@bundle_label: Edit own content @suffix', $t),
    ];
    $permissions[static::MODULE_NAME . " edit any $bundle_id content"] = [
      'title' => $this->t('@bundle_label: Edit any content @suffix', $t),
    ];
    $permissions[static::MODULE_NAME . " delete own $bundle_id content"] = [
      'title' => $this->t('@bundle_label: Delete own content @suffix', $t),
    ];
    $permissions[static::MODULE_NAME . " delete any $bundle_id content"] = [
      'title' => $this->t('@bundle_label: Delete any content @suffix', $t),
    ];
  }

}
