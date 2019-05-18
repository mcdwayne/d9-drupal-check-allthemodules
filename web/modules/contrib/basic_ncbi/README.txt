##############
# How to use #
##############

<?php
// Load PubMed Service.
$pubMed = \Drupal::service('basic_ncbi.pubmed');

// Search Signature : Term, nb result, start index
// Search 10 article on HIV.
$results = $pubMed->search('HIV', 10);

var_dump('nb articles found : ' . $results['results_count']);
var_dump('nb articles returned  : ' . $results['results_max']);
var_dump('start index : ' . $results['results_start']);
$ids = $results['results'];

// Load Articles.
$articles = $pubMed->getMultipleArticleById($ids);
ksm($articles);
