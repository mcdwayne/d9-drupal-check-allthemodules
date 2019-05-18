<?php

namespace Drupal\partner_link\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Block\BlockPluginInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\taxonomy\Entity\Term;

/**
 * Provides a 'Partner Link' Block.
 *
 * @Block(
 *   id = "partner_link_block",
 *   admin_label = @Translation("Partner Link"),
 * )
 */
class PartnerLinkBlock extends BlockBase implements BlockPluginInterface {

  /**
   * {@inheritdoc}
   */
  public function build() {

    $config = $this->getConfiguration();
    $twitter_account = "";
    $partner_block_display = 'partner_link';
    if (isset($config['partner_link_block_settings_display'])) {
      $partner_block_display = $config['partner_link_block_settings_display'];
    }

    $query = \Drupal::entityQuery('taxonomy_term');
    $query->condition('vid', "partner_link__partners");
    $query->condition('field_partner_link__enabled', 1);
    $query->condition('field_partner_link__display_bloc', 1);
    $tids = $query->execute();
    $terms = Term::loadMultiple($tids);

    $partners = [];
    foreach ($terms as $term) {
      $view_builder = \Drupal::entityTypeManager()->getViewBuilder($term->getEntityTypeId());
      $partners[] = $view_builder->view($term, $partner_block_display);
    }

    $rendering = [
      '#theme' => 'partner_link_list',
      '#partners' => $partners,
    ];

    if (!isset($config['partner_link_block_settings_css']) || (isset($config['partner_link_block_settings_css']) && $config['partner_link_block_settings_css'])) {
      $rendering['#attached']['library'][] = 'partner_link/partner_link_default';
    }

    return $rendering;
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form = parent::blockForm($form, $form_state);

    $config = $this->getConfiguration();

    $form['partner_link_block_settings_display'] = [
      '#type' => 'select',
      '#title' => $this->t('Display'),
      '#description' => $this->t('Choose how to display the partners.'),
      '#options' => [
        'partner_link' => $this->t('Display only partner links'),
        'partner_link_icon' => $this->t('Display partner icons'),
        'partner_link_both' => $this->t('Display both (icons and links)'),
      ],
      '#default_value' => isset($config['partner_link_block_settings_display']) ? $config['partner_link_block_settings_display'] : 'partner_link'
    ];

    $form['partner_link_block_settings_css'] = [
      '#type' => 'checkbox',
      '#title' => t('Add default style'),
      '#default_value' => isset($config['partner_link_block_settings_css']) ? $config['partner_link_block_settings_css'] : 1
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $display = $form_state->getValue('partner_link_block_settings_display');
    $this->setConfigurationValue('partner_link_block_settings_display', $display);
    $css = $form_state->getValue('partner_link_block_settings_css');
    $this->setConfigurationValue('partner_link_block_settings_css', $css);
  }

}
