/**
 * @file
 */

(function ($) {
    Drupal.behaviors.superselect = {
        attach: function (context) {
			//console.log(drupalSettings.superselect.selector);
            // $('select').not('.superselect-disable').tokenize2(drupalSettings.superselect);.
			if(drupalSettings.superselect.selector){
			   $(drupalSettings.superselect.selector).not('.superselect-disable').tokenize2(drupalSettings.superselect);	
			}            
            $('select.superselect-enable').not('.superselect-disable').tokenize2(drupalSettings.superselect);
        }
    };
})(jQuery);
