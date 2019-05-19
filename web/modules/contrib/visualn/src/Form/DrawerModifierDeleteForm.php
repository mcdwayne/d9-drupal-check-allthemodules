<?php

namespace Drupal\visualn\Form;

use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\visualn\Entity\VisualNDrawerInterface;

/**
 * Form for deleting an drawer modifier.
 */
class DrawerModifierDeleteForm extends ConfirmFormBase {

  /**
   * The image style containing the drawer modifier to be deleted.
   *
   * @var \Drupal\visualn\Entity\VisualNDrawerInterface
   */
  protected $visualnDrawer;

  /**
   * The drawer modifier to be deleted.
   *
   * @var \Drupal\visualn\Plugin\VisualNDrawerModifierInterface
   */
  protected $drawerModifier;

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to delete the @modifier modifier from the %sudrawer subdrawer?', ['%sudrawer' => $this->visualnDrawer->label(), '@modifier' => $this->drawerModifier->label()]);
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this->t('Delete');
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return $this->visualnDrawer->urlInfo('edit-form');
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'drawer_modifier_delete_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, VisualNDrawerInterface $visualn_drawer = NULL, $drawer_modifier = NULL) {
    $this->visualnDrawer = $visualn_drawer;
    $this->drawerModifier = $this->visualnDrawer->getModifier($drawer_modifier);

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->visualnDrawer->deleteDrawerModifier($this->drawerModifier);
    drupal_set_message($this->t('The drawer modifier %name has been deleted.', ['%name' => $this->drawerModifier->label()]));
    $form_state->setRedirectUrl($this->visualnDrawer->urlInfo('edit-form'));
  }

}

