<?php

namespace Drupal\adva_example_provider\Plugin\adva\AccessProvider;

use Drupal\adva\Plugin\adva\AccessProvider;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\ContentEntityTypeInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides an example access provider.
 *
 * Implements author access to content.
 *
 * @AccessProvider(
 *   id = "example",
 *   label = @Translation("Example Access Provider"),
 *   operations = {
 *     "edit",
 *     "update",
 *     "delete",
 *   },
 * )
 */
class ExampleAccessProvider extends AccessProvider {

  /**
   * Current Entity Field Manager.
   *
   * @var Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * Current Entity Field Manager.
   *
   * @var Drupal\Core\Entity\EntityTypeBundleInfoInterface
   */
  protected $entityBundleManager;

  /**
   * {@inheritdoc}
   */
  public static function appliesToType(EntityTypeInterface $entityType) {
    if ($entityType instanceof ContentEntityTypeInterface) {
      $entityTypeId = $entityType->id();
      // This provider should apply to any entity type with a uid field.
      $entityFieldManager = \Drupal::service("entity_field.manager");
      $fields = $entityFieldManager->getBaseFieldDefinitions($entityTypeId);
      if (isset($fields["uid"])) {
        return TRUE;
      }
      $entityBundleManager = \Drupal::service("entity_type.bundle.info");
      $bundles = $entityBundleManager->getBundleInfo($entityTypeId);
      foreach ($bundles as $bundle) {
        $fields = $this->entityFieldManager->getFieldDefinitions($entityTypeId, $bundle);
        if (isset($fields["uid"])) {
          return TRUE;
        }
      }
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getAccessRecords(EntityInterface $entity) {
    // Grant all to author.
    return [
      [
        'realm' => 'example_uid',
        'gid' => $entity->get("uid")->entity->id(),
        'grant_view' => 1,
        'grant_update' => 1,
        'grant_delete' => 1,
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getAccessGrants($operation, AccountInterface $account) {
    // Provide author with grant to own content.
    return [
      "example_uid" => [
        $account->id(),
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigForm(array $form, FormStateInterface $form_state) {
    $form["sample_config"] = [
      "#type" => "textarea",
      "#title" => "Example config data.",
      "#description" => $this->t("This is an example field."),
      "#default_value" => $this->configuration["sample_config"] ?: "",
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigForm(array &$form, FormStateInterface $form_state) {
    // Optionally run form validate here.
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigForm(array &$form, FormStateInterface $form_state) {
    // Save consumer config.
    $this->configuration["sample_config"] = $form_state->getValue("sample_config");
  }

}
