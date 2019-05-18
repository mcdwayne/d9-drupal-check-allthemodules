<?php

namespace Drupal\bibcite_entity\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Common contributor settings.
 */
class ContributorSettings extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['bibcite_entity.contributor.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'bibcite_entity_contributor_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('bibcite_entity.contributor.settings');
    $form = parent::buildForm($form, $form_state);

    $form['full_name_pattern'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Full name pattern'),
      '#description' => $this->t('Describe how generate full name of contributor from name parts.'),
      '#default_value' => $config->get('full_name_pattern'),
    ];
    $form['description'] = [
      '#type' => 'item',
      '#markup' => $this->t("Available name parts:\n@tokens", [
        '@tokens' => implode(", ", [
          '@last_name', '@first_name', '@suffix', '@prefix',
        ]),
      ]),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('bibcite_entity.contributor.settings');

    $config->set('full_name_pattern', $form_state->getValue('full_name_pattern'))
      ->save();

    parent::submitForm($form, $form_state);
  }

}
