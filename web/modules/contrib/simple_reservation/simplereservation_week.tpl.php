<?php

/**
 * @file
 * Item theme template for the simplereservation.module.
 *
 */

$imgpath = base_path() . drupal_get_path('module', 'simplereservation') .'/images/';
$week_day = date_week_days_ordered(date_week_days(TRUE));

  if (user_access('access simple reservations')) { ?>
<!-- Header -->

<div id="srHeader">
  <div id="srMonth">
    <div id="srPm">
      <div id="srPImg">
        <?php print( l('<img src="'. $imgpath .'holder.png" alt='. t("Previous month") .' title="'. t("Previous month") .'"></a>', 'simplereservation/'. $config["prev_4week"], array('html' => TRUE)));?>
      </div>
        <?php print( l(t("previous month"), 'simplereservation/'. $config["prev_4week"], array('html' => TRUE)))?>
      </div>
        <?php  print($config["month"] ."&nbsp;". $config["year"]);?><div id="srNm"><?php print(l(t("next month"), 'simplereservation/'. $config["next_4week"], array('html' => TRUE)))?><div id="srNImg"><?php print( l('<img src="'. $imgpath .'holder.png" alt='. t("Next month") .' title="'. t("Next month") .'"></a>', 'simplereservation/'. $config["next_4week"], array('html' => TRUE)));?>
    </div>
  </div>
</div>

  <div id="srWeek">
    <div id="srPw">
      <div id="srPImg">
       <?php print( l('<img src="'. $imgpath .'holder.png" alt='. t("Previous week") .' title="'. t("Previous week") .'"></a>', 'simplereservation/'. $config["prev_week"], array('html' => TRUE))); ?>
      </div>
      <?php print( l(t("previous week"), 'simplereservation/'. $config["prev_week"], array('html' => TRUE))) ?>
    </div>
    <?php  print (t("week") ."&nbsp;");  print $config["week"]; ?><div id="srNw"><?php print( l(t("next week"), 'simplereservation/'. $config["next_week"], array('html' => TRUE)))?><div id="srNImg"><?php print( l('<img src="'. $imgpath .'holder.png" alt='. t("Next week") .' title="'. t("Next week") .'"></a>', 'simplereservation/'. $config["next_week"], array('html' => TRUE))); ?></div>
    </div>
  </div>
</div>

<br>

<?php  $oddeven = 0; ?>

<?php for ($i = 0; $i < 7; $i++) { ?>
<div id="srDate">
  <div id="srDay">
    <?php
      print $week_day[$i] .',&nbsp;';
      print $config["calendar"][$i]->format('d.m.Y');
    ?>
  </div>
  <div id="srAdd">
    <?php
      // Check if date is not in past.
      if (mktime(23, 59, 59, $config["calendar"][$i]->format('m'), $config["calendar"][$i]->format('d'), $config["calendar"][$i]->format('Y')) <= time()){
        $old_date = TRUE;}
      else {
        $old_date = FALSE;}

      if (((user_access('add simple reservations') || user_access('add simple reservations for others')) AND !$old_date)) {
        print( l('<img src="'. $imgpath .'holder.png" alt='. t("Add reservation") .' title="'. t("Add reservation") .'"></a>', 'simplereservation/add/'. $config["calendar"][$i]->format('Y') ."/". $config["calendar"][$i]->format('m') ."/". $config["calendar"][$i]->format('d') ."/", array('html' => TRUE)));
      }
      else print('');
    ?>
  </div>
</div>

<div id="srReservations">
  <?php
    foreach ($reservations[$i] as $reservation)  {
      if ($reservation["rid"] > 0)  {
        $from=user_load($reservation["uid"]);
        $for=user_load($reservation["for_uid"]);?>
        <div id="srSeparator">&nbsp;</div>
          <div id="srHours">
            <?php
              // From Begin.
              if ($reservation["begin"] <= date_format($config["calendar"][$i], "U")) print( "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;..." );
              else print( date("H:i", $reservation["begin"]));
              print(" - ");
              // To End.
              if ($reservation["ending"] >= date_format($config["calendar"][$i+1], "U"))  print( "..." );
              else print( date("H:i", $reservation["ending"]));?>
          </div>
          <div id='srDescription'>
            <?php print("<a  title=\"". $reservation["description"] ."\">". $reservation["name"] ."</a>"); ?> 
          </div>
          <div id="srBy">
            <?php print(" ". t("booked by") ." ". l($from->name, 'user/'. $from->uid) );?>
          </div>
        <div id="srFor">
          <?php if (($reservation["for_uid"] > 0) && ($reservation["uid"] != $reservation["for_uid"])) {
              print(" (". t("for") ." ". l($for->name, 'user/'. $for->uid)) . ")";}
              else print("&nbsp;");?>
        </div>
        <div id="srComment">
           <?php if ($reservation["rcomment"] != "") print( $reservation["rcomment"]);
              else print("&nbsp;");?>
        </div>
        <div id="srEdit">
          <?php

            // Check if reservation ending date is not in past.
            if ($reservation["ending"] <= time()){
             $ending_in_future = FALSE;}
            else {
             $ending_in_future = TRUE;}

            // Check access rights to edit reservation.
            $user_can_edit = FALSE;

            if (user_access('edit simple reservations of others')) $user_can_edit = TRUE;
            if ((user_access('edit own simple reservations')) && $reservation["uid"] == $user->uid) $user_can_edit = TRUE;

            if ($user_can_edit AND $ending_in_future) {
              print(l('<img src="'. $imgpath .'holder.png" title="Edit reservation">', 'simplereservation/edit/'. $reservation["rid"], array('html' => TRUE)));
            }?>
        </div>
<?php }
    }?>
</div>
<?php } ?>
<?php } ?>
