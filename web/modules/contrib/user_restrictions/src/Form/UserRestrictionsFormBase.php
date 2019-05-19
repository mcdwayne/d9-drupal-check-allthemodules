<?php

namespace Drupal\user_restrictions\Form;

use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\user_restrictions\Entity\UserRestrictions;
use Drupal\Core\Url;

/**
 * Base form for image style add and edit forms.
 */
abstract class UserRestrictionsFormBase extends EntityForm {

  /**
   * The entity being used by this form.
   *
   * @var \Drupal\user_restrictions\Entity\UserRestrictions
   */
  protected $entity;

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\user_restrictions\UserRestrictionTypeManagerInterface $type_manager */
    $type_manager = \Drupal::service('user_restrictions.type_manager');

    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('User restriction name'),
      '#default_value' => $this->entity->label(),
      '#required' => TRUE,
    ];
    $form['name'] = [
      '#type' => 'machine_name',
      '#machine_name' => [
        'exists' => ['\Drupal\user_restrictions\Entity\UserRestrictions', 'load'],
      ],
      '#default_value' => $this->entity->id(),
      '#required' => TRUE,
    ];

    $regex_help = Url::fromUri('https://regex101.com');
    $redos = Url::fromUri('https://www.owasp.org/index.php/Regular_expression_Denial_of_Service_-_ReDoS');
    $form['pattern'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Pattern'),
      '#size' => 10,
      '#maxlength' => 64,
      '#default_value' => $this->entity->getPattern(),
      '#field_prefix' => '/',
      '#field_suffix' => '/i',
      '#description' => $this->t('Add a pattern for this rule to match.<br />Regular expressions are accepted and can be used for more complex restrictions. If you want to block patterns with regex characters in, you will need to escape them.<br /><a href=":regex">Test out regex patterns here first if you are unsure</a> and use appropriate caution when adding regex patterns to ensure you don\'t accidentally <a href=":redos">ReDos</a> yourself.', [':regex' => $regex_help->getUri(), ':redos' => $redos->getUri()]),
      '#required' => TRUE,
    ];

    $form['access_type'] = [
      '#type' => 'radios',
      '#title' => t('Access type'),
      '#default_value' => (int) $this->entity->getAccessType(),
      '#options' => [UserRestrictions::BLACKLIST => $this->t('Blacklist'), UserRestrictions::WHITELIST => $this->t('Whitelist')],
      '#required' => TRUE,
    ];

    $form['rule_type'] = [
      '#type' => 'radios',
      '#title' => t('Restriction type'),
      '#default_value' => $this->entity->getRuleType(),
      '#options' => $type_manager->getTypesAsOptions(),
      '#required' => TRUE,
    ];

    $form['expiration'] = [
      '#type' => 'details',
      '#title' => $this->t('Expiration'),
      '#description' => $this->t('Set a time for this user restriction to expire or create a permanent restriction.'),
      '#open' => TRUE,
      '#required' => TRUE,
    ];
    $form['expiration']['permanent'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Never expire'),
      '#default_value' => ($this->entity->getExpiry() == UserRestrictions::NO_EXPIRY),
    ];
    $form['expiration']['expiry_container'] = [
      '#type' => 'container',
      '#states' => [
        // Hide the additional settings when the blocked email is disabled.
        'invisible' => [
          'input[name="permanent"]' => ['checked' => TRUE],
        ],
      ],
      '#open' => TRUE,
    ];

    // Set the default expiration to be 7 days in the future.
    $default_expiration = new DrupalDateTime('now +7 days');

    if ($expiration = (int) $this->entity->getExpiry()) {
      $default_expiration = DrupalDateTime::createFromTimestamp($expiration);
    }

    $form['expiration']['expiry_container']['expiry'] = [
      '#type' => 'datetime',
      '#default_value' => $default_expiration,
      '#title_display' => 'invisible',
      '#title' => $this->t('Expiry time'),
      '#required' => TRUE,
    ];

    return parent::form($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    // Check for duplicate pattern.
    $existing = $this->entityTypeManager->getStorage('user_restrictions')->loadByProperties(['rule_type' => $form_state->getValue('rule_type'), 'pattern' => $form_state->getValue('pattern')]);
    if (!empty($existing)) {
      $form_state->setError($form['pattern'], $this->t('A rule with the same pattern already exists.'));
    }

    // Store the expiration time as unixtime as configuration entities may
    // only use scalar values.
    /* @var $datetime DrupalDateTime */
    $datetime = $form_state->getValue('expiry');

    if ($form_state->getValue('permanent')) {
      $form_state->setValue('expiry', UserRestrictions::NO_EXPIRY);
    }
    else {
      $form_state->setValue('expiry', (int) $datetime->format('U'));
    }

    parent::validateForm($form, $form_state);
  }

}
