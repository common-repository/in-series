<?php
/*

Copyright 2007 Travis Snoozy (ai2097@users.sourceforge.net)
Released under the terms of the GNU GPL v2

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

*/

/**
 * Outputs and processes the In Series series editing sidebar.
 */

$epid = InSeriesInternal::make_int($_REQUEST['post']);

$edit_series_title = __("In Series", "in_series");
$put_at_start_label= __("Start", "in_series");
$put_at_end_label = __("End", "in_series");
$new_series_option = __("--- New Series ---", "in_series");

$all_series = InSeries::adv_SeriesList_BySeriesName();
$available_series = "<option value=''>{$new_series_option}</option>";
$in_series = InSeries::adv_PostToSeries($epid);
$existing_series = "";
$post = get_post($epid);
global $user_ID;
get_currentuserinfo();

// New post; go off the current user info
if(empty($post) || empty($post->post_author))
    { $aid = $user_ID; }
else
    { $aid = $post->post_author; }

foreach($all_series as $series) {
    if(isset($in_series["{$series->series_id}"]))
        { continue; }

    if(InSeriesInternal::can_alter_series($series->series_id, $aid)) { 
        $series_name = stripslashes($series->series_name);
        $available_series .= "<option value='{$series->series_id}'>{$series_name}</option>";
    }
}
if(!empty($in_series))
{
    foreach($in_series as $series) {
        $sid = $series->series_id;
        $first_option = __("--- First ---", "in_series");
        $delete_option = __("--- Remove ---", "in_series");
        $pid = InSeries::adv_FirstInSeries($sid, false);
        $ppid = InSeries::adv_PrevInSeries($sid, $epid, false);
        $first_option_value = ($pid == $epid)? "" : "NULL";
        $post_list = "<option value='{$first_option_value}'>{$first_option}</option>";
        while(!is_null($pid)) {
            $selected = "value='$pid'";
            $title = strip_tags(InSeriesInternal::get_the_title($pid));
            if($pid == $ppid)
                { $selected = "value='' selected='selected'"; }
            if($pid != $epid)
                { $post_list .= "<option {$selected}>{$title}</option>"; }
            $pid = InSeries::adv_NextInSeries($sid, $pid, false);
        }
        $post_list .= "<option value='delete'>{$delete_option}</option>";
        $series_name = stripslashes($series->series_name);
        $existing_series .= "<label>{$series_name}<select id='in_series__existing__{$series->series_id}' name='in_series[existing][{$series->series_id}]'>{$post_list}</select></label>";
    }
}

$output = "
<fieldset id='in_series_fieldset' class='dbx-box'>
  <h3 class='dbx-handle'>{$edit_series_title}</h3>
  <div class='dbx-content'>
    <select id='in_series__add_to_series__series' name='in_series[add_to_series][series]'>
      {$available_series}
    </select>
    <label><input type='radio' id='in_series__add_to_series__position__start' name='in_series[add_to_series][position]' value='start' />{$put_at_start_label}</label>
    <label><input type='radio' id='in_series__add_to_series__position__end' checked='checked' name='in_series[add_to_series][position]' value='end' />{$put_at_end_label}</label>
    <label><input type='text' id='in_series__add_to_series__new_series' name='in_series[add_to_series][new_series]' value='' /></label>
    {$existing_series}
  </div>
</fieldset>";


echo $output;

?>
