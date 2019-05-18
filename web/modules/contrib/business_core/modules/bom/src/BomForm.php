<?php

namespace Drupal\bom;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form handler for the bom edit forms.
 */
class BomForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $bom = $this->entity;
    $insert = $bom->isNew();
    $bom->save();
    $bom_link = $bom->link($this->t('View'));
    $context = ['%title' => $bom->label(), 'link' => $bom_link];
    $t_args = ['%title' => $bom->link($bom->label())];

    if ($insert) {
      $this->logger('bom')->notice('Bom: added %title.', $context);
      drupal_set_message($this->t('Bom %title has been created.', $t_args));
    }
    else {
      $this->logger('bom')->notice('Bom: updated %title.', $context);
      drupal_set_message($this->t('Bom %title has been updated.', $t_args));
    }
  }

}
