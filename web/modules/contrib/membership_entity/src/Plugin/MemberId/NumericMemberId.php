<?php
declare(strict_types=1);

namespace Drupal\membership_entity\Plugin\MemberId;

use Drupal\Core\Form\FormStateInterface;
use Drupal\membership_entity\MemberId\MemberIdBase;

/**
 * Defines the numeric Member Id Plugin.
 *
 * @MemberId(
 *   id = "numeric_member_id",
 *   title = @Translation("Numeric Member ID"),
 *   description = @Translation("An auto incrementing numeric Member ID with optional start ")
 * )
 */
class NumericMemberId extends MemberIdBase {

  /**
   * {@inheritdoc}
   */
  public function next(): string {
    $options = $this->options;
    $length = !empty($options['length']) ? $options['length'] : 5;
    $member_id = '';
    //$member_id = variable_get('membership_entity_next_member_id', 0);
    //variable_set('membership_entity_next_member_id', ++$member_id);
    return str_pad($member_id, $length, '0', STR_PAD_LEFT);
  }

  /**
   * {@inheritdoc}
   */
  public function sampleValue(): string {
    // Just return the next available MemberID.
    return $this->next();
  }

  /**
   * @inheritDoc
   */
  public function optionsForm(array $form, FormStateInterface $form_state): array {
    $form = parent::optionsForm($form, $form_state);
    $options = $this->options;

    $form['length'] = array(
      '#type' => 'number',
      '#title' => $this->t('Member ID Length'),
      '#description' => $this->t("The minimum number of digits for the Member ID. IDs with fewer than <em>length</em> digits will be padded with '0's (eg. 00001). Set to 0 to disable padding."),
      '#size' => 5,
      '#min' => 0,
      '#required' => TRUE,
      '#default_value' => !empty($options['length']) ? $options['length'] : 5,
    );

    $form['start'] = array(
      '#type' => 'number',
      '#title' => $this->t('Starting Member ID'),
      '#description' => $this->t("The starting Member ID. If empty, Member IDs will start at 0."),
      '#size' => 5,
      '#min' => 0,
      '#required' => FALSE,
      '#default_value' => !empty($options['start']) ? $options['start'] : 0,
    );

    return $form;
  }

  /**
   * Validate the settings form.
   */
  public function validateSettings(&$element, &$form_state) {
    $schema = drupal_get_schema('membership_entity');
    if ($element['length']['#value'] > $schema['fields']['member_id']['length']) {
      form_error($element['length'], t('Member ID length cannot exceed %max.', array(
        '%max' => $schema['fields']['member_id']['length'],
      )));
    }
  }


}
