<?php

namespace Drupal\aws\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\aws\Entity\Profile;

/**
 * Provides a form for creating and editing AWS profiles.
 */
class ProfileForm extends EntityForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    // If the form is being rebuilt, rebuild the entity with the current form
    // values.
    if ($form_state->isRebuilding()) {
      $this->entity = $this->buildEntity($form, $form_state);
    }

    $form = parent::form($form, $form_state);

    /** @var \Drupal\aws\Entity\ProfileInterface $profile */
    $profile = $this->getEntity();

    // Set the page title according to whether we are creating or editing
    // the profile.
    if ($profile->isNew()) {
      $form['#title'] = $this->t('Add AWS Profile');
    }
    else {
      $form['#title'] = $this->t("Edit '%label'", ['%label' => $profile->label()]);
    }

    $form['name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Profile name'),
      '#description' => $this->t('Enter the name for the profile.'),
      '#default_value' => $profile->label(),
      '#required' => TRUE,
    ];

    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $profile->isNew() ? NULL : $profile->id(),
      '#maxlength' => 50,
      '#required' => TRUE,
      '#machine_name' => [
        'exists' => '\Drupal\aws\Entity\Profile::load',
        'source' => ['name'],
      ],
      '#disabled' => !$profile->isNew(),
    ];

    $form['default'] = [
      '#type' => 'checkbox',
      '#description' => $this->t('If checked, this profile will be used as the default.'),
      '#title' => $this->t('Default'),
      '#default_value' => $profile->getDefault(),
    ];

    $form['aws_access_key_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Access Key'),
      '#description' => $this->t('AWS access key.'),
      '#default_value' => $profile->getAccessKey(),
    ];

    $form['aws_secret_access_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Secret Access Key'),
      '#description' => $this->t('AWS secret key.'),
      '#default_value' => $profile->getSecretAccessKey(),
    ];

    // Get regions from EC2::describeRegions();
    $form['region'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Region'),
      '#description' => $this->t('AWS region.'),
      '#default_value' => $profile->getRegion(),
    ];

    return $form;
  }

  /**
   * Returns an array of supported actions for the current entity form.
   *
   * @todo Consider introducing a 'preview' action here, since it is used by
   *   many entity types.
   */
  protected function actions(array $form, FormStateInterface $form_state) {
    // @todo Consider renaming the action key from submit to save. The impacts
    //   are hard to predict. For example, see
    //   \Drupal\language\Element\LanguageConfiguration::processLanguageConfiguration().
    $actions['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save'),
      '#submit' => [
        '::submitForm',
        '::save',
        '::ensureUniqueDefault',
        '::redirectToProfiles',
      ],
    ];

    if (!$this->entity->isNew() && $this->entity->hasLinkTemplate('delete-form')) {
      $route_info = $this->entity->urlInfo('delete-form');
      if ($this->getRequest()->query->has('destination')) {
        $query = $route_info->getOption('query');
        $query['destination'] = $this->getRequest()->query->get('destination');
        $route_info->setOption('query', $query);
      }
      $actions['delete'] = [
        '#type' => 'link',
        '#title' => $this->t('Delete'),
        '#access' => $this->entity->access('delete'),
        '#attributes' => [
          'class' => ['button', 'button--danger'],
        ],
      ];
      $actions['delete']['#url'] = $route_info;
    }

    return $actions;
  }

  /**
   * Undocumented function.
   */
  public function ensureUniqueDefault(array &$form, FormStateInterface $form_state) {
    $profiles = \Drupal::entityQuery('aws_profile')->execute();
    foreach ($profiles as $profile_id) {
      if ($this->entity->id() != $profile_id && $this->entity->getDefault()) {
        $profile = Profile::load($profile_id);
        $profile->setDefault(FALSE);
        $profile->save();
      }
    }
  }

  /**
   * Undocumented function.
   */
  public function redirectToProfiles(array &$form, FormStateInterface $form_state) {
    $form_state->setRedirect('aws.configuration.profiles');
  }

  /**
   * {@inheritdoc}
   */
  public function buildEntity(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\aws\Entity\ProfileInterface $profile */
    $profile = parent::buildEntity($form, $form_state);
    if (!$form_state->isValueEmpty('aws_secret_access_key')) {
      $profile->setSecretAccessKey($form_state->getValue('aws_secret_access_key'));
    }
    return $profile;
  }

}
