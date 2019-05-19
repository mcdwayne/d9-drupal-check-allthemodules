var charstart=2;
var hColor='#ccc';
var hElement='span';
var hClass='ts-highlight';
var caseSensitiveSearch = 0;

(function ($, Drupal, drupalSettings) {
  'use strict';
  console.log(drupalSettings.tablesearch);
  
  if (drupalSettings.tablesearch.characters) charstart = drupalSettings.tablesearch.characters;
  if (drupalSettings.tablesearch.highlightColor) hColor = drupalSettings.tablesearch.highlightColor;
  if (drupalSettings.tablesearch.highlightElement) hElement = drupalSettings.tablesearch.highlightElement;
  if (drupalSettings.tablesearch.highlightClass) hClass = drupalSettings.tablesearch.highlightClass;
   if (drupalSettings.tablesearch.caseSensitiveSearch) caseSensitiveSearch = drupalSettings.tablesearch.caseSensitiveSearch;
  
 })(jQuery, Drupal, drupalSettings);


(function($) {	
$(document).ready(function(){
	var options = {
    input:"#searchableinput",
    start:charstart,
    tr:"table.table-ts tbody tr",
    td:".views-tableseachable-1",
	highlightEl:hElement,
	highlightClass: hClass,
	highlightColor: hColor
	};
	
	tablesearch(options);
	
});


$("#casesensitive").change(function() {
	if ($(this).is(':checked'))  caseSensitiveSearch = 1;
	else caseSensitiveSearch = 0;
	console.log(caseSensitiveSearch);
});


function tablesearch(options)	{
		$(options.input).keyup(function(){
		var valeur = $(this).val();
		var length = valeur.length;
		
		if (length >= options.start) {
			_check_table(options.tr, options.td, valeur, length , options.highlightEl,options.highlightClass, options.highlightColor, caseSensitiveSearch );
		}
		else {
			_clear_table(options.tr, options.td, options.highlightEl,options.highlightClass );
		}
	});
}


function _check_table(tablenametr, tablenametd, searchstring,length, highlightEl, highlightClass, highlightColor, caseSensitiveSearch) {

	var found = 0;
	var x = '';
	var x2 = '';
	var text = '';
	var m ='';
	$(tablenametr).each(function(){
	found = 0;
		$(this).find(tablenametd).each( function(){
			var text = $(this).text().trim();
			var textNotCaseSensitive = text.toLowerCase();
			var searchstringNotCaseSensitive = searchstring.toLowerCase();
			console.log(textNotCaseSensitive + " >>>> " +searchstringNotCaseSensitive);
			$(this).find(highlightEl+'.'+highlightClass).contents().unwrap();
			var html = $(this).html();
			var x = 0;
			 	//case sensitive search 
			 	if (caseSensitiveSearch) {
				if (text.indexOf(searchstring, x) >= 0)  {
					x =  text.indexOf(searchstring);
					x2 = x + length;
					m= '<'+highlightEl+' class="'+highlightClass+'" style="background-color:'+highlightColor+';">'+text.substr(x, x2-x)+'</'+highlightEl+'>';
					m = m.trim();
					
					var regex = new RegExp(text.substr(x, x2-x), 'g');
					html = html.replace(regex, m); 
					$(this).html(html.replace(text, m));
					found = 1;
					}
				}
				
				else {
				
				//case unsesitive search
				if (textNotCaseSensitive.indexOf(searchstringNotCaseSensitive, x) >= 0)  {
					console.log('unsesitive found');
					x =  textNotCaseSensitive.indexOf(searchstringNotCaseSensitive);
					x2 = x + length;
					m= '<'+highlightEl+' class="'+highlightClass+'" style="background-color:'+highlightColor+';">'+text.substr(x, x2-x)+'</'+highlightEl+'>';
					m = m.trim();
					
					var regex = new RegExp(text.substr(x, x2-x), 'g');
					html = html.replace(regex, m); 
					$(this).html(html.replace(text, m));
					found = 1;
					} 
				}
				
				
			});
			if (found == 0) $(this).closest('tr').css('display', 'none');
			else $(this).closest('tr').css('display', 'table-row');
			});
}

function _clear_table(tablenametr, tablenametd, highlightEl, highlightClass) {
		
		$(tablenametr ).each(function(){
		$(this).find(tablenametd).each( function(){
			$(this).find('.'+highlightClass).contents().unwrap();
		});
		});
	$(tablenametr ).css('display', 'table-row');
}

})(jQuery);