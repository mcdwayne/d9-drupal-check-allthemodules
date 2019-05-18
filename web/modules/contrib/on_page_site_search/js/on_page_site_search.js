	 var count = 0;
          var lastt=0, tdiff=0;
          
	jQuery(document).keypress(function(event){
                if(lastt){
            tdiff = event.timeStamp - lastt;}
                lastt = event.timeStamp;
                var key_code = (event.keyCode ? event.keyCode : event.which);
        if((key_code  >=97 && key_code  <=122) || (key_code  >=48 && key_code <=57)){
            count++;}
		if (document.activeElement.tagName=="INPUT"||document.activeElement.tagName=="SELECT"){
         
		}
		else{     
                      if(count==4&&tdiff<500)
               {
                count=0;
window.location.replace(drupalSettings.path.baseUrl + drupalSettings.path.pathPrefix +"/search/node");
                  }
			else { 
				if(tdiff>=500)
				{//console.log(tdiff);
				count=0;
				}
				//console.log(count);}
		}
            }})
