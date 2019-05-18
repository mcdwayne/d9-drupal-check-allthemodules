(function($){
    Drupal.behaviors.hierarchical_multi_select = {
	    attach: function(context, setting) {
	    	//Multiselect list
	    	var multiSelectIds = drupalSettings.hierarchical_multi_select.hierarchical_multi_select_ids.split(',');
    		multiSelectIds.forEach(function( value, index){
    			if( '' != value ) {
	    			$("#" + value).on('change', function (e) {
			    	    var optionSelected = $("#" + value + " option").filter(':selected:last');
			    		var arrSelectedText = optionSelected.text().split('');
			    		var str = '';
			    		for( var i = 0; i < arrSelectedText.length; i++  ) {
			    			if( arrSelectedText[i] == '-' ) {
			    				str += '-';
			    			}
			    		}
			    		str += '-';
			    		var bool = false;
			    		$("#" + value + " option").each(function() {
			    	    	if( bool == true ) {
			    	            if( $(this).text().indexOf(str) == 0 ) {
			    					$(this).prop('selected', true);
			    	            } else {
			    					bool = false;
			    				}
			    	        }
			    			if( $(this).val() == optionSelected.val() ) {
			    				bool = true;
			    	        }
	
			    		});
	    			});
    			}
	    	});
    		
    		//Checkboxes
    		var multiSelectNames = drupalSettings.hierarchical_multi_select.hierarchical_multi_select_chk_box_names.split(',');
    		multiSelectNames.forEach(function( value, index){
    			if( '' != value ) {
    				var arrChkNames = value.split('[');
    				if( arrChkNames[1] === undefined ) {
    					$( 'input[name="' + arrChkNames[0] + '[0]"]' ).parent().hide();
    				} else {
    					$( 'input[name="' + arrChkNames[0] + '[' + arrChkNames[1] + '[0]"]' ).parent().hide();
    				}

    				$('input[name^="' + arrChkNames[0] + '"]').parent().parent().addClass('hierarchical-multi-select-chk-box');
    				$('input[name^="' + arrChkNames[0] + '"]').change(function() {
				        if($(this).is(":checked")) {
							var optionSelected = $(this);
							var arrSelectedText = $(this).parent().text().split('');
				            var str = '';
				            for( var i = 0; i < arrSelectedText.length; i++  ) {
				                if( arrSelectedText[i] == '-' ) {
				                    str += '-';
				                }
				            }
				            str += '-';
							var bool = false;
				            $('.hierarchical-multi-select-chk-box').each(function() {
								if( bool == true ) {
				                    if( $(this).children().text().indexOf(str) == 0 ) {
				                        $(this).children().children().attr("checked", 'checked');
				                    } else {
										bool = false;
									}
				                }
								if( optionSelected.val() == $(this).children().children().val() ) {
									bool = true;
								}
				            });
				        }
    				});
    			}
	    	});
	    }
	  };
})(jQuery);