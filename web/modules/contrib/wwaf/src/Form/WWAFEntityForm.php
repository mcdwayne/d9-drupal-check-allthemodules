<?php
/**
 * Created by PhpStorm.
 * User: kowalskj
 * Date: 14/11/2017
 * Time: 11:54
 */

namespace Drupal\wwaf\Form;


use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Language\Language;
use Drupal\Core\Form\FormStateInterface;

class WWAFEntityForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    /* @var $entity \Drupal\wwaf\Entity\WWAFStore */
    $form = parent::buildForm($form, $form_state);
    $entity = $this->entity;

    $form['langcode'] = array(
      '#title' => $this->t('Language'),
      '#type' => 'language_select',
      '#default_value' => $entity->getUntranslated()->language()->getId(),
      '#languages' => Language::STATE_ALL,
    );
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $status = parent::save($form, $form_state);

    $entity = $this->entity;
    if ($status == SAVED_UPDATED) {
      drupal_set_message($this->t('The store %feed has been updated.', ['%feed' => $entity->toLink()->toString()]));
    } 
    else {
      drupal_set_message($this->t('The store %feed has been added.', ['%feed' => $entity->toLink()->toString()]));
    }

    $form_state->setRedirectUrl($this->entity->toUrl('collection'));
    return $status;
  }
}