<?php

namespace Drupal\entity_generic\Plugin\views\field;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\views\ResultRow;

/**
 * A handler to provide a field that is completely custom by the administrator.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("entity_generic_link_edit_modal")
 */
class GenericEditModal extends GenericOperationModalBase {

  /**
   * {@inheritdoc}
   */
  public function getUrlInfo(ResultRow $row) {
    $url = Url::fromRoute('entity.' . $this->getEntityType() . '.edit_modal_form', [
      $this->getEntityType() => $this->getEntity($row)->id(),
      'form_mode' => $this->options['form_mode'],
    ]);
    return $url;
  }

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['form_mode'] = ['default' => FALSE];
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    /** @var \Drupal\Core\Entity\EntityDisplayRepositoryInterface $entityDisplayRepository */
    $entityDisplayRepository = \Drupal::service('entity_display.repository');
    $options = $entityDisplayRepository->getFormModeOptions($this->getEntityType());
    $form['form_mode'] = [
      '#type' => 'select',
      '#title' => $this->t('Form mode'),
      '#options' => $options,
      '#default_value' => $this->options['form_mode']
    ];

    parent::buildOptionsForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  protected function getDefaultLabel() {
    return $this->t('edit');
  }

}
