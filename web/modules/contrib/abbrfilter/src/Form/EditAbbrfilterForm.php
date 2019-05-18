<?php
namespace Drupal\abbrfilter\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\system\Form;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\abbrfilter\AbbrfilterData;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Implements an DeleteAbbrfilter form.
 */
class EditAbbrfilterForm extends ConfigFormBase  {

  /**
   * @var \Drupal\abbrfilter\AbbrfilterData;
   */
  protected $abbrfilters;

  /**
   * @var abbrfilter ID.
   */
  protected $abbrfilter_id;

  /**
   * {@inheritdoc}
   */
  public function __construct(AbbrfilterData $abbrfilters) {
    $this->abbrfilters = $abbrfilters;
  }
  
  /**
   * This method lets us inject the services this class needs.
   *
   * Only inject services that are actually needed. Which services
   * are needed will vary by the controller.
   */
  public static function create(ContainerInterface $container) {
    return new static($container->get('abbrfilter.data'));
  }

   /**
   * {@inheritdoc}.
   */
  public function getFormID() {
    return 'abbrfilter_edit_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['abbrlist.settings'];
  }

  /**
   * Edit abbr filter form.
   *
   * form for editing abbreviations
   *
   * @return
   *   the form array
   */
  public function buildForm(array $form, FormStateInterface $form_state, $abbrfilter_id = null) {
    $buildinfo = $form_state->getBuildInfo();
    $this->abbrfilter_id = $buildinfo['args'][0];
    if (!isset($this->abbrfilter_id) || !is_numeric($this->abbrfilter_id)) {
      drupal_set_message(t('The ABBRfilter ID of the abbr or phrase you are trying to edit is missing or is not a number.'), 'error');
      return new RedirectResponse(\Drupal::url('abbrfilter.admin'));
    }

    $form['id'] = array(
      '#type' => 'hidden',
      '#value' => $this->abbrfilter_id,
    );

    $form['abbrs'] = array(
      '#type' => 'textfield',
      '#title' => t('Abbreviation or phrase to filter'),
      '#default_value' => $this->abbrfilters->get_abbr_text($this->abbrfilter_id),
      '#description' => t('Enter the abbreviation you want to filter.'),
      '#size' => 20,
      '#maxlength' => 255,
      '#required' => TRUE,
    );
    $form['replacement'] = array(
      '#type' => 'textfield',
      '#title' => t('Replacement text'),
      '#description' => t('Enter the full, unabbreviated term to which the abbreviation refers.'),
      '#default_value' => $this->abbrfilters->get_abbr_replacetext($this->abbrfilter_id),
      '#size' => 70,
      '#maxlength' => 255,
    );
    $form['Save abbr filter'] = array(
      '#type' => 'submit',
      '#value' => t('Save abbr filter'),
    );

    return $form;

  }

  /**
   * Edit abbr filter form submit handler.
   *
   * Validates submission by checking for duplicate entries, invalid
   * characters, and that there is an abbreviation and phrase pair
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    $row['id']          = $form_state->getValue('id');
    $row['abbrs']       = trim($form_state->getValue('abbrs'));
    $row['replacement'] = trim($form_state->getValue('replacement'));

    $list = $this->abbrfilters->get_abbr_list();
    if (preg_match('/^[\w][\w\.~\/\-]+$/', $row['abbrs'])==FALSE) {
      drupal_set_message(t('Cannot add abbreviation. %abbr contains invalid characters.', array('%abbr' => $row['abbrs'])), 'error');
      $form_state->disableRedirect(TRUE);
    }
    elseif ($row['replacement']) {
      $list[$row['id']] = array(
        'abbrs' => $row['abbrs'],
        'replacement' => $row['replacement'],
      );
      \Drupal::configFactory()->getEditable('abbrlist.settings')->set('abbr_list', $list)->save();
      drupal_set_message(t('Updated filter for: %abbr', array('%abbr' => $row['abbrs'])));
      $form_state->setRedirect('abbrfilter.admin');
      \Drupal::cache()->delete('filter');
    }
    else {
      drupal_set_message(t('Cannot add abbreviation.  You must specify the unabbreviated text for: %abbr',  array('%abbr' => $row['abbrs'])), 'error');
      $form_state->disableRedirect(TRUE);
    }
  }
}
