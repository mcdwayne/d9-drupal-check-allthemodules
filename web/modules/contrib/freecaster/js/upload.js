(function ($, Drupal) {

    var n_uploads = 0;

    function FCUpload(file, videoid) {
        this.proxy = drupalSettings.freecaster.base_url + "/admin/config/freecaster/proxy";
        this.file = file;
        this.video_id = videoid;
        this.start();
    }
    
    FCUpload.prototype = {
    
        constructor: FCUpload,
    
        currentOffset: 0,
        chunkSize: 1048576,
        running: false,
        onprogress: null,
    
        start: function() {
            if (this.running) return;
            this.running = true;
    
            this.n_errors = 0;
    
            if (n_uploads++ == 0) {
                window.onbeforeunload = this.preventClose;
            }
            jQuery.ajax({
                type: "GET",
                url: this.proxy,
                data: {
                    method: "upload_video",
                    video_id: this.video_id,
                    name:     this.file.name,
                    size:     this.file.size,
                    type:     this.file.type
                },
                dataType : "json",
                context: this,
                success: function(res) {
                    if (res.error) {
                        n_uploads--;
                        alert(res.error);
                        return;
                    }
                    this.url = res.upload_url;
                    if (res.upload_headers) {
                        this.upload_headers = res.upload_headers;
                    }
                    if (res.report_progress) {
                        this.report_progress = res.report_progress;
                    }
                    if (res.resume) {
                        this.currentOffset = parseInt(res.resume);
                        if (this.currentOffset >= this.file.size) {
                            n_uploads--;
                            alert("File was already uploaded.");
                            return;
                        }
                    }
                    this.uploadChunk();
                },
                error : function(request, message, error) {
                    alert(message);
                }
            });
        },
    
        uploadChunk: function() {
            var start = this.currentOffset;
            var size = this.file.size;
            var end = Math.min(this.currentOffset + this.chunkSize, size);
    
            var xhr = this.createCORSRequest("POST", this.url);
            this.xhr = xhr;
            xhr.setRequestHeader("Content-Disposition", 'attachment; filename="' + encodeURIComponent(this.file.name) + '"');
            xhr.setRequestHeader('Content-Type', "application/octet-stream");
            xhr.setRequestHeader('X-Content-Range', "bytes " + start + "-" + (end - 1) + "/" + size);
            if (this.upload_headers) {
                for (var head in this.upload_headers) {
                    xhr.setRequestHeader(head, this.upload_headers[head]);
                }
            }
    
            var self = this;
            xhr.onreadystatechange = function() {
                if (xhr.readyState == 4) {
                    if (xhr.status == 200) {
                        self.running = false;
                        self.completed = true;
                        if (self.onprogress) self.onprogress(self.file.name, 100);
                        if (self.report_progress) {
                            jQuery.ajax({
                                url: self.report_progress,
                                type: "POST",
                                data: {
                                    progress: size,
                                    size: size
                                }
                            });
                        }
                        n_uploads--;
                    }
                    else if (xhr.status == 201) {
                        var m = xhr.responseText.trim().match(/^(\d+)-(\d+)\/(\d+)$/);
                        if (!m) {
                            self.handleError("Received invalid response from the server.");
                            return;
                        }
                        this.n_errors = 0;
                        self.currentOffset = parseInt(m[2]) + 1;
                        if (self.onprogress) self.onprogress(self.file.name, Math.floor(self.currentOffset * 100 / size));
                        if (self.report_progress) {
                            jQuery.ajax({
                                url: self.report_progress,
                                type: "POST",
                                data: {
                                    progress: self.currentOffset,
                                    size: size
                                }
                            });
                        }
                        if (self.running) {
                            self.uploadChunk();
                        }
                        else {
                            n_uploads--;
                        }
                    }
                    else {
                        self.handleError("Error: " + xhr.responseText);
                    }
                }
            };
    
            var f;
            if (this.file.mozSlice) f = this.file.mozSlice;
            else if (this.file.webkitSlice) f = this.file.webkitSlice;
            else f = this.file.slice;
    
            xhr.send(f.call(this.file, start, end, "application/octet-stream"));
        },
    
        handleError: function(error) {
            var self = this;
            if (++this.n_errors >= 5) {
                alert(error);
                n_uploads--;
            }
            else {
                setTimeout(function(){
                    var start = self.currentOffset;
                    var size = self.file.size;
                    var end = Math.min(self.currentOffset + self.chunkSize, size);
    
                    var f;
                    if (self.file.mozSlice) f = self.file.mozSlice;
                    else if (self.file.webkitSlice) f = self.file.webkitSlice;
                    else f = self.file.slice;
    
                    self.xhr.send(f.call(self.file, start, end, "application/octet-stream"));
                }, 1000);
            }
        },
    
        createCORSRequest: function(method, url) {
            var xhr = new XMLHttpRequest();
            if ("withCredentials" in xhr) {
                xhr.open(method, url, true);
            }
            else if (typeof XDomainRequest != "undefined") {
                xhr = new XDomainRequest();
                xhr.open(method, url);
            }
            else {
                xhr = null;
            }
            return xhr;
        },
    
        preventClose: function() {
            if (n_uploads > 0) return "You are uploading a video. Closing this window will interrupt the upload process. Are you sure you want to continue?";
        }
    
    };
    
    function can_resume() {
        return (
            // File object supported
            (typeof(File) !== 'undefined') &&
            // Blob object supported
            (typeof(Blob) !== 'undefined') &&
            // FileList object supported
            (typeof(FileList) !== 'undefined') &&
            // Slice supported
            (!!Blob.prototype.webkitSlice || !!Blob.prototype.mozSlice || !!Blob.prototype.slice)
        );
    }
    
    function can_upload_files(items) {
        if ((!items) || (items.length == 0)) return true;
    
        var r = new RegExp("^(video/)");
    
        for (var i = 0; i < items.length; i++) {
            if (!items[i].type) return true;
            if (items[i].type.match(r)) return true;
        }
        return false;
    }

    jQuery(function($){
        if (drupalSettings.freecaster.videoname != 'null' && drupalSettings.freecaster.videoid != 'null') {
            var output = '<div class="videos-main-container"><div class="new-video-block" id="'+drupalSettings.freecaster.videoid+'">' +
                '<h3>'+drupalSettings.freecaster.videoname+' - '+drupalSettings.freecaster.videoid+'</h3>' +
                '<p class="drag-and-drop"><strong>Merci de glisser-déposer le fichier vidéo pour ce média</strong></p>' +
                '</div></div>';
            $(output).insertAfter('#media-freecaster-video-edit-form')
        }

        function create_progress(filename, video_id) {
            var id = "progress_" + filename.replace(/[^a-z0-9]/gi, "");
            var progress = $("<div></div>").addClass("progress").attr("id", id);
            $("<h3></h3>").text(filename).appendTo(progress);
            var bar = $("<div></div>").addClass("bar").appendTo(progress);
            $("<div></div>").addClass("filled").css({ width: "0%" }).appendTo(bar);
            $("<div></div>").addClass("percentage").text("0%").appendTo(progress);

            var block_id = '#' + video_id;
            progress.appendTo($(block_id));
        }

        function update_progress(filename, progress) {
            var id = "progress_" + filename.replace(/[^a-z0-9]/gi, "");
            var el = $("#" + id);

            if (progress >= 100) {
                el.find(".bar").remove();
                $('.new-video-block').css('border-color','#c9e1bd');
                $('.new-video-block').css('background-color','#f3faef');
                el.find(".percentage").text("Upload complete.");
            }
            else {
                el.find(".filled").css({ width: progress + "%" });
                el.find(".percentage").text(progress + "%");
            }
        }

        function upload_files(files, video_id) {
            var i, type, upload;

            for (i = 0; i < files.length; i++) {
                type = files[i].type;

                if (type.match(/^video\//)) {
                    // Upload a video
                    upload = new FCUpload(files[i], video_id);
                    upload.onprogress = update_progress;
                    create_progress(files[i].name, video_id);
                }
                else {
                    alert("Unsupported file type: %s".replace("%s", type));
                }
            }
        }

        var body = $("body");
        var drag_stack = $();

        $.event.props.push("dataTransfer");

        $('.new-video-block').on("dragenter", function(e) {
            var videoId = $(this).attr('id');
            var block_id = '#' + videoId;
            if ((drag_stack.length == 0) && (e.dataTransfer)) {
                var files = e.dataTransfer.items || e.dataTransfer.files;

                body.addClass("drag_ok");
                if (can_upload_files(files)) {
                    body.removeClass("drag_unknown");
                }
                else {
                    body.addClass("drag_unknown");
                }
            }
            drag_stack = drag_stack.add(e.target);
            return false;
        }).on("dragleave", function(e) {
            var videoId = $(this).attr('id');
            var block_id = '#' + videoId;
            drag_stack = drag_stack.not(e.target);
            if (drag_stack.length == 0) {
                body.removeClass("drag_ok drag_unknown");
            }
            return false;
        }).on("dragover", function(e) {
            var videoId = $(this).attr('id');
            var block_id = '#' + videoId;
            if (body.hasClass("drag_unknown")) {
                e.dataTransfer.dropEffect = "none";
                return false;
            }

            if (drag_stack.length == 0)
            {
                $(this).triggerHandler("dragenter");
            }
            e.dataTransfer.dropEffect = "copy";
            return false;
        }).on("drop", function(e) {
            var videoId = $(this).attr('id');
            var block_id = '#' + videoId;
            var drag_drop_class = block_id + ' .drag-and-drop';
            $(drag_drop_class).remove();
            if (drag_stack.length == 0) return true;
            drag_stack.not(e.target);

            body.removeClass("drag_ok drag_unknown");

            if ((e.dataTransfer) && (e.dataTransfer.files) && (e.dataTransfer.files.length > 0)) {
                upload_files(e.dataTransfer.files, videoId);
            }
            return false;
        }).on("change", '.media-freecaster-video-edit-form #input-file', function(){
            var files = this.files;
            if (files.length > 0)
            {
                upload_files(files, videoId);
            }
            return false;
        });

        if (!can_resume()) {
            $('#media-freecaster-video-edit-form').hide();
            $("#obsolete").show();
        }


    });


})(jQuery, Drupal);


