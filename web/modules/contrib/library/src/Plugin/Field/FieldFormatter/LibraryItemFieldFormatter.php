<?php

namespace Drupal\library\Plugin\Field\FieldFormatter;

use Drupal\Component\Utility\SafeMarkup;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\library\Entity\LibraryAction;
use Drupal\library\Entity\LibraryItem;
use Drupal\library\LibraryItemInterface;

/**
 * Plugin implementation of the 'library_item_field_formatter' formatter.
 *
 * @FieldFormatter(
 *   id = "library_item_field_formatter",
 *   label = @Translation("Library item formatter"),
 *   field_types = {
 *     "library_item_field_type"
 *   }
 * )
 */
class LibraryItemFieldFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      // Implement default settings.
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    return [
      // Implement settings form.
    ] + parent::settingsForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [
      '#type' => 'table',
      '#title' => t('Library items'),
      '#header' => ['Barcode', 'Library status', 'Notes', 'Actions'],
    ];
    $rows = [];
    foreach ($items as $delta => $target) {
      $item = entity_load('library_item', $target->getValue()['target_id']);
      if ($item->barcode || $item->in_circulation) {
        $rows[$delta]['barcode'] = nl2br(SafeMarkup::checkPlain($item->get('barcode')->value));
        $rows[$delta]['library_status'] = $this->checkAvailability($item->get('in_circulation')->value, $item->get('library_status')->value);
        $rows[$delta]['notes'] = nl2br(SafeMarkup::checkPlain($item->get('notes')->value));

        $actions = $this->getActions($item->get('in_circulation')->value, $item->get('library_status')->value, $target->getValue()['target_id']);
        if ($actions) {
          $actions = [
            '#type' => 'operations',
            '#links' => $actions,
          ];
          $rows[$delta]['actions'] = drupal_render($actions);
        }
        else {
          unset($elements['#header'][3]);
        }
      }
    }
    $elements['#rows'] = $rows;
    return $elements;
  }

  /**
   * Check availability of item.
   *
   * @param int $in_circulation
   *   Circulation parameter.
   * @param int $status
   *   Status parameter.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup
   *   Formatted response for user.
   */
  protected function checkAvailability($in_circulation, $status) {
    if ($in_circulation == LibraryItemInterface::REFERENCE_ONLY) {
      return t('Reference only');
    }
    else {
      if ($status == LibraryItemInterface::ITEM_AVAILABLE) {
        return t('Item available');

      }
      else {
        return t('Item unavailable');
      }
    }
  }

  /**
   * Get actions.
   *
   * @param int $in_circulation
   *   Circulation parameter.
   * @param int $status
   *   Status parameter.
   * @param int $item
   *   Item.
   *
   * @return array
   *   Actions.
   */
  protected function getActions($in_circulation, $status, $item) {
    $actions = [];
    if ($in_circulation == LibraryItemInterface::IN_CIRCULATION) {
      if ($status == LibraryItemInterface::ITEM_AVAILABLE) {
        $availableActions = \Drupal::entityQuery('library_action')
          ->condition('action', LibraryAction::CHANGE_TO_UNAVAILABLE)
          ->execute();
        $actions = $this->renderAction($availableActions, $item);
      }
      else {
        $query = \Drupal::entityQuery('library_action');
        $group = $query->orConditionGroup()
          ->condition('action', LibraryAction::CHANGE_TO_AVAILABLE)
          ->condition('action', LibraryAction::NO_CHANGE);
        $availableActions = $query
          ->condition($group)
          ->execute();
        $actions = $this->renderAction($availableActions, $item);
        $actions['edit'] = $this->renderEditAction($item);
      }
    }
    return $actions;
  }

  /**
   * Render the available actions.
   *
   * @param \Drupal\library\Entity\LibraryAction[] $actions
   *   Actions to render.
   * @param int $item
   *   Item by ID.
   *
   * @return array
   *   Markup to render.
   */
  private function renderAction(array $actions, $item) {
    $output = [];
    foreach ($actions as $action) {
      $actionEntity = LibraryAction::load($action);
      if ($actionEntity) {
        $output[$actionEntity->id()] = [
          'title' => $actionEntity->label(),
          'url' => Url::fromRoute('library.single_transaction', ['action' => $actionEntity->id(), 'item' => $item]),
        ];
      }

    }
    return $output;
  }

  /**
   * Render the edit action.
   *
   * Separate, because conditional on existing transactions.
   *
   * @param int $item
   *   Item by ID.
   *
   * @return array
   *   Markup to render.
   */
  private function renderEditAction($item) {
    $output = [];
    $itemEntity = LibraryItem::load($item);
    if ($itemEntity) {
      $transaction = $itemEntity->getLatestTransaction();
      if (!empty($transaction)) {
        $output = [
          'title' => 'Edit note',
          'url' => Url::fromRoute('library.edit_transaction', ['transaction' => array_shift($transaction)]),
        ];
      }
    }
    return $output;
  }

}
