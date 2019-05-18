<?php

namespace Drupal\entity_generic\Plugin\views\field;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\views\ResultRow;

/**
 * Field handler to present a link to toggle the status of the entity.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("entity_generic_toggle_status_modal")
 */
class GenericToggleStatusModal extends GenericOperationModalBase {

  /**
   * {@inheritdoc}
   */
  public function getUrlInfo(ResultRow $row) {
    $url = Url::fromRoute('entity.' . $this->getEntityType() . '.status_toggle_modal', [
      $this->getEntityType() => $this->getEntity($row)->id(),
    ]);
    return $url;
  }

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();
    unset($options['text']);
    $options['text_enabled'] = ['default' => $this->getDefaultLabelEnabled()];
    $options['text_disabled'] = ['default' => $this->getDefaultLabelDisabled()];
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);

    $form['text']['#access'] = FALSE;

    $form['text_enabled'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Text to display if entity have status 1'),
      '#default_value' => $this->options['text_enabled'],
    ];

    $form['text_disabled'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Text to display if entity have status 0'),
      '#default_value' => $this->options['text_disabled'],
    ];

  }

  /**
   * {@inheritdoc}
   */
  protected function getEntityLinkTemplate() {
    return 'status';
  }

  /**
   * {@inheritdoc}
   */
  protected function renderLink(ResultRow $row) {
    $this->options['alter']['query'] = $this->getDestinationArray();
    if ($row->_entity->getStatus()) {
      $this->options['text'] = 'deactivate';
    }
    else {
      $this->options['text'] = 'activate';
    }
    return parent::renderLink($row);
  }

  /**
   * {@inheritdoc}
   */
  protected function getDefaultLabelEnabled() {
    return $this->t('enabled');
  }

  /**
   * {@inheritdoc}
   */
  protected function getDefaultLabelDisabled() {
    return $this->t('disabled');
  }

}
