(function($, window) {
    Drupal.behaviors.image_edit = {
        attach: function (context) {
            var canvas;
            var ctx;
            var editorOverlaySelector = '.inline-file-editor ';
            var $img;
            var imgForCanvas = new Image; 
            var imageID = null;
            var caman;
            var $overlay = $(editorOverlaySelector);
            var appliedFilters = [];
            var sliderTypeFilters = ['hue', 'contrast', 'vibrance', 'sepia'];
            var queue = [];
            var processIsRunning = false; 
            var processWatcher; 

            function closeOverlay(){
                $overlay.removeClass('open');
                document.getElementById('canvas').remove();
                clearInterval(processWatcher); 
            }

            // Checks if the given filter is applied already,
            // Returns the index of the filter in the appliedFilters array
            // or -1 if it is not applied 
            function isFilterApplied(filterName) {
                filterIndex = -1;
                appliedFilters.map((item, i) => {
                    var currItemName = Object.keys(item)[0];
                    if(currItemName == filterName) {
                        filterIndex = i;
                    }
                });
                return filterIndex; 
            }
            
            // Applies specific filter to the image
            function applyFilter(filterName, attributes='') {
                var newFilterObject = {
                    [filterName]: attributes
                };
                var filterIndex = isFilterApplied(filterName); 
                var isSliderTypeFilter = sliderTypeFilters.indexOf(filterName) !== -1; 

                // Add new filter to the appliedFilters or update if already there
                if (isFilterApplied(filterName) > -1) {
                    // If filter is applied already, 
                    // update value if it is a slider type filter
                    if (isSliderTypeFilter) {
                        appliedFilters.map((item, i) => {
                            var currItemName = Object.keys(item)[0];
                            if(currItemName == filterName) {
                                item[currItemName] = attributes;
                            }
                        });
                    } else {
                        // remove from appliedFilters if it is a button type filter
                        appliedFilters.splice(filterIndex, 1); 
                    }
                } else {
                    // Otherwise add filter to the appliedFilters
                    appliedFilters.push(newFilterObject); 
                }
                
                // Open loading overlay
                var bottomPosOverlay = ($('#canvas-container').height() - $('#canvas').height()) + 'px'; 
                $('.inline-file-editor__canvas-overlay').addClass('show').css({'bottom': bottomPosOverlay, 'width': $('#canvas').width() + 'px'}); 

                // Reset to original image
                caman.revert();
                // Execute all applied filters to the original image
                appliedFilters.forEach(filter => {
                    var currFilterName = Object.keys(filter)[0];
                    console.log('applying filter: ', currFilterName, ', value:', filter[currFilterName] );
                    if (Array.isArray(filter[currFilterName])) {
                        caman[currFilterName].apply(caman, filter[currFilterName]); 
                    } else {
                        caman[currFilterName](filter[currFilterName]);
                    }
                });
                caman.render(function() {
                    // Set process to finished, and remove loading overlay
                    processIsRunning = false; 
                    $('.inline-file-editor__canvas-overlay').removeClass('show');
                });
                
            }

            // Handles filter click/change - and adds it to the process queue
            function handleFilterChange(filterName, attributes='') {
                // Add filter to process queue
                queue.push([filterName, attributes]);          
           }  

            $('img').once('image-edit-processed').each(function(){
                imageID = $(this).data('image-id');
                if (imageID) {
                    var editBtn = "<button class='inline-file-editor__edit-button'>Edit image</button>";
                    $(this).parent().addClass('inline-file-editor__img-parent').append(editBtn); 
                }
            });
            
            $('.inline-file-editor__edit-button').once('image-edit-initialized').click(function(event) {
                event.preventDefault();
                $img = $(this).parent().find('img');
                imgSrc = $img.attr('src');
                imgForCanvas.src = imgSrc; 
                imageID = $img.data('image-id');

                // Process the queue
                processWatcher = setInterval(function(){
                    console.log('checking for processes');
                    if(processIsRunning || queue.length < 1) {
                        return;
                    }
                    // Set processing to true. 
                    // (processing will be released in applyFilter function at caman.render callback function)
                    processIsRunning = true; 

                    // Execute the fist item of the queue
                    var processToRun = queue.shift();
                    var processFilterName = processToRun[0];
                    var processAttributes = processToRun[1]; 
                    applyFilter(processFilterName, processAttributes); 
                    return;
                }
                , 300);   

                // Reset controls 
                $('.control-button').removeClass('active');
                $('input[type=range]').val(0);

                // Open editing overlay
                if (!imageID) {
                    alert(Drupal.t("This Image is not editable."));
                } else {
                    // Create canvas element
                    var canv = document.createElement('canvas');
                    canv.id = 'canvas';
                    document.getElementById('canvas-container').appendChild(canv);
                    $overlay.addClass('open');
                }

                canvas = document.getElementById('canvas');
                ctx = canvas.getContext('2d');
                canvas.width = event.target.width;
                canvas.height = event.target.height;
                // Initialize caman and reset it if necessary
                caman = Caman('#canvas', imgSrc, function() {
                    appliedFilters = [];
                });
            });
            
            $('.inline-file-editor').once('image-edit-processed').each(function() {  
                $('#savebtn').click(function(e) {
                    var randomNumber = Math.floor((Math.random() * 100) + 1);;
                    var imgSrc = $img.attr('src'); 
                    var cacheBust = 'cache=bust' + randomNumber;
                    if( imgSrc.lastIndexOf('?') === -1 ) {
                        cacheBust = '?' + cacheBust; 
                    } else {
                        cacheBust = '&' + cacheBust; 
                    }

                    $.ajax(
                        '/xi/replace/' + imageID,
                        {
                            dataType: 'text',
                            data: canvas.toDataURL().split(',').slice(1).join(''),// only interested in the base64-part
                            method: 'POST',
                            success: function (data, textStatus, jqXHR) {
                                // forcing a reload of the image
                                $img.attr('src', imgSrc + cacheBust);
                                closeOverlay();
                            },
                            error: function (jqXHR, textStatus, errorThrown) {
                                alert(Drupal.t('The image cannot be saved.'));
                                console.log("failure");
                                console.log(jqXHR);
                            }
                        }
                    );
                });
                
                $('#resetbtn').on('click', function(e) {
                    ctx.drawImage(imgForCanvas, 0, 0, imgForCanvas.width, imgForCanvas.height);
                    caman = Caman(canvas);
                    caman.revert();
                    $('input[type=range]').val(0);
                    $('.control-button').removeClass('active');
                    appliedFilters = [];
                });

                $('#close').click(closeOverlay);

                /* FILTER BUTTONS */

                /* Creating custom filters */
                Caman.Filter.register("oldpaper", function() {
                    caman.pinhole();
                    caman.noise(10);
                    caman.orangePeel();
                });
                
                Caman.Filter.register("pleasant", function() {
                    caman.colorize(60, 105, 218, 10);
                    caman.contrast(10);
                    caman.sunrise();
                    caman.hazyDays();
                });

                Caman.Filter.register("hdreffect", function() {
                    caman.contrast(10);
                    caman.contrast(10);
                    caman.jarques();
                });
                
                $('#hue').on('change', function(event) {
                    handleFilterChange('hue', Number.parseInt($(this).val()));
                });
                
                $('#contrast').on('change', function(event) {
                    handleFilterChange('contrast', Number.parseInt($(this).val()));
                });
                
                $('#vibrance').on('change', function(event) {
                    handleFilterChange('vibrance', Number.parseInt($(this).val()));
                });
                
                $('#sepia').on('change', function(event) {
                    handleFilterChange('sepia', Number.parseInt($(this).val()));
                });
                
                $('#brightnessbtn').on('click', function(e) {
                    $(this).toggleClass('active');
                    handleFilterChange('brightness', 30);
                });
                
                $('#noisebtn').on('click', function(e) {
                    $(this).toggleClass('active');
                    handleFilterChange('noise', 10);
                });
                
                $('#contrastbtn').on('click', function(e) {
                    $(this).toggleClass('active');
                    handleFilterChange('contrast', 10);
                });
                
                $('#sepiabtn').on('click', function(e) {
                    $(this).toggleClass('active');
                    handleFilterChange('sepia', 20);
                });
                
                $('#colorbtn').on('click', function(e) {
                    $(this).toggleClass('active');
                    handleFilterChange('colorize', [60, 105, 218, 10]);
                });
                
                $('#vintagebtn').on('click', function(e) {
                    $(this).toggleClass('active');
                    handleFilterChange('vintage');
                });
                
                $('#lomobtn').on('click', function(e) {
                    $(this).toggleClass('active');
                    handleFilterChange('lomo');
                });
                
                $('#embossbtn').on('click', function(e) {
                    $(this).toggleClass('active');
                    handleFilterChange('emboss');
                });
                
                $('#tiltshiftbtn').on('click', function(e) {
                    $(this).toggleClass('active');
                    handleFilterChange('tiltShift', { angle: 90, focusWidth: 600});
                });
                
                $('#radialblurbtn').on('click', function(e) {
                    $(this).toggleClass('active');
                    handleFilterChange('radialBlur');
                });
                
                $('#edgeenhancebtn').on('click', function(e) {
                    $(this).toggleClass('active');
                    handleFilterChange('edgeEnhance');
                });
                
                $('#posterizebtn').on('click', function(e) {
                    $(this).toggleClass('active');
                    handleFilterChange('posterize', [8, 8]);
                });
                
                $('#claritybtn').on('click', function(e) {
                    $(this).toggleClass('active');
                    handleFilterChange('clarity');
                });
                
                $('#orangepeelbtn').on('click', function(e) {
                    $(this).toggleClass('active');
                    handleFilterChange('orangePeel');
                });
                
                $('#sincitybtn').on('click', function(e) {
                    $(this).toggleClass('active');
                    handleFilterChange('sinCity');
                });
                
                $('#sunrisebtn').on('click', function(e) {
                    $(this).toggleClass('active');
                    handleFilterChange('sunrise');
                });
                
                $('#crossprocessbtn').on('click', function(e) {
                    $(this).toggleClass('active');
                    handleFilterChange('crossProcess');
                });
                
                $('#lovebtn').on('click', function(e) {
                    $(this).toggleClass('active');
                    handleFilterChange('love');
                });
                
                $('#grungybtn').on('click', function(e) {
                    $(this).toggleClass('active');
                    handleFilterChange('grungy');
                });
                
                $('#jarquesbtn').on('click', function(e) {
                    $(this).toggleClass('active');
                    handleFilterChange('jarques');
                });
                
                $('#pinholebtn').on('click', function(e) {
                    $(this).toggleClass('active');
                    handleFilterChange('pinhole');
                });
                
                $('#oldbootbtn').on('click', function(e) {
                    $(this).toggleClass('active');
                    handleFilterChange('oldBoot');
                });
                
                $('#glowingsunbtn').on('click', function(e) {
                    $(this).toggleClass('active');
                    handleFilterChange('glowingSun');
                });
                
                $('#hazydaysbtn').on('click', function(e) {
                    $(this).toggleClass('active');
                    handleFilterChange('hazyDays');
                });
                
                $('#hdrbtn').on('click', function(e) {
                    $(this).toggleClass('active');
                    handleFilterChange('hdreffect');
                });
                
                $('#oldpaperbtn').on('click', function(e) {
                    $(this).toggleClass('active');
                    handleFilterChange('oldpaper');
                });
                
                $('#pleasantbtn').on('click', function(e) {
                    $(this).toggleClass('active');
                    handleFilterChange('pleasant');
                });
            });
        }
    }
})(window.jQuery, window);