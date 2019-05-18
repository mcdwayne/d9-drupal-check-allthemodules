<?php
namespace Drupal\abbrfilter\Plugin\Filter;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\filter\FilterProcessResult;
use Drupal\filter\Plugin\FilterBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\abbrfilter\AbbrfilterData;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;

/**
 * Provides a filter to limit allowed HTML tags.
 *
 * @Filter(
 *   id = "filter_abbr",
 *   title = @Translation("convert abbreviations"),
 *   type = Drupal\filter\Plugin\FilterInterface::TYPE_MARKUP_LANGUAGE,
 *   settings = {
 *   },
 *   weight = -10
 * )
 */
class AbbrfilterFilter extends FilterBase implements ContainerFactoryPluginInterface{
  /**
   * Injected \Drupal\abbrfilter\AbbrFilterData service.
   */
  protected $abbrfilterData;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, AbbrfilterData $abbrfilterData) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->abbrfilterData= $abbrfilterData;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('abbrfilter.data')
    );
  }
  
  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $form['abbr_filter'] = array(
      '#type' => 'fieldset',
      '#title' => t('ABBR filter'),
      '#description' => t('You can define a global list of abbreviations to be filtered on the <a href="!url">Abbreviations Filter settings page</a>.', array('!url' => Url::fromRoute('abbrfilter.admin')->toString())),
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function process($text, $langcode) {
    $list = $this->abbrfilterData->get_abbr_list();
    return new FilterProcessResult($this->abbrfilterData->perform_subs($text, $list));
  }
  
  /**
   * {@inheritdoc}
   */
  public function tips($long = FALSE) {
    // ...
    if ($long) {
      return $this->t("If you include a abbreviation in your post that's in the whitelist, it will be augmented by an &lt;abbr&gt; tag.") .'<br />';
    }
    else {
      return $this->t('Whitelisted abbreviations will be augmented with an &lt;abbr&gt; tag.');
    }
  }
}
