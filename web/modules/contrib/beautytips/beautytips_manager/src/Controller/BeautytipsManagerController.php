<?php

namespace Drupal\beautytips_manager\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Component\Utility\Html;
use Drupal\Core\Url;
use Drupal\Core\Link;

class BeautytipsManagerController extends ControllerBase {

  /**
   * Custom tips administration.
   */
  public function customTipsOverview() {
    $rows = [];
    $header = [
      $this->t('Element'),
      $this->t('Style'),
      $this->t('Status'),
      $this->t('Visibility'),
      $this->t('Pages'),
      $this->t('operations'),
      '',
    ];
    $tips = beautytips_manager_get_custom_tips();
    if (count($tips)) {
      $visibility = [
        $this->t('Show on every page except the listed pages.'),
        $this->t('Show on only the listed pages.'),
      ];
      foreach ($tips as $tip) {
        $tip->pages = Html::escape($tip->pages);
        $pages = ($tip->pages != substr($tip->pages, 0, 40)) ? substr($tip->pages, 0, 40) . '...' : substr($tip->pages, 0, 40);
        $rows[$tip->id]['element'] = Html::escape($tip->element);
        $rows[$tip->id]['style'] = $tip->style;
        $rows[$tip->id]['enabled'] = $tip->enabled ? $this->t('Enabled') : $this->t('Disabled');
        $rows[$tip->id]['visibility'] = $visibility[$tip->visibility];
        $rows[$tip->id]['pages'] = $pages;
        $url = Url::fromUserInput("/admin/config/user-interface/beautytips/custom-tips/$tip->id/edit");
        $rows[$tip->id]['edit'] = Link::fromTextAndUrl($this->t('edit'), $url)->toString();
        $url = Url::fromUserInput("/admin/config/user-interface/beautytips/custom-tips/$tip->id/delete");
        $rows[$tip->id]['delete'] = Link::fromTextAndUrl($this->t('delete'), $url)->toString();
      }
    }
    else {
      return [
        '#type' => 'markup',
        '#markup' => $this->t('There are no custom beautytips yet.'),
      ];
    }
    return [
      '#theme' => 'table',
      '#header' => $header,
      '#rows' => $rows,
    ];
  }

  /**
   * Custom styles administration.
   */
  public function customStylesOverview() {
    $options = $rows = [];
    $header = [$this->t('Name'), $this->t('operations'), ''];
    $styles = beautytips_manager_get_custom_styles();
    if (count($styles)) {
      foreach ($styles as $style) {
        $name = Html::escape($style->name);
        unset($style->name);
        $rows[$style->id]['name'] = new \Drupal\Component\Render\FormattableMarkup("<span class='bt-style-$name'>%name</span>", ['%name' => $name]);
        // $rows[$style->id]['name'] = '<span class="bt-style-' . $name . '">' . $name . '</span>';
        $url = Url::fromUserInput("/admin/config/user-interface/beautytips/custom-styles/$style->id/edit");
        $rows[$style->id]['edit'] = Link::fromTextAndUrl($this->t('the block admin page'), $url)->toString();
        if ($name != \Drupal::config('beautytips.basic')
            ->get('beautytips_default_style')) {
          $url = Url::fromUserInput("/admin/config/user-interface/beautytips/custom-styles/$style->id/delete");
          $rows[$style->id]['delete'] = Link::fromTextAndUrl($this->t('delete'), $url)->toString();
        }
        else {
          $rows[$style->id]['delete'] = $this->t('Default style');
        }

        $options[][$name] = [
          'cssSelect' => 'td .bt-style-' . $name,
          'text' => $this->t('<h2>Default Text</h2><p>Nam magna enim, accumsan eu, blandit sed, blandit a, eros.  Nam ante nulla, interdum vel, tristique ac, condimentum non, tellus.</p><p>Nulla facilisi. Nam magna enim, accumsan eu, blandit sed, blandit a, eros.</p>'),
          'trigger' => 'hover',
          'style' => $name,
          //'shrinkToFit' => TRUE,
        ];
      }
    }
    else {
      return [
        '#type' => 'markup',
        '#markup' => $this->t('There are no custom beautytip styles yet.'),
      ];
    }

    $table = [
      '#theme' => 'table',
      '#header' => $header,
      '#rows' => $rows,
    ];
    beautytips_add_beautytips($table, [$name => $options]);
    return $table;
  }
}
