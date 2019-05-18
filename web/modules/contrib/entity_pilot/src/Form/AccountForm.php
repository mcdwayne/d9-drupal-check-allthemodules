<?php

namespace Drupal\entity_pilot\Form;

use Defuse\Crypto\Exception\BadFormatException;
use Defuse\Crypto\Exception\EnvironmentIsBrokenException;
use Defuse\Crypto\Key;
use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\Core\Utility\LinkGeneratorInterface;
use Drupal\entity_pilot\LegacyMessagingTrait;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Account form.
 */
class AccountForm extends EntityForm {

  use LegacyMessagingTrait;

  /**
   * The link generator service.
   *
   * @var \Drupal\Core\Utility\LinkGeneratorInterface
   */
  protected $linkGenerator;

  /**
   * Logger channel.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('link_generator'),
      $container->get('logger.factory')->get('entity_pilot')
    );
  }

  /**
   * Constructs a new \Drupal\entity_pilot\Form\AccountForm.
   *
   * @param \Drupal\Core\Utility\LinkGeneratorInterface $link_generator
   *   The link generator service.
   * @param \Psr\Log\LoggerInterface $logger
   *   Logger channel.
   */
  public function __construct(LinkGeneratorInterface $link_generator, LoggerInterface $logger) {
    $this->linkGenerator = $link_generator;
    $this->logger = $logger;
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);
    $account = $this->entity;

    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $account->label(),
      '#description' => $this->t("Provide a label for this account to help identify it in the administration pages."),
      '#required' => TRUE,
    ];
    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $account->id(),
      '#machine_name' => [
        'exists' => '\Drupal\entity_pilot\Entity\Account::load',
      ],
      '#maxlength' => EntityTypeInterface::BUNDLE_MAX_LENGTH,
      '#disabled' => !$account->isNew(),
    ];

    $form['carrierId'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Carrier ID'),
      '#maxlength' => 255,
      '#default_value' => $account->getCarrierId(),
      '#description' => $this->t('Enter your Carrier ID for this account as found on the <a href=":url">Entity Pilot website</a>.', [
        ':url' => Url::fromUri('https://entitypilot.com/')->toString(),
      ]),
      '#required' => TRUE,
    ];

    $form['blackBoxKey'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Black box key'),
      '#maxlength' => 255,
      '#default_value' => $account->getBlackBoxKey(),
      '#description' => $this->t('Enter your Black Box key for this account as found on the <a href=":url">Entity Pilot website</a>. Please do not share this key, treat it as you would a password.', [
        ':url' => Url::fromUri('https://entitypilot.com/')->toString(),
      ]),
      '#required' => TRUE,
    ];

    $form['secret'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Secret'),
      '#maxlength' => 255,
      '#default_value' => $account->getSecret(),
      '#description' => $this->t("Enter your encryption/decryption secret. Please do not share this secret, treat it as you would a password. If you lose this secret you will not be able to retrieve your content from Entity Pilot."),
    ];

    if ($account->isNew()) {
      $form['auto_secret'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Auto-generate a secret for me.'),
        '#default_value' => 0,
      ];
      $form['secret']['#states'] = [
        'disabled' => [
          ':input[name="auto_secret"]' => ['checked' => TRUE],
        ],
      ];
      $items = [];
      foreach (range(1, 10) as $ix) {
        try {
          $items[] = Key::createNewRandomKey()->saveToAsciiSafeString();
        }
        catch (EnvironmentIsBrokenException $e) {
        }
      }
      if (!$items) {
        $items[] = $this->t('Environment is broken, could not generate keys');
      }
      $items[] = $this->t('Press reload to generate another set.');
      $form['sample_keys'] = [
        '#type' => 'details',
        '#title' => $this->t('Sample secrets'),
        '#open' => FALSE,
      ];
      $form['sample_keys']['list'] = [
        '#theme' => 'item_list',
        '#items' => $items,
      ];
    }

    $form['description'] = [
      '#type' => 'textarea',
      '#default_value' => $account->getDescription(),
      '#description' => $this->t('Enter a description for this account.'),
      '#title' => $this->t('Description'),
    ];

    $form['actions'] = ['#type' => 'actions'];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    /* @var \Drupal\entity_pilot\AccountInterface $account */
    $account = $this->entity;
    if ($account->isNew() && $form_state->getValue('auto_secret') && !$account->getSecret()) {
      try {
        $account->setSecret(Key::createNewRandomKey()->saveToAsciiSafeString());
      }
      catch (EnvironmentIsBrokenException $e) {
        $form_state->setErrorByName('secret', $this->t('Your environment does not support encryption/decryption.'));
        $form_state->setRebuild();
        return;
      }
    }
    else {
      $account->setSecret($form_state->getValue('secret'));
    }
    $status = $account->save();

    $edit_link = $this->entity->link($this->t('Edit'));
    if ($status == SAVED_UPDATED) {
      $this->setMessage($this->t('Entity Pilot account %label has been updated.', ['%label' => $account->label()]));
      $this->logger->notice('Entity Pilot account %label has been updated.', ['%label' => $account->label(), 'link' => $edit_link]);
    }
    else {
      $this->setMessage($this->t('Entity Pilot account %label has been added.', ['%label' => $account->label()]));
      $this->logger->notice('Entity Pilot account %label has been added.', ['%label' => $account->label(), 'link' => $edit_link]);
    }

    $form_state->setRedirect('entity_pilot.account_list');
  }

  /**
   * {@inheritdoc}
   */
  public function validate(array $form, FormStateInterface $form_state) {
    parent::validate($form, $form_state);
    $secret = $form_state->getValue('secret');
    if (!$form_state->hasValue('auto_secret') || !$form_state->getValue('auto_secret')) {
      // Test the key.
      try {
        Key::loadFromAsciiSafeString($form_state->getValue('secret'));
      }
      catch (BadFormatException $e) {
        $form_state->setErrorByName('secret', $this->t('Your secret is not the correct length - it must be 32 characters.'));
      }
      catch (EnvironmentIsBrokenException $e) {
        $form_state->setErrorByName('secret', $this->t('Your environment does not support encryption/decryption.'));
      }
    }
    if (!$secret && !$form_state->getValue('auto_secret')) {
      $form_state->setErrorByName('secret', $this->t('You must enter a secret or have one auto-generated.'));
    }
  }

}
