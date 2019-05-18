<?php
/**
 * @file
 * Contains \Drupal\jvector\Form\JvectorDeleteForm.
 */

namespace Drupal\jvector\Form;

use Drupal\Core\Entity\EntityConfirmFormBase;
use Drupal\Core\Url;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Builds the form to delete a Jvector.
 */
class JvectorConfigDeleteForm extends EntityConfirmFormBase {

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    $config = \Drupal::routeMatch()->getParameter('customconfig');
    // We cannot delete the default set
    if ($config == 'default') {
      return $this->t('Are you sure you want to revert the default configuration?');
    }
    $entity = $this->entity;
    $configs = $entity->customconfig;
    return $this->t('Are you sure you want to delete configuration %name?', array('%name' => $configs[$config]['label']));
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    $test = $this->entity->urlInfo('view-form');
    return $test;
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    $config = \Drupal::routeMatch()->getParameter('customconfig');
    if ($config == 'default') {
      return $this->t('Revert config');
    }
    return $this->t('Delete config');
  }

  /**
   * {@inheritdoc}
   */

  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = \Drupal::routeMatch()->getParameter('customconfig');
    if ($config == 'default') {
      drupal_set_message($this->t('Default jvector configuration has been reverted.'));
      //@todo Do the actual revert work here.
      $form_state->setRedirectUrl($this->getCancelUrl());
    }
    else {
      $entity = $this->entity;
      $config = \Drupal::routeMatch()->getParameter('customconfig');
      unset($this->entity->customconfig[$config]);
      $this->entity->save();
      drupal_set_message($this->t('Jvector configuration has been deleted.'));
      $form_state->setRedirectUrl($this->getCancelUrl());
    }
  }
}
