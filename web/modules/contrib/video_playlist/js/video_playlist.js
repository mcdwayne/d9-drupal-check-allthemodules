/**
 * Created by bcgreen on 5/18/17.
 * Supporting JavaScript for Video Playlist Drupal 8 module.
 */
jQuery(document).ready(function(){
  jQuery('video.vid_player').hide();
  jQuery('div.vid_player_div').hide();
  jQuery('a.vid_link').click(function(){
    jQuery('div.vid_player_div').show();
    jQuery('video.vid_player').show();
    jQuery('video.vid_player').attr('src', jQuery(this).attr('name'));
    jQuery('video.vid_player')[0].play();
  });
});
