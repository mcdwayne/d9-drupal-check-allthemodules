jQuery(function() {
jQuery('#idproofother-js').hide();
jQuery('body').on('change', '#idproof-js', function (event) {
var idproof = jQuery('#idproof-js').val();
if(idproof == "Other") {
  jQuery('#idproofother-js').show();
}
else {
  jQuery('#idproofother-js').hide();
  jQuery('#idproofother-js').val('');
}
jQuery('#iddetails-js').val('');
var idproofother = jQuery('#idproofother-js').val();
if(idproof == "Other" && idproofother != "")
{
  jQuery('#idproofother-js').show();
}
});
});

