/* Global variables */
var PausePlayer_PageLoaded = false; //True if page is ready (onload event dispatched)
//This script can be launched after the js in the template
if (typeof PausePlayer_VideosList == "undefined") {
	var PausePlayer_VideosList = [];
}

/* Init function for wait the full loading of the page */
function pausePlayerInit() {
	if (PausePlayer_PageLoaded) {
		return;
	}
	
	//The DOM is maybe not ready
	if (document.readyState === 'interactive' || document.readyState === 'complete') { //readyState : loading, interactive (DOMContentLoaded), complete (load)
		//DOM is ready
		//Handle it asynchronously to allow scripts the opportunity to delay ready
		setTimeout(pausePlayerPageLoadedOK);
	} else {
		//DOM is not ready
		if (document.addEventListener) {
			// Use the handy event callback
			document.addEventListener("DOMContentLoaded", pausePlayerPageLoadedOK, false);

			// A fallback to window.onload, that will always work
			window.addEventListener("load", pausePlayerPageLoadedOK, false);
		} else {
			//Internet Explorer model
			
			// Ensure firing before onload, maybe late but safe also for iframes
			document.attachEvent("onreadystatechange", pausePlayerPageLoadedOK);

			// A fallback to window.onload, that will always work
			window.attachEvent("onload", pausePlayerPageLoadedOK);
		}
	}
}

/* Event : if the function is called, the page is loaded (DOM ready or page fully loaded) */
function pausePlayerPageLoadedOK() {
	if (document.addEventListener) {
		document.removeEventListener("DOMContentLoaded", pausePlayerPageLoadedOK);
		window.removeEventListener("load", pausePlayerPageLoadedOK);
	} else { //IE <= 8
		document.detachEvent("onreadystatechange", pausePlayerPageLoadedOK);
		window.detachEvent("onload", pausePlayerPageLoadedOK);
	}
	PausePlayer_PageLoaded = true;
	
	if (PausePlayer_VideosList.length > 0) {
		pausePlayerManageVideos();
	}
}

/* Create an instance of Pause Player for each video */
function pausePlayerManageVideos() {
	if (PausePlayer_VideosList.length > 0) {
		if (typeof pauseplayer == "undefined") {
			if (typeof console != "undefined" && typeof console.log != "undefined") {
				console.error("Error : the javascript library Pause Player is not loaded");
			}
			return;
		}
		
		//Debug mode
		pauseplayer.setDebug(PausePlayer_VideosList[0].debug);
		//Create player
		pauseplayer.createPlayer(PausePlayer_VideosList[0].idvideo, PausePlayer_VideosList[0]);
		PausePlayer_VideosList.shift(); //delete first element
		pausePlayerManageVideos();
	}
}

//Init function for wait the full loading of the page
pausePlayerInit();
