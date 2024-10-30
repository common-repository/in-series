<?php
/*

Copyright 2007 Travis Snoozy (ai2097@users.sourceforge.net)
Released under the terms of the GNU GPL v2

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

*/

function make_series_list_format(&$options) {
    $basic_opts =& $options["basic_config"];
    $before = "<li><a href='%url' title='%title'>";
    $after = "</a></li>";
    $content = "%series";
    if($basic_opts['show_series_post_count']) {
        switch($basic_opts['series_post_count_position']) {
            case 'before':
                $content = "(%count) %series";
                break;
            case 'after': // Fall through
            default:
                $content = "%series (%count)";
                break;
        }
    }

    return $before . $content . $after;
}

function make_format($show_toc, $show_links, &$options) {
    $basic_opts =& $options["basic_config"];

    $toc = "<div class='series_toc'>%toc</div>";
    $half_links = "<div class='series_links'>%prev %next</div>";
    $full_links = "<div class='series_links'>%first %prev %next %last</div>";
    $before = "";
    $after = "";

    if($show_toc) {
        switch($basic_opts['toc_position']) {
            case "bottom":
                $after .= $toc;
                break;

            case "top": // Fall through
            default:
                $before = $toc . $before;
                break;
        }
    }

    switch($basic_opts['link_types']) {
        case "first_prev_next_last":
            $links =& $full_links;
            break;

        case "prev_next": // Fall through
        default:
            $links =& $half_links;
            break;
    }

    if($show_links) {
        switch($basic_opts['link_position']) {
            case "top":
                $before = $links . $before;
                break;

            case "both":
                $before = $links . $before;
                $after .= $links;
                break;

            case "bottom": // Fall through
            default:
                $after .= $links;
                break;
        }
    }

    return $before . "%content" . $after;
}

function apply_basic_options(&$options) {
    $basic_opts =& $options['basic_config'];

    $active =& $options['active_config'];
    $active['format_first'] = "<a href='%url' title='%title'>{$basic_opts['first_link_text']}</a>";
    $active['format_first_active'] = "";
    $active['format_prev'] = "<a href='%url' title='%title'>{$basic_opts['prev_link_text']}</a>";
    $active['format_prev_first'] = "";
    $active['format_next'] = "<a href='%url' title='%title'>{$basic_opts['next_link_text']}</a>";
    $active['format_next_last'] = "";
    $active['format_last'] = "<a href='%url' title='%title'>{$basic_opts['last_link_text']}</a>";
    $active['format_last_active'] = "";
    $active['format_toc_block'] = "<h3>{$basic_opts['toc_title_text']}</h3><ol>%entries</ol>";
    $active['format_toc_entry'] = "<li><a href='%url' title='%title'>%title</a></li>";
    $active['format_toc_active_entry'] = "<li>%title</li>";
    $active['format_post_multi_switch'] = true;
    $active['format_series_list_block'] = "<div class='series_list'><ul>%entries</ul></div>";
    $active['format_series_list_entry'] = make_series_list_format($options);
    $active['meta_links'] = $basic_opts['meta_links'];


    $active['format_post'] = make_format($basic_opts['show_toc'], $basic_opts['show_links'], $options);
    $active['format_post_multi'] = 
        make_format(
            $basic_opts['show_toc'] && strtolower($basic_opts['toc_visibility'] == "all"),
            $basic_opts['show_links'] && strtolower($basic_opts['link_visibility']) == "all",
            $options);
}

if($_POST['in_series']['submit'] || $config_switch) {
    check_admin_referer($option_key);
    apply_basic_options($options);
    update_option("in_series", $options);
}

$default_show_toc = InSeriesInternal::make_check($basic_opts['show_toc']);
$default_toc_visibility_everywhere = InSeriesInternal::make_select($basic_opts['toc_visibility'], "all");
$default_toc_visibility_single = InSeriesInternal::make_select($default_toc_visibility_everywhere && true);
$default_toc_location_bottom = InSeriesInternal::make_select($basic_opts['toc_position'], "bottom");
$default_toc_location_top = InSeriesInternal::make_select($default_toc_location_bottom && true);
$default_toc_title_text = htmlspecialchars($basic_opts['toc_title_text'], ENT_QUOTES);
$default_show_links = InSeriesInternal::make_check($basic_opts['show_links']);
$default_links_visibility_single = InSeriesInternal::make_select($basic_opts['link_visibility'], "single");
$default_links_visibility_everywhere = InSeriesInternal::make_select($default_links_visibility_single && true);
$default_links_prev_next = InSeriesInternal::make_select($basic_opts['link_types'], "prev_next");
$default_links_first_prev_next_last = InSeriesInternal::make_select($default_links_prev_next && true);
$default_link_location_top = InSeriesInternal::make_select($basic_opts['link_position'], "top");
$default_link_location_both = InSeriesInternal::make_select($basic_opts['link_position'], "both");
$default_link_location_bottom = InSeriesInternal::make_select($default_link_location_top || $default_link_location_both);
$default_first_link_text = htmlspecialchars($basic_opts['first_link_text'], ENT_QUOTES);
$default_prev_link_text = htmlspecialchars($basic_opts['prev_link_text'], ENT_QUOTES);
$default_next_link_text = htmlspecialchars($basic_opts['next_link_text'], ENT_QUOTES);
$default_last_link_text = htmlspecialchars($basic_opts['last_link_text'], ENT_QUOTES);
$default_show_series_post_count = InSeriesInternal::make_check($basic_opts['show_series_post_count']);
$default_series_post_count_position_before = InSeriesInternal::make_select($basic_opts['series_post_count_position'], "before");
$default_series_post_count_position_after = InSeriesInternal::make_select($default_series_post_count_position_before && true);
$default_meta_links = InSeriesInternal::make_check($basic_opts['meta_links']);

$meta_links_label = __("Use &lt;link&gt; tags to cross-link articles (enables <a href='http://developer.mozilla.org/en/docs/Link_prefetching_FAQ'>browser prefetching</a>).", "in_series");
$first_link_text_label = __("Links to the first post in a series have the following text: ", "in_series");
$prev_link_text_label = __("Links to the previous post in a series have the following text: ", "in_series");
$next_link_text_label = __("Links to the next post in a series have the following text: ", "in_series");
$last_link_text_label = __("Links to the last post in a series have the following text: ", "in_series");
$series_post_count_label = __("I want the number of posts in a series to show up in the series list.", "in_series");

$basic_toc_query = __(
"
    <p>
        <label><input type='checkbox' name='in_series[show_toc]' {$default_show_toc} />I want a <acronym title='Table of Contents'>ToC</acronym></label>
        <label>titled \"<input type='text' name='in_series[toc_title_text]' value='{$default_toc_title_text}'/>\"</label>
        <label>to appear
          <select name='in_series[toc_visibility]'>
            <option value='all' {$default_toc_visibility_everywhere}>in all views</option>
            <option value='single' {$default_toc_visibility_single}>only in single post views</option>
          </select>
        </label>
        <label>at the
            <select name='in_series[toc_position]'>
                <option value='top' {$default_toc_location_top}>top</option>
                <option value='bottom' {$default_toc_location_bottom}>bottom</option>
            </select>
        of the post</label>.
    </p>
", "in_series");

$basic_links_query = __("
    <p>
        <label><input type='checkbox' name='in_series[show_links]' {$default_show_links} />I want navigational links</label>
        <label>for the
            <select name='in_series[link_types]'>
                <option value='prev_next' {$default_links_prev_next}>previous and next</option>
                <option value='first_prev_next_last' {$default_links_first_prev_next_last}>first, previous, next and last</option>
            </select>
        posts</label>
        <label>to appear
            <select name='in_series[link_visibility]'>
                <option value='all' {$default_links_visibility_everywhere}>in all views</option>
                <option value='single' {$default_links_visibility_single}>only in single post views</option>
            </select>
        </label>
        <label>at the
            <select name='in_series[link_position]'>
                <option value='top' {$default_link_location_top}>top</option>
                <option value='bottom' {$default_link_location_bottom}>bottom</option>
                <option value='both' {$default_link_location_both}>top and bottom</option>
            </select>
        of the post</label>.
    </p>
", "in_series");

$basic_list_query = __("
    <p>
        <label><input type='checkbox' name='in_series[show_series_post_count]' {$default_show_series_post_count} />I would like post counts to show up in series lists</label>
        <label>
            <select name='in_series[series_post_count_position]'>
                <option value='before' {$default_series_post_count_position_before}>before</option>
                <option value='after' {$default_series_post_count_position_after}>after</option>
            </select>
        the series name.</label>
    </p>
");

$form = 
"
    {$basic_toc_query}
    {$basic_links_query}
    <p><label>{$first_link_text_label}<input type='text' name='in_series[first_link_text]' value='{$default_first_link_text}' /></label></p>
    <p><label>{$prev_link_text_label}<input type='text' name='in_series[prev_link_text]' value='{$default_prev_link_text}' /></label></p>
    <p><label>{$next_link_text_label}<input type='text' name='in_series[next_link_text]' value='{$default_next_link_text}' /></label></p>
    <p><label>{$last_link_text_label}<input type='text' name='in_series[last_link_text]' value='{$default_last_link_text}' /></label></p>
    {$basic_list_query}
    <p><label><input type='checkbox' name='in_series[meta_links]' {$default_meta_links} />{$meta_links_label}</label></p>
";

?>
