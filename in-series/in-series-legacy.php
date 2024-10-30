<?php
/*

Copyright 2007 Travis Snoozy (ai2097@users.sourceforge.net)
Released under the terms of the GNU GPL v2

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

*/

/******************************************************************************\
 *                         Legacy API  --  DEPRECATED                         *
 *       These functions are provided for backwards-compatibility only.       *
 *      They are KNOWN TO BE BROKEN, and their use is HIGHLY DISCOURAGED.     *
\******************************************************************************/

/**
 * @deprecated 3.0
 * @access public
 * @since 1.0
 * @param string $sep Output between links
 * @param string $before Output before the first link
 * @param string $after Output after the last link
 * @uses $post If unset, outputs nothing
 * @uses $wpdb If unset, outputs nothing
 *
 * Outputs an HTML block of the format:
 *   $before <a href="permalink">post title</a>[$sep<a href...] $after
 *
 * Posts are listed in series order.
 *
 * If the current post is NOT in a series, outputs nothing.
 * 
 * Only posts with the same series AND the same author as the current post are
 * displayed.
 *
 */
function all_in_series($sep=',', $before=false, $after=' &raquo;') {

    $sid = InSeries::adv_CurrentSeries();
    if(is_null($sid))
        { return; }

    $sid = $sid->series_id;
    $pid = InSeries::adv_FirstInSeries($sid);
    $current_pid = InSeriesInternal::get_the_ID();

    if(is_null($sid) || is_null($pid) || is_null($current_pid))
        { return; }

    if($before === false)
        { $before = __("&raquo; Read the whole series:", "in_series")." "; }
    
    $output = $before;
    $counter = 0;
    $separator = "";
    while(!is_null($pid)) {
        $counter++;
        $title = InSeriesInternal::get_the_title($pid);
        $url = get_permalink($pid);
        if($pid == $current_pid)
            { $output .= "{$separator}{$counter}"; }
        else
            { $output .= "{$separator}<a href='{$url}' title='{$title}'>{$counter}</a>"; }
        $separator = $sep;
        $pid = InSeries::adv_NextInSeries($sid, $pid);
    }
    $output .= $after;

    echo $output;
}

/**
 * @deprecated 3.0
 * @access public
 * @since 1.9 
 * @param string $before Output before first link
 * @param string $after Output after last link
 * @param string $order The order the series are displayed in ('id' or 'name')
 *
 * Outputs an HTML block of the following format:
 *  $before <li><a href="permalink" title="post title">post title</a></li>
 *  $after
 *
 * If $order is 'name', series are listed in alphabetical order by series_name
 * (ascending). If $order is 'id', series are listed in the order the series
 * were created (ascending). Note that the semantics of ordering by 'id' have
 * been poorly defined in the past; the current behavior may not match the
 * behavior of prior versions.
 *
 * If no series exist, outputs nothing.
 *
 */
function all_series($before = '<ol>', $after = '</ol>', $order = 'name') {
    if($order == "id")
        { $all_series = InSeries::adv_SeriesList_BySeriesCreation(); }
    else
        { $all_series = InSeries::adv_SeriesList_BySeriesName(); }

    $output = $before;
    foreach($all_series as $series) {
        $pid = InSeries::adv_FirstInSeries($series->series_id);
        $url = get_permalink($pid);
        $title = InSeriesInternal::get_the_title($pid);
        $series_name = stripslashes($series->series_name);
        $output .= "<li><a href='{$url}' title='{$title}'>{$series_name}</a></li>";
    }
    $output .= $after;

    echo $output;
}

/**
 * @deprecated 3.0
 * @access public
 * @since 1.0
 * @param string $series Case-insensitive series name
 * @uses $wpdb
 * @uses $post If unset, outputs nothing
 * @return array Keys: post_id, values: post_title, order: series_order
 *
 * Only returns posts by the same author as the current post.
 *
 * Matches are NOT case-sensitive.
 *
 */
function get_all_in_series($series = '') {
    global $wpdb;

    $series_table = InSeriesInternal::get_series_table_name();
    $aid = InSeriesInternal::make_int(get_the_author_id());
    if(is_null($aid))
        { return array(); }

    $series = $wpdb->escape($series);

    $candidates = $wpdb->get_results("
SELECT series_id FROM {$series_table}
  WHERE series_name='{$series}' AND
    owner_id='{$aid}'
  LIMIT 1");

    if(empty($candidates))
        { return array(); }

    $sid = $candidates[0]->series_id;
    $pid = InSeries::adv_FirstInSeries($sid);
    $retval = array();
    while(!is_null($pid)) {
        $retval["$pid"] = InSeriesInternal::get_the_title($pid);
        $pid = InSeries::adv_NextInSeries($sid, $pid);
    }

    return $retval;

}

/**
 * @deprecated 3.0
 * @access public
 * @since 1.9 get_all_series([string]) 
 * @since 2.1.1 get_all_series([string],[bool])
 * @param string $order The order the returned results are sorted by ('id' or
 * 'name')
 * @param bool $published Whether results should be limited to only published
 * posts
 * @return array Keys: post_id, values: series_name, order: depends on
 * $order
 *
 * Don't use this function. The interface is horribly, horribly broken.
 *
 * Returns all series in existence, provided that no two series have the same
 * name, and no two series contain the same poist. If two series exist with the
 * same name, or a single post exists in more than one series, the behavior of
 * this function is unspecified.
 *
 * Setting $order to "id" causes the returned array to be sorted so that the
 * series containing the highest post ID for the highest-ordered ("last")
 * article is ranked first. Setting $order to "name" causes the returned array
 * to be sorted by series_name.
 *
 * Setting $published to false allows for entirely-unpublished series to show
 * up in the return array. This feature was added in 2.1 to support the
 * drop-down list of series in on the "write" page sidebar, and really doesn't
 * have much other purpose. Setting published to false causes the returned
 * array's keys to be of unspecified values (so don't use them!).
 *
 */
function get_all_series($order = 'name', $published = true) {
    if($order != "id")
        { $all_series = InSeries::adv_SeriesList_BySeriesName(); }
    else
        { $all_series = InSeries::adv_SeriesList_BySeriesCreation(); }

    if(empty($all_series))
        { return array(); }

    $retval = array();
    foreach($all_series as $series) {
        $pid = InSeries::adv_FirstInSeries($series->series_id);
        $retval["$pid"] = $series->series_name;
    }

    return $retval;
}

/**
 * @deprecated 3.0
 * @access public
 * @since 1.0 get_next_in_series()
 * @since 2.1 get_next_in_series([bool])
 * @param bool $single_only Controls in which viewing contexts a result is
 * returned
 * @uses $post
 * @return object|NULL Represents the next post in the series, or NULL
 *
 * The returned object has the following members:
 *  - ID (string) indicates the post_id
 *  - post_title (string) indicates the post_title
 *
 * Returns NULL if any of the following are true:
 *  - There is no $post
 *  - The current post is not in a series
 *  - The current post is the last in its series
 *  - If $single_only is true, and the function is called outside the context of
 *    a "single"-style page
 *
 */
function get_next_in_series($single_only = true) {
    if($single_only && !is_single())
        { return NULL; }

    $series = InSeries::adv_CurrentSeries();
    if(is_null($series))
        { return NULL; }

    $pid = InSeries::adv_NextInSeries($series->series_id);
    if(is_null($pid))
        { return NULL; }

    $retval = NULL;
    $retval->ID = $pid;
    $retval->post_title = InSeriesInternal::get_the_title($pid);

    return $retval;
}

/**
 * @deprecated 3.0
 * @access public
 * @since 1.0 get_previous_in_series()
 * @since 2.1 get_previous_in_series([bool])
 * @param bool $single_only Controls in which viewing contexts a result is
 * returned
 * @uses $post
 * @return object|NULL Represents the previous post in the series, or NULL
 *
 * The returned object has the following members:
 *  - ID (string) indicates the post_id
 *  - post_title (string) indicates the post_title
 *
 * Returns NULL if any of the following are true:
 *  - There is no $post
 *  - The current post is not in a series
 *  - The current post is the first in its series
 *  - If $single_only is true, and the function is called outside the context of
 *    a "single"-style page
 *
 */
function get_previous_in_series($single_only = true) {
    if($single_only && !is_single())
        { return NULL; }

    $series = InSeries::adv_CurrentSeries();
    if(is_null($series))
        { return NULL; }

    $pid = InSeries::adv_PrevInSeries($series->series_id);
    if(is_null($pid))
        { return NULL; }

    $retval = NULL;
    $retval->ID = $pid;
    $retval->post_title = InSeriesInternal::get_the_title($pid);

    return $retval;
}

/**
 * @deprecated 3.0
 * @access public
 * @since 2.2
 * @param string $id The id of the post to generate a table of contents for
 * @param string $class The HTML class given to each of the output li elements
 * @param string $before Output before the first li element
 * @param string $after Output after the last li element
 * @return string Containins the HTML-formatted table of contents
 *
 * Returns an HTML block (string) of the following format:
 *  $before <li [class="class"]><a href="permalink" title="post title">post
 *  title</a></li> $after
 *
 * Posts are listed in series_order.
 *
 * Returns nothing if the post (associated with the given id) is not in a
 * series.
 *
 */
function get_series_table_of_contents($id, $class = '', $before = '<ol>', $after = '</ol>') {

    $sid = InSeries::adv_CurrentSeries($id);
    if(is_null($sid))
        { return ""; }

    $sid = $sid->series_id;
    $current_pid = InSeriesInternal::get_the_ID();

    $output = $before;
    if(!empty($class)) {
        $class = htmlspecialchars($class);
        $class = " class='{$class}'";
    }
    $pid = InSeries::adv_FirstInSeries($sid);
    while(!is_null($pid)) {
        $url = get_permalink($pid);
        $title = InSeriesInternal::get_the_title($pid);
        if($current_pid == $pid)
            { $output .= "<li{$class}>{$title}</li>"; }
        else
            { $output .= "<li{$class}><a href='{$url}' title='{$title}'>{$title}</a></li>"; }
        $pid = InSeries::adv_NextInSeries($sid, $pid);
    }
    $output .= $after;

    return $output;
}

/**
 * @deprecated 3.0
 * @access public
 * @since 1.0
 * @param string $format Controls the text surrounding the link
 * @param string $link Defines the text of the link
 * @uses $post
 *
 * $format can take the following special tokens:
 *   %link -- Expands to an HTML hyperlink to the next post
 *
 * $link can take the following special tokens:
 *   %title -- Expands to the post title for the next post
 *
 * Outputs HTML as described by the $format parameter. All instances of %link
 * have an href attribute referring to the next post, and link text as
 * described by $link parameter.
 * 
 * Outputs nothing if any of the following are true:
 *  - There is no $post
 *  - The current post is not in a series
 *  - The current post is the last in its series
 *  - The function is called outside the context of a "single"-style page
 *
 */
function next_in_series($format='%link &raquo;', $link='%title') {
    $sid = InSeries::adv_CurrentSeries();
    if(is_null($sid))
        { return; }
    $sid = $sid->series_id;

    $pid = InSeries::adv_NextInSeries($sid);
    if(is_null($pid))
        { return; }

    $url = get_permalink($pid);
    $title = InSeriesInternal::get_the_title($pid);
    $link = str_replace('%title', $title, $link);
    $link = "<a href='{$url}' title='{$title}'>{$link}</a>";
    $format = str_replace('%link', $link, $format);
    echo $format;
}

/**
 * @deprecated 3.0
 * @access public
 * @since 1.0
 * @param string $format Controls the text surrounding the link
 * @param string $link Defines the text of the link
 * @uses $post
 *
 * $format can take the following special tokens:
 *   %link -- Expands to an HTML hyperlink to the previous post
 *
 * $link can take the following special tokens:
 *   %title -- Expands to the post title for the previous post
 *
 * Outputs HTML as described by the $format parameter. All instances of %link
 * have an href attribute referring to the previous post, and link text as
 * described by $link parameter.
 * 
 * Outputs nothing if any of the following are true:
 *  - There is no $post
 *  - The current post is not in a series
 *  - The current post is the first in its series
 *  - The function is called outside the context of a "single"-style page
 *
 */
function previous_in_series($format='&laquo; %link', $link='%title') {
    $sid = InSeries::adv_CurrentSeries();
    if(is_null($sid))
        { return; }
    $sid = $sid->series_id;

    $pid = InSeries::adv_PrevInSeries($sid);
    if(is_null($pid))
        { return; }

    $url = get_permalink($pid);
    $title = InSeriesInternal::get_the_title($pid);
    $link = str_replace('%title', $title, $link);
    $link = "<a href='{$url}' title='{$title}'>{$link}</a>";
    $format = str_replace('%link', $link, $format);
    echo $format;
}

/**
 * @deprecated 3.0
 * @access public
 * @since 1.1 series_table_of_contents([string])
 * @since 1.5 series_table_of_contents([string],[string],[string])
 * @param string $class The HTML class given to each of the output li elements
 * @param string $before Output before the first li element
 * @param string $after Output after the last li element
 * @uses $post
 *
 * Outputs an HTML block of the following format:
 *  $before <li [class="class"]><a href="permalink" title="post title">post
 *  title</a></li> $after
 *
 * Posts are listed in series_order.
 *
 * Outputs nothing if the post is not part of a series, or if this function is
 * called outside the context of a "single"-style page.
 *
 */
function series_table_of_contents($class='', $before = '<ol>', $after = '</ol>') {
    echo get_series_table_of_contents(get_the_ID(), $class, $before, $after);
}

?>
