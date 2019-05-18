<?php

namespace Drupal\address_formatter\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class AddressFormatterForm.
 *
 * @package Drupal\address_formatter\Form
 */
class AddressFormatterForm extends EntityForm {

  protected $number = 0;

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    $entity = $this->entity;
    $options = $entity->getOptions();

    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $entity->label(),
      '#description' => $this->t('A human-readable title for this option set.'),
      '#required' => TRUE,
    ];

    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $entity->id(),
      '#machine_name' => [
        'exists' => '\Drupal\address_formatter\Entity\AddressFormatter::load',
      ],
      '#disabled' => !$entity->isNew(),
    ];

    $countries = \Drupal::service('address.country_repository')->getList();

    $languagesSelect['all'] = $this->t('All');
    $languages = \Drupal::service('language_manager')->getLanguages();
    foreach ($languages as $key => $language) {
      $languagesSelect[$key] = $language->getName();
    }

    $number = $this->number;
    if ($options && (count($options) - 1) > $number) {
      $number = count($options) - 1;
    }
    $optionsValues = array_values($options);

    $description = '<p>' . $this->t('Available placeholders:') . '</p>';
    $description .= '<span>%given_name%</span>, ';
    $description .= '<span>%family_name%</span>, ';
    $description .= '<span>%address_line1%</span>, ';
    $description .= '<span>%address_line2%</span>, ';
    $description .= '<span>%locality%</span>, ';
    $description .= '<span>%organization%</span>, ';
    $description .= '<span>%administrative_area%</span>, ';
    $description .= '<span>%postal_code%</span>, ';
    $description .= '<span>%country%</span>.';
    $description .= '<span>%country_code%</span>.';
    $format = 'basic_html';
    $form['#tree'] = TRUE;
    $form['container'] = [
      '#type'       => 'container',
      '#attributes' => ['id' => 'settings-container'],
    ];
    for ($i = 0; $i <= $number; $i++) {
      $form['container']['fieldsset_' . $i] = [
        '#type' => 'fieldset',
        '#title' => $this->t('Settings'),
      ];

      $form['container']['fieldsset_' . $i]['country'] = [
        '#type'       => 'select',
        '#title' => $this->t('Country'),
        '#options' => $countries,
        '#required'   => TRUE,
        '#default_value' => $optionsValues[$i]['country'] ?? '',
      ];

      $form['container']['fieldsset_' . $i]['language'] = [
        '#type'       => 'select',
        '#title' => $this->t('Language'),
        '#options' => $languagesSelect,
        '#required'   => TRUE,
        '#default_value' => $optionsValues[$i]['language'] ?? 'all',
      ];

      $form['container']['fieldsset_' . $i]['template'] = [
        '#type'       => 'text_format',
        '#format' => $format,
        '#title' => $this->t('Template'),
        '#required'   => TRUE,
        '#default_value' => $optionsValues[$i]['template']['value'] ?? '',
        '#description' => $description,
      ];
    }
    // Disable caching on this form.
    $form_state->setCached(FALSE);
    $form['container']['actions'] = [
      '#type' => 'actions',
    ];

    $form['container']['actions']['add_item'] = [
      '#type'   => 'submit',
      '#value'  => $this->t('Add another item'),
      '#submit' => ['::addItem'],
      '#ajax'   => [
        'callback' => '::ajaxCallback',
        'wrapper'  => 'settings-container',
      ],
    ];
    if ($this->number > 0) {
      $form['container']['actions']['remove_item'] = [
        '#type'                    => 'submit',
        '#value'                   => $this->t('Remove last item'),
        '#submit'                  => ['::removeItem'],
        // Since we are removing a name, don't validate until later.
        '#limit_validation_errors' => [],
        '#ajax'                    => [
          'callback' => '::ajaxCallback',
          'wrapper'  => 'settings-container',
        ],
      ];
    }

    return $form;
  }

  /**
   * Ajax callback for Add/Remove item btn.
   *
   * @param array $form
   *   Form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form state object.
   *
   * @return mixed
   *   Container.
   */
  public function ajaxCallback(array $form, FormStateInterface $form_state) {
    return $form['container'];
  }

  /**
   * Add item handler.
   *
   * @param array $form
   *   Form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form state object.
   */
  public function addItem(array &$form, FormStateInterface $form_state) {
    $this->number++;
    $form_state->setRebuild();
  }

  /**
   * Remove item handler.
   *
   * @param array $form
   *   Form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form state object.
   */
  public function removeItem(array &$form, FormStateInterface $form_state) {
    if ($this->number > 0) {
      $this->number--;
    }
    $form_state->setRebuild();
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\address_formatter\Entity\AddressFormatter $entity */
    $entity = $this->entity;

    $status = parent::save($form, $form_state);

    switch ($status) {
      case SAVED_NEW:
        $this->messenger()->addMessage($this->t('Created the %label AddressFormatter options.', [
          '%label' => $entity->label(),
        ]));
        break;

      default:
        $this->messenger()->addMessage($this->t('Saved the %label AddressFormatter options.', [
          '%label' => $entity->label(),
        ]));
    }
    $form_state->setRedirectUrl($entity->toUrl('collection'));
  }

  /**
   * {@inheritdoc}
   */
  protected function copyFormValuesToEntity(EntityInterface $entity, array $form, FormStateInterface $form_state) {
    $options = [];
    $values = $form_state->getValues();
    foreach ($values as $key => $value) {
      if (in_array($key, ['id', 'label'])) {
        $entity->set($key, $value);
      }
      elseif (strpos($key, 'container') !== FALSE && is_array($value) && $value) {
        foreach ($value as $fieldsetName => $countryOption) {
          if (strpos($fieldsetName, 'fieldsset_') !== FALSE) {
            $optionKey = $countryOption['country'] . '-' . $countryOption['language'];
            $options[$optionKey] = $countryOption;
          }
        }
      }
    }
    $entity->set('options', $options);
  }

  /**
   * {@inheritdoc}
   */
  protected function actions(array $form, FormStateInterface $form_state) {
    $actions = parent::actions($form, $form_state);
    // Prevent access to delete button when editing default configuration.
    if ($this->entity->id() == 'default' && isset($actions['delete'])) {
      $actions['delete']['#access'] = FALSE;
    }
    return $actions;
  }

}
