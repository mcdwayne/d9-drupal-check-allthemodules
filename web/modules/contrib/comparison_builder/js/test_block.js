/*
**for js on comparison builder
*/
jQuery(document).ready(function(){
  
jQuery(document).on("change",'.plan_select',function(){

        var blockid = jQuery(this).parents('.block').attr('id');
        var head =  jQuery(this).attr('head');
         var node =  jQuery(this).attr('nodes');
         var fields =  jQuery(this).attr('fields');
         var curr_field_id =  jQuery(this).attr('id');
         curr_field_id = curr_field_id.split('__');
         curr_field_id = curr_field_id[0];
         var curr_field_value =  jQuery(this).val();
         var blockId =  jQuery(this).attr('blockId');
         jQuery(this).parents('.myTable').addClass('changedplans');
         var site_url = drupalSettings.path.baseUrl;

       jQuery.ajax({
       	type: 'POST',
       	url: site_url+'plans-change',
       	data: 'head=' + head + '&node=' + node + '&fields=' + fields + '&curr_field_id=' + curr_field_id + '&curr_field_value=' + curr_field_value + '&blockId=' + blockId,
       	success: function (data) {
          data = data.replace("<?php", "");
          jQuery('.changedplans').html(data);
       	},
       	error: function (data) {
          alert("failed");
       	},
       	async: false,
       });
   });
});
