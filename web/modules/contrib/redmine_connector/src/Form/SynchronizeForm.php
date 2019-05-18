<?php

namespace Drupal\redmine_connector\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\node\Entity\Node;

/**
 * Class SynchronizeForm.
 */
class SynchronizeForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'redmine_connector_synchronize_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['synchronize_type'] = [
      '#type' => 'select',
      '#options' => [
        'projects' => t('Projects'),
        'users' => t('Users'),
        'issues' => t('Issues'),
      ],
      '#title' => t('What would you like to synchronize?'),
    ];
    $form['markup'] = [
      '#type' => 'html_tag',
      '#tag' => 'h3',
      '#value' => t('It can take 3-5 minutes.'),
    ];
    $form['synchronize_submit'] = [
      '#type' => 'submit',
      '#value' => t('Synchronize Now'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    switch ($form_state->getValue('synchronize_type')) {
      case 'projects':
        \Drupal::service('redmine_connector.synchronization')
          ->synchronizeProjects();
        break;

      case 'users':
        \Drupal::service('redmine_connector.synchronization')
          ->synchronizeUsers();
        break;

      case 'issues':
        \Drupal::service('redmine_connector.synchronization')
          ->synchronizeIssues();
        break;
    }
    drupal_set_message(ucfirst($form_state->getValue('synchronize_type', 2)) . " " . $this->t('have been successfully synchronized.'));
  }

}
