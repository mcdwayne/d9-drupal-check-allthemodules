function expandable_search(){
	// get vars
	var searchEl = document.querySelector(".expanded_search--input");
	var labelEl = document.querySelector(".expanded_search--label");

	// register clicks and toggle classes
	labelEl.addEventListener("click",function(e){
	   if (classie.has(searchEl,"focus")) {
			classie.remove(searchEl,"focus");
			classie.remove(labelEl,"active");
		} else {
			e.preventDefault();
			searchEl.className += " focus";
			classie.add(searchEl,"focus");
			classie.add(labelEl,"active");
			return false;
		}
		e.preventDefault();
		return false;
	});
	labelEl.addEventListener("tap",function(e){
		if (classie.has(searchEl,"focus")) {
			classie.remove(searchEl,"focus");
			classie.remove(labelEl,"active");
		} else {
			
			e.preventDefault();
			searchEl.className += " focus";
			classie.add(searchEl,"focus");
			classie.add(labelEl,"active");
			return false;
		}
	});
	// register clicks outisde search box, and toggle correct classes
	document.addEventListener("click",function(e){
		hide(e);
	});

	function hide(e) {
		var clickedID = e.target;
		if (clickedID != labelEl && clickedID != searchEl) {
			if (classie.has(searchEl,"focus")) {
				classie.remove(searchEl,"focus");
				classie.remove(labelEl,"active");
			}
		}
	}
}
(function(window){
	expandable_search();
}(window));