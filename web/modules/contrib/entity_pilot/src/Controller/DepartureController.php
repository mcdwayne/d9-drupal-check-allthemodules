<?php

namespace Drupal\entity_pilot\Controller;

use Drupal\Component\Utility\SafeMarkup;
use Drupal\Core\Controller\ControllerBase;
use Drupal\entity_pilot\AccountInterface;
use Drupal\entity_pilot\DepartureInterface;

/**
 * Provides controller for Departure page callbacks.
 */
class DepartureController extends ControllerBase {

  /**
   * Displays add departure links for available accounts.
   *
   * @return array
   *   A render array for a list of the accounts that a departure can be added
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
          'url' => $account->toUrl('create-departure'),
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
   * Presents the departure creation form.
   *
   * @param \Drupal\entity_pilot\AccountInterface $ep_account
   *   Account to create the departure against.
   *
   * @return array
   *   A form array as expected by drupal_render().
   */
  public function addForm(AccountInterface $ep_account) {
    $departure = $this->entityManager()->getStorage('ep_departure')->create([
      'account' => $ep_account->id(),
    ]);
    return $this->entityFormBuilder()->getForm($departure);
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
    return $this->t('Add departure for %account', ['%account' => $ep_account->label()]);
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
   * The _title_callback for the page that renders a single departure.
   *
   * @param \Drupal\entity_pilot\DepartureInterface $ep_departure
   *   The current node.
   *
   * @return string
   *   The page title.
   */
  public function title(DepartureInterface $ep_departure) {
    return SafeMarkup::checkPlain($this->entityManager()->getTranslationFromContext($ep_departure)->label());
  }

  /**
   * The _title_callback for the page that renders departure approve form.
   *
   * @param \Drupal\entity_pilot\DepartureInterface $ep_departure
   *   The current node.
   *
   * @return string
   *   The page title.
   */
  public function titleApprove(DepartureInterface $ep_departure) {
    return $this->t('Approve %label', [
      '%label' => $this->entityManager()->getTranslationFromContext($ep_departure)->label(),
    ]);
  }

  /**
   * The _title_callback for the page that renders departure edit form.
   *
   * @param \Drupal\entity_pilot\DepartureInterface $ep_departure
   *   The current node.
   *
   * @return string
   *   The page title.
   */
  public function titleEdit(DepartureInterface $ep_departure) {
    return $this->t('Edit %label', [
      '%label' => $this->entityManager()->getTranslationFromContext($ep_departure)->label(),
    ]);
  }

  /**
   * The _title_callback for the page that renders departure queue form.
   *
   * @param \Drupal\entity_pilot\DepartureInterface $ep_departure
   *   The current node.
   *
   * @return string
   *   The page title.
   */
  public function titleQueue(DepartureInterface $ep_departure) {
    return $this->t('Queue %label', [
      '%label' => $this->entityManager()->getTranslationFromContext($ep_departure)->label(),
    ]);
  }

}
