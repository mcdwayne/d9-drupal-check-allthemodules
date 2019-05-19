<?php

namespace Drupal\skillset_inview\Form;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Database;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\Xss;
use Drupal\Component\Utility\Unicode;

/**
 * Class AddForm.
 *
 * @package Drupal\skillset_inview\Form
 */
class AddSkill extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'skillset_inview_add_form';
  }

  /**
   * Add Form fields.
   *
   * @param array $form
   *   Form array.
   * @param FormStateInterface $form_state
   *   Form_state obj.
   *
   * @return array
   *   Form render array.
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = [
      '#attached' => [
        'library' => [
          'skillset_inview/admin',
        ],
      ],
    ];
    $form['add'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Add A Skill'),
      '#collapsible' => FALSE,
      '#description' => _skillset_inview_allowed_tags_description(),
    ];
    $form['add']['column'] = [
      '#type' => 'container',
      '#attributes' => [
        'class' => 'skill-wrapper',
      ],
    ];
    $form['add']['column']['name'] = [
      '#type' => 'textfield',
      '#title' => 'Skill Name',
      '#size' => '16',
      '#wrapper_attributes' => [
        'class' => ['left-column'],
      ],
    ];
    $form['add']['column']['percent'] = [
      '#type' => 'range',
      '#title' => $this->t('Skill Percent'),
      '#default_value' => 78,
      '#field_suffix' => '<span class="visual-assist">78</span>%',
      '#attributes' => [
        'max' => 100,
        'min' => 0,
        'step' => 1,
        'style' => 'width:84%;',
      ],
      '#wrapper_attributes' => [
        'class' => ['right-column', 'percent-column'],
      ],
    ];
    $form['actions'] = [
      '#type' => 'actions',
      '#weight' => 10,
    ];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Add Skill'),
    ];
    $current = '';
    $db = \Drupal::service('database');
    $result = $db->select('skillset_inview', 'w')
      ->fields('w')
      ->orderBy('weight')
      ->execute()
      ->fetchAll();
    $allows_tags = _skillset_inview_allowed_tags();
    foreach ($result as $item) {
      $current .= '<li>' . Xss::filter($item->name, $allows_tags) . ' <span>' . $item->percent . '%</span></li>' . PHP_EOL;
    }
    if ($current != '') {
      $form['list'] = [
        '#type' => 'details',
        '#title' => $this->t('Current Skills'),
        '#open' => TRUE,
        '#weight' => 100,
      ];
      $form['list']['current-items'] = [
        '#markup' => '<ul>' . PHP_EOL . $current . '</ul>' . PHP_EOL,
      ];
    }
    return $form;
  }


  /**
   * Validate Routine.
   *
   * @param array $form
   *   Form array.
   * @param FormStateInterface $form_state
   *   Form_state obj.
   *
   * @throws \Exception
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $name = Html::normalize($values['name']);
    $pure_name = trim(Unicode::strtolower(strip_tags($name)));

    if ($name != $values['name']) {
      $form_state->setErrorByName('name', $this->t('Your input has disallowed or broken HTML, please correct as needed.'));
    }

    if ($pure_name == '') {
      $form_state->setErrorByName('name', $this->t('A name is required.'));
    }
    $db = \Drupal::service('database');
    $result = $db->select('skillset_inview', 'w')
      ->fields('w')
      ->condition('name', '%' . $db->escapeLike($pure_name) . '%', 'LIKE')
      ->execute()
      ->fetchAssoc();
    if (!empty($result)) {
      $form_state->setErrorByName('name', $this->t('A skill with the name <q>@name</q> already exists.', ['@name' => $pure_name])
      );
    }
  }


  /**
   * Submit routine.
   *
   * @param array $form
   *   Form array.
   * @param FormStateInterface $form_state
   *   Form_state obj.
   *
   * @throws \Exception
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $name = $values['name'];
    $allows_tags = _skillset_inview_allowed_tags();
    $clean_name = Xss::filter(Html::normalize($name), $allows_tags);
    $percent = $values['percent'];
    $db = \Drupal::service('database');
    $db->insert('skillset_inview')
      ->fields([
        'name' => $clean_name,
        'percent' => $percent,
        'weight' => 0,
      ])
      ->execute();

    Cache::invalidateTags(['rendered']);
    drupal_set_message($this->t('The skill <q>@name</q> has been added.', [
      '@name' => strip_tags($name),
    ]), 'status');

  }

}
