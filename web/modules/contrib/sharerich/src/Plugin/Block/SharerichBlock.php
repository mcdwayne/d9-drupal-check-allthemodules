<?php

/**
 * @file
 * Contains \Drupal\sharerich\Plugin\Block\SharerichBlock.
 */

namespace Drupal\sharerich\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Routing\RedirectDestinationTrait;
/**
 * Provides a Sharerich block.
 *
 * @Block(
 *   id = "sharerich",
 *   admin_label = @Translation("Sharerich"),
 * )
 */
class SharerichBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, \Drupal\Core\Form\FormStateInterface $form_state) {
    $form = parent::blockForm($form, $form_state);
    $configuration = $this->configuration;

    $options = array();
    $entity_storage = \Drupal::entityTypeManager()->getStorage('sharerich');
    foreach ($entity_storage->loadMultiple() as $entity) {
      $entity_id = $entity->id();
      $options[$entity_id] = $entity->label();
    }

    $form['sharerich_set'] = array(
      '#type' => 'select',
      '#title' => t('Sharerich Set'),
      '#options' => $options,
      '#default_value' => isset($configuration['sharerich_set']) ? $configuration['sharerich_set'] : array(),
    );

    $form['orientation'] = array(
      '#type' => 'select',
      '#title' => t('Orientation'),
      '#options' => array('horizontal' => t('Horizontal'), 'vertical' => t('Vertical')),
      '#default_value' => isset($configuration['orientation']) ? $configuration['orientation'] : array(),
      '#description' => t('If you set to vertical and place the block on the top of the main content area, it will float on the side.'),
    );

    $form['sticky'] = array(
      '#type' => 'checkbox',
      '#title' => t('Sticky'),
      '#default_value' => isset($configuration['sticky']) ? $configuration['sticky'] : 0,
      '#description' => t('Stick to the top when scrolling.'),
      '#states' => array(
        'visible' => array(
          ':input[name="settings[orientation]"]' => array('value' => 'vertical'),
        ),
      ),
    );

    return $form;
  }

  /**
   * Overrides \Drupal\block\BlockBase::blockSubmit().
   */
  public function blockSubmit($form, \Drupal\Core\Form\FormStateInterface $form_state) {
    $this->configuration['sharerich_set'] = $form_state->getValue('sharerich_set');
    $this->configuration['orientation'] = $form_state->getValue('orientation');
    $this->configuration['sticky'] = $form_state->getValue('sticky');
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $entity_storage = \Drupal::entityTypeManager()->getStorage('sharerich');

    if ($sharerich_set = $entity_storage->load($this->configuration['sharerich_set'])) {
      $buttons = array();
      foreach ($sharerich_set->getServices() as $name => $service) {
        $buttons[$name] = [
          '#attributes' => ['class' => ['sharerich-buttons-wrapper', 'rrssb-buttons-wrapper']],
          '#wrapper_attributes' => ['class' => ['rrssb-' . $name]],
          '#markup' => $service['markup'],
          '#allowed_tags' => sharerich_allowed_tags(),
        ];
      }
      // Allow other modules to alter the buttons before they are rendered.
      $context = _sharerich_get_token_data();
      \Drupal::moduleHandler()->alter('sharerich_buttons', $buttons, $context);

      // Render tokens.
      foreach ($buttons as &$button) {
        $button['#markup'] = \Drupal::token()->replace($button['#markup'], _sharerich_get_token_data());
      }

      $item_list = [
        '#theme' => 'item_list',
        '#items' => $buttons,
        '#type' => 'ul',
        '#wrapper_attributes' => [
          'class' => [
            'sharerich-wrapper',
            'share-container',
            'sharerich-' . $this->configuration['sharerich_set'],
            'sharerich-' . $this->configuration['orientation'],
            ($this->configuration['sticky']) ? 'sharerich-sticky' : '',
          ]
        ],
        '#attributes' => ['class' => ['sharerich-buttons', 'rrssb-buttons']],
        '#attached' => [
          'library' => [
            'sharerich/rrssb',
            'sharerich/sharerich'
          ]
        ],
      ];

      $build['content'] = [
        '#theme' => 'sharerich',
        '#buttons' => $item_list,
        '#cache' => [
          'contexts' => ['url.path']
        ],
      ];

      return $build;
    }
  }
}
