<?php
namespace Drupal\abbrfilter\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\system\Form;
use Drupal\abbrfilter\AbbrfilterData;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Config\ConfigFactoryInterface;

/**
 * Implements an AddAbbrfilter form.
 */
class AddAbbrfilterForm extends ConfigFormBase {
  /**
   * Drupal configFactory object.
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;
  
  /**
   * @var \Drupal\abbrfilter\AbbrfilterData;
   */
  protected $abbrfilters;

  /**
   * {@inheritdoc}
   */
  public function __construct(ConfigFactoryInterface $config_factory, AbbrfilterData $abbrfilters) {
    parent::__construct($config_factory);
    
    $this->configFactory = $config_factory;
    $this->abbrfilters = $abbrfilters;
  }

  /**
   * This method lets us inject the services this class needs.
   *
   * Only inject services that are actually needed. Which services
   * are needed will vary by the controller.
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('abbrfilter.data')
    );
  }

  /**
   * {@inheritdoc}.
   */
  public function getFormID() {
    return 'abbrfilter_add_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['abbrlist.settings'];
  }

  /**
   * Add abbr filter form.
   *
   * form for adding multiple abbreviations at once
   *
   * @return
   *   the form array
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['help'] = array(
      '#type' => 'markup',
      '#value' => t("Enter an abbreviation you want to augment followed by '|' and the full text unabbrevaited term. Example: 'USA|United States of America'. You can enter multiple abbreviation and terms pairs by hitting return and adding more. Abbreviations are 2+ characters, begin with an alphanumeric, are case sensitive, and must be paired with an unabbreviated term. Allowed characters: [ A-Z 0-9 . - / ~ ]"),
    );

    $form['abbrs'] = array(
      '#type' => 'textarea',
      '#title' => t('abbrs'),
      '#description' => t("Enter a abbreviation you want to augment followed by '|' and the full term or phrase. Example: 'USA|United States of America'."),
      '#required' => true
    );

    $form['skipdupes'] = array(
      '#type' => 'checkbox',
      '#title' => t('Skip Duplicates'),
      '#description' => t('If checked, this options will ignore any pre-existing abbreviations and add the remaining abbreviations to the master list. Useful when importing an exported list.'),
    );

    $form['Save abbr filter'] = array(
      '#type' => 'submit',
      '#value' => t('Save ABBR filter')
    );
    return $form;
  }

  /**
   * Add abbr filter form submit handler.
   *
   * Validates submission by checking for duplicate entries, invalid
   * characters, and that there is an abbreviation and phrase pair
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $pairs = explode("\n", $form_state->getValue('abbrs'));
    $pairs = array_map('trim', $pairs);
    $pairs = array_filter($pairs, 'strlen');
    $submissioncheck = '';
    $submissions = '';
    foreach ($pairs as $pair) {
      $row = array();
      list($row['abbrs'], $row['replacement']) = explode('|', $pair);
      $row['abbrs'] = trim($row['abbrs']);
      $row['replacement'] = trim($row['replacement']);

      $list = $this->abbrfilters->get_abbr_list();
      $abbrduplicate = '';
      foreach ($list as $abbr) {
        if ($abbr['abbrs'] == $row['abbrs']) {
          $abbrduplicate .= 'DUPE';
        }
      }

      if ($abbrduplicate != '' && $form_state->getValue('skipdupes') == 1) {
        drupal_set_message(t('Skipping abbreviation %abbr, already exists.', array('%abbr' => $row['abbrs'])));
        $form_state->setRedirect('abbrfilter.admin');
      } else if ($abbrduplicate != '') {
        drupal_set_message(t('Cannot add abbreviation. %abbr already exists.', array('%abbr' => $row['abbrs'])), 'error');
        $form_state->setRedirect(FALSE);
        $submissioncheck = 'NO';
      }
      elseif (preg_match('/^[\w][\w\.~\/\-]+$/', $row['abbrs']) == FALSE) {
        drupal_set_message(t('Cannot add abbreviation. %abbr contains invalid characters.', array('%abbr' => $row['abbrs'])), 'error');
        $form_state->setRedirect(FALSE);
        $submissioncheck = 'NO';
      }
      elseif ($row['replacement']) {
        $submissions[] = $row;
      }
      else {
        drupal_set_message(t('Cannot add abbreviation.  You must specify the unabbreviated text for: %abbr',  array('%abbr' => $row['abbrs'])), 'error');
        $form_state->disableRedirect(TRUE);
        $submissioncheck = 'NO';
      }
    }

    if ($submissioncheck == '') {
      $this->configFactory->getEditable('abbrlist.settings')->set('abbr_list', $submissions)->save();
      drupal_set_message(t('Added filter for: %abbr', array('%abbr' => $row['abbrs'])));
      $form_state->setRedirect('abbrfilter.admin');
    }
    else {
      $form_state->setRedirect(FALSE);
    }
  }
}
