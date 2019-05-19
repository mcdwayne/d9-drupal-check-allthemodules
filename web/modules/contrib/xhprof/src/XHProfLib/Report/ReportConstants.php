<?php

namespace Drupal\xhprof\XHProfLib\Report;

class ReportConstants {

  /**
   * @return array
   */
  public static function getSortableColumns() {
    return array(
      "fn" => 1,
      "ct" => 1,
      "wt" => 1,
      "excl_wt" => 1,
      "ut" => 1,
      "excl_ut" => 1,
      "st" => 1,
      "excl_st" => 1,
      "mu" => 1,
      "excl_mu" => 1,
      "pmu" => 1,
      "excl_pmu" => 1,
      "cpu" => 1,
      "excl_cpu" => 1,
      "samples" => 1,
      "excl_samples" => 1
    );
  }

  /**
   * @return array
   */
  public static function getDescriptions() {
    return array(
      "fn" => "Function Name",
      "ct" => "Calls",
      "ct_perc" => "Calls%",
      "wt" => "Incl. Wall Time (microsec)",
      "wt_perc" => "IWall%",
      "excl_wt" => "Excl. Wall Time (microsec)",
      "excl_wt_perc" => "EWall%",
      "ut" => "Incl. User (microsecs)",
      "ut_perc" => "IUser%",
      "excl_ut" => "Excl. User (microsec)",
      "excl_ut_perc" => "EUser%",
      "st" => "Incl. Sys (microsec)",
      "st_perc" => "ISys%",
      "excl_st" => "Excl. Sys (microsec)",
      "excl_st_perc" => "ESys%",
      "cpu" => "Incl. CPU (microsecs)",
      "cpu_perc" => "ICpu%",
      "excl_cpu" => "Excl. CPU (microsec)",
      "excl_cpu_perc" => "ECPU%",
      "mu" => "Incl. MemUse (bytes)",
      "mu_perc" => "IMemUse%",
      "excl_mu" => "Excl. MemUse (bytes)",
      "excl_mu_perc" => "EMemUse%",
      "pmu" => "Incl. PeakMemUse (bytes)",
      "pmu_perc" => "IPeakMemUse%",
      "excl_pmu" => "Excl. PeakMemUse (bytes)",
      "excl_pmu_perc" => "EPeakMemUse%",
      "samples" => "Incl. Samples",
      "samples_perc" => "ISamples%",
      "excl_samples" => "Excl. Samples",
      "excl_samples_perc" => "ESamples%",
    );
  }

  /**
   * @return array
   */
  public static function getDiffDescriptions() {
    return array(
      "fn" => "Function Name",
      "ct" => "Calls Diff",
      "Calls%" => "Calls<br>Diff%",
      "wt" => "Incl. Wall<br>Diff<br>(microsec)",
      "IWall%" => "IWall<br> Diff%",
      "excl_wt" => "Excl. Wall<br>Diff<br>(microsec)",
      "EWall%" => "EWall<br>Diff%",
      "ut" => "Incl. User Diff<br>(microsec)",
      "IUser%" => "IUser<br>Diff%",
      "excl_ut" => "Excl. User<br>Diff<br>(microsec)",
      "EUser%" => "EUser<br>Diff%",
      "cpu" => "Incl. CPU Diff<br>(microsec)",
      "ICpu%" => "ICpu<br>Diff%",
      "excl_cpu" => "Excl. CPU<br>Diff<br>(microsec)",
      "ECpu%" => "ECpu<br>Diff%",
      "st" => "Incl. Sys Diff<br>(microsec)",
      "ISys%" => "ISys<br>Diff%",
      "excl_st" => "Excl. Sys Diff<br>(microsec)",
      "ESys%" => "ESys<br>Diff%",
      "mu" => "Incl.<br>MemUse<br>Diff<br>(bytes)",
      "IMUse%" => "IMemUse<br>Diff%",
      "excl_mu" => "Excl.<br>MemUse<br>Diff<br>(bytes)",
      "EMUse%" => "EMemUse<br>Diff%",
      "pmu" => "Incl.<br> PeakMemUse<br>Diff<br>(bytes)",
      "IPMUse%" => "IPeakMemUse<br>Diff%",
      "excl_pmu" => "Excl.<br>PeakMemUse<br>Diff<br>(bytes)",
      "EPMUse%" => "EPeakMemUse<br>Diff%",
      "samples" => "Incl. Samples Diff",
      "ISamples%" => "ISamples Diff%",
      "excl_samples" => "Excl. Samples Diff",
      "ESamples%" => "ESamples Diff%",
    );
  }

  /**
   * @return array
   */
  public static function getFormatCbk() {
    $class = 'Drupal\\xhprof\\XHProfLib\\Report\\ReportConstants';

    return array(
      "fn" => "",
      "ct" => array($class, "countFormat"),
      "ct_perc" => array($class, "percentFormat"),
      "wt" => "number_format",
      "wt_perc" => array($class, "percentFormat"),
      "excl_wt" => "number_format",
      "excl_wt_perc" => array($class, "percentFormat"),
      "ut" => "number_format",
      "ut_perc" => array($class, "percentFormat"),
      "excl_ut" => "number_format",
      "excl_ut_perc" => array($class, "percentFormat"),
      "st" => "number_format",
      "st_perc" => array($class, "percentFormat"),
      "excl_st" => "number_format",
      "excl_st_perc" => array($class, "percentFormat"),
      "cpu" => "number_format",
      "cpu_perc" => array($class, "percentFormat"),
      "excl_cpu" => "number_format",
      "excl_cpu_perc" => array($class, "percentFormat"),
      "mu" => "number_format",
      "mu_perc" => array($class, "percentFormat"),
      "excl_mu" => "number_format",
      "excl_mu_perc" => array($class, "percentFormat"),
      "pmu" => "number_format",
      "pmu_perc" => array($class, "percentFormat"),
      "excl_pmu" => "number_format",
      "excl_pmu_perc" => array($class, "percentFormat"),
      "samples" => "number_format",
      "samples_perc" => array($class, "percentFormat"),
      "excl_samples" => "number_format",
      "excl_samples_perc" => array($class, "percentFormat"),
    );
  }

  /**
   * @param $num
   *
   * @return string
   */
  public static function countFormat($num) {
    $num = round($num, 3);
    if (round($num) == $num) {
      return number_format($num);
    }
    else {
      return number_format($num, 3);
    }
  }

  /**
   * @param $s
   * @param int $precision
   *
   * @return string
   */
  public static function percentFormat($s, $precision = 1) {
    return sprintf('%.' . $precision . 'f%%', 100 * $s);
  }

}
