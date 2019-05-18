<?php

namespace Drupal\blocktabs\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\blocktabs\BlocktabsInterface;

/**
 * Provides an edit form for tab.
 */
class TabEditForm extends TabFormBase {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, BlocktabsInterface $blocktabs = NULL, $tab = NULL) {
    $form = parent::buildForm($form, $form_state, $blocktabs, $tab);

    $form['#title'] = $this->t('Edit %label tab', ['%label' => $this->tab->label()]);
    $form['actions']['submit']['#value'] = $this->t('Update tab');

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  protected function prepareTab($tab) {
    return $this->blocktabs->getTab($tab);
  }

}
