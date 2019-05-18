<?php
namespace Drupal\a12_best_bets\Form;


use Drupal\a12_connect\Inc\A12Config;
use Drupal\Core\Entity\Entity;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\enterprise_search\Plugin\search_api\backend\EnterpriseSearchApiPlugin;
use Drupal\enterprise_search\Utility\A12Utils;
use Symfony\Component\HttpFoundation\RedirectResponse;

class NodeTermAdd extends A12BestBetsTermAddBase {
    public function getFormId() {
        return 'a12_best_bets.node_term_add';
    }

}