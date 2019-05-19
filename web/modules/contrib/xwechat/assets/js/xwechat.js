(function($){
	$(function(){		
    /*模板Tab */
    var dataType = 'title';
    
    function _loadtemp(dataType){
      $("#template-loading").show();
      $.ajax({
        type: "POST",
        url: Drupal.settings.basePath + "wxeditor/loadtemp",
        data: {"type":dataType},
        success: function(data){
          $("#template-loading").hide();
          var tabPane = $("#temp-"+dataType);
          tabPane.html(data);
          var _tempLi = tabPane.find('.template-list li');
          _tempLi.hover(function(){
            $(this).css({"background-color":"#f5f5f5"});
          },function(){
            $(this).css({"background-color":"#fff"});
          });
          _tempLi.click(function(){
            if(dataType=='tpl'){
              var _tempHtml = $(this).find('.tpl-code').html();
              Drupal.wysiwyg.instances['edit-media-eidtor-value']['rendered'].setContent("");
              Drupal.wysiwyg.instances['edit-media-eidtor-value']['rendered'].execCommand('insertHtml', _tempHtml);
            }else{
              var _tempHtml = $(this).html();
              insertHtml(_tempHtml + "<p><br/></p>");
            }						
          });
        }
      });
    }
    _loadtemp(dataType);//加载
    //TAB切换
    $('#templateTab a').click(function (e){
      e.preventDefault();
      jQuery(".span_of_1").slide({titCell:"#templateTab li", mainCell:".template-content", effect:"fold", trigger:"click"});
      
      dataType = $(this).attr("data-type");
      if(dataType){
        var tabPane = $("#temp-"+dataType);
        if(tabPane.find('.template-list').length<=0){
          _loadtemp(dataType);
        }
      }
    });

    //清空
    $('#clear-editor').click(function(){
      if(confirm('是否确认清空内容，清空后内容将无法恢复')){
        Drupal.wysiwyg.instances['edit-media-eidtor-value']['rendered'].setContent("");
      }        
    });

    //预览效果
    $("#wx-template-name").blur(function(){
      var val = $(this).val();
      if(val)
      {
        $("#wxpreview h4").html(val);
      }
    });
    $("#wx-template-dateline").blur(function(){
      var val = $(this).val();
      if(val)
      {
        $("#wxpreview em").html(val);
      }
    });
    $("#wx-template-cover").blur(function(){
      var val = $(this).val();
      if(val)
      {
        $("#wxpreview .wxpreimg").html('<img src="'+ val +'" width="290" height="209">');
      }
    });
    
    $("#wx-template-intro").blur(function(){
      var val = $(this).val();
      if(val)
      {
        $("#wxpreview .wxstr").html(val);
      }
    });

    $("#wx-template-wxid").change(function(){
      var val = $(this).find("option:selected").val(),text = $(this).find("option:selected").text(),url=$(this).find("option:selected").attr("data-url");

      if(text)
      {
        if(val==0) text = '';
        if(!url) url = 'javascript:void(0);';
        $("#wxpreview .wxhao").html('<a href="'+url+'" target="_blank">'+text+'</a>');
      }
    });
    //定制效果
    $("#is_show_title").click(function(){
      var cked = $(this).attr("checked");
      if(cked==undefined)
      {
        $("#wx_show_title").hide();
        
      }else
      {
        $("#wx_show_title").show();
      }
    });
    $("#is_show_statis").click(function(){
      var cked = $(this).attr("checked");
      if(cked==undefined)
      {
        $("#wxpreview .wxfoot").hide();
        
      }else
      {
        $("#wxpreview .wxfoot").show();
      }
    });
	})		
})(jQuery)

function getSelectionHtml() {
    var range = Drupal.wysiwyg.instances['edit-media-eidtor-value']['rendered'].selection.getRange();
    range.select();
    var selectionObj = Drupal.wysiwyg.instances['edit-media-eidtor-value']['rendered'].selection.getNative();
    var rangeObj = selectionObj.getRangeAt(0);
    var docFragment = rangeObj.cloneContents();
    var testDiv = document.createElement("div");
    testDiv.appendChild(docFragment);
    var selectHtml = testDiv.innerHTML;
    return selectHtml;
}

function insertHtml(html) {
	var select_html = getSelectionHtml();
	if (select_html != "") {
		select_html = strip_tags(select_html, '<br><p><h1><h2><h3><h4><h5><h6><img>');
		var select_obj = $('<div>' + select_html + '</div>');

		/*select_obj.find('*').each(function() {
			$(this).removeAttr('style');
			$(this).removeAttr('class');
			$(this).removeAttr('placeholder');
		});*/
		var obj = $('<div>' + html + '</div>');
		/*select_obj.find('h1,h2,h3,h4,h5,h6').each(function(i) {
			var title = obj.find('.title').eq(i);
			if (title && title.size() > 0) {
				title.html($.trim($(this).text()));
				$(this).remove();
			} else {
				$(this).replaceWith('<p>' + $(this).text() + '</p>');
			}
		});*/
		/*var bgimg_size = obj.find('.135bg').size();
		select_obj.find('img').each(function(i) {
			var bgimg = obj.find('.135bg').eq(i);
			if (bgimg && bgimg.size() > 0) {
				bgimg.css('background-image', 'url(' + $(this).attr('src') + ')');
				$(this).remove();
			}
		});*/
		select_obj.find('img').each(function(i) {
			var img = obj.find('img').eq(i);
			if (img && img.size() > 0) {
				img.attr('src', $(this).attr('src'));
				$(this).remove();
			}
		});
		var brushs = obj.find('.wweibrush');
		var total = brushs.size();
		if (total > 0) {
			if (total == 1) {
				var brush_item = obj.find('.wweibrush:first');
				if (brush_item.data('brushtype') == 'text') {
					brush_item.html($.trim(select_obj.text()));
				} else {
					select_obj.contents().each(function(i) {
						var $this = this;
						if (this.tagName == "IMG") {
							return;
						};
						if ($.trim($($this).text()) == "" || this.tagName == 'BR' || $(this).html() == "" || $(this).html() == "&nbsp;" || $(this).html() == "<br>" || $(this).html() == "<br/>") {
							$(this).remove();
						}
					});
					var style = brush_item.data('style');
					if (style) {
						select_obj.find('*').each(function() {
							$(this).attr('style', style);
						});
					}
					brush_item.html(select_obj.html());
				}
			} else {
				select_obj.contents().each(function(i) {
					var $this = this;
					if (this.tagName == "IMG") {
						return;
					};
					if ($.trim($($this).text()) == "" || this.tagName == 'BR' || $(this).html() == "" || $(this).html() == "&nbsp;" || $(this).html() == "<br>" || $(this).html() == "<br/>") {
						$(this).remove();
					}
				});
				select_obj.contents().each(function(i) {
					var $this = this;
					if ($this.nodeType == 3) {
						$this = $('<p>' + $(this).text() + '</p>').get(0);
					}
					if (i < total) {
						var brush_item = brushs.eq(i);
						if (brush_item.data('brushtype') == 'text') {
							brush_item.html($.trim($($this).text()));
						} else {
							var style = brush_item.data('style');
							if (style) {
								$($this).attr('style', style);
							}
							brush_item.empty().append($($this));
						}
					} else {
						var brush_item = brushs.eq(total - 1);
						if (brush_item.data('brushtype') == 'text') {
							brush_item.append($($this).text());
						} else {
							var style = brush_item.data('style');
							if (style) {
								$($this).attr('style', style);
							}
							brush_item.append($($this));
						}
					}
				});
			}
			obj.find('p').each(function(i) {
				if ($(this).html() == "" || $(this).html() == "&nbsp;" || $(this).html() == "<br>" || $(this).html() == "<br/>") {
					if (typeof $(this).attr('style') == 'undefined') {
						$(this).remove();
					}
				}
			});
		}
		html = obj.html();
		Drupal.wysiwyg.instances['edit-media-eidtor-value']['rendered'].execCommand('insertHtml', html);
		Drupal.wysiwyg.instances['edit-media-eidtor-value']['rendered'].undoManger.save();
		return true;
	} else {}

    Drupal.wysiwyg.instances['edit-media-eidtor-value']['rendered'].execCommand('insertHtml', html);
    Drupal.wysiwyg.instances['edit-media-eidtor-value']['rendered'].undoManger.save();
    return true;
}
