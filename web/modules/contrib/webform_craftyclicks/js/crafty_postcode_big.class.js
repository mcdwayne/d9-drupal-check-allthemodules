/*!
// This is a collection of JavaScript code to allow easy integration of 
// postcode / address finder functionality into any website
//
// Provided by www.CraftyClicks.co.uk
//
// Version - 4.9.2 (17/05/2011)
//
// Feel free to copy/use/modify this code any way you see fit. Please keep this
// comment header in place when you do.
//
// To integrate UK postcode / address lookup on your website, please visit www.craftyclicks.co.uk for
// details of how to sign up for an account.
//
**********************************************************************************/
if (!_cp_js_included) {
var _cp_js_included = 1;
var _cp_instances = [], 
	_cp_instance_idx = 0,
	_cp_pl = ['FLAT', 'SHOP', 'UNIT', 'BLOCK', 'STALL', 'SUITE', 'APARTMENT', 'MAISONETTE', 'HOUSE NUMBER'];
function CraftyPostcodeCreate() {
	_cp_instance_idx++;
	_cp_instances[_cp_instance_idx] = new CraftyPostcodeClass();
	_cp_instances[_cp_instance_idx].obj_idx = _cp_instance_idx;
	return _cp_instances[_cp_instance_idx];
}

// strip prefix
function _cp_sp(a) {
	var pi = '', ii;
	for (ii=0; ii<_cp_pl.length; ii++) {
		pi = _cp_pl[ii];
		if (pi == a.substr(0,pi.length).toUpperCase()) {
			return (a.substr(pi.length)); // return rest of input string after known prefix
		}
	}
	return ('');
}

// extract house number
function _cp_eh(ha) {
	var hn = '';
	while  (hn = ha.shift()) {
		if (!isNaN(parseInt(hn))) {
			return (parseInt(hn));
		}
	}
	return '';
}

// handle hey press on result box
function _cp_kp(e) {
	var cc;
	if (!e) e = window.event;
	if(e.keyCode) {cc = e.keyCode;}
	else if(e.which) {cc = e.which;}
	if(cc == 13){
		this.onclick();
	}
}

function CraftyPostcodeClass () {
	this.config = { 
		lookup_url		: 'pcls1.craftyclicks.co.uk/js/', // url to use for lookup
		access_token	: '', // specify access token here to use the direct JS method,  lookup_url must be set to 'http://pcls1.craftyclicks.co.uk/lookup_js.php'
		basic_address	: 0, // 0 - Rapid/Flexi Address, 1 - Basic Address
		traditional_county	: 0, // 0 - postal county, 1 - traditional county name
		busy_img_url	: 'crafty_postcode_busy.gif',	// full url of the gif to show when waiting for result
		hide_result		: 0,		// 1 - results box disappears once a result is clicked, 0 - it stays up
		org_uppercase	: 1,		// 0 - leading uppercase, 1- all in uppercase
		town_uppercase	: 1,		// 0 - leading uppercase, 1- all in uppercase
		county_uppercase: 0,		// 0 - leading uppercase, 1- all in uppercase
		addr_uppercase	: 0,		// 0 - leading uppercase, 1- all in uppercase
		delimiter		: ', ',
		msg1			: 'Please wait while we find the address',
		err_msg1		: 'This postcode could not be found, please try again or enter your address manually',
		err_msg2		: 'This postcode is not valid, please try again or enter your address manually',
		err_msg3		: 'Unable to connect to address lookup server, please enter your address manually.',
		err_msg4		: 'An unexpected error occured, please enter your address manually.',
		res_autoselect	: 1, // the first result will be auto-selected by default
		res_select_on_change : 1, // 1 - if the user scrolls through the results they will be selected, 0 - user must explicitly click to select 
		debug_mode		: 0,
		lookup_timeout	: 10000, // time in ms
		form			: '',	// if left blank in/out elements will be shearched by id, if provided elemts will be searched by name
		elements		: '',   // element ids or form fields
		max_width		: '400px',  // width of the results box in px
		max_lines		: 1,		// height of the rsults box in text lines
		first_res_line	: '---- please select your address ----', // adds a dummy 1st line 
		result_elem_id	: '',
		on_result_ready : null,
		on_result_selected : null,
		on_error : null,
		pre_populate_common_address_parts : 0, // 1 - every time the drop-down is shown all common parts of the address get placed on the form
		elem_company    : 'crafty_out_company',
		elem_house_num  : '', // if this is blank the house hame/number is placed on street lines
		elem_street1    : 'crafty_out_street1',
		elem_street2    : 'crafty_out_street2',
		elem_street3    : 'crafty_out_street3',
		elem_town       : 'crafty_out_town',
		elem_county     : 'crafty_out_county',
		elem_postcode   : 'crafty_in_out_postcode',
		elem_udprn		: 'crafty_out_udprn',
		single_res_autoselect : 0, // 1 - if only one result is found, we select it right away rather than show a one line drop down!
		single_res_notice	: '---- address found, see below ----', // if single_res_autoselect = 1, show this message if drop down in not shown
		// extra fields for search by house name/number & flexi search
		elem_search_house : 'crafty_in_search_house',
		elem_search_street : 'crafty_in_search_street',
		elem_search_town : 'crafty_in_search_town',
		max_results		: 25, // maximum search results to display
		err_msg5		: 'The house name/number could not be found, please try again.',
		err_msg6		: 'No results found, please modify your search and try again.',
		err_msg7		: 'Too many results, please modify your search and try again.',
		err_msg9		: 'Please provide more data and try again.',
		// trial limit error msg
		err_msg8		: 'Trial account limit reached, please use AA11AA, AA11AB, AA11AD or AA11AE.'
	};

	this.xmlhttp = null;
	this.res_arr = null;
	this.disp_arr = null;
	this.res_arr_idx = 0;
	this.dummy_1st_line = 0;
	this.cc = 0;
	this.flexi_search = 0;
	this.lookup_timeout = null;
	this.obj_name = '';
	this.house_search = 0;

	this.set = function(field, val){
		this.config[field] = val;
	}

	this.res_clicked = function(idx) {
		this.cc++;
		if (this.res_selected(idx)) {
			if(0 != this.config.hide_result && ( (2 >=this.config.max_lines && 1 < this.cc) || (2 < this.config.max_lines) ) ) {
				this.update_res(null);
				this.cc = 0;
			}
		}
	}
	
	this.res_selected = function(index) {
		if (1 == this.dummy_1st_line) {
			if (0 == index) {
				return 0; // don't select the dummy first line if present
			} else {
				index--;
			}
		}
		// translate index
		index = this.disp_arr[index]['index'];
		this.populate_form_fields(this.res_arr[index]);

		if (this.config.on_result_selected) {
			this.config.on_result_selected(index);
		}
		return 1;
	}
	
	this.populate_form_fields = function(selected_line) {
		var elem = [];
		var dc = this.config.delimiter;
		
		for (var i=0; i<8; i++) {
			elem[i] = this.get_elem(i);
		}	

		elem[11] = this.get_elem(11); // udprn
		if (elem[11]) {
			elem[11].value = selected_line['udprn'];
		}
		
		if (elem[0]) { // company
			if (elem[0] == elem[1] && '' != selected_line['org']) { 
				// put company name on line1 of address
				elem[1].value = selected_line['org'];
				// shift up remaining lines
				elem[1] = elem[2]; elem[2] = elem[3]; elem[3] = null;
			} else {
				elem[0].value = selected_line['org'];
			}
		}
		
		var house_name = selected_line['housename2'];
		if ('' != house_name && '' != selected_line['housename1']) {
			house_name += dc;
		}
		house_name += selected_line['housename1'];
		var house_num  = selected_line['housenumber'];
		if (elem[7]) { // display the house name/number in a separate field
			elem[7].value = house_name;
			if ('' != house_name && '' != house_num) {
				elem[7].value += dc;
			}
			elem[7].value += house_num;
			house_name = '';
			house_num = '';
		}

		var str1 = selected_line['street1'];
		var str2 = selected_line['street2'];
		// add the house num (if any) to the street
		if ('' != house_num) {
			if ('' != str2) {
				str2 = house_num + ' ' + str2;
			} else if ('' != str1) {
				str1 = house_num + ' ' + str1;
			} else {
				str1 = house_num;
			}
		}

		var combined_street =  str2 + (str2==''?'':(str1==''?'':dc)) + str1;
		var locality_dep = selected_line['locality_dep'] ;
		var locality = selected_line['locality'] ;
		if ('' != combined_street && parseInt(combined_street) == combined_street) {
			if ('' != locality_dep) {
				locality_dep = parseInt(combined_street) + ' ' + locality_dep;
			} else {
				locality = parseInt(combined_street) + ' ' + locality;
			}
			combined_street = ''; str1 = '';
		}
		var combined_loc = locality_dep + (locality_dep=='' || locality=='' ? '':dc) + locality;
		var combined_str_loc = combined_street + (combined_street=='' || combined_loc=='' ? '':dc) + combined_loc;

		if (elem[1] && elem[2] && elem[3]) {
			if ('' != selected_line['pobox'] || '' != house_name) {
				if ('' != selected_line['pobox']) { elem[1].value = selected_line['pobox']; } else { elem[1].value = house_name; }
				if ('' == combined_loc) {
					if ('' == str2) {
						elem[2].value = str1;
						elem[3].value = '';
					} else {
						elem[2].value = str2;
						elem[3].value = str1;
					}
				} else if ('' == combined_street) {
					if ('' == locality_dep) {
						elem[2].value = locality;
						elem[3].value = '';
					} else {
						elem[2].value = locality_dep;
						elem[3].value = locality;
					}
				} else {
					elem[2].value = combined_street;
					elem[3].value = combined_loc;
				}
			} else if ('' == combined_street) { 
				if ('' == locality_dep) {
					elem[1].value = locality;
					elem[2].value = '';
					elem[3].value = '';
				} else {
					elem[1].value = locality_dep;
					elem[2].value = locality;
					elem[3].value = '';
				}
			} else if ('' == combined_loc) { 
				if ('' == str2) {
					elem[1].value = str1;
					elem[2].value = '';
					elem[3].value = '';
				} else {
					elem[1].value = str2;
					elem[2].value = str1;
					elem[3].value = '';
				}
			} else { 
				if ('' == str2) {
					elem[1].value = str1;
					if ('' == locality_dep) {
						elem[2].value = locality;
						elem[3].value = '';
					} else {
						elem[2].value = locality_dep;
						elem[3].value = locality;
					}
				} else {
					if ('' == locality_dep) {
						elem[1].value = str2;
						elem[2].value = str1;
						elem[3].value = locality;
					} else {
						if (combined_street.length < combined_loc.length) {
							elem[1].value = combined_street;
							elem[2].value = locality_dep;
							elem[3].value = locality;
						} else {
							elem[1].value = str2;
							elem[2].value = str1;
							elem[3].value = combined_loc;
						}
					}
				}
			} 
		} else if (elem[1] && elem[2])	{
			if ('' != selected_line['pobox']) {
				elem[1].value = selected_line['pobox'];
				elem[2].value = combined_str_loc; // might be blank
			} else if ('' != house_name && '' != combined_street && '' != combined_loc) { // got it all baby! spread it evenly
				if ((house_name.length + combined_street.length) < (combined_street.length + combined_loc.length)) {
					elem[1].value = house_name + (house_name==''?'':dc) + combined_street;
					elem[2].value = combined_loc;
				} else {
					elem[1].value = house_name;
					elem[2].value = combined_street + (combined_street==''?'':dc) + combined_loc;
				}
			} else if ('' != house_name && '' != combined_street) { // got house, street but no loc
				elem[1].value = house_name;
				elem[2].value = combined_street;
			} else if ('' == house_name && '' != combined_street) { // got street, no house, maybe loc
				if ('' == combined_loc) {
					if ('' != str2) {
						elem[1].value = str2;
						elem[2].value = str1;
					} else {
						elem[1].value = combined_street;
						elem[2].value = '';
					}
				} else {
					elem[1].value = combined_street;
					elem[2].value = combined_loc;
				}
			} else if ('' == combined_street && '' != house_name) { // got house, no street, maybe loc
				elem[1].value = house_name;
				elem[2].value = combined_loc;
			} else { // got no house, no street but maybe loc
				elem[1].value = combined_loc;
				elem[2].value = '';
			} 
		} else { // only got one line!
			var single_elem;
			if (elem[1]) { single_elem = elem[1]; } else if (elem[2]) { single_elem = elem[2]; } else { single_elem = elem[3]; }
			if ('' != selected_line['pobox']) {
				single_elem.value = selected_line['pobox'] + dc + combined_loc;
			} else {
				single_elem.value = house_name + (house_name=='' || combined_str_loc=='' ? '':dc) + combined_str_loc;
			}
		}
		
		if (elem[4]) {
			elem[4].value = selected_line['town'];
		}
		
		if (elem[5]) {
			elem[5].value = selected_line['county'];
		}

		if (elem[6]) {
			elem[6].value = selected_line['postcode'];
		}
		
		return 1;
	}

	this.show_busy = function() {
		var bi = document.createElement('img');
		var na = document.createAttribute('src');
		na.value = this.config.busy_img_url;
		bi.setAttributeNode(na);
		na = document.createAttribute('title');
		na.value = this.config.msg1;
		bi.setAttributeNode(na);
		this.update_res(bi);
	}

	this.disp_err = function(error_code, dbg_msg) {
		var err_node = null;	
		var err_decoded_str = '';
		if ('' != error_code) {	
			switch (error_code) {
				case '0001':
					err_decoded_str = this.config.err_msg1;
					break;
				case '0002':
					err_decoded_str = this.config.err_msg2;
					break;
				case '9001':
					err_decoded_str = this.config.err_msg3;
					break;
				case '0003':
					err_decoded_str = this.config.err_msg9;
					break;
				case '0004':
					err_decoded_str = this.config.err_msg6;
					break;
				case '0005':
					err_decoded_str = this.config.err_msg7;
					break;
				case '7001':
					err_decoded_str = this.config.err_msg8;
					break;
				default:
					err_decoded_str = '('+error_code+') '+this.config.err_msg4;
					break;
			}
			if (this.config.debug_mode) {
				var err_info = '';
				switch (error_code) {
					case '8000': err_info = ' :: No Access Token '; break; 
					case '8001': err_info = ' :: Invalid Token Format '; break; 
					case '8002': err_info = ' :: Invalid Token '; break; 
					case '8003': err_info = ' :: Out of Credits '; break; 
					case '8004': err_info = ' :: Restricted by rules '; break; 
					case '8005': err_info = ' :: Token suspended '; break; 
				}
				err_decoded_str += err_info+' :: DBG :: '+dbg_msg;
			}
			err_node = document.createTextNode(err_decoded_str);
		}	
		this.update_res (err_node);
		if (this.config.on_error) {
			this.config.on_error(err_decoded_str);
		}
	}

	this.disp_err_msg = function(error_msg) {
		var err_node = null;
		if ('' != error_msg) {	
			err_node = document.createTextNode(error_msg);
		}	
		this.update_res (err_node);
		if (this.config.on_error) {
			this.config.on_error(error_msg);
		}
	}

	this.display_res_line = function(dispstr, index) {
		// see if an options box exists already
		var postcodeResult = document.getElementById("crafty_postcode_lookup_result_option"+this.obj_idx);
		var newOption = document.createElement('option');
		newOption.appendChild(document.createTextNode(dispstr));
        if (-1 == index) newOption.setAttribute('selected', 'selected');

		if (null != postcodeResult) {	// just add a new line to existing select box
			postcodeResult.appendChild(newOption);
		} else {	// create a new select drop down list
			var newSelection = document.createElement('select');
			newSelection.id = 'crafty_postcode_lookup_result_option'+this.obj_idx;
			newSelection.onclick=Function("_cp_instances["+this.obj_idx+"].res_clicked(this.selectedIndex);");
			newSelection.onkeypress=_cp_kp;

			if (0 != this.config.res_select_on_change) {newSelection.onchange=Function("_cp_instances["+this.obj_idx+"].res_selected(this.selectedIndex);");}
			if (this.config.max_width && '' != this.config.max_width) {
				newSelection.style.width=this.config.max_width;
			}
			var num_res_lines = this.res_arr_idx;
			if (1 == this.dummy_1st_line) {
				num_res_lines++;
			}
			if ((navigator.appName=="Microsoft Internet Explorer") && (parseFloat(navigator.appVersion)<=4)) {
				newSelection.size=0;
			} else {
				if (num_res_lines >= this.config.max_lines) {
					newSelection.size=this.config.max_lines;
				} else 	{
					newSelection.size=num_res_lines;
				}
			}
			newSelection.appendChild(newOption);
			this.update_res(newSelection);
		}
	}

	this.update_res = function(new_element) {
		if (this.lookup_timeout) {
			clearTimeout (this.lookup_timeout);
		}
		
		try	{
			if (document.getElementById) {
				var the_parent = document.getElementById(this.config.result_elem_id);
				// clear out any existing contents
				if (the_parent.hasChildNodes())	{
					while (the_parent.firstChild) {
						the_parent.removeChild(the_parent.firstChild);
					}
				}
			
				// insert new contents
				if (null != new_element) {
					the_parent.appendChild(new_element);
				}		
			}
		}
		catch(er) {};
	}

	this.str_trim = function(s) {
		var l=0; 
		var r=s.length -1;
		while(l < s.length && s[l] == ' ') { l++; }
		while(r > l && s[r] == ' ') { r-=1;	}
		return s.substring(l, r+1);
	}

	this.cp_uc = function(text) {
		if ("PC" == text || "UK" == text || "EU" == text) {return (text);}
		var alpha="ABCDEFGHIJKLMNOPQRSTUVWXYZ";
		var out_text = '';
		var do_uc = 1;
		var all_uc = 0;
		for (var i=0; i<text.length; i++){
			if (-1 != alpha.indexOf(text.charAt(i))) {
				if (do_uc || all_uc) {
					out_text = out_text + text.charAt(i);
					do_uc = 0;
				} else {
					out_text = out_text + text.charAt(i).toLowerCase();
				}
			} else {
				out_text = out_text + text.charAt(i);
				if (i+2 >= text.length && "'" == text.charAt(i)) { // only one more char left, don't capitalise
					do_uc = 0; 
				} else if ("(" == text.charAt(i)) {
					close_idx = text.indexOf(")",i+1);
					if (i+3 < close_idx) { // more than 2 chars
						all_uc = 0; do_uc = 1;
					} else { // no closing bracket or 2 or les chars in brackets, leave uppercase
						all_uc = 1; 
					}				
				} else if (")" == text.charAt(i)) {
					all_uc = 0; do_uc = 1;
				} else if ("-" == text.charAt(i)) {
					close_idx = text.indexOf("-",i+1);
					if ((-1 != close_idx && i+3 >= close_idx) || i+3 >= text.length) { // less than 2 chars
						all_uc = 0; do_uc = 0;
					} else { // 2 or more chars 
						all_uc = 0; do_uc = 1;
					}		
				} else if (i+2 < text.length && "0" <= text.charAt(i) && "9" >= text.charAt(i)) {
					do_uc = 0;
				} else {
					do_uc = 1;
				}
			}
		}
		return (out_text);
	}

	this.leading_caps = function(txt, dont_do_it) {
		if (0 != dont_do_it || 2 > txt.length) { return (txt) }
		var out_text = '';
		var words = txt.split(" ");
		for (var i=0; i<words.length; i++) {	// each word in turn
			var word = this.str_trim(words[i]);
			if ('' != word)	{
				if ('' != out_text)	{
					out_text = out_text + ' ';
				}
				out_text = out_text + this.cp_uc(word);
			}
		}
		return (out_text);
	}

	this.new_res_line = function() {
		var al = [];
		al['org'] = ''; al['housename1'] = ''; al['housename2'] = ''; al['pobox'] = '';
		al['housenumber'] = ''; al['street1'] = ''; al['street2'] = '';
		al['locality_dep'] = ''; al['locality'] = ''; al['town'] = '';
		al['county'] = ''; al['postcode'] = ''; al['udprn'] = '';
		return (al);
	}

	this.res_arr_compare = function(a,b) {
		// sort by street name first, then by number
		if (a['match_quality'] > b['match_quality']) {
			return (1);
		} 
		if (a['match_quality'] < b['match_quality']) {
			return (-1);
		} 
		if (a['street1'] > b['street1']) {
			return (1);
		} 
		if (a['street1'] < b['street1']) {
			return (-1);
		}
		
		if (a['street2'] > b['street2']) {
			return (1);
		}
		if (a['street2'] < b['street2']) {
			return (-1);
		}
		
		// compare house numbers
		var numA;
		if ('' == a['housenumber']) {
			numA = _cp_eh(Array(a['housename1'], a['housename2']));
		} else {
			numA = parseInt(a['housenumber']);
		}
		var numB;
		if ('' == b['housenumber']) {
			numB = _cp_eh(Array(b['housename1'], b['housename2']));
		} else {
			numB = parseInt(b['housenumber']);
		}
		// premises with street numbers go to the top of the list
		if ('' == numA && '' != numB) {
			return (1);
		} else if ('' != numA && '' == numB) {
			return (-1);
		} else {
			if (numA > numB) {
				return (1);
			}
			if (numA < numB) {
				return (-1);
			}
		}
		
		// strip off known prefixes
		var hseA = _cp_sp(a['housename1']);
		if (!isNaN(parseInt(hseA))) {
			hseA = parseInt(hseA);
		}
		var hseB = _cp_sp(b['housename1']);
		if (!isNaN(parseInt(hseB))) {
			hseB = parseInt(hseB);
		}
		if (hseA > hseB) {
			return (1);
		}
		if (hseA < hseB) {
			return (-1);
		}

		var hseA = _cp_sp(a['housename2']);
		if (!isNaN(parseInt(hseA))) {
			hseA = parseInt(hseA);
		}
		var hseB = _cp_sp(b['housename2']);
		if (!isNaN(parseInt(hseB))) {
			hseB = parseInt(hseB);
		}
		if (hseA > hseB) {
			return (1);
		}
		if (hseA < hseB) {
			return (-1);
		}
		
		hseA = a['housename2']+a['housename1'];
		hseB = b['housename2']+b['housename1'];
		if (hseA > hseB) {
			return (1);
		}
		if (hseA < hseB) {
			return (-1);
		}

		if (a['org'] > b['org']) {
			return (1);
		}
		if (a['org'] < b['org']) {
			return (-1);
		}

		return (1);
	}
	
	this.disp_res_arr = function() {
		// sort the results
		this.res_arr = this.res_arr.sort(this.res_arr_compare);
		// select top result in required
		if (0 != this.config.res_autoselect) {
			this.populate_form_fields(this.res_arr[0]);
		}				
		// create a display array
		var dc = this.config.delimiter;
		this.disp_arr = [];
		for (var i=0;i<this.res_arr_idx;i++) {
			var arrayline = this.res_arr[i];
			var dispstr = arrayline['org'] + (arrayline['org'] !='' ? dc : '') + 
						  arrayline['housename2'] + (arrayline['housename2'] != '' ? dc : '') + 
						  arrayline['housename1'] + (arrayline['housename1'] != '' ? dc : '') + 
						  arrayline['pobox'] + (arrayline['pobox'] != '' ? dc : '') + 
						  arrayline['housenumber'] + (arrayline['housenumber'] != '' ? ' ' : '') +
						  arrayline['street2'] + (arrayline['street2'] != '' ? dc : '') +
						  arrayline['street1'] + (arrayline['street1'] != '' ? dc : '') +
						  arrayline['locality_dep'] + (arrayline['locality_dep'] != '' ? dc : '') +
						  arrayline['locality'] + (arrayline['locality'] != '' ? dc : '') +
						  arrayline['town'];
			if (this.flexi_search) {
				dispstr += dc + arrayline['postcode'];
			}
						  
			var displine = [];
			displine['index'] = i;
			displine['str'] = dispstr;
			this.disp_arr[i] = displine;
		}
		// display it
		this.dummy_1st_line = 0;
		if ('' != this.config.first_res_line) {
			this.dummy_1st_line = 1;
			this.display_res_line(this.config.first_res_line, -1);
		}
		for (var i=0;i<this.res_arr_idx;i++) {
			this.display_res_line(this.disp_arr[i]['str'], i);
		}
		if (this.config.pre_populate_common_address_parts) {
			// build an array containing the common parts of all the addresses
			var common_result = this.new_res_line();
			common_result['org'] = this.res_arr[0]['org'];
			common_result['housename1'] = this.res_arr[0]['housename1']; 
			common_result['housename2'] = this.res_arr[0]['housename2']; 
			common_result['pobox'] = this.res_arr[0]['pobox'];
			common_result['housenumber'] = this.res_arr[0]['housenumber']; 
			common_result['street1'] = this.res_arr[0]['street1']; 
			common_result['street2'] = this.res_arr[0]['street2'];
			common_result['locality_dep'] = this.res_arr[0]['locality_dep'];
			common_result['locality'] = this.res_arr[0]['locality'];
			common_result['town'] = this.res_arr[0]['town'];
			common_result['county'] = this.res_arr[0]['county'];
			common_result['postcode'] = this.res_arr[0]['postcode'];
			common_result['udprn'] = this.res_arr[0]['udprn'];
			for (var i=1;i<this.res_arr_idx;i++) {
				if (this.res_arr[i]['org'] != common_result['org']) {
					common_result['org'] = '';
				}
				if (this.res_arr[i]['housename2'] != common_result['housename2']) {
					common_result['housename2'] = '';
				}
				if (this.res_arr[i]['housename1'] != common_result['housename1']) {
					common_result['housename1'] = '';
				}
				if (this.res_arr[i]['pobox'] != common_result['pobox']) {
					common_result['pobox'] = '';
				}
				if (this.res_arr[i]['housenumber'] != common_result['housenumber']) {
					common_result['housenumber'] = '';
				}
				if (this.res_arr[i]['street1'] != common_result['street1']) {
					common_result['street1'] = '';
				}
				if (this.res_arr[i]['street2'] != common_result['street2']) {
					common_result['street2'] = '';
				}
				if (this.res_arr[i]['locality_dep'] != common_result['locality_dep']) {
					common_result['locality_dep'] = '';
				}
				if (this.res_arr[i]['locality'] != common_result['locality']) {
					common_result['locality'] = '';
				}
				if (this.res_arr[i]['town'] != common_result['town']) {
					common_result['town'] = '';
				}
				if (this.res_arr[i]['county'] != common_result['county']) {
					common_result['county'] = '';
				}
				if (this.res_arr[i]['postcode'] != common_result['postcode']) {
					common_result['postcode'] = '';
				}
				if (this.res_arr[i]['udprn'] != common_result['udprn']) {
					common_result['udprn'] = '';
				}
			}

			this.populate_form_fields(common_result);
		}
	}

	this.get_elem = function(idx) {
		var el_name = '';
		var el = null;
		if ('' != this.config.elements) {
			// old comma separated list method
			var en = this.config.elements.split(",");
			el_name = en[idx];
		} else {
			// new way, translated to old way.. to keep legacy code happy
			switch (idx) {
				case 0:
					el_name = this.config.elem_company;
					break;
				case 1:
					el_name = this.config.elem_street1;
					break;
				case 2:
					el_name = this.config.elem_street2;
					break;
				case 3:
					el_name = this.config.elem_street3;
					break;
				case 4:
					el_name = this.config.elem_town;
					break;
				case 5:
					el_name = this.config.elem_county;
					break;
				case 6:
				default:
					el_name = this.config.elem_postcode;
					break;
				case 7: // new separate house name or number field
					el_name = this.config.elem_house_num;
					break;
				case 8: // new input for house and flexi address search
					el_name = this.config.elem_search_house;
					break;
				case 9: // new input for flexi address search
					el_name = this.config.elem_search_street;
					break;
				case 10: // new input for flexi address search
					el_name = this.config.elem_search_town;
					break;
				case 11: 
					el_name = this.config.elem_udprn;
					break;
			}
		}
		if ('' != el_name) {
			if ('' != this.config.form) {
				el = document.forms[this.config.form].elements[el_name];
			} else if (document.getElementById) {
				el = document.getElementById(el_name);
			}
		}
		return (el);
	}

	this.doHouseSearch = function() {
		var he = this.get_elem(8);
		if (he && 0 < he.value.length) {
			this.house_search = 1;
		}
		this.doLookup();
	}

	this.doLookup = function() {
		this.xmlhttp=null;

		var pe = this.get_elem(6);
		var pc = null;

		if (pe) { 
			this.show_busy(); // show busy img - this will clear any errors/previous results
			this.lookup_timeout = setTimeout ( "_cp_instances["+this.obj_idx+"].lookup_timeout_err()", this.config.lookup_timeout );
			pc = this.validate_pc(pe.value);
		}
		
		if (null != pc) {
			this.direct_xml_fetch(0, pc);
		} else {
			this.disp_err('0002', 'invalid postcode format');
		}
	}

	this.flexiSearch = function() {
		this.xmlhttp=null;

		var in_str = '';
		if (this.get_elem(8) && '' != this.get_elem(8).value) {
			in_str+='&search_house='+this.get_elem(8).value;
		}
		if (this.get_elem(9) && '' != this.get_elem(9).value) {
			in_str+='&search_street='+this.get_elem(9).value;
		}
		if (this.get_elem(10) && '' != this.get_elem(10).value) {
			in_str+='&search_town='+this.get_elem(10).value;
		}

		if ('' != in_str) { 
			this.show_busy(); // show busy img - this will clear any errors/previous results
			this.lookup_timeout = setTimeout ( "_cp_instances["+this.obj_idx+"].lookup_timeout_err()", this.config.lookup_timeout );
			this.direct_xml_fetch(1, in_str);
		} else {
			this.disp_err('0003', 'search string too short');
		}
	}

	this.validate_pc = function (dirty_pc) {
		// first strip out anything not alphanumenric
		var pc = '';
		do {
			pc = dirty_pc;
			dirty_pc = dirty_pc.replace(/[^A-Za-z0-9]/, "");
		} while (pc != dirty_pc);
		pc = dirty_pc.toUpperCase();
		// check if we have the right length with what is left
		if (7 >= pc.length && 5 <= pc.length) {
			// get the in code 
			var inc = pc.substring(pc.length-3,pc.length);
			// get the out code
			var outc = pc.substring(0, pc.length-3);
			// now validate both in and out codes
			if (true == /[CIKMOV]/.test(inc)) {
				return null;
			}
			// inCode must be NAA
			if ( '0' <= inc.charAt(0) && '9' >= inc.charAt(0) &&
				 'A' <= inc.charAt(1) && 'Z' >= inc.charAt(1) &&
				 'A' <= inc.charAt(2) && 'Z' >= inc.charAt(2) ) {
				// outcode must be one of AN, ANN, AAN, ANA, AANN, AANA
				switch (outc.length) { 
					case 2: // AN
						if ('A' <= outc.charAt(0) && 'Z' >= outc.charAt(0) &&
							'0' <= outc.charAt(1) && '9' >= outc.charAt(1) ) { return (pc); }
						break;
					case 3: // ANN, AAN, ANA
						if ('A' <= outc.charAt(0) && 'Z' >= outc.charAt(0)) {
							if ('0' <= outc.charAt(1) && '9' >= outc.charAt(1) &&
								'0' <= outc.charAt(2) && '9' >= outc.charAt(2) ) { return (pc); }
							else if ('A' <= outc.charAt(1) && 'Z' >= outc.charAt(1) &&
									 '0' <= outc.charAt(2) && '9' >= outc.charAt(2) ) { return (pc); }
							else if ('0' <= outc.charAt(1) && '9' >= outc.charAt(1) &&
									 'A' <= outc.charAt(2) && 'Z' >= outc.charAt(2) ) { return (pc); }
						}
						break;
					case 4: // AANN, AANA
						if ('A' <= outc.charAt(0) && 'Z' >= outc.charAt(0) &&
							'A' <= outc.charAt(1) && 'Z' >= outc.charAt(1) &&
							'0' <= outc.charAt(2) && '9' >= outc.charAt(2)) {
							if ('0' <= outc.charAt(3) && '9' >= outc.charAt(3) ) { return (pc); }
							else if ('A' <= outc.charAt(3) && 'Z' >= outc.charAt(3) ) { return (pc); }
						}
						break;
					default:
						break;
				}
			}
		}
		return null;
	}


	this.direct_xml_fetch = function(type, search_str) {
		try	{
			var the_parent = document.getElementById(this.config.result_elem_id);

			var url = '';
			if ("https:" == document.location.protocol) {
				url = 'https://';
			} else {
				url = 'http://';
			}
			
			if (0 == type) { // postcode search
				url += this.config.lookup_url;
				if (this.config.basic_address) {
					url += 'basicaddress';
				} else {
					url += 'rapidaddress';
				}
				url += '?postcode='+search_str+'&callback=_cp_instances['+this.obj_idx+'].handle_js_response&callback_id=0';
			} else { // flexi address search
				if (this.config.basic_address) {
					this.disp_err('1207', 'BasicAddress can\'t be used for Flexi Search!');
					return;
				} else {
					url += this.config.lookup_url+'flexiaddress?callback=_cp_instances['+this.obj_idx+'].handle_js_response&callback_id=1';
					url += '&max_results='+this.config.max_results;
					url += search_str;
				}

			}
			if ('' != this.config.access_token) {
				url += '&key='+this.config.access_token;
			}
			var cs = document.createElement("script");
			cs.src = encodeURI(url);
			cs.type = "text/javascript";
			the_parent.appendChild(cs);
		}
		catch(er){
			this.disp_err('1206', er);
		};
	}

	this.handle_js_response = function (callback_id, status, data) {
		if (!status) { // an error
			var ef = data['error_code'];
			var em = data['error_msg'];
			this.disp_err(ef, em);
		} else { // got data
			this.res_arr = [];
			this.res_arr_idx = 0;

			if (0 == callback_id) {
				// single postcode
				this.flexi_search = 0;
				if (this.house_search) {
					data = this.filter_data_by_house_name(data);
					if (null == data) {
						// no luck
						this.disp_err_msg(this.config.err_msg5);
						return;
					}
				}
				this.add_to_res_array(data);
			} else {
				// flexi result, could be a few postcodes
				this.flexi_search = 1;
				this.res_arr['total_postcode_count'] = data['total_postcode_count'];
				this.res_arr['total_thoroughfare_count'] = data['total_thoroughfare_count'];
				this.res_arr['total_delivery_point_count'] = data['total_delivery_point_count'];
				for (var res_idx=1; res_idx<=data['total_postcode_count']; res_idx++) {
					this.add_to_res_array(data[res_idx]);
				}
			}

			if (this.res_arr_idx) {
				var res_autoselected = false;
				if (1 == this.res_arr_idx && this.config.single_res_autoselect) {
					// only one result no need to show a drop down, just use the result
					var msg_node = null;
					if ('' != this.config.single_res_notice) {
						msg_node = document.createTextNode(this.config.single_res_notice);
					}
					this.update_res (msg_node);
					this.populate_form_fields(this.res_arr[0]);
					res_autoselected = true;
				} else {
					// sort & display results
					this.disp_res_arr();
					document.getElementById("crafty_postcode_lookup_result_option"+this.obj_idx).focus();
				}
				if (0 == callback_id && '' != data['postcode']) {
					var pe = this.get_elem(6);
					pe.value = data['postcode'];
				}
				if (this.config.on_result_ready) {
					this.config.on_result_ready();
				}
				if (res_autoselected && this.config.on_result_selected) {
					this.config.on_result_selected(0);
				}
			} else {
				this.disp_err( '1205', 'no result to display' );
			}
		}
	}

	this.add_to_res_array = function (data) {
		// loop over all streets
		for (var str_idx=1; str_idx<=data['thoroughfare_count']; str_idx++) {
			var str1 = data[str_idx]['thoroughfare_name'];
			if ('' != data[str_idx]['thoroughfare_descriptor']) {
				str1 += ' '+data[str_idx]['thoroughfare_descriptor'];
			}
			str1 = this.leading_caps(str1, this.config.addr_uppercase);
			var str2 = data[str_idx]['dependent_thoroughfare_name'];
			if ('' != data[str_idx]['dependent_thoroughfare_descriptor']) {
				str2 += ' '+data[str_idx]['dependent_thoroughfare_descriptor'];
			}
			str2 = this.leading_caps(str2, this.config.addr_uppercase);
			if ('delivery_point_count' in data[str_idx]  && 0 < data[str_idx]['delivery_point_count']) {
				// loop over all premises on this street
				for (var p_idx=1; p_idx<=data[str_idx]['delivery_point_count']; p_idx++) {
					var al = this.new_res_line();
					al['street1'] = str1;
					al['street2'] = str2;
					var prem = data[str_idx][p_idx];
					if( 'match_quality' in prem ) {
						// indication of how good this premises matched the search string
						al['match_quality'] = prem['match_quality']; 
					} else {
						al['match_quality'] = 1; 
					}
					al['housenumber'] = prem['building_number'];
					al['housename2'] = this.leading_caps(prem['sub_building_name'], this.config.addr_uppercase);
					al['housename1'] = this.leading_caps(prem['building_name'], this.config.addr_uppercase);
					al['org'] = prem['department_name'];
					if ('' != al['org'] && '' != prem['organisation_name']) {
						al['org'] += this.config.delimiter;
					}
					al['org'] = this.leading_caps(al['org']+prem['organisation_name'], this.config.org_uppercase);
					al['pobox'] = this.leading_caps(prem['po_box_number'], this.config.addr_uppercase);
					al['postcode'] = data['postcode'];
					al['town'] = this.leading_caps(data['town'], this.config.town_uppercase);
					al['locality'] = this.leading_caps(data['dependent_locality'], this.config.addr_uppercase);;
					al['locality_dep'] = this.leading_caps(data['double_dependent_locality'], this.config.addr_uppercase);
					if (this.config.traditional_county) {
						al['county'] = this.leading_caps(data['traditional_county'], this.config.county_uppercase);
					} else {
						al['county'] = this.leading_caps(data['postal_county'], this.config.county_uppercase);
					}
					al['udprn'] = prem['udprn'];  
					this.res_arr[this.res_arr_idx] = al;
					this.res_arr_idx++;
				}
			} else {
				// street level data only
				var al = this.new_res_line();
				al['street1'] = str1;
				al['street2'] = str2;
				al['postcode'] = data['postcode'];
				al['town'] = this.leading_caps(data['town'], this.config.town_uppercase);
				al['locality'] = this.leading_caps(data['dependent_locality'], this.config.addr_uppercase);;
				al['locality_dep'] = this.leading_caps(data['double_dependent_locality'], this.config.addr_uppercase);
				if (this.config.traditional_county) {
					al['county'] = this.leading_caps(data['traditional_county'], this.config.county_uppercase);
				} else {
					al['county'] = this.leading_caps(data['postal_county'], this.config.county_uppercase);
				}
				al['match_quality'] = 2; 
				this.res_arr[this.res_arr_idx] = al;
				this.res_arr_idx++;
			}
		}
	}

	this.filter_data_by_house_name = function(data) {
		var he = this.get_elem(8);
		if (!he || !he.value.length) {
			return data;
		}
		var input = he.value.toUpperCase();
		var search_number = -1;
		if (parseInt(input) == input) {	
			// a pure number is what we are looking for
			search_number = parseInt(input);		
		}
		var search_string = ' '+input; // add a leading space to make sure we match of start of a word only
		var data_out = [];
		var str_idx_out = 1;
		var p_idx_out = 0;
		// loop over all streets
		for (var str_idx=1; str_idx<=data['thoroughfare_count']; str_idx++) {
			// loop over all premises on this street
			data_out[str_idx_out] = [];
			p_idx_out = 0;
			for (var p_idx=1; p_idx<=data[str_idx]['delivery_point_count']; p_idx++) {
				var prem = data[str_idx][p_idx];
				var search_target = ' '+prem['sub_building_name']+' '+prem['building_name']+' ';
				if (-1 != search_target.indexOf(search_string) || search_number == parseInt(prem['building_number'])) {
					// got a match!
					p_idx_out++;
					data_out[str_idx_out][p_idx_out] = [];
					data_out[str_idx_out][p_idx_out]['building_number']   = prem['building_number'];
					data_out[str_idx_out][p_idx_out]['sub_building_name'] = prem['sub_building_name'];
					data_out[str_idx_out][p_idx_out]['building_name'] 	  = prem['building_name'];
					data_out[str_idx_out][p_idx_out]['department_name']   = prem['department_name'];
					data_out[str_idx_out][p_idx_out]['organisation_name'] = prem['organisation_name'];
					data_out[str_idx_out][p_idx_out]['po_box_number']     = prem['po_box_number'];
					data_out[str_idx_out][p_idx_out]['udprn']     		  = prem['udprn'];
				}
			}
			// any hits on this thoroughfare?
			if (p_idx_out) {
				data_out[str_idx_out]['delivery_point_count'] = p_idx_out;
				// copy the rest of the data
				data_out[str_idx_out]['thoroughfare_name']					= data[str_idx]['thoroughfare_name'];
				data_out[str_idx_out]['thoroughfare_descriptor']			= data[str_idx]['thoroughfare_descriptor'];
				data_out[str_idx_out]['dependent_thoroughfare_name']		= data[str_idx]['dependent_thoroughfare_name'];
				data_out[str_idx_out]['dependent_thoroughfare_descriptor']	= data[str_idx]['dependent_thoroughfare_descriptor'];
				str_idx_out++;
			}
		}
		// any hits at all?
		if (1 < str_idx_out) {
			data_out['thoroughfare_count'] = str_idx_out-1;
			// copy all common data now
			data_out['town'] 					  = data['town'];
			data_out['dependent_locality'] 		  = data['dependent_locality'];
			data_out['double_dependent_locality'] = data['double_dependent_locality'];
			data_out['traditional_county'] 		  = data['traditional_county'];
			data_out['postal_county'] 			  = data['postal_county'];
			data_out['postcode']				  = data['postcode'];
			return data_out;
		}
		return null;
	}	
	
	this.lookup_timeout_err = function() {
		this.disp_err('9001', 'Internal Timeout after '+this.config.lookup_timeout+'ms')
	}
}
}
