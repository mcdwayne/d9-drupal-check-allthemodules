<?php

namespace Drupal\xero;

/**
 * Provides tax types in Xero.
 */
trait TaxTypeTrait {

  /**
   * Provide the correct Xero Tax Types for validation.
   */
  public static function getTaxTypes() {
    return array(
      // Global types
      'INPUT', 'OUTPUT', 'NONE', 'GSTONIMPORTS',
      // Aussie
      'CAPEXINPUT', 'EXEMPTEXPORT', 'EXEMPTEXPENSES', 'EXEMPTCAPITAL',
      'EXEMPTOUTPUT', 'INPUTTAXED', 'BASEXCLUDED', 'GSTONCAPIMPORTS',
      'GSTONIMPORTS',
      // Kiwi
      'INPUT2', 'OUTPUT2', 'ZERORATED',
      // Brit
      'CAPEXINPUT2', 'CAPEXOUTPUT', 'CAPEXOUTPUT2', 'CAPEXSRINPUT',
      'CAPEXSROUTPUT', 'ECZRINPUT', 'ECZROUTPUT', 'ECZROUTPUTSERVICES',
      'EXEMPTINPUT', 'RRINPUT', 'RROUTPUT', 'SRINPUT', 'SROUTPUT',
      'ZERORATEDINPUT', 'ZERORATEDOUTPUT',
    );
  }

}