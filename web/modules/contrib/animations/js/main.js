function animations_html_escape(str) {
    return str
        .replace(/&/g, '&amp;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#39;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;');
}
jQuery(function(){
	new WOW().init();
	// You should add logic for all effects in the animations.config.yml here
	
	// Typewriter effect
	if(drupalSettings.animations.typewriter != undefined && drupalSettings.animations.typewriter.classes != undefined){
		// get all css selectors for which we should apply the effect
		for(i=0;i<drupalSettings.animations.typewriter.classes.length;i++){
			if(drupalSettings.animations.typewriter.classes[i].length > 0){
				jQuery(drupalSettings.animations.typewriter.classes[i]).each(function(){
					var $parent = jQuery(this);
					
					text = animations_html_escape($parent.text());
					$parent.html("");
					$parent.typed({
						strings: [text],
						typeSpeed: 0,
						backSpeed: -50,
						backDelay: 800,
						loop: !0,
						cursorChar: "&nbsp;"
					});
					
				});

			}
		}
	}
	
	// Typewriter effect for displaying multiple children in a queue
	if(drupalSettings.animations.typewriterMultiple != undefined && drupalSettings.animations.typewriterMultiple.classes != undefined){
		// get all css selectors for which we should apply the effect
		for(i=0;i<drupalSettings.animations.typewriterMultiple.classes.length;i++){
			if(drupalSettings.animations.typewriterMultiple.classes[i].length > 0){			
				
				jQuery(drupalSettings.animations.typewriterMultiple.classes[i]).each(function(){
					
					var $parent = jQuery(this);
					var texts = [];
					var j = 0;
					
					$parent.children().each(function () {
						texts[j] = animations_html_escape(jQuery(this).text());
							
						j++;
					});
					$parent.html("");
					
					$parent.typed({
						strings: texts,
						typeSpeed: 0,
						backSpeed: -50,
						backDelay: 800,
						loop: !0,
						cursorChar: "&nbsp;"
					});
				});

			}
		}
	}
	
	
	// animate.css effects
	var animateCssEffects = ["bounce","flash","pulse","rubberBand","shake","headShake","swing",
	"tada","wobble","jello","bounceIn","bounceInDown","bounceInLeft","bounceInRight",
	"bounceInUp","bounceOut","bounceOutDown","bounceOutLeft","bounceOutRight",
	"bounceOutUp","fadeIn","fadeInDown","fadeInDownBig","fadeInLeft","fadeInLeftBig",
	"fadeInRight","fadeInRightBig","fadeInUp","fadeInUpBig","fadeOut","fadeOutDown",
	"fadeOutDownBig","fadeOutLeft","fadeOutLeftBig","fadeOutRight","fadeOutRightBig",
	"fadeOutUp","fadeOutUpBig","flipInX","flipInY","flipOutX","flipOutY","lightSpeedIn",
	"lightSpeedOut","rotateIn","rotateInDownLeft","rotateInDownRight","rotateInUpLeft",
	"rotateInUpRight","rotateOut","rotateOutDownLeft","rotateOutDownRight","rotateOutUpLeft",
	"rotateOutUpRight","hinge","rollIn","rollOut","zoomIn","zoomInDown","zoomInLeft","zoomInRight",
	"zoomInUp","zoomOut","zoomOutDown","zoomOutLeft","zoomOutRight","zoomOutUp","slideInDown",
	"slideInLeft","slideInRight","slideInUp","slideOutDown","slideOutLeft","slideOutRight","slideOutUp"];
	
	for(i=0;i<animateCssEffects.length;i++){
		var effect = animateCssEffects[i];
		var cssSelectors = (drupalSettings.animations[effect] != undefined && drupalSettings.animations[effect].classes != undefined) ? drupalSettings.animations[effect].classes : [] ;
		
		for(j=0;j<cssSelectors.length;j++){
			if(cssSelectors[j].length > 0){
				jQuery(cssSelectors[j]).addClass("wow "+effect);
			}
		}
	}
	

	
});