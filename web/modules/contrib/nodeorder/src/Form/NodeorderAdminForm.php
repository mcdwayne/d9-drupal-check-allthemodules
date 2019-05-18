<?php

namespace Drupal\nodeorder\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides forms for managing Node Order.
 */
class NodeorderAdminForm extends ConfigFormBase {

  /**
   * {@inheritdoc}.
   */
  public function getFormId() {
    return 'nodeorder_admin';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->configFactory()->getEditable('nodeorder.settings');
    $form['nodeorder_show_links'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Display ordering links'),
      '#description' => $this->t('Choose whether to show ordering links. Links can be shown for all categories associated to a node or for the currently active category. It is also possible to not show the ordering links at all.'),
    ];
    $form['nodeorder_show_links']['nodeorder_show_links_on_node'] = [
      '#type' => 'radios',
      '#title' => $this->t('Choose how to display ordering links'),
      '#default_value' => $config->get('show_links_on_node'),
      '#description' => $this->t('When displaying links based on the context, they will only be shown on taxonomy and nodeorder pages.'),
      '#options' => [
        $this->t("Don't display ordering links"),
        $this->t('Display ordering links for all categories'),
        $this->t('Display ordering links based on the active category'),
      ],
    ];

    $form['nodeorder_link_to_ordering_page'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Display link to the ordering page'),
      '#description' => $this->t('If enabled, a tab will appear on all <em>nodeorder/term/%</em> and <em>taxonomy/term/%</em> pages that quickly allows administrators to get to the node ordering administration page for the term.'),
      '#default_value' => $config->get('link_to_ordering_page'),
    ];

    $form['nodeorder_link_to_ordering_page_taxonomy_admin'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Display link to the ordering page on taxonomy administration page'),
      '#description' => $this->t('If enabled, a tab will appear on <em>admin/content/taxonomy/%</em> pages that quickly allows administrators to get to the node ordering administration page for the term.'),
      '#default_value' => $config->get('link_to_ordering_page_taxonomy_admin'),
    ];

    $form['nodeorder_override_taxonomy_page'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Override the default taxonomy page with one from nodeorder'),
      '#description' => $this->t('Disabling this will allow the panels module to override taxonomy pages instead. See <a href="https://www.drupal.org/node/1713048">this issue</a> for more information. You will have to clear caches for the change to take effect.'),
      '#default_value' => $config->get('override_taxonomy_page'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    // ...
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->configFactory()->getEditable('nodeorder.settings')
      ->set('show_links_on_node', $form_state->getValue('nodeorder_show_links_on_node'))
      ->set('link_to_ordering_page', $form_state->getValue('nodeorder_link_to_ordering_page'))
      ->set('link_to_ordering_page_taxonomy_admin', $form_state->getValue('nodeorder_link_to_ordering_page_taxonomy_admin'))
      ->set('override_taxonomy_page', $form_state->getValue('nodeorder_override_taxonomy_page'))
      ->save();

    parent::submitForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['nodeorder.settings'];
  }

}
