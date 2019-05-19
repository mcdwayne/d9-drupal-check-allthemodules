<?php

namespace Drupal\usajobs;

use GuzzleHttp\Exception\RequestException;

/**
 * Request data from USAJobs API.
 */
class UsaJobsData {

/**
 * The response retrieved from USAJobs API.
 *
 * @var \Drupal\Core\Cache\CacheableJsonResponse
 */
protected $response;

/**
 * Constructs a new UsaJobsData instance.
 */
 public function __construct() {
   $this->response = $this->getData();
 }

/**
 * Get the response object.
 */
 public function getResponse() {
   return $this->response;
 }

 /**
  * Get data from the USAJobs Search API.
  *
  * @return object
  *   Return a response object.
  */
  private function getData(){
    return '[{"id":"usajobs:476095800","position_title":"SUPERVISORY VETERINARY MEDICAL OFFICER (PUBLIC HEALTH)","organization_name":"Food Safety and Inspection Service","rate_interval_code":"PA","minimum":72168,"maximum":93821,"start_date":"2017-08-03","end_date":"2017-08-08","locations":["Eureka, CA"],"url":"https://www.usajobs.gov/GetJob/ViewDetails/476095800"},{"id":"usajobs:476119700","position_title":"Maintenance Worker","organization_name":"Forest Service","rate_interval_code":"PH","minimum":20,"maximum":20,"start_date":"2017-08-04","end_date":"2017-08-14","locations":["Idaho City, ID"],"url":"https://www.usajobs.gov/GetJob/ViewDetails/476119700"},{"id":"usajobs:476161700","position_title":"National Program Leader (Data Science)","organization_name":"National Institute of Food and Agriculture","rate_interval_code":"PA","minimum":94796,"maximum":161900,"start_date":"2017-08-03","end_date":"2017-08-23","locations":["Washington, DC"],"url":"https://www.usajobs.gov/GetJob/ViewDetails/476161700"},{"id":"usajobs:476164300","position_title":"NATIONAL PROGRAM LEADER- PLANT PATHOLOGY","organization_name":"National Institute of Food and Agriculture","rate_interval_code":"PA","minimum":94796,"maximum":161900,"start_date":"2017-08-03","end_date":"2017-08-23","locations":["Washington, DC"],"url":"https://www.usajobs.gov/GetJob/ViewDetails/476164300"},{"id":"usajobs:475995500","position_title":"Agricultural Commodity Grader","organization_name":"Agricultural Marketing Service","rate_interval_code":"PH","minimum":15,"maximum":26,"start_date":"2017-08-04","end_date":"2017-08-10","locations":["Burley, ID","Albany, OR"],"url":"https://www.usajobs.gov/GetJob/ViewDetails/475995500"},{"id":"usajobs:476057600","position_title":"Wastewater Treatment Plant Operator","organization_name":"Forest Service","rate_interval_code":"PH","minimum":21,"maximum":21,"start_date":"2017-08-04","end_date":"2017-08-10","locations":["Mammoth Cave, KY"],"url":"https://www.usajobs.gov/GetJob/ViewDetails/476057600"},{"id":"usajobs:476061100","position_title":"Laborer","organization_name":"Forest Service","rate_interval_code":"PH","minimum":18,"maximum":18,"start_date":"2017-08-03","end_date":"2017-08-08","locations":["Girdwood, AK","Moose Pass, AK"],"url":"https://www.usajobs.gov/GetJob/ViewDetails/476061100"},{"id":"usajobs:475863800","position_title":"Student Trainee (Human Resources)","organization_name":"Animal and Plant Health Inspection Service","rate_interval_code":"PH","minimum":15,"maximum":19,"start_date":"2017-08-04","end_date":"2017-08-11","locations":["Minneapolis, MN"],"url":"https://www.usajobs.gov/GetJob/ViewDetails/475863800"},{"id":"usajobs:475629900","position_title":"Civil Engineer","organization_name":"Forest Service","rate_interval_code":"PA","minimum":89210,"maximum":89210,"start_date":"2017-07-31","end_date":"2017-08-14","locations":["Milwaukee, WI"],"url":"https://www.usajobs.gov/GetJob/ViewDetails/475629900"},{"id":"usajobs:475909700","position_title":"Microbiologist","organization_name":"Animal and Plant Health Inspection Service","rate_interval_code":"PA","minimum":49765,"maximum":64697,"start_date":"2017-08-03","end_date":"2017-08-09","locations":["Ames, IA"],"url":"https://www.usajobs.gov/GetJob/ViewDetails/475909700"}]';
  }
}
