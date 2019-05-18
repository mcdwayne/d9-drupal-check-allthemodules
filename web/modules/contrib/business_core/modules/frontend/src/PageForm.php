<?php

namespace Drupal\frontend;

use Drupal\Core\Form\FormStateInterface;

class PageForm extends ContainerForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\frontend\PageInterface $page */
    $page = $this->entity;
    $form = parent::form($form, $form_state);

    $form['layout'] = [
      '#type' => 'entity_autocomplete',
      '#title' => $this->t('Layout'),
      '#target_type' => 'layout',
      '#default_value' => $page->getLayout(),
    ];

    $form['path'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Path'),
      '#default_value' => $page->getPath(),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function nameExists($value) {
    if ($this->entityTypeManager->getStorage('page')->getQuery()->condition('id', $value)->range(0, 1)->count()->execute()) {
      return TRUE;
    }

    return FALSE;
  }

}
