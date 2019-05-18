<?php

/**
 * Created by PhpStorm.
 * User: USER
 * Date: 6/7/18
 * Time: 5:47 PM
 */
namespace Drupal\drupal_helper;


class DrupalHepler extends DrupalQuery
{
    public $helper;
    public $views;
    public $crud;
    public $form;
    public $query;
    public $user;


    public function __construct()
    {
        $this->helper = new DrupalCommonHelper();
        $this->crud = new DrupalCRUD();
        $this->views = new DrupalViewsHelper();
        $this->form = new DrupalFormAPI();
        $this->query = new DrupalQuery();
        $this->user = new DrupalUser();
        parent::__construct();
    }

}