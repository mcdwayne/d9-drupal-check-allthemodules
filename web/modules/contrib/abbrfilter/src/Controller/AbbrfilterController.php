<?php
namespace Drupal\abbrfilter\Controller;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
Use Drupal\Component\Utility\Html;
use Drupal\Core\Url;
use Drupal\Core\Link;
use Drupal\abbrfilter\AbbrfilterData;

class AbbrfilterController extends ControllerBase implements ContainerInjectionInterface {
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
   * {@inheritdoc}
   */
  public function __construct(AbbrfilterData $abbrfilters) {
    $this->abbrfilters = $abbrfilters;
  }

  /**
   * This is the method that will get called, with the services above already available.
   */
  public function ListAbbrfilter() {
    $header = array(
      array('data' => t('Abbreviation'), 'field' => 'abbrs', 'sort' => 'asc'),
      array('data' => t('Full term'), 'field' => 'replacement'),
      array('data' => t('Operations'), 'colspan' => 2)
    );
    $rows = array();
    $list = $this->abbrfilters->get_abbr_list();
    foreach ($list as $key => $abbr) {
      $edit_url = Url::fromRoute('abbrfilter.edit');
      $edit_url->setRouteParameter('abbrfilter_id', $key);
      $delete_url = Url::fromRoute('abbrfilter.delete');
      $delete_url->setRouteParameter('abbrfilter_id', $key);

      $rows[] = array(
        Html::escape($abbr['abbrs']),
        Html::escape($abbr['replacement']),

        Link::fromTextAndUrl(t('Edit'), $edit_url),
        Link::fromTextAndUrl(t('Delete'), $delete_url),
      );
    }

    return array(
      '#theme' => 'table',
      '#header' => $header,
      '#rows' => $rows,
      '#empty' => t('No filters available.'),
    );
  }
}
