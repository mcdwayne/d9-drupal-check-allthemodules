<?php

namespace Drupal\simple_modal_entity_form\Plugin\views\field;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\views\ResultRow;

/**
 * A handler to provide a field that is completely custom by the administrator.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("modal_entity_view_field")
 */
class ModalEntityViewField extends ModalEntityOperationBase {

  /**
   * {@inheritdoc}
   */
  public function getUrlInfo(ResultRow $row) {
    $url = Url::fromRoute('modal_entity_form.view', [
      'entity_type' => $this->getEntityType(),
      'entity' => $this->getEntity($row)->id(),
      'view_mode' => $this->options['view_mode']
    ]);
    return $url;
  }

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['view_mode'] = ['default' => FALSE];
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    /** @var \Drupal\Core\Entity\EntityDisplayRepositoryInterface $entityDisplayRepository */
    $entityDisplayRepository = \Drupal::service('entity_display.repository');
    $options = $entityDisplayRepository->getViewModeOptions($this->getEntityType());
    $form['view_mode'] = [
      '#type' => 'select',
      '#title' => $this->t('View mode'),
      '#options' => $options,
      '#default_value' => $this->options['view_mode']
    ];

    parent::buildOptionsForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  protected function getDefaultLabel() {
    return $this->t('view');
  }

}
