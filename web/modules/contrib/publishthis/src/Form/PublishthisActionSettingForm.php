<?php

namespace Drupal\publishthis\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\publishthis\Classes\Publishthis_API;

/**
 * Implements an example form.
 */
class PublishthisActionSettingForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'publishthis_action_setting_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $id = NULL) {

    $pta_action_value = NULL;
    if (!empty($id)) {
      $query = \Drupal::database()->select('pt_publishactions', 'pb')
        ->fields('pb', [])
        ->condition('pb.id', $id)
        ->execute();

      $result = $query->fetchAssoc();
      $pta_action_value = unserialize($result['value']);
    }

    // Adding css.
    $form['#attached']['library'][] = 'publishthis/publishthis-settings-css';

    $form['publishthis_action_fieldset'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Publishthis Action Settings'),
    ];

    $form['publishthis_action_fieldset']['pta_title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Title'),
      '#required' => TRUE,
      '#default_value' => !empty($pta_action_value['pta_title']) ? $pta_action_value['pta_title'] : '',
    ];

    $user_storage = \Drupal::service('entity_type.manager')->getStorage('user');

    $ids = $user_storage->getQuery()
      ->condition('status', 1)
      ->condition('roles', 'administrator')
      ->execute();
    $users = $user_storage->loadMultiple($ids);

    $author = [];
    foreach ($users as $key => $user) {
      $author[$key] = $user->get('name')->value;
    }

    $form['publishthis_action_fieldset']['pta_publish_author'] = [
      '#type' => 'select',
      '#title' => $this->t('Publishing Author'),
      '#options' => $author,
      '#required' => TRUE,
      '#default_value' => isset($pta_action_value['pta_publish_author']) ? $pta_action_value['pta_publish_author'] : '1',
    ];

    $form['publishthis_action_fieldset']['pta_publish_image'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Image Field'),
      '#required' => TRUE,
      '#default_value' => isset($pta_action_value['pta_publish_image']) ? $pta_action_value['pta_publish_image'] : '',
    ];

    $form['publishthis_action_fieldset']['pta_source_url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Source Url Field'),
      '#required' => TRUE,
      '#default_value' => isset($pta_action_value['pta_source_url']) ? $pta_action_value['pta_source_url'] : '',
    ];

    $publishThisApi = new Publishthis_API();
    $templates = $publishThisApi->get_feed_templates();

    $form['publishthis_action_fieldset']['pta_feed_template'] = [
      '#type'          => 'select',
      '#title'         => $this->t('Publish This Feed Template'),
      '#required' => TRUE,
      '#options'       => $templates,
      '#default_value' => isset($pta_action_value['pta_feed_template']) ? $pta_action_value['pta_feed_template'] : '',
    ];

    $content_types = [];
    $node_types = \Drupal::service('entity.manager')->getStorage('node_type')->loadMultiple();
    foreach ($node_types as $key => $node_type) {
      $content_types[$key] = $node_type->get('name');
    }

    $form['publishthis_action_fieldset']['pta_content_type'] = [
      '#type' => 'select',
      '#title' => $this->t('Content Type'),
      '#options' => $content_types,
      '#required' => TRUE,
      '#default_value' => isset($pta_action_value['pta_content_type']) ? $pta_action_value['pta_content_type'] : '0',
    ];

    $form['publishthis_action_fieldset']['pta_format_type'] = [
      '#type' => 'radios',
      '#title' => $this->t('Content Type Format'),
      '#attributes' => [
        'class' => [
          'horizontal-list',
        ],
      ],
      '#options' => [
        'Individual' => $this->t('Individual'),
      ],
      '#default_value' => !empty($pta_action_value['pta_format_type']) ? $pta_action_value['pta_format_type'] : 'Individual',
    ];

    // Individual.
    $form['publishthis_action_fieldset']['pta_ind_add_posts'] = [
      '#type' => 'radios',
      '#title' => $this->t('Add Posts from new content'),
      '#attributes' => [
        'class' => [
          'horizontal-list',
        ],
      ],
      '#options' => [
        '0' => $this->t('No'),
        '1' => $this->t('Yes'),
      ],
      '#default_value' => isset($pta_action_value['pta_ind_add_posts']) ? $pta_action_value['pta_ind_add_posts'] : '1',
    ];

    $form['publishthis_action_fieldset']['pta_ind_modified_content'] = [
      '#type'          => 'radios',
      '#title'         => $this->t('Modified content in PublishThis updates Posts'),
      '#attributes' => [
        'class' => [
          'horizontal-list',
        ],
      ],
      '#options'       => [
        '0' => $this->t('No'),
        '1' => $this->t('Yes'),
      ],
      '#default_value' => isset($pta_action_value['pta_ind_modified_content']) ? $pta_action_value['pta_ind_modified_content'] : '1',
    ];

    $form['publishthis_action_fieldset']['pta_content_status'] = [
      '#type' => 'radios',
      '#title' => $this->t('Content Status'),
      '#attributes' => [
        'class' => [
          'horizontal-list',
        ],
      ],
      '#options' => [
        '1' => $this->t('Publish'),
        '0' => $this->t('Not Publish'),
      ],
      '#required' => TRUE,
      '#default_value' => isset($pta_action_value['pta_content_status']) ? $pta_action_value['pta_content_status'] : '0',
    ];

    $form['publishthis_action_fieldset']['pta_id'] = [
      '#type' => 'hidden',
      '#default_value' => $id,
    ];

    if (!empty($id)) {
      $button_text = 'Update Configuration';
    }
    else {
      $button_text = 'Save Configuration';
    }

    $form['publishthis_action_fieldset']['actions']['#type'] = 'actions';
    $form['publishthis_action_fieldset']['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $button_text,
      '#button_type' => 'primary',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    $id = $form_state->getValue('pta_id');
    $content_type = $form_state->getValue('pta_content_type');
    $action_title = $form_state->getValue('pta_title');
    $publish_type_id = $form_state->getValue('pta_feed_template');
    $format_type = $form_state->getValue('pta_format_type');

    $pta_action_value = serialize($form_state->getValues());

    \Drupal::database()->merge('pt_publishactions')
      ->key(['id' => $id])
      ->insertFields([
        'name' => $content_type,
        'title' => $action_title,
        'publish_type_id' => $publish_type_id,
        'format_type' => $format_type,
        'value' => $pta_action_value,
      ])
      ->updateFields([
        'name' => $content_type,
        'title' => $action_title,
        'publish_type_id' => $publish_type_id,
        'format_type' => $format_type,
        'value' => $pta_action_value,
      ])->execute();

    if (!empty($id)) {
      drupal_set_message($this->t('Publishthis Action updated successfully.'));
    }
    else {
      drupal_set_message($this->t('Publishthis Action saved successfully.'));
    }
    $url = Url::fromRoute('publishthis.publishthis-action');
    $form_state->setRedirectUrl($url);
  }

}
