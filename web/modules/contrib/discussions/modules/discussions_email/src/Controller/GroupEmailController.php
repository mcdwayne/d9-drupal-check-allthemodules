<?php

namespace Drupal\discussions_email\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityFormBuilderInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\group\Entity\GroupInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Controller for group email.
 *
 * @ingroup discussions_email
 */
class GroupEmailController extends ControllerBase {

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * The entity form builder.
   *
   * @var \Drupal\Core\Entity\EntityFormBuilderInterface
   */
  protected $entityFormBuilder;

  /**
   * Constructs a new GroupEmailController.
   *
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   * @param \Drupal\Core\Entity\EntityFormBuilderInterface $entity_form_builder
   *   The entity form builder.
   */
  public function __construct(AccountInterface $current_user, EntityFormBuilderInterface $entity_form_builder) {
    $this->currentUser = $current_user;
    $this->entityFormBuilder = $entity_form_builder;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('current_user'),
      $container->get('entity.form_builder')
    );
  }

  /**
   * Provides the form for a group member's group preferences.
   *
   * @param \Drupal\group\Entity\GroupInterface $group
   *   The group to set preferences for.
   *
   * @return array
   *   A group preferences form.
   */
  public function preferencesForm(GroupInterface $group) {
    // Use group content edit form to configure group preferences.
    $group_content = $group->getMember($this->currentUser)->getGroupContent();
    $form = $this->entityFormBuilder->getForm($group_content, 'edit');

    // Hide non-preference fields.
    $form['group_roles']['#access'] = FALSE;
    $form['group_requires_approval']['#access'] = FALSE;

    // Hide group content delete button.
    $form['actions']['delete']['#access'] = FALSE;

    return $form;
  }

  /**
   * The _title_callback for the group preferences form route.
   *
   * @param \Drupal\group\Entity\GroupInterface $group
   *   The group to set preferences for.
   *
   * @return string
   *   The page title.
   */
  public function preferencesFormTitle(GroupInterface $group) {
    return $this->t('%label preferences', ['%label' => $group->label()]);
  }

}
