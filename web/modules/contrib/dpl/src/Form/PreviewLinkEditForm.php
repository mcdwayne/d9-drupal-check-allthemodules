<?php

namespace Drupal\dpl\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\dpl\BrowserSize;
use Drupal\dpl\Entity\DecoupledPreviewLink;

class PreviewLinkEditForm extends EntityForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    parent::form($form, $form_state);

    $decoupled_preview_link = $this->entity;
    assert($decoupled_preview_link instanceof DecoupledPreviewLink);

    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#default_value' => $decoupled_preview_link->label(),
      '#required' => TRUE,
    ];

    $form['id'] = [
      '#type' => 'machine_name',
      '#machine_name' => [
        'exists' => [$this, 'exists'],
        'source' => ['label'],
      ],
      '#default_value' => $decoupled_preview_link->id(),
      '#required' => TRUE,
    ];

    $form['preview_url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Preview URL'),
      '#default_value' => $decoupled_preview_link->getPreviewUrl(),
      '#description' => $this->t('Provide a URL editors can visit to preview future revisions. You can use any token in those URLs'),
    ];

    if (\Drupal::moduleHandler()->moduleExists('token')) {
      /** @var \Drupal\token\TreeBuilderInterface $tree_builder */
      $tree_builder = \Drupal::service('token.tree_builder');
      $form['token_help'] = $tree_builder->buildAllRenderable();
    }
    else {
      $form['token_help'] = [
        '#markup' => $this->t('Enable the token module to see a list of available tokens.')
      ];
    }

    $form['tab_label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Preview tab label'),
      '#default_value' => $decoupled_preview_link->getTabLabel(),
    ];

    $form['open_external_label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Open external label'),
      '#default_value' => $decoupled_preview_link->getOpenExternalLabel(),
    ];

    $browser_sizes = $decoupled_preview_link->toPreviewLinkInstance()->getBrowserSizes();
    $options = array_combine(array_map(function (BrowserSize $size) {
      return $size->getShortLabel();
    }, $browser_sizes), array_map(function (BrowserSize $size) {
      return $size->getLabel();
    }, $browser_sizes));
    $form['default_size'] = [
      '#type' => 'select',
      '#title' => $this->t('Default size'),
      '#default_value' => $decoupled_preview_link->getDefaultSize(),
      '#options' => $options,
    ];

    return $form;
  }

  /**
   * Determines if the decoupled preview link already exists.
   *
   * @param string $id
   *   The deeoupled preview link ID.
   *
   * @return bool
   *   TRUE if the decoupled preview link exists, FALSE otherwise.
   */
  public function exists($id) {
    return !empty(DecoupledPreviewLink::load($id));
  }


}
