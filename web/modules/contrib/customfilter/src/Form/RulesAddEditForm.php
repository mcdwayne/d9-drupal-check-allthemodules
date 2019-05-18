<?php

// Namespace for all forms in this module.
namespace Drupal\customfilter\Form;

// Load the Drupal interface for forms.
use Drupal\Core\Form\FormInterface;

// Load the class with the custom filter entity.
use Drupal\customfilter\Entity\CustomFilter;

// Load the Drupal interface for the current state of a form.
use Drupal\Core\Form\FormStateInterface;

// Necessary for $this->t().
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Defines a form to add/edit the rules.
 */
class RulesAddEditForm implements FormInterface {
  
  use StringTranslationTrait;
  
  /**
   * Instance of customfilter_filter config entity with the rule.
   *
   * @var \Drupal\customfilter\Entity\CustomFilter $entity
   */
  protected $entity;

  /**
   * Define the ID of the form.
   */
  public function getFormID() {
    return 'customfilter_rules_add_edit_form';
  }

  /**
   * Create the form that list the rules.
   */
  public function buildForm(array $form, FormStateInterface $form_state, CustomFilter $customfilter = NULL, $rule_id = '', $operation = '') {
    $this->entity = $customfilter;
    $form = array();
    $item = array();
    if ($operation == 'edit') {
      $item = $this->entity->getRule($rule_id);
    }
    elseif ($operation == 'add') {
      $item = array(
        'rid' => '',
        'prid' => $rule_id,
        'fid' => $this->entity->id(),
        'name' => '',
        'description' => '',
        'enabled' => 1,
        'matches' => 0,
        'pattern' => '',
        'replacement' => '',
        'code' => 0,
        'weight' => 0,
      );
    }

    $matchopt = array_combine(range(0, 99), range(0, 99));
    $form['name'] = array(
      '#type' => 'textfield',
      '#description' => $this->t('The label of this replacement rule.'),
      '#title' => $this->t('Label'),
      '#default_value' => $item['name'],
      '#required' => TRUE,
    );

    $form['rid'] = array(
      '#type' => 'machine_name',
      '#default_value' => $item['rid'],
      '#description' => $this->t('The machine-readable name of the rule. This name
        must contain only lowercase letters, numbers, and underscores and
        can not be changed latter'),
      '#machine_name' => array(
        'exists' => [$this->entity, 'getRule'],
        'source' => ['name'],
      ),
      '#disabled' => ($item['rid'] == '') ? FALSE : TRUE,
      '#required' => TRUE,
    );

    $form['fid'] = array(
      '#type' => 'value',
      '#value' => $this->entity->id(),
    );

    $form['prid'] = array(
      '#type' => 'value',
      '#value' => $item['prid'],
    );

    $form['operation'] = array(
      '#type' => 'value',
      '#value' => $operation,
    );

    if ($item['prid'] != '') {
      $form['matches'] = array(
        '#type' => 'select',
        '#title' => $this->t('# Match'),
        '#description' => $this->t('n-th matched substring in parent rule. This replacement rule will replace only for that substring.'),
        '#options' => $matchopt,
        '#default_value' => $item['matches'],
      );
    }
    else {
      $form['matches'] = array(
        '#type' => 'value',
        '#value' => '',
      );
    }

    $form['weight'] = array(
      '#type' => 'value',
      '#value' => $item['weight'],
    );

    $form['enabled'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Enabled'),
      '#description' => $this->t('If selected, the rule is used.'),
      '#default_value' => $item['enabled'],
    );

    $form['description'] = array(
      '#type' => 'textarea',
      '#title' => $this->t('Description'),
      '#description' => $this->t('The description of this rule.'),
      '#default_value' => $item['description'],
    );

    $form['pattern'] = array(
      '#type' => 'textarea',
      '#title' => $this->t('Pattern'),
      '#description' => $this->t('The regular expression to use. Example: <em>/foo.*bar/</em>. Look at <a href="http://www.php.net/manual/en/regexp.reference.php">Regular Expression Details</a> for more help.'),
      '#default_value' => $item['pattern'],
      '#rows' => 3,
    );

    $form['code'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('PHP Code'),
      '#description' => $this->t('If selected, the replacement text is PHP code.'),
      '#default_value' => $item['code'],
    );

    $form['replacement'] = array(
      '#type' => 'textarea',
      '#title' => $this->t('Replacement text'),
      '#description' => $this->t('The replacement text that will replace the matched string. Use $n (i.e. $1, $25) or ${n} (i.e. ${1}, ${25}), with n range from 0 to 99, to get the n-th original strings matched ($0 represents the entire matched string). If you select <strong>PHP Code</strong>, you can enter PHP code that will be executed during the elaboration of the rules. n-th matched string is provided in <code>$matches[n]</code>, and there is a global variable <code>$vars</code> you can use to store values that will be kept during the execution of different rules of the same filter. PHP code must set a value for <code>$result</code>, and must not be entered between <code><</code><code>?php ?></code>. Note that executing incorrect PHP-code can break your Drupal site.'),
      '#default_value' => $item['replacement'],
      '#rows' => 16,
    );

    $form['submit'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Save'),
    );
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->entity = entity_load("customfilter", $form_state->getValue('fid'));
    $item = array(
      'rid' => $form_state->getValue('rid'),
      'prid' => $form_state->getValue('prid'),
      'fid' => $this->entity->id(),
      'name' => $form_state->getValue('name'),
      'description' => $form_state->getValue('description'),
      'enabled' => $form_state->getValue('enabled'),
      'matches' => $form_state->getValue('matches'),
      'pattern' => $form_state->getValue('pattern'),
      'replacement' => $form_state->getValue('replacement'),
      'code' => $form_state->getValue('code'),
      'weight' => $form_state->getValue('weight'),
    );
    switch ($form_state->getValue('operation')) {
      case 'edit':
        $this->entity->updateRule($item);
        $this->entity->save();
        break;

      case 'add':
        $this->entity->addRule($item);
        $this->entity->save();
        break;
    }
    $form_state->setRedirect('customfilter.rules.list', array('customfilter' => $form_state->getValue('fid')));
  }

}
