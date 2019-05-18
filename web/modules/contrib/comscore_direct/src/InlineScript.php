<?php

namespace Drupal\comscore_direct;

use Drupal\Core\Render\Markup;
use Drupal\Core\Url;

class InlineScript {

  protected function getData(ComscoreInformation $comscore_information) {
    $data = [];
    $data['c1'] = '2';

    if ($site_id = $comscore_information->getSiteId()) {
      $data['c2'] = $site_id;
    }

    $data['c3'] = '';

    if ($current_url = $comscore_information->getCurrentUrl()) {
      $data['c4'] = $current_url;
    }

    if ($genre = $comscore_information->getGenre()) {
      $data['c5'] = $genre;
    }

    if ($package = $comscore_information->getPackage()) {
      $data['c6'] = $package;
    }

    if ($segment = $comscore_information->getPackage()) {
      $data['c15'] = $segment;
    }

    return $data;
  }

  public function getScriptRenderable(ComscoreInformation $comscore_information) {
    $data = $this->getData($comscore_information);

    $json = json_encode($data);

    $script = <<<COMSCORE
 var _comscore = _comscore || [];
  _comscore.push($json);

 (function() {
   var s = document.createElement("script"), el = document.getElementsByTagName("script")[0]; s.async = true;
   s.src = (document.location.protocol == "https:" ? "https://sb" : "http://b") + ".scorecardresearch.com/beacon.js";
   el.parentNode.insertBefore(s, el);
 })();
COMSCORE;
    return [
//      '#type' => 'html_tag',
      '#tag' => 'script',
      '#value' => Markup::create($script),
    ];
  }

  public function getNoScriptRenderable(ComscoreInformation $comscore_information) {
    $query = $this->getData($comscore_information);
    $query['cv'] = 2.0;
    $query['cj'] = 1;

    $url = Url::fromUri('http://b.scorecardresearch.com/p', [
      'query' => $query,
    ]);
    $url_string = $url->toString(TRUE)->getGeneratedUrl();

    return [
      '#tag' => 'img',
      '#attributes' => [
        'src' => $url_string,
      ],
      '#noscript' => TRUE,
    ];
  }

}
