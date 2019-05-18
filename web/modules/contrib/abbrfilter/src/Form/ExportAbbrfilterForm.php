<?php
/**
 * @file
 * Contains \Drupal\abbrfilter\Form\ExportAbbrfilterForm.
 */
namespace Drupal\abbrfilter\Form;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\system\Form;
use Drupal\abbrfilter\AbbrfilterData;
use Symfony\Component\DependencyInjection\ContainerInterface;
/**
 * Implements an ExportAbbrfilter form.
 */
class ExportAbbrfilterForm extends ConfigFormBase {
  /**
   * @var \Drupal\abbrfilter\AbbrfilterData;
   */
  protected $abbrfilters;

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
    return 'abbrfilter_export_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['book.settings'];
  }

  /**
   * Export abbr filter form.
   *
   * A page that writes out your ABBR filters in the same format they go in as
   *
   * @return
   *   the form array
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['#redirect'] = FALSE;

    $list = $this->abbrfilters->get_abbr_list();
    $content = '';
    foreach ($list as $abbr) {
      $content .= $abbr['abbrs'] ."|". $abbr['replacement'] ."\n";
    }

    $form['export_abbrs'] = array(
      '#type' => 'textarea',
      '#title' => t('abbrs to export'),
      '#description' => t('use copy and paste to save this list of abbreviations or send to another site'),
      '#value' => $content,
    );

    return $form;
  }

}
?>
