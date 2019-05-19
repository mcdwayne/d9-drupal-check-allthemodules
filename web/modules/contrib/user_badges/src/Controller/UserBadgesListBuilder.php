<?php

/**
 * @file
 * Contains \Drupal\user_badges\Controller\UserBadgesListBuilder.
 */

namespace Drupal\user_badges\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\user\UserInterface;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Form\FormInterface;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityManagerInterface;

/**
 * Class UserBadgesListBuilder.
 *
 * @package Drupal\user_badges\Controller
 */
class UserBadgesListBuilder extends ControllerBase implements FormInterface{

  /**
   * The form builder.
   *
   * @var \Drupal\Core\Form\FormBuilderInterface
   */
  protected $formBuilder;

  /**
   * User object of currently being edited user.
   *
   * @var \Drupal\user\User
   */
  protected $user;

  /**
   * The term storage handler.
   *
   * @var \Drupal\taxonomy\TermStorageInterface
   */
  protected $storageController;

  /**
   * Constructs a new BlockListBuilder object.

   * @param \Drupal\Core\Form\FormBuilderInterface $form_builder
   *   The form builder.
   */
  public function __construct(FormBuilderInterface $form_builder, EntityManagerInterface $entity_manager) {
    $this->formBuilder = $form_builder;
    $this->storageController = $entity_manager->getStorage('badge');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('form_builder'),
      $container->get('entity.manager')
    );
  }

  /**
   * Provides listing of user badges.
   *
   * @param \Drupal\user\UserInterface $user
   *   The user account.
   *
   * @return array
   *   The block list as a renderable array.
   */
  public function badgesListing(UserInterface $user) {
    $this->user = $user;
    return $this->formBuilder->getForm($this);
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'badge_display_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\user\Entity\User $user */
    $user = $this->user;

    $form['badge'] = array(
      '#type' => 'table',
      '#header' => array(t('Label'), t('Weight'), t('Select Weight')),
      '#empty' => t('There are no badges yet'),
    );
    $field_item_list = $user->get('field_user_badges');
    foreach ($field_item_list->filterEmptyItems() as $index => $item) {
      /** @var \Drupal\user_badges\Entity\Badge $badge */
      $badge = $item->get('entity')->getValue();
      $form['badge'][$badge->id()]['#attributes']['class'][] = 'draggable';
      $form['badge'][$badge->id()]['#weight'] = $badge->getBadgeWeight();

      // Some table columns containing raw markup.
      $form['badge'][$badge->id()]['label'] = array(
        '#plain_text' => $badge->label(),
      );
      $form['badge'][$badge->id()]['id'] = array(
        '#plain_text' => $badge->getBadgeWeight(),
      );

      // TableDrag: Weight column element.
      $form['badge'][$badge->id()]['weight'] = array(
        '#type' => 'weight',
        '#title' => t('Weight for @title', array('@title' => $badge->label())),
        '#title_display' => 'invisible',
        '#default_value' => $badge->getBadgeWeight(),
        '#attributes' => array('class' => array('user-badges-order-weight')),
      );
    }
    $form['badge']['#tabledrag'][] = array(
      'action' => 'order',
      'relationship' => 'sibling',
      'group' => 'user-badges-order-weight',
    );

    $form['actions'] = array(
      '#tree' => FALSE,
      '#type' => 'actions',
    );
    $form['actions']['submit'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Save Badges'),
      '#button_type' => 'primary',
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    // No validation.
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $badge_ids = array_keys($form_state->getValue('badge'));
    /** @var \Drupal\user\Entity\User $user */
    $user = $this->user;
    $user->set('field_user_badges', $badge_ids);
    $user->save();
  }

}
