<?php

/**
 * @file
 * Contains \Drupal\quickscript\Form\QuickScriptForm.
 */

namespace Drupal\quickscript\Form;

use Drupal\Component\Utility\Crypt;
use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\quickscript\Entity\QuickScript;
use Symfony\Component\Yaml\Yaml;

/**
 * Form controller for Quick Script edit forms.
 *
 * @ingroup quickscript
 */
class QuickScriptForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    /* @var $entity \Drupal\quickscript\Entity\QuickScript */
    $form = parent::buildForm($form, $form_state);

    $entity = $this->entity;

    $config = $this->config('quickscript.settings');

    $form['code']['widget'][0]['value']['#default_value'] = $entity->getCode();

    // Always add PHP tag for formatting purposes.
    $form['code']['widget'][0]['value']['#default_value'] = "<?php\n" . $form['code']['widget'][0]['value']['#default_value'];

    $form['code']['#attached']['library'][] = 'quickscript/codemirror';
    $form['code']['#attached']['library'][] = 'quickscript/quickscript';
    $form['code']['#attached']['drupalSettings']['quickscript']['enable_code_editor'] = $config->get('enable_code_editor') === 0 ? 0 : 1;

    $form['machine_name']['widget'][0]['value']['#type'] = 'machine_name';
    $form['machine_name']['widget'][0]['value'] += [
      '#machine_name' => [
        'exists' => 'quickscript_load',
        'source' => ['name', 'widget', 0, 'value'],
        'replace_pattern' => '[\s]+',
      ],
    ];

    $form['advanced'] = [
      '#type' => 'details',
      '#title' => $this->t('Advanced'),
    ];

    $form['advanced']['cron_run'] = [
      '#type' => 'select',
      '#title' => $this->t('Run on Cron'),
      '#empty_option' => 'Never',
      '#options' => [
        QuickScript::CRON_EVERY_TIME => 'Every time cron runs',
        QuickScript::CRON_EVERY_1HOUR => 'Every hour',
        QuickScript::CRON_EVERY_3HOURS => 'Every 3 hours',
        QuickScript::CRON_EVERY_6HOURS => 'Every 6 hours',
        QuickScript::CRON_EVERY_12HOURS => 'Every 12 hours',
        QuickScript::CRON_EVERY_1DAY => 'Every day',
      ],
      '#default_value' => $entity->cron_run->value,
    ];

    $form['advanced']['public_access'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable Public Access'),
      '#default_value' => $entity->public_access->value,
      '#description' => t('<em>WARNING: This will allow the script to be executed by any anonymous user that knows the URL.</em>'),
    ];

    $form['advanced']['access_token'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Access Token'),
      '#default_value' => $entity->access_token->value ? $entity->access_token->value : Crypt::randomBytesBase64(),
      '#disabled' => TRUE,
      '#states' => [
        'visible' => [
          'input[name="public_access"]' => ['checked' => TRUE],
        ],
      ],
    ];

    if ($entity->public_access->value && $entity->access_token->value) {
      $public_access_url = Url::fromRoute('entity.quickscript.public_execute', [
        'quickscript' => $entity->id(),
        'access_token' => $entity->access_token->value,
      ], ['absolute' => TRUE]);

      $form['advanced']['public_access_url'] = [
        '#type' => 'textfield',
        '#disabled' => TRUE,
        '#title' => t('Public URL'),
        '#default_value' => $public_access_url->toString(),
        '#states' => [
          'visible' => [
            'input[name="public_access"]' => ['checked' => TRUE],
          ],
        ],
      ];
    }

    $form['form'] = [
      '#type' => 'details',
      '#title' => t('Form'),
      '#description' => t('Create a custom form that is used to set configuration before running the script.<br/>Using YAML format, describe your form elements. <a href="#" class="view-yaml-example">View an example.</a>'),
      '#collapsible' => TRUE,
      '#collapsed' => TRUE,
    ];

    $form['form']['form_yaml'] = $form['form_yaml'];
    unset($form['form_yaml']);

    $form['actions']['submit_run'] = [
      '#type' => 'submit',
      '#name' => 'submit_run',
      '#value' => $this->t('Save & Run'),
      '#submit' => ['::submitForm', '::save'],
      '#weight' => 6,
    ];

    return $form;
  }

  /**
   * Removes unnecessary PHP tags.
   *
   * @param \Drupal\quickscript\Entity\QuickScript $entity
   */
  private function removePhpTags(QuickScript $entity) {
    $entity->code->value = str_replace("<?php\r\n", '', $entity->code->value);
    $entity->code->value = str_replace("<?php\n", '', $entity->code->value);
    $entity->code->value = str_replace('<?php', '', $entity->code->value);
    $entity->code->value = str_replace('<?', '', $entity->code->value);
    $entity->code->value = str_replace('?>', '', $entity->code->value);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    // Ensure the YAML can be parsed correctly.
    $form_yaml = $form_state->getValue('form_yaml')[0]['value'];
    if (!empty($form_yaml)) {
      try {
        Yaml::parse($form_yaml);
      } catch (\Exception $e) {
        $form_state->setErrorByName('form_yaml', t('YAML Parse Error: @message', ['@message' => $e->getMessage()]));
      }
    }
    return parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $entity = $this->entity;

    // Need to remove PHP tags, they are for style purposes only.
    $this->removePhpTags($entity);

    if (QuickScriptSettingsForm::encryptionEnabled()) {
      $entity->code = $entity->encrypt();
      $entity->encrypted = TRUE;
    }

    $status = parent::save($form, $form_state);

    switch ($status) {
      case SAVED_NEW:
        drupal_set_message($this->t('Created the %label Quick Script.', [
          '%label' => $entity->label(),
        ]));
        break;

      default:
        drupal_set_message($this->t('Saved the %label Quick Script.', [
          '%label' => $entity->label(),
        ]));
    }

    if ($form_state->getTriggeringElement()['#name'] == 'submit_run') {
      $form_state->setRedirect('entity.quickscript.execute', ['quickscript' => $entity->id()]);
    }
    else {
      $form_state->setRedirect('entity.quickscript.edit_form', ['quickscript' => $entity->id()]);
    }
  }

}
