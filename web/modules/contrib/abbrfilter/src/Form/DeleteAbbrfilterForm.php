<?php
namespace Drupal\abbrfilter\Form;
use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\system\Form;
use Drupal\abbrfilter\AbbrfilterData;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Implements an DeleteAbbrfilter form.
 */
class DeleteAbbrfilterForm extends ConfirmFormBase {

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
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'abbrfilter_delete_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $abbrfilter_id = null) {
    $buildinfo = $form_state->getBuildInfo();
    $this->abbrfilter_id = $buildinfo['args'][0];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return t('This action cannot be undone.');
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return t('Confirm');
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelText() {
    return t('Cancel');
  }

  /**
   * {@inheritdoc}
   */
  public function getFormName() {
    return 'confirm';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return t('Are you sure you want to remove %title ?', array('%title' => $this->abbrfilters->get_abbr_text($this->abbrfilter_id)));
  }

  /**V
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url('abbrfilter.admin');
  }

  /**
   * Add abbr filter form submit handler.
   *
   * Validates submission by checking for duplicate entries, invalid
   * characters, and that there is an abbreviation and phrase pair
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->abbrfilters->del_abbr($this->abbrfilter_id);
    Drupal_set_message(t('The abbreviation <em>!abbr</em> was removed from the ABBR filter list', array('!abbr' => $this->abbrfilters->get_abbr_text($this->abbrfilter_id))));
    $form_state->setRedirect('abbrfilter.admin');
  }
}
