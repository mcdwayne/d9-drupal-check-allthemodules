(function ($) {
  var fields;
  var ufields;
  var parfields;
  var fieldData;


  function fillSelect(box, optionlist, default_val = null, filter = false){
    $(box).empty();
    $(box).append($('<option>', {
         value: null,
         text: '-select-',
         attribute:'selected',
        }));

    for(var key in optionlist){ // add the fields appropriate to the content type
    if(filter == 'refs' && !(fieldData[key]['handler'] == 'default:paragraph' || fieldData[key]['handler'] == 'default:node')) {
      continue; // we wanted an entity reference
    }
		$(box).append($('<option>', {
          value: key,
         text: optionlist[key]
        }));
	  } 
       
      if(default_val!=null) $(box).val(default_val); 

  }


  function setFieldVals(cell){
   var myRow = $(cell).parents('tr');

   var myNodeBox = $(myRow).find('.ctype');
   var myNodeType = $(myNodeBox).val();

   var myFieldBox = $(myRow).find('.fields');
   var myFieldVal = $(myFieldBox).val();
   var myRuleBox = $(myRow).find('.rtype');
   var myRule = $(myRuleBox).val();
   var myExtraBox = $(myRow).find('.extra');
   var myExtraVal = $(myExtraBox).val();
   $(myFieldBox).empty();
   $(myExtraBox).empty();
  
   if(myNodeType == "" ){
     return; //that's it; we're done.
   }

	if( myRule == 'manage_referenced'){ // we need to show all linky options

      fillSelect(myExtraBox, ctypes, myExtraVal);


     }

     

     $(myFieldBox).append($('<option>', {
         value: null,
         text: '-select-',
         attribute:'selected',
        }));
		for(var conType in fields) {
		  var addMe = false;
		  var myGroup = $('<optgroup>', {
		          label: ctypes[conType],
		          
		        });
		  for(var key in fields[conType]){ // add the fields appropriate to the content type

			if(fieldData[key]['handler'] == 'default:paragraph' || fieldData[key]['handler'] == 'default:node'){
				$(myGroup).append($('<option>', {
		          value: key,
		          text: fields[conType][key]
		        }));
		        addMe = true;
			   }
             if(addMe) $(myFieldBox).append(myGroup);
			}

		} 
  if(myFieldVal!=null) {
    $(myFieldBox).val(myFieldVal);
	} else if( myRule == 'inherit'){ // we need to show all linky options here too
		
		
		  for(var key in fields[myNodeType]){ // add the fields appropriate to the content type
			if(fieldData[key]['handler'] == 'default:paragraph' || fieldData[key]['handler'] == 'default:node'){
				$(myFieldBox).append($('<option>', {
		          value: key,
		          text: fields[myNodeType][key]
		        }));
			   }
			}
		 
       $(myFieldBox).val(myFieldVal);
	}else {
      fillSelect(myFieldBox, fields[myNodeType], myFieldVal); 
	}

	if (myRule == "shared" ){
	  fillSelect(myExtraBox, ufields, myExtraVal);
	}
    if (myRule == "user" ){
    fillSelect(myFieldBox, fields[myNodeType], myFieldVal);
  }


	// now, let us see if we have a val for the field box that is a reference

	var myData = (fieldData[myFieldVal]);
	console.log(myData);
	if (myData['handler'] == 'default:paragraph'){
		fillSelect(myExtraBox, parfields, myExtraVal); 
	}
  }


  $(document).ready(function(){
    

    ctypes = drupalSettings['ctypes'];
    fields = drupalSettings['fields'];
    ufields = drupalSettings['ufields'];
    parfields = drupalSettings['parfields'];
    fieldData = drupalSettings['fieldData'];

    var cboxes = $.find('.ctype');


    $.each(cboxes,function(){
 
      setFieldVals($(this));
    });


    $('.ctype, .rtype, .fields').change(function(){

      setFieldVals($(this));
    });


   });


})(jQuery);
