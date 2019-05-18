<?php

namespace Drupal\migrate_d2d_ui\Wizard;

use Drupal\Core\Form\FormStateInterface;
use Drupal\ctools\Wizard\FormWizardBase;
use Drupal\migrate_d2d\DrupalMigrationGenerator;

class ImportWizard extends FormWizardBase {

  /**
   * {@inheritdoc}
   */
  public function getOperations($cached_values) {
    $steps = [
      'source_select' => [
        'form' => 'Drupal\migrate_d2d_ui\Form\SourceSelectForm',
        'title' => $this->t('Data source'),
      ],
      'users' => [
        'form' => 'Drupal\migrate_d2d_ui\Form\UserForm',
        'title' => $this->t('Users and roles'),
      ],
     'vocabulary_select' => [
        'form' => 'Drupal\migrate_d2d_ui\Form\VocabularySelectForm',
        'title' => $this->t('Vocabularies'),
      ],
      'files' => [
        'form' => 'Drupal\migrate_d2d_ui\Form\FileForm',
        'title' => $this->t('Files'),
      ],
      'content_select' => [
        'form' => 'Drupal\migrate_d2d_ui\Form\ContentSelectForm',
        'title' => $this->t('Content'),
      ],
    ];
    $steps += [
      'review' => [
        'form' => 'Drupal\migrate_d2d_ui\Form\ReviewForm',
        'title' => $this->t('Review'),
      ],
    ];
    return $steps;
    // Dynamically add the content migration(s) that have been configured by
    // ContentSelectForm.
    if (!empty($cached_values['post']['type'])) {
      $steps += [
        'blog_post' => [
          'form' => 'Drupal\wordpress_migrate_ui\Form\ContentTypeForm',
          'title' => $this->t('Posts'),
          'values' => ['wordpress_content_type' => 'post'],
        ],
      ];
    }
    if (!empty($cached_values['page']['type'])) {
      $steps += [
        'page' => [
          'form' => 'Drupal\wordpress_migrate_ui\Form\ContentTypeForm',
          'title' => $this->t('Pages'),
          'values' => ['wordpress_content_type' => 'page'],
        ],
      ];
    }

    return $steps;
  }

  /**
   * {@inheritdoc}
   */
  public function getRouteName() {
    return 'migrate_d2d_ui.wizard.import.step';
  }

  /**
   * {@inheritdoc}
   */
  public function finish(array &$form, FormStateInterface $form_state) {
    $cached_values = $form_state->getTemporaryValue('wizard');
    $generator = new DrupalMigrationGenerator($cached_values);
    $generator->createMigrations();
    // Go to the dashboard for this migration group.
    $form_state->setRedirect('entity.migration.list', ['migration_group' => $cached_values['group_id']]);
    parent::finish($form, $form_state);
  }

}
