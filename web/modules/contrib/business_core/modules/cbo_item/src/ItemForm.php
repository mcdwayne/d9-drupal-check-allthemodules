<?php

namespace Drupal\cbo_item;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form handler for the item edit forms.
 */
class ItemForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form['advanced'] = [
      '#type' => 'vertical_tabs',
      '#attributes' => ['class' => ['entity-meta']],
      '#weight' => 99,
    ];
    $form = parent::form($form, $form_state);

    if (isset($form['relationships'])) {
      $form['relationships_group'] = [
        '#type' => 'details',
        '#group' => 'advanced',
        '#title' => $this->t('Relationships'),
      ];
      $form['relationships']['#group'] = 'relationships_group';
    }
    if (isset($form['attachments'])) {
      $form['attachments_group'] = [
        '#type' => 'details',
        '#group' => 'advanced',
        '#title' => $this->t('Attachments'),
        '#weight' => 90,
      ];
      $form['attachments']['#group'] = 'attachments_group';
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $item = $this->entity;
    $insert = $item->isNew();
    $item->save();
    $item_link = $item->link($this->t('View'));
    $context = ['%title' => $item->label(), 'link' => $item_link];
    $t_args = ['%title' => $item->link($item->label())];

    if ($insert) {
      $this->logger('item')->notice('Item: added %title.', $context);
      drupal_set_message($this->t('Item %title has been created.', $t_args));
    }
    else {
      $this->logger('item')->notice('Item: updated %title.', $context);
      drupal_set_message($this->t('Item %title has been updated.', $t_args));
    }
  }

}
