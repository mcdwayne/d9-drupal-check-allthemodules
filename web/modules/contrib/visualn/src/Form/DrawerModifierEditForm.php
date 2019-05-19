<?php

namespace Drupal\visualn\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\visualn\Entity\VisualNDrawerInterface;

/**
 * Provides an edit form for drawer modifiers.
 */
class DrawerModifierEditForm extends DrawerModifierFormBase {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, VisualNDrawerInterface $visualn_drawer = NULL, $drawer_modifier = NULL) {
    $form = parent::buildForm($form, $form_state, $visualn_drawer, $drawer_modifier);

    $form['#title'] = $this->t('Edit %label modifier', ['%label' => $this->drawerModifier->label()]);
    $form['actions']['submit']['#value'] = $this->t('Update modifier');

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  protected function prepareDrawerModifier($drawer_modifier) {
    return $this->visualnDrawer->getModifier($drawer_modifier);
  }

}

