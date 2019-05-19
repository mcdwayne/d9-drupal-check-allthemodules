<?php

namespace Drupal\skillset_inview\Form;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Class DeleteForm.
 *
 * @property null|object skill
 * @package Drupal\skillset_inview\Form
 */
class DeleteSkill extends ConfirmFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'skillset_inview_delete';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Delete <big><q>@name</q></big> from your skillset?', array('@name' => strip_tags($this->skill->name)));
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url('skillset_inview.order');
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->t("You're rated @percent% in this, are you sure you want to remove it", array('@percent' => $this->skill->percent));
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this->t('Yes, Delete it!');
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelText() {
    return $this->t('Cancel');
  }

  /**
   * {@inheritdoc}
   *
   * @param object $skill
   *   (optional) The skill object of the item to be deleted.
   */
  public function buildForm(array $form, FormStateInterface $form_state, $skill = NULL) {
    $this->skill = $skill;
    if (!$skill) {
      // If ID doesn't exist redirst to main order form.
      drupal_set_message($this->t('Sorry, there is no skill with that ID.'), 'warning');
      return $this->redirect('skillset_inview.order');
    }
    $form = parent::buildForm($form, $form_state);
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $db = \Drupal::service('database');
    $db->delete('skillset_inview')
      ->condition('id', $this->skill->id)
      ->execute();
    // Strip tag as input may have had html in it and would show as escaped text.
    drupal_set_message($this->t('The skill <q>@name</q> has been deleted.', [
      '@name' => strip_tags($this->skill->name),
    ]), 'status');

    Cache::invalidateTags(['rendered']);
    $form_state->setRedirect('skillset_inview.order');
  }

}
