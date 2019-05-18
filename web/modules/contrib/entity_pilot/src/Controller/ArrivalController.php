<?php

namespace Drupal\entity_pilot\Controller;

use Drupal\Component\Utility\SafeMarkup;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityFormBuilderInterface;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\entity_pilot\AccountInterface;
use Drupal\entity_pilot\ArrivalInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides controller for Arrival page callbacks.
 */
class ArrivalController extends ControllerBase implements ContainerInjectionInterface {

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.manager'),
      $container->get('entity.form_builder')
    );
  }

  /**
   * Constructs a new Arrival controller.
   *
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   Entity manager service.
   * @param \Drupal\Core\Entity\EntityFormBuilderInterface $entity_form
   *   Entity form service.
   */
  public function __construct(EntityManagerInterface $entity_manager, EntityFormBuilderInterface $entity_form) {
    $this->entityManager = $entity_manager;
    $this->entityFormBuilder = $entity_form;
  }

  /**
   * Displays add arrival links for available accounts.
   *
   * @return array
   *   A render array for a list of the accounts that a arrival can be added
   *   for. If there is only one account, displays the add form for that
   *   account.
   */
  public function add() {
    $accounts = $this->entityManager()->getStorage('ep_account')->loadMultiple();
    // Only one account exists, display the add form for it.
    if ($accounts) {
      if (count($accounts) == 1) {
        $account = reset($accounts);
        return $this->addForm($account);
      }
      $account_links = [];
      /** @var \Drupal\entity_pilot\AccountInterface $account */
      foreach ($accounts as $account) {
        $account_links[$account->id()] = [
          'title' => $account->label(),
          'description' => $account->getDescription(),
          'url' => $account->toUrl('create-arrival'),
          'localized_options' => [],
        ];
      }
      return [
        '#theme' => 'admin_block_content',
        '#content' => $account_links,
      ];
    }
    else {
      return [
        [
          '#type' => 'markup',
          '#markup' => $this->t('No Entity Pilot accounts have been created.<br/>Please <a href=":url">create an account</a> first.', [
            ':url' => $this->url('entity_pilot.account_add'),
          ]),
        ],
      ];
    }
  }

  /**
   * Presents the arrival creation form.
   *
   * @param \Drupal\entity_pilot\AccountInterface $ep_account
   *   Account to create the arrival against.
   *
   * @return array
   *   A form array as expected by drupal_render().
   */
  public function addForm(AccountInterface $ep_account) {
    $arrival = $this->entityManager()->getStorage('ep_arrival')->create([
      'account' => $ep_account->id(),
    ]);
    return $this->entityFormBuilder()->getForm($arrival, 'add');
  }

  /**
   * Provides the page title for this controller.
   *
   * @param \Drupal\entity_pilot\AccountInterface $ep_account
   *   The account being used.
   *
   * @return string
   *   The page title.
   */
  public function getAddFormTitle(AccountInterface $ep_account) {
    return $this->t('Add arrival for %account', ['%account' => $ep_account->label()]);
  }

  /**
   * Provides the page title for this controller.
   *
   * @param \Drupal\entity_pilot\AccountInterface $ep_account
   *   The account being edited.
   *
   * @return string
   *   The page title.
   */
  public function titleAccountEdit(AccountInterface $ep_account) {
    return $this->t('Edit account %account', ['%account' => $ep_account->label()]);
  }

  /**
   * The _title_callback for the page that renders a single arrival.
   *
   * @param \Drupal\entity_pilot\ArrivalInterface $ep_arrival
   *   The current node.
   *
   * @return string
   *   The page title.
   */
  public function title(ArrivalInterface $ep_arrival) {
    return SafeMarkup::checkPlain($this->entityManager()->getTranslationFromContext($ep_arrival)->label());
  }

  /**
   * The _title_callback for the page that renders arrival approve form.
   *
   * @param \Drupal\entity_pilot\ArrivalInterface $ep_arrival
   *   The current node.
   *
   * @return string
   *   The page title.
   */
  public function titleApprove(ArrivalInterface $ep_arrival) {
    return $this->t('Approve %label', [
      '%label' => $this->entityManager()->getTranslationFromContext($ep_arrival)->label(),
    ]);
  }

  /**
   * The _title_callback for the page that renders arrival edit form.
   *
   * @param \Drupal\entity_pilot\ArrivalInterface $ep_arrival
   *   The current node.
   *
   * @return string
   *   The page title.
   */
  public function titleEdit(ArrivalInterface $ep_arrival) {
    return $this->t('Edit %label', [
      '%label' => $this->entityManager()->getTranslationFromContext($ep_arrival)->label(),
    ]);
  }

  /**
   * The _title_callback for the page that renders arrival queue form.
   *
   * @param \Drupal\entity_pilot\ArrivalInterface $ep_arrival
   *   The current node.
   *
   * @return string
   *   The page title.
   */
  public function titleQueue(ArrivalInterface $ep_arrival) {
    return $this->t('Queue %label', [
      '%label' => $this->entityManager()->getTranslationFromContext($ep_arrival)->label(),
    ]);
  }

}
