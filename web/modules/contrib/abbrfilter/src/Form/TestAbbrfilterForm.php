<?php
namespace Drupal\abbrfilter\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\system\Form;
use Drupal\abbrfilter\AbbrfilterData;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Implements an TestAbbrfilter form.
 */
class TestAbbrfilterForm extends ConfigFormBase {
  /**
   * @var \Drupal\abbrfilter\AbbrfilterData;
   */
  protected $abbrfilters;
  
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
  public function __construct(AbbrfilterData $abbrfilters) {
    $this->abbrfilters = $abbrfilters;
  }
  
  /**
   * {@inheritdoc}.
   */
  public function getFormID() {
    return 'abbrfilter_test_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['book.settings'];
  }

  /**
   * Test abbr filter form.
   *
   * A form to let you test a phrase in the admin interface
   *
   * @return
   *   the form array
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['test_abbr'] = array(
      '#type' => 'textfield',
      '#title' => t('Abbreviation to test'),
      '#description' => t('Enter an abbreviation or phrase that you want to test your abbrfilters on'),
      '#size' => 50,
      '#maxlength' => 255,
      '#required' => TRUE,
    );

    $list = $this->abbrfilters->get_abbr_list();
    if ($form_state->getValue('test_abbr')) {
      $test_result = $this->abbrfilters->perform_subs($form_state->getValue('test_abbr'), $list);
      if ($form_state->getValue('test_abbr') == $test_result) {
        $markup =  t("Your test abbreviation '%testphrase' did not match any filters", array('%testphrase' => $form_state->getValue('test_abbr')));
      }
      else {
        $markup = t("Your test abbreviation '%testphrase' was filtered to '%filtered_phrase'", array('%testphrase' => $form_state->getValue('test_abbr'), '%filtered_phrase' => $test_result));
      }
    }

    // @TODO: consider moving this user feedback into drupal_set_message() with status/error based on outcome
    if ($form_state->getValue('test_abbr')) {
      $form['text_result'] = array(
        '#type' => 'markup',
        '#markup' => $markup,
        '#prefix' => '<div class="abbrfilter-test-filter">',
        '#suffix' => '</div>',
      );
    }

    $form['submit'] = array(
      '#type' => 'submit',
      '#value' => t('Test this Abbreviation'),
    );

    return $form;
  }

  /**
   * Test abbr filter form submit handler.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $form_state->setRebuild(TRUE);
  }
}
