<?php

namespace Drupal\paragraphs_type_help;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure user settings for this site.
 */
class ParagraphsTypeHelpSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'paragraphs_type_help_admin_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    // @TODO: global admin settings needed?
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);

    $form['description'] = [
      '#markup' => $this->t('Placeholder for admin settings form.'),
    ];

    return $form;
  }

}
