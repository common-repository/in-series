<?php
/*

Copyright 2007 Travis Snoozy (ai2097@users.sourceforge.net)
Released under the terms of the GNU GPL v2

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

*/

$options = get_option("in_series");
$option_key = "in-series_set-options";
$nonce = "";
if(function_exists("wp_create_nonce"))
    { $nonce = wp_create_nonce($option_key); }

if(function_exists("attribute_escape")) {
    $ref = attribute_escape($_SERVER['REQUEST_URI']);
    if ( wp_get_original_referer() ) {
        $original_ref = attribute_escape(stripslashes(wp_get_original_referer()));
    }
}

$basic_opts =& $options['basic_config'];
$advanced_opts =& $options['advanced_config'];

$config_mode = $options['adv_config_mode'];
$config_switch = false;

if($_POST['in_series']) {
    check_admin_referer($option_key);
    if(isset($_POST['in_series']['adv_config_mode'])) {
        $config_mode = $_POST['in_series']['adv_config_mode'];
        $config_switch = $config_mode != $options['adv_config_mode'];
        $options['adv_config_mode'] = $config_mode;
    }
    else if($_POST['in_series']['submit']) {
        if($config_mode)
            { $save_opts =& $advanced_opts; }
        else
            { $save_opts =& $basic_opts; }
        foreach($save_opts as $key => $value) {
                $save_opts["$key"] = stripslashes($_POST['in_series']["$key"]);
        }
    }
    update_option("in_series", $options);
}

if($config_mode) {
    $default_adv_config_mode = "0";
    $adv_config_mode_label = __("Switch to Basic", "in_series");
    require_once("in-series-config-advanced.php");
}
else {
    $default_adv_config_mode = "1";
    $adv_config_mode_label = __("Switch to Advanced", "in_series");
    require_once("in-series-config-basic.php");
}

$in_series_options_header = __("In Series Options", "in_series");
$in_series_submit_button = __("Update Options &raquo;", "in_series");

$output = "
<div class='wrap in_series_config'>
  <h2>{$in_series_options_header}</h2>
  <form method='post' action=''>
  <input type='hidden' name='_wpnonce' value='{$nonce}' />
  <input type='hidden' name='_wp_http_referer' value='{$ref}' />
  <input type='hidden' name='_wp_http_original_referer' value='{$original_ref}' />
  <p><button type='submit' name='in_series[adv_config_mode]' value='{$default_adv_config_mode}'>${adv_config_mode_label}</button></p>
  <p class='submit'>
    <input type='submit' name='in_series[submit]' value='{$in_series_submit_button}' />
  </p>
  {$form}
  <p class='submit'>
    <input type='submit' name='in_series[submit]' value='{$in_series_submit_button}' />
  </p>
  </form>
</div>
";

echo $output;
?>
