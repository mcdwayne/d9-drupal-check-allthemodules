/**
 * @file
 * Javascript behaviors for Dropdown test.
 */

(function ($) {
  'use strict';
  Drupal.behaviors.ucDropdownTest = {
    attach: function (context) {
      var results = $('main#content').find('div#results').html();
      var step = 1;
      if (typeof (results) == 'undefined') {
        $('main#content').prepend('<div id="results"><p>Test started</p><p style="display:none">Step: <span id="count">1</div></p></div>');
      }
      else {
        var step = parseInt($('main#content').find('span#count').html());
        step += 1;
        $('main#content').find('span#count').html(step);
      }

      var pid = getProductID('Parent');
      var pcid = getProductID('Class parent');

      switch (step) {
        case 2:
          // Open the attributes so they can be seen.
          $('form.uc-product-add-to-cart-form details').each(function () {
            $(this).find('summary').attr('aria-expanded', 'true');
            $(this).find('summary').attr('aria-pressed', 'true');
            $(this).attr('open', 'open');
          });

          $('div#results').append('<p>Product</p>');

          var parent = getAttributeID('Parent');
          var child = getAttributeID('Child');
          var grandchild = getAttributeID('Grandchild');
          if (child === 0) {
            $('div#results').append('<p style="color:green">Child attribute hidden</p>');
          }
          else {
            $('div#results').append('<p style="color:red">Error: Child attribute displayed</p>');
          }
          if (grandchild === 0) {
            $('div#results').append('<p style="color:green">Grandchild attribute hidden</p>');
          }
          else {
            $('div#results').append('<p style="color:red">Error: Grandchild attribute displayed</p>');
          }

          var oid = 0;
          $('input[name="products[' + pid + '][attributes][' + parent + ']"]').each(function () {
            if ($.isNumeric(this.value) &&
              (oid === 0 || oid > parseInt(this.value))) {

              oid = parseInt(this.value);
            }
          });
          if (oid > 0) {
            $('input[name="products[' + pid + '][attributes][' + parent + ']"][value="' + oid + '"]').prop('checked', true);
            $('input[name="products[' + pid + '][attributes][' + parent + ']"][value="' + oid + '"]').change();
            $('div#results').append('<p>Parent dependent option selected</p>');
          }
          break;

        case 5:
          var child = getAttributeID('Child');
          if (child !== 0) {
            $('div#results').append('<p style="color:green">Child attribute displayed</p>');
          }
          else {
            $('div#results').append('<p style="color:red">Error: Child attribute hidden</p>');
          }

          var oid = 0;
          $('input[name="products[' + pid + '][attributes][' + child + ']"]').each(function () {
            if ($.isNumeric(this.value) &&
              (oid === 0 || oid > parseInt(this.value))) {

              oid = parseInt(this.value);
            }
          });
          if (oid > 0) {
            $('input[name="products[' + pid + '][attributes][' + child + ']"][value="' + oid + '"]').prop('checked', true);
            $('input[name="products[' + pid + '][attributes][' + child + ']"][value="' + oid + '"]').change();
            $('div#results').append('<p>Child dependent option selected</p>');
          }
          break;

        case 8:
          var grandchild = getAttributeID('Grandchild');
          if (grandchild !== 0) {
            $('div#results').append('<p style="color:green">Grandchild attribute displayed</p>');
          }
          else {
            $('div#results').append('<p style="color:red">Error: Grandchild attribute hidden</p>');
          }

          var oid = 0;
          $('input[name="products[' + pid + '][attributes][' + grandchild + ']"]').each(function () {
            if ($.isNumeric(this.value) &&
              (oid === 0 || oid > parseInt(this.value))) {

              oid = parseInt(this.value);
            }
          });
          if (oid > 0) {
            $('input[name="products[' + pid + '][attributes][' + grandchild + ']"][value="' + oid + '"]').prop('checked', true);
            $('input[name="products[' + pid + '][attributes][' + grandchild + ']"][value="' + oid + '"]').change();
            $('div#results').append('<p>Grandchild option selected</p>');
          }

          var grandchild = getAttributeID('Grandchild');
          var oid = $('input[name="products[' + pid + '][attributes][' + grandchild + ']"]:checked').val();
          if (oid !== 0) {
            $('div#results').append('<p style="color:green">Grandchild option displayed</p>');
          }
          else {
            $('div#results').append('<p style="color:red">Error: Grandchild option hidden</p>');
          }

          var parent = getAttributeID('Parent');

          var oid = 0;
          $('input[name="products[' + pid + '][attributes][' + parent + ']"]').each(function () {
            if ($.isNumeric(this.value) && oid < parseInt(this.value)) {
              oid = parseInt(this.value);
            }
          });
          if (oid > 0) {
            $('input[name="products[' + pid + '][attributes][' + parent + ']"][value="' + oid + '"]').prop('checked', true);
            $('input[name="products[' + pid + '][attributes][' + parent + ']"][value="' + oid + '"]').change();
            $('div#results').append('<p>Parent non-dependent option selected</p>');
          }
          break;

        case 11:
          var parent = getAttributeID('Parent');
          var child = getAttributeID('Child');
          var grandchild = getAttributeID('Grandchild');
          if (child === 0) {
            $('div#results').append('<p style="color:green">Child attribute hidden</p>');
          }
          else {
            $('div#results').append('<p style="color:red">Error: Child attribute displayed</p>');
          }
          if (grandchild === 0) {
            $('div#results').append('<p style="color:green">Grandchild attribute hidden</p>');
          }
          else {
            $('div#results').append('<p style="color:red">Error: Grandchild attribute displayed</p>');
          }

          var oid = 0;
          $('input[name="products[' + pid + '][attributes][' + parent + ']"]').each(function () {
            if ($.isNumeric(this.value) &&
              (oid === 0 || oid > parseInt(this.value))) {

              oid = parseInt(this.value);
            }
          });
          if (oid > 0) {
            $('input[name="products[' + pid + '][attributes][' + parent + ']"][value="' + oid + '"]').prop('checked', true);
            $('input[name="products[' + pid + '][attributes][' + parent + ']"][value="' + oid + '"]').change();
            $('div#results').append('<p>Parent dependent option selected</p>');
          }
          break;

        case 14:
          var child = getAttributeID('Child');
          if (child !== 0) {
            $('div#results').append('<p style="color:green">Child attribute displayed</p>');
          }
          else {
            $('div#results').append('<p style="color:red">Error: Child attribute hidden</p>');
          }
          var oid = $('input[name="products[' + pid + '][attributes][' + child + ']"]:checked').val();
          if (typeof (oid) == 'undefined') {
            $('div#results').append('<p style="color:green">Child option removed</p>');
          }
          else {
            $('div#results').append('<p style="color:red">Error: Child option selected</p>');
          }

          oid = 0;
          $('input[name="products[' + pid + '][attributes][' + child + ']"]').each(function () {
            if ($.isNumeric(this.value) &&
              (oid === 0 || oid > parseInt(this.value))) {

              oid = parseInt(this.value);
            }
          });
          if (oid > 0) {
            $('input[name="products[' + pid + '][attributes][' + child + ']"][value="' + oid + '"]').prop('checked', true);
            $('input[name="products[' + pid + '][attributes][' + child + ']"][value="' + oid + '"]').change();
            $('div#results').append('<p>Child dependent option selected</p>');
          }
          break;

        case 17:
          var grandchild = getAttributeID('Grandchild');
          if (grandchild !== 0) {
            $('div#results').append('<p style="color:green">Grandchild attribute displayed</p>');
          }
          else {
            $('div#results').append('<p style="color:red">Error: Grandchild attribute hidden</p>');
          }
          var oid = $('input[name="products[' + pid + '][attributes][' + grandchild + ']"]:checked').val();
          if (typeof (oid) == 'undefined') {
            $('div#results').append('<p style="color:green">Grandchild option removed</p>');
          }
          else {
            $('div#results').append('<p style="color:red">Error: Grandchild option selected</p>');
          }

          $('div#results').append('<p>Product class</p>');

          var parent = getAttributeID('Class parent');
          var child = getAttributeID('Class child');
          var grandchild = getAttributeID('Class grandchild');
          if (child === 0) {
            $('div#results').append('<p style="color:green">Child attribute hidden</p>');
          }
          else {
            $('div#results').append('<p style="color:red">Error: Child attribute displayed</p>');
          }
          if (grandchild === 0) {
            $('div#results').append('<p style="color:green">Grandchild attribute hidden</p>');
          }
          else {
            $('div#results').append('<p style="color:red">Error: Grandchild attribute displayed</p>');
          }

          var oid = 0;
          $('input[name="products[' + pcid + '][attributes][' + parent + ']"]').each(function () {
            if ($.isNumeric(this.value) &&
              (oid === 0 || oid > parseInt(this.value))) {

              oid = parseInt(this.value);
            }
          });
          if (oid > 0) {
            $('input[name="products[' + pcid + '][attributes][' + parent + ']"][value="' + oid + '"]').prop('checked', true);
            $('input[name="products[' + pcid + '][attributes][' + parent + ']"][value="' + oid + '"]').change();
            $('div#results').append('<p>Parent dependent option selected</p>');
          }
          break;

        case 20:
          var child = getAttributeID('Class child');
          if (child !== 0) {
            $('div#results').append('<p style="color:green">Child attribute displayed</p>');
          }
          else {
            $('div#results').append('<p style="color:red">Error: Child attribute hidden</p>');
          }

          var oid = 0;
          $('input[name="products[' + pcid + '][attributes][' + child + ']"]').each(function () {
            if ($.isNumeric(this.value) &&
              (oid === 0 || oid > parseInt(this.value))) {

              oid = parseInt(this.value);
            }
          });
          if (oid > 0) {
            $('input[name="products[' + pcid + '][attributes][' + child + ']"][value="' + oid + '"]').prop('checked', true);
            $('input[name="products[' + pcid + '][attributes][' + child + ']"][value="' + oid + '"]').change();
            $('div#results').append('<p>Child dependent option selected</p>');
          }
          break;

        case 23:
          var grandchild = getAttributeID('Class grandchild');
          if (grandchild !== 0) {
            $('div#results').append('<p style="color:green">Grandchild attribute displayed</p>');
          }
          else {
            $('div#results').append('<p style="color:red">Error: Grandchild attribute hidden</p>');
          }

          var oid = 0;
          $('input[name="products[' + pcid + '][attributes][' + grandchild + ']"]').each(function () {
            if ($.isNumeric(this.value) &&
              (oid === 0 || oid > parseInt(this.value))) {

              oid = parseInt(this.value);
            }
          });
          if (oid > 0) {
            $('input[name="products[' + pcid + '][attributes][' + grandchild + ']"][value="' + oid + '"]').prop('checked', true);
            $('input[name="products[' + pcid + '][attributes][' + grandchild + ']"][value="' + oid + '"]').change();
            $('div#results').append('<p>Grandchild option selected</p>');
          }

          var grandchild = getAttributeID('Class grandchild');
          var oid = $('input[name="products[' + pcid + '][attributes][' + grandchild + ']"]:checked').val();
          if (oid !== 0) {
            $('div#results').append('<p style="color:green">Grandchild option displayed</p>');
          }
          else {
            $('div#results').append('<p style="color:red">Error: Grandchild option hidden</p>');
          }

          var parent = getAttributeID('Class parent');

          var oid = 0;
          $('input[name="products[' + pcid + '][attributes][' + parent + ']"]').each(function () {
            if ($.isNumeric(this.value) && oid < parseInt(this.value)) {
              oid = parseInt(this.value);
            }
          });
          if (oid > 0) {
            $('input[name="products[' + pcid + '][attributes][' + parent + ']"][value="' + oid + '"]').prop('checked', true);
            $('input[name="products[' + pcid + '][attributes][' + parent + ']"][value="' + oid + '"]').change();
            $('div#results').append('<p>Parent non-dependent option selected</p>');
          }
          break;

        case 26:
          var parent = getAttributeID('Class parent');
          var child = getAttributeID('Class child');
          var grandchild = getAttributeID('Class grandchild');
          if (child === 0) {
            $('div#results').append('<p style="color:green">Child attribute hidden</p>');
          }
          else {
            $('div#results').append('<p style="color:red">Error: Child attribute displayed</p>');
          }
          if (grandchild === 0) {
            $('div#results').append('<p style="color:green">Grandchild attribute hidden</p>');
          }
          else {
            $('div#results').append('<p style="color:red">Error: Grandchild attribute displayed</p>');
          }

          var oid = 0;
          $('input[name="products[' + pcid + '][attributes][' + parent + ']"]').each(function () {
            if ($.isNumeric(this.value) &&
              (oid === 0 || oid > parseInt(this.value))) {

              oid = parseInt(this.value);
            }
          });
          if (oid > 0) {
            $('input[name="products[' + pcid + '][attributes][' + parent + ']"][value="' + oid + '"]').prop('checked', true);
            $('input[name="products[' + pcid + '][attributes][' + parent + ']"][value="' + oid + '"]').change();
            $('div#results').append('<p>Parent dependent option selected</p>');
          }
          break;

        case 29:
          var child = getAttributeID('Class child');
          if (child !== 0) {
            $('div#results').append('<p style="color:green">Child attribute displayed</p>');
          }
          else {
            $('div#results').append('<p style="color:red">Error: Child attribute hidden</p>');
          }
          var oid = $('input[name="products[' + pcid + '][attributes][' + child + ']"]:checked').val();
          if (typeof (oid) == 'undefined') {
            $('div#results').append('<p style="color:green">Child option removed</p>');
          }
          else {
            $('div#results').append('<p style="color:red">Error: Child option selected</p>');
          }

          oid = 0;
          $('input[name="products[' + pcid + '][attributes][' + child + ']"]').each(function () {
            if ($.isNumeric(this.value) &&
              (oid === 0 || oid > parseInt(this.value))) {

              oid = parseInt(this.value);
            }
          });
          if (oid > 0) {
            $('input[name="products[' + pcid + '][attributes][' + child + ']"][value="' + oid + '"]').prop('checked', true);
            $('input[name="products[' + pcid + '][attributes][' + child + ']"][value="' + oid + '"]').change();
            $('div#results').append('<p>Child dependent option selected</p>');
          }
          break;

        case 32:
          var grandchild = getAttributeID('Class grandchild');
          if (grandchild !== 0) {
            $('div#results').append('<p style="color:green">Grandchild attribute displayed</p>');
          }
          else {
            $('div#results').append('<p style="color:red">Error: Grandchild attribute hidden</p>');
          }
          var oid = $('input[name="products[' + pcid + '][attributes][' + grandchild + ']"]:checked').val();
          if (typeof (oid) == 'undefined') {
            $('div#results').append('<p style="color:green">Grandchild option removed</p>');
          }
          else {
            $('div#results').append('<p style="color:red">Error: Grandchild option selected</p>');
          }

          $('div#results').append('<p>Test finished.</p>');
          break;

        default:
          break;

      }
      return false;

    }
  };

  var getProductID = function (label) {
    var pid = 0;
    $('form.uc-product-add-to-cart-form details').each(function () {
      $('legend span.fieldset-legend').each(function () {
        if (label === $(this).html()) {
          var attr = $(this).parent().parent().attr('id');
          var attribute = attr.split('-');
          pid = attribute[2];
        }
      });
    });
    return pid;
  };

  var getAttributeID = function (label) {
    var aid = 0;
    $('legend span.fieldset-legend').each(function () {
      if (label === $(this).html()) {
        var attr = $(this).parent().parent().find('div.fieldset-wrapper').find('div.form-radios').attr('id');
        var attribute = attr.split('-');
        aid = attribute[4];
      }
    });
    return aid;
  };

})(jQuery);
