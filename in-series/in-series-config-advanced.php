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
 * Outputs and processes the In Series configuration page.
 */

function apply_advanced_options(&$options) {
    $advanced_opts =& $options['advanced_config'];
    foreach($options['active_config'] as $key => $value)
        { $options['active_config']["$key"] = $advanced_opts["$key"]; }
}

if($_POST['in_series']['submit'] || $config_switch) {
    check_admin_referer($option_key);
    apply_advanced_options($options);
    update_option("in_series", $options);
}

$default_post = htmlspecialchars($advanced_opts['format_post'], ENT_QUOTES);
$default_post_multi_switch = empty($advanced_opts['format_post_multi_switch']) ? "" : "checked='checked'";
$default_post_multi = htmlspecialchars($advanced_opts['format_post_multi'], ENT_QUOTES);
$default_next = htmlspecialchars($advanced_opts['format_next'], ENT_QUOTES);
$default_next_last = htmlspecialchars($advanced_opts['format_next_last'], ENT_QUOTES);
$default_prev = htmlspecialchars($advanced_opts['format_prev'], ENT_QUOTES);
$default_prev_first = htmlspecialchars($advanced_opts['format_prev_first'], ENT_QUOTES);
$default_first = htmlspecialchars($advanced_opts['format_first'], ENT_QUOTES);
$default_first_active = htmlspecialchars($advanced_opts['format_first_active'], ENT_QUOTES);
$default_last = htmlspecialchars($advanced_opts['format_last'], ENT_QUOTES);
$default_last_active = htmlspecialchars($advanced_opts['format_last_active'], ENT_QUOTES);
$default_toc_block = htmlspecialchars($advanced_opts['format_toc_block'], ENT_QUOTES);
$default_toc_entry = htmlspecialchars($advanced_opts['format_toc_entry'], ENT_QUOTES);
$default_toc_active_entry = htmlspecialchars($advanced_opts['format_toc_active_entry'], ENT_QUOTES);
$default_series_block = htmlspecialchars($advanced_opts['format_series_list_block'], ENT_QUOTES);
$default_series_entry = htmlspecialchars($advanced_opts['format_series_list_entry'], ENT_QUOTES);
$default_meta_links = empty($advanced_opts['meta_links']) ? "" : "checked='checked'";
$post_format_label = __("Post layout: ", "in_series");
$post_format_multi_toggle_label = __("Use a different layout for multi-page views: ", "in_series");
$post_format_multi_label = __("Post layout (multi):", "in_series");
$next_link_label = __("Next link: ", "in_series");
$next_last_link_label = __("Next link (on last post):", "in_series");
$prev_link_label = __("Previous link: ", "in_series");
$prev_first_link_label = __("Previous link (on first post): ", "in_series");
$first_link_label = __("First link: ", "in_series");
$first_active_link_label = __("First link (on first post): ", "in_series");
$last_link_label = __("Last link: ", "in_series");
$last_active_link_label = __("Last link (on last post): ", "in_series");
$toc_block_label = __("Table of contents layout: ", "in_series");
$toc_entry_label = __("Entry link: ", "in_series");
$toc_active_entry_label = __("Active entry link: ", "in_series");
$series_block_label = __("Series list layout: ", "in_series");
$series_entry_label = __("Entry link: ", "in_series");
$meta_links_label = __("Insert &lt;link&gt; tags: ", "in_series");
$link_formatting_title = __("Link Formatting", "in_series");
$toc_formatting_title = __("Table of Contents Formatting", "in_series");
$post_formatting_title = __("Post Formatting", "in_series");
$series_formatting_title = __("Series List Formatting", "in_series");
$misc_title = __("Misc Options", "in_series");

$ta_rows = 4;
$ta_cols = 40;

$form = "
  <fieldset class='options'>
    <legend>{$post_formatting_title}</legend>
    <p><label for='in_series__format_post'>{$post_format_label}</label></p>
    <p><textarea name='in_series[format_post]' id='in_series__format_post' rows='{$ta_rows}' cols='{$ta_cols}'>{$default_post}</textarea></p>
    <p><label>{$post_format_multi_toggle_label}<input type='checkbox' name='in_series[format_post_multi_switch]' {$default_post_multi_switch} /></label></p>
    <p><label for='in_series__format_post_multi'>{$post_format_multi_label}</label></p>
    <p><textarea name='in_series[format_post_multi]' id='in_series__format_post_multi' rows='{$ta_rows}' cols='{$ta_cols}'>{$default_post_multi}</textarea></p>
  </fieldset>
  <fieldset class='options'>
    <legend>{$link_formatting_title}</legend>
    <p><label for='in_series__format_next'>{$next_link_label}</label></p>
    <p><textarea name='in_series[format_next]' id='in_series__format_next' rows='{$ta_rows}' cols='{$ta_cols}'>{$default_next}</textarea></p>
    <p><label for='in_series__format_next_last'>{$next_last_link_label}</label></p>
    <p><textarea name='in_series[format_next_last]' id='in_series__format_next_last' rows='{$ta_rows}' cols='{$ta_cols}'>{$default_next_last}</textarea></p>
    <p><label for='in_series__format_prev'>{$prev_link_label}</label></p>
    <p><textarea name='in_series[format_prev]' id='in_series__format_prev' rows='{$ta_rows}' cols='{$ta_cols}'>{$default_prev}</textarea></p>
    <p><label for='in_series__format_prev_first'>{$prev_first_link_label}</label></p>
    <p><textarea name='in_series[format_prev_first]' id='in_series__format_prev_first' rows='{$ta_rows}' cols='{$ta_cols}'>{$default_prev_first}</textarea></p>
    <p><label for='in_series__format_first'>{$first_link_label}</label></p>
    <p><textarea name='in_series[format_first]' id='in_series__format_first' rows='{$ta_rows}' cols='{$ta_cols}'>{$default_first}</textarea></p>
    <p><label for='in_series__format_first_active'>{$first_active_link_label}</label></p>
    <p><textarea name='in_series[format_first_active]' id='in_series__format_first_active' rows='{$ta_rows}' cols='{$ta_cols}'>{$default_first_active}</textarea></p>
    <p><label for='in_series__format_last'>{$last_link_label}</label></p>
    <p><textarea name='in_series[format_last]' id='in_series__format_last' rows='{$ta_rows}' cols='{$ta_cols}'>{$default_last}</textarea></p>
    <p><label for='in_series__format_last_active'>{$last_active_link_label}</label></p>
    <p><textarea name='in_series[format_last_active]' id='in_series__format_last_active' rows='{$ta_rows}' cols='{$ta_cols}'>{$default_last_active}</textarea></p>
  </fieldset>
  <fieldset class='options'>
    <legend>{$toc_formatting_title}</legend>
    <p><label for='in_series__format_toc_block'>{$toc_block_label}</label></p>
    <p><textarea name='in_series[format_toc_block]' id='in_series__format_toc_block' rows='{$ta_rows}' cols='{$ta_cols}'>{$default_toc_block}</textarea></p>
    <p><label for='in_series__format_toc_entry'>{$toc_entry_label}</label></p>
    <p><textarea name='in_series[format_toc_entry]' id='in_series__format_toc_entry' rows='{$ta_rows}' cols='{$ta_cols}'>{$default_toc_entry}</textarea></p>
    <p><label for='in_series__format_toc_active_entry'>{$toc_active_entry_label}</label></p>
    <p><textarea name='in_series[format_toc_active_entry]' id='in_series__format_toc_active_entry' rows='{$ta_rows}' cols='{$ta_cols}'>{$default_toc_active_entry}</textarea></p>
  </fieldset>
  <fieldset class='options'>
    <legend>{$series_formatting_title}</legend>
    <p><label for='in_series__format_series_list_block'>{$series_block_label}</label></p>
    <p><textarea name='in_series[format_series_list_block]' id='in_series__format_series_list_block' rows='{$ta_rows}' cols='{$ta_cols}'>{$default_series_block}</textarea></p>
    <p><label for='in_series__format_series_list_entry'>{$series_entry_label}</label></p>
    <p><textarea name='in_series[format_series_list_entry]' id='in_series__format_series_list_entry' rows='{$ta_rows}' cols='{$ta_cols}'>{$default_series_entry}</textarea></p>
  </fieldset>
  <fieldset class='options'>
    <legend>{$misc_title}</legend>
    <p><label>{$meta_links_label}<input type='checkbox' name='in_series[meta_links]' {$default_meta_links} /></label></p>
  </fieldset>
";

echo $output;

?>
