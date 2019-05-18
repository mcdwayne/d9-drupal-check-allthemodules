<?php

/**
 * @file
 * Contains Drupal\google_adwords_path\Form\GoogleAdwordsPathConfigDeleteForm.
 */

namespace Drupal\google_adwords_path\Form;

use Drupal\Core\Entity\EntityConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\google_adwords_path\GoogleAdwordsPathTracker;

/**
 * Builds the form to delete Google AdWords Path Config entities.
 */
class GoogleAdwordsPathConfigDeleteForm extends EntityConfirmFormBase {
  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to delete %name?', array('%name' => $this->entity->label()));
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url('entity.google_adwords_path_config.collection');
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
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->entity->delete();

    drupal_set_message(
      $this->t('content @type: deleted @label.',
        [
          '@type' => $this->entity->bundle(),
          '@label' => $this->entity->label()
        ]
        )
    );

    $form_state->setRedirectUrl($this->getCancelUrl());

    /**
     * @var \Drupal\google_adwords_path\GoogleAdwordsPathTracker $pathTracker
     *  The path tracker service, which will be used to invalidate the cache
     */
    $pathTracker = \DRUPAL::service('google_adwords_path.pathtracker');
    // re-build the tree
    if ($pathTracker instanceof GoogleAdwordsPathTracker) {
      $pathTracker->buildPathTree(TRUE);
    }
    else {
      drupal_set_message(__method__.'::'.__line__.':: CACHE CLEAR FAIL');
    }

  }

}
