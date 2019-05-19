About
-----
This module help to integrate Szube API to drupal sites.


SzuBe
-----
SzuBe is a webmaster tools system allow to analyse monitor web sites (http://www.szube.com).



Configuration
-------------
/admin/config/services/szube_api

API activation (And config on SzuBe) : https://szu.be/szu/apiconfig
API documentation : https://szu.be/documentation/1002/szu-api


Usage and Example
-----------------

1. Test the API
    $result = (new \Drupal\szube_api\SzuBeAPI\Test())->test();

2. Get Sites list
    $result = (new \Drupal\szube_api\SzuBeAPI\Site())->getSitesList();

3. Get Monitors list (Sites status)
    $result = (new \Drupal\szube_api\SzuBeAPI\Monitor())->getMonitorsList();

4.1 Get Review site ids
   $result = (new \Drupal\szube_api\SzuBeAPI\Review())->getSitesList();

4.2 Get Reviews list
   $result = (new \Drupal\szube_api\SzuBeAPI\Review())->getReviewsList($siteId);



