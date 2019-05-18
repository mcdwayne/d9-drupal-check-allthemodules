<?php

namespace Drupal\paragraphs_collection\Plugin\paragraphs\Behavior;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\Display\EntityViewDisplayInterface;
use Drupal\Core\Entity\EntityFieldManager;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\paragraphs\Entity\Paragraph;
use Drupal\paragraphs\Entity\ParagraphsType;
use Drupal\paragraphs\ParagraphInterface;
use Drupal\paragraphs\ParagraphsBehaviorBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a Paragraphs Lockable plugin.
 *
 * @ParagraphsBehavior(
 *   id = "lockable",
 *   label = @Translation("Lock editing"),
 *   description = @Translation("Prevent from editing without special permission."),
 *   weight = 3
 * )
 */
class ParagraphsLockablePlugin extends ParagraphsBehaviorBase {

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * Constructs a new ParagraphsLockablePlugin object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityFieldManager $entity_field_manager
   *   Entity Field Manager for base.
   * @param \Drupal\Core\Session\AccountProxyInterface $currentUser
   *   Current user for permissions scope.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityFieldManager $entity_field_manager, AccountProxyInterface $currentUser) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $entity_field_manager);
    $this->currentUser = $currentUser;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static($configuration, $plugin_id, $plugin_definition, $container->get('entity_field.manager'), $container->get('current_user'));
  }

  /**
   * {@inheritdoc}
   */
  public function buildBehaviorForm(ParagraphInterface $paragraph, array &$form, FormStateInterface $form_state) {
    // Only display if this plugin is enabled and the user has the permissions.
    $form['locked'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Lock content'),
      '#description' => $this->t('If selected this paragraph content will be locked for anyone without the appropriate permission.'),
      '#default_value' => $paragraph->getBehaviorSetting($this->getPluginId(), 'locked'),
      '#access' => $this->currentUser->hasPermission('administer lockable paragraph'),
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function view(array &$build, Paragraph $paragraphs_entity, EntityViewDisplayInterface $display, $view_mode) {
    // This implementation of view does nothing.
    // As this behavior affects the back end and not the display to the user.
  }

  /**
   * Check the access for the paragraph based on the lockable setting.
   *
   * Only add content access controls if:
   *  Not a view operation
   *  Lockable Behavior is enabled
   *  Lockable has the locked value set as true.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The paragraph entity.
   * @param string $operation
   *   The current operation.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The current logged in user.
   *
   * @return \Drupal\Core\Access\AccessResult
   *    The access result, will be false if the paragraph is locked
   *    and the user does not have the appropriate permission.
   */
  public static function determineParagraphAccess(EntityInterface $entity, $operation, AccountInterface $account) {

    /** @var ParagraphsType $paragraphs_type */
    $paragraphs_type = $entity->getParagraphType();

    if ($operation !== 'view' && $entity->getBehaviorSetting('lockable', 'locked') && $paragraphs_type->hasEnabledBehaviorPlugin('lockable')) {
      $accessResult = AccessResult::forbiddenIf(!$account->hasPermission('administer lockable paragraph'));
    }
    else {
      $accessResult = AccessResult::neutral();
    }

    $accessResult->addCacheableDependency($entity);
    $accessResult->addCacheableDependency($paragraphs_type);
    return $accessResult;

  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary(Paragraph $paragraph) {
    $locked = $paragraph->getBehaviorSetting($this->getPluginId(), 'locked');
    $unicode_closed_lock = json_decode('"\ud83d\udd12"');
    $summary = [
      [
        'value' => $unicode_closed_lock,
      ]
    ];
    return $locked ? $summary : [];
  }

}
