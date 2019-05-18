<?php
/**
 * Created by PhpStorm.
 * User: tconstantin
 * Date: 19/07/2017
 * Time: 11:48
 */

namespace Drupal\a12_best_bets\Form;


use Drupal\Core\Form\FormBase;

class TaxonomyOverviewForm extends A12BestBetsOverviewFormBase {
    public function getFormId() {
        return 'a12_best_bets.taxonomy_overview';
    }
}