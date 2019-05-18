(function (exports) {
'use strict';

(function ($) {
    $.fn.savingsCalculator = function(settings) {
      /**
        * Default result calculation.
        *
        * @param {Integer} x value to calculate the result from.
        * @returns {Integer}
        */
      function calculate(x) {
        var result = x * 1.12 * 12 - x * .23 * 12;
        return Math.round(result);
      }
      
      /**
        * Default result formatting. Separate every group of thousands by a comma.
        *
        * @param {Integer} x result to format.
        * @returns {String}
        */
      function format(x) {
        x = x.toString();

        var parts = [];
        for (var i = x.length; i > 0; i-= 3) {
          parts.push(x.substring(i - 3, i));
        }
            
        return parts.reverse().join(',');
      }
      
      settings = $.extend({
        calculate: calculate,
        format: format,
        numberInput: 'input[name="number"]',
        resultContainer: '.result'
      }, settings);
      
      return this.each(function() {
        // Cache DOM elements
        var $el = $(this);
        var $number = $el.find(settings.numberInput);
        var $result = $el.find(settings.resultContainer);
        
        /**
         * Update result on form submission.
         */
        $el.on('submit', function(e) {
          e.preventDefault();
          
          var value = parseInt($number.val()) || 0;
          var result = settings.format(settings.calculate(value));
          
          $result.text(result);
        });
      });
    };

    $('form.savings-calculator').savingsCalculator();
})(jQuery);

}((this.LaravelElixirBundle = this.LaravelElixirBundle || {})));

//# sourceMappingURL=savings-calculator.js.map
