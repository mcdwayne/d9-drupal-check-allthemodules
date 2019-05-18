<?php

namespace Drupal\drd\Entity\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a form for reviewing Core update logs.
 *
 * @ingroup drd
 */
class CoreUpdateLog extends FormBase {

  /**
   * DRD Core entity for which we handle update logs.
   *
   * @var \Drupal\drd\Entity\CoreInterface
   */
  protected $core;

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'drd_core_updatelog';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $drd_core = NULL, $timestamp = 0) {
    $this->core = $drd_core;
    $logList = $this->core->getUpdateLogList();
    if (empty($logList)) {
      $form['info'] = [
        '#markup' => $this->t('No update logs available'),
      ];
      return $form;
    }

    $current = FALSE;
    $select = [];
    foreach ($logList as $item) {
      $select[$item['timestamp']] = \Drupal::service('date.formatter')->format($item['timestamp']);
      if ($item['timestamp'] == $timestamp) {
        $current = $item;
      }
    }
    if (empty($current)) {
      // Default to latest item.
      /* @noinspection PhpUndefinedVariableInspection */
      $current = $item;
    }

    $form['select'] = [
      '#type' => 'select',
      '#options' => $select,
      '#default_value' => $current['timestamp'],
    ];
    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Select'),
    ];
    $form['log'] = [
      '#type' => 'textarea',
      '#default_value' => $this->core->getUpdateLog($current['timestamp']),
      '#rows' => 30,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $form_state->setRedirect('entity.drd_core.updatelog', [
      'drd_core' => $this->core->id(),
      'timestamp' => $form_state->getValue('select'),
    ]);
  }

}
