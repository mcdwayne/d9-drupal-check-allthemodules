<?php

namespace Drupal\changelog\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class ChangelogEntityForm.
 */
class ChangelogEntityForm extends EntityForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    /** @var \Drupal\changelog\Entity\ChangelogEntity $changelog */
    $changelog = $this->entity;
    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#default_value' => $changelog->getLabel(),
    ];

    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $changelog->id(),
      '#machine_name' => [
        'exists' => '\Drupal\changelog\Entity\ChangelogEntity::load',
        'source' => ['label'],
      ],
      '#disabled' => !$changelog->isNew(),
    ];

    $form['version'] = [
      '#type' => 'textfield',
      '#title' => t('Target version string'),
      '#description' => $this->t('It is recommended to use semantic versioning in this string, see <a href="http://semver.org/">semver.org</a> for more info.'),
      '#default_value' => $changelog->getLogVersion(),
      '#required' => TRUE,
    ];
    $entry_format = $changelog->getLogFormat();
    $entry_value = $changelog->getLogValue();

    $form['log'] = [
      '#type' => 'text_format',
      '#title' => $this->t('Log entry'),
      '#format' => $entry_format,
      '#allowed_formats' => ['full_html', 'plain_text'],
      '#default_value' => $entry_value,
      '#description' => t('The user-facing changelog description'),
      '#required' => TRUE,
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\changelog\Entity\ChangelogEntity $changelog */
    $changelog = $this->entity;
    $entry = $form_state->getValue('log');
    $changelog->setLogFormat($entry['format']);
    $changelog->setLogValue($entry['value']);
    $changelog->setCreatedTime(time());
    // Add version string to start of ID.
    $version = $form_state->getValue('version');
    $version = preg_replace('/[^a-z0-9_]+/', '-', $version);
    if ($changelog->isNew()) {
      $changelog->set('id', $version . '_' . $form_state->getValue('id'));
    }
    $status = $changelog->save();

    switch ($status) {
      case SAVED_NEW:
        drupal_set_message($this->t('Created the %label changelog item.', [
          '%label' => $changelog->label(),
        ]));
        break;

      default:
        drupal_set_message($this->t('Saved the %label changelog item.', [
          '%label' => $changelog->label(),
        ]));
    }
    $form_state->setRedirectUrl($changelog->toUrl('collection'));
  }

}
