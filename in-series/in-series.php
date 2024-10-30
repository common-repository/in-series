<?php
/*
Plugin Name: In Series
Plugin URI: http://remstate.com/projects/in-series/
Description: Gives authors an easy way to connect posts together as a series.
Version: SVN
Author: Travis Snoozy
Author URI: http://remstate.com/

The original creator is Scott Merrill <http://www.skippy.net/>.

Copyright 2007 Travis Snoozy (ai2097@users.sourceforge.net)
Released under the terms of the GNU GPL v2

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

*/

require_once("in-series-internal.php");

/******************************************************************************\
 *                               In Series API                                *
\******************************************************************************/

/**
 * @static
 *
 * Provides "tags" for WordPress template hackers to insert In Series data into
 * their pages. Also acts as a namespace to house callbacks and other non-public
 * functions related to the In Series plugin.
 */

class InSeries {
    
    /**************************************************************************\
     *                               Basic API                                *
    \**************************************************************************/

    /**
     * @access public
     * @since 3.0
     * @uses $post
     *
     * For the current post, find and output a link to the next post that's in
     * the same series. If the current post is in more than one series, use the
     * "current" series. If no series is currently set, the post is the last in
     * the series, or the post is not in a series, do nothing.
     *
     * Link formatting is controlled via the "Link Format" options in the In
     * Series admin panel.
     *
     */
    function Next() {
        echo InSeriesInternal::NextHtml();
    }

    /**
     * @access public
     * @since 3.0
     * @uses $post
     *
     * For the current post, find and output a link to the previous post that's
     * in the same series. If the current post is in more than one series, use
     * the "current" series. If no series is currently set, the post is the
     * first in the series, or the post is not in a series, do nothing.
     *
     * Link formatting is controlled via the "Link Format" options in the In
     * Series admin panel.
     *
     */
    function Prev() {
        echo InSeriesInternal::PrevHtml();
    }

    /**
     * @access public
     * @since 3.0
     * @uses $post
     *
     * For the current post, generate and output a table of contents for all the
     * posts in the same series. If the current post is in more than one series,
     * use the "current" series. If no series is currently set, or the post is
     * not in a series, do nothing.
     *
     * ToC formatting is controlled via the "ToC Format" options in the In
     * Series admin panel.
     *
     */
    function ToC() {
        echo InSeriesInternal::ToCHtml();
    }

    /**
     * @access public
     * @since 3.0
     * 
     * Output a list of series. The formatting is controlled via the "Series
     * List Formatting" options in the In Series admin panel.
     *
     */
    function SeriesList() {
        echo InSeriesInternal::SeriesListHtml();
    }

    /**************************************************************************\
     *                              Advanced API                              *
    \**************************************************************************/

    /**
     * @access public
     * @since 3.0
     * @param integer $id A post ID (defaults to the post ID of $post)
     * @uses $post
     * @return object|NULL Indicates the current series, or NULL
     *
     * Given a post, find the series that the post is in. If the given post is
     * in more than one series, use the "current" series. If no series is
     * currently set, or the post is not in a series, return NULL. Also returns
     * NULL if $post is unavailable, and no explicit $id is given.
     *
     * The returned object has the following members:
     *  - series_id: The ID of the current series
     *  - owner_id: The author ID of the author who created the series
     *  - series_name: The title of the current series
     *
     */
    function adv_CurrentSeries($id = false) {
        $sid = InSeriesInternal::get_current_series_id($id);
        if(!empty($sid))
            { return InSeries::adv_SidToSeries($sid); }

        return NULL;
    }

    /**
     * @access public
     * @since 3.0
     * @param mixed $ascending The direction of the array ordering
     * @uses $wpdb
     * @return array Contains all of the series in the site's database
     *
     * Returns an array of objects, representing all of the series that are
     * present in this database. If no series are present, an array with no
     * elements is returned.
     *
     * The objects in the array have the following members:
     *   - series_id: The ID of the series
     *   - owner_id: The author ID of the author who created the series
     *   - series_name: The title of the series
     *
     * The array is unordered. However, many other query functions do have an
     * ordering, and refer to the documentation of this function. So, the API
     * for ordering is as follows:
     *
     *  - If $ascending is not passed, the default ordering for the function is
     *    used.
     *
     *  - If $ascending is true or false, then the primary sort is ascending or
     *    descending (respectively). Secondary sorts will NOT necessarily be set
     *    the same as the primary sort -- functions must document how the
     *    secondary (and further) sorts behave when only true or false is passed
     *    for $ascending. It is suggested that functions chose
     *    most-frequently-desired sortings for criteria past the first one.
     *
     *  - $ascending may be passed as an array of boolean values. Each boolean
     *    value corresponds to a sort order of a criterion. Functions MUST
     *    define which positions in the array correspond to which criterion. As
     *    with $ascending being true or false with a single-criteria function,
     *    each true and false in the array corresponds to an ascending or
     *    descending, respectively.
     *
     * $ascending === true
     *    
     *    The array is unordered. This is the default.
     *
     * $ascending === false
     *
     *    The array is unordered.
     * 
     * $ascending[0]
     *
     *    This does not control a sort criteria, because the array is unsorted.
     *
     */
    function adv_SeriesList($ascending = true) {
        global $wpdb;

        $table = InSeriesInternal::get_series_table_name();
        return $wpdb->get_results("SELECT * FROM {$table}");
    }

    /**
     * @since 3.0
     * @see adv_SeriesList()
     *
     * The return array is ordered from the most-recently created series, to the
     * least recently created series (descending) or vice-versa. Ordering is
     * based on when the series was created in relation to other series, and has
     * nothing to do with when or if posts are added to the series.
     *
     * $ascending === true
     *
     *    The array is ordered from the least-recently created series to the
     *    most recently created series.
     *
     * $ascending === false
     *
     *    The array is ordered from the most-recently created series to the
     *    least recently created series. This is the default.
     *
     */
    function adv_SeriesList_BySeriesCreation($ascending = false) {
        global $wpdb;

        $table = InSeriesInternal::get_series_table_name();
        if($ascending === true)
            { $order = "ASC"; }
        else
            { $order = "DESC"; }

        // ($order contains SQL syntax, and is wholly defined by us)
        return $wpdb->get_results("SELECT * FROM {$table} ORDER BY series_id {$order}");
    }

    /**
     * @since 3.1
     * @see adv_SeriesList()
     *
     * The return array is ordered from the series containing the post with the
     * most recent timestamp, to the series containing the least recent
     * timestamp (descending) or vice-versa. Two series with most recent posts
     * that have an exact match will be tie-broken using the series name (using
     * the same method as with avd_SeriesList_BySeriesName). Ties will have an
     * unspecified ordering amongst all posts with the exact same timestamp.
     * Post edits/updates do NOT affect the series ordering of this function --
     * only the posted date (timestamp in the edit view) is taken into account.
     *
     * $ascending === true
     *
     *    The array is ordered from the least-recently posted-to series to the
     *    most recently posted-to series.
     *
     * $ascending === false
     *
     *    The array is ordered from the most-recently posted-to series to the
     *    least recently posted-to series. This is the default.
     *
     */
    function adv_SeriesList_BySeriesRecentPost($ascending = false) {
        global $wpdb;

        $series = InSeriesInternal::get_series_table_name();
        $entries = InSeriesInternal::get_entry_table_name();
        if($ascending === true)
            { $order = "ASC"; }
        else
            { $order = "DESC"; }
        
        return $wpdb->get_results("
SELECT a.series_id,owner_id,series_name
  FROM
    {$series} AS a
    JOIN (
      SELECT series_id,MAX(post_date) AS pd
      FROM {$wpdb->posts} JOIN {$entries} ON post_id=ID
      GROUP BY series_id
    ) AS b
    ON a.series_id=b.series_id
    ORDER BY b.pd {$order};
");

    }

    /**
     * @since 3.0
     * @see adv_SeriesList()
     *
     * The return array is ordered alphabetically by series name, in ascending
     * or descending order based on the locale and collation rules of the
     * database. Series with the same name are then sorted by the order in which
     * they were created.
     *
     * $ascending === true
     *
     *   Series are sorted based on series name; for English, this usually means
     *   A-Z. Actual collation depends on language and collation settings in the
     *   database backend. Series with the same name are sorted from the
     *   most-recently created to the least-recently created (descending) in
     *   this mode. This is the default.
     *
     * $ascending === false
     *
     *   Descending instead of ascending; this usually means Z-A for English.
     *   Series with the same name are sorted from the least-recently created to
     *   the most-recently created (ascending) in this mode.
     *
     *
     * $ascending[0]
     *
     *    This controls the sort on series name.
     *
     * $ascending[1]
     *
     *    This controls the sort on series creation order.
     */
    function adv_SeriesList_BySeriesName($ascending = true) {
        global $wpdb;

        $table = InSeriesInternal::get_series_table_name();

        $name_order = "ASC";
        $series_order = "DESC";

        if(is_array($ascending)) {
            if($ascending[0] === false)
                { $name_order = "DESC"; }
            if($ascending[1] === true)
                { $series_order = "ASC"; }
        }
        else if($ascending === false) {
            $name_order = "DESC";
            $series_order = "ASC";
        }

        // ($series_order and $name_order contain SQL syntax, and are wholly defined by us)
        return $wpdb->get_results("SELECT * FROM {$table} ORDER BY series_name {$name_order}, series_id {$series_order}");
    }

    /**
     * @since 3.1
     * @param int $sid The series ID to count the number of posts in
     * @return int The number of posts that are part of the series, or -1 in the
     * case of an error
     *
     * Given a series ID, returns the number of posts that are part of that
     * series. 0 is returned if the series does not exist, while -1 is returned
     * if $sid is not a valid integer value.
     */
    function adv_CountPostsInSeries($sid) {
        global $wpdb;

        $table = InSeriesInternal::get_entry_table_name();

        $sid = InSeriesInternal::make_int($sid);
        if(is_null($sid))
            { return -1; }

        return $wpdb->get_var("SELECT COUNT(*) FROM {$table} WHERE series_id='{$sid}'");
    }

    /**
     * @since 3.0 adv_FirstInSeries(int)
     * @since 3.0.4 adv_FirstInSeries(int,[bool])
     * @param int $sid The series ID to do the lookup on
     * @param bool $filter Whether to ignore (filter) non-published posts
     * @return int The post ID corresponding to the first post in the
     * series
     *
     * Given a series_id, returns the post_id of the first post in the series.
     * If $filter is true, returns the post_id of the first published post in
     * the series. If no series exists for the given series_id, or if none of
     * the posts in the series are published, returns NULL.
     *
     */
    function adv_FirstInSeries($sid, $filter = true) {
        $sid = InSeriesInternal::make_int($sid);
        if(is_null($sid))
            { return NULL; }

        $cache =& InSeriesInternal::GetEntriesForSeries($sid);
        $retval = $cache["first"]->post_id;

        if($filter && !empty($retval)) {
            $post = get_post($retval);
            if($post->post_status != "publish")
                { $retval = InSeries::adv_NextInSeries($sid,$post->ID,true); }
        }

        return $retval;
    }

    /**
     * @since 3.0 adv_LastInSeries(int)
     * @since 3.0.4 adv_LastInSeries(int,[bool])
     * @param int $sid The series ID to do the lookup on
     * @param bool $filter Whether to ignore (filter) non-published posts
     * @return int The post ID corresponding to the last post in the
     * series
     *
     * Given a series_id, returns the post_id of the last post in the series.
     * If $filter is true, returns the post_id of the last published post in
     * the series. If no series exists for the given series_id, or if none of
     * the posts in the series are published, returns NULL.
     *
     */
    function adv_LastInSeries($sid, $filter = true) {
        $sid = InSeriesInternal::make_int($sid);
        if(is_null($sid))
            { return NULL; }

        $cache =& InSeriesInternal::GetEntriesForSeries($sid);
        $retval = $cache["last"]->post_id;

        if($filter && !empty($retval)) {
            $post = get_post($retval);
            if($post->post_status != "publish")
                { $retval = InSeries::adv_PrevInSeries($sid,$post->ID,true); }
        }

        return $retval;
    }

    /**
     * @since 3.0 adv_NextInSeries(int,[int])
     * @since 3.0.4 adv_NextInSeries(int,[int],[bool])
     * @param int $sid The series ID to do the lookup against
     * @param int $pid The post ID representing the current post in the series.
     * @param bool $filter Whether to ignore (filter) non-published posts
     * (defaults to the post ID of $post)
     * @uses $post
     * @return int|NULL The post ID of the next post in the series, or NULL
     *
     * Given a series_id and a post_id, returns the next post in the series. If
     * $filter is true, returns the next published post in the series. If the
     * post represented by $pid is the last post in the series represented by
     * $sid, returns NULL. If $filter is true, and $pid is the last published
     * post in $sid, returns NULL. Also returns NULL if the specified post is
     * not in the specified series.
     *
     */
    function adv_NextInSeries($sid, $pid = false, $filter = true) {
        $sid = InSeriesInternal::make_int($sid);
        if($pid === false)
            { $pid = InSeriesInternal::make_int(InSeriesInternal::get_the_ID()); }
        else
            { $pid = InSeriesInternal::make_int($pid); }

        if(is_null($sid) || is_null($pid))
            { return NULL; }

        do {
            $cache =& InSeriesInternal::GetEntriesForSeries($sid);
            $retval = $cache["$pid"]->next_post_id;

            if($filter) {
                $post = get_post($retval);
                if(empty($post))
                    { return NULL; }
                if($post->post_status == 'publish')
                    { break; }
                $pid = InSeriesInternal::make_int($post->ID);
                if(is_null($pid))
                    { return NULL; }
            }
            else
                { break; }
        } while(!is_null($retval));

        return $retval;
    }

    /**
     * @since 3.0 adv_PrevInSeries(int,[int])
     * @since 3.0.4 adv_PrevInSeries(int,[int],[bool])
     * @param int $sid The series ID to do the lookup against
     * @param int $pid The post ID representing the current post in the series.
     * @param bool $filter Whether to ignore (filter) non-published posts
     * (defaults to the post ID of $post)
     * @uses $post
     * @return int|NULL The post ID of the previous post in the series, or NULL
     *
     * Given a series_id and a post_id, returns the previous post in the series.
     * If $filter is true, returns the previous published post in the series. If
     * the post represented by $pid is the first post in the series represented
     * by $sid, returns NULL. If $filter is true, and $pid is the first
     * published post in $sid, returns NULL. Also returns NULL if the specified
     * post is not in the specified series.
     *
     */
    function adv_PrevInSeries($sid, $pid = false, $filter = true) {
        $sid = InSeriesInternal::make_int($sid);
        if($pid === false)
            { $pid = InSeriesInternal::make_int(InSeriesInternal::get_the_ID()); }
        else
            { $pid = InSeriesInternal::make_int($pid); }

        if(is_null($sid) || is_null($pid))
            { return NULL; }

        do {
            $cache =& InSeriesInternal::GetEntriesForSeries($sid);
            $retval = $cache["$pid"]->prev_post_id;

            if($filter) {
                $post = get_post($retval);
                if(empty($post))
                    { return NULL; }
                if($post->post_status == 'publish')
                    { break; }
                $pid = InSeriesInternal::make_int($post->ID);
                if(is_null($pid))
                    { return NULL; }
            }
            else
                { break; }
        } while(!is_null($retval));

        return $retval;

    }

    /**
     * @access public
     * @since 3.0
     * @param integer $sid A series ID
     * @return object|NULL Indicates the series with $sid, or NULL
     *
     * Given a series_id, find the series with that id. If no series corresponds
     * with $sid, return NULL.
     *
     * The returned object has the following members:
     *  - series_id: The ID of the current series
     *  - owner_id: The author ID of the author who created the series
     *  - series_name: The title of the current series
     *
     */
    function adv_SidToSeries($sid) {
        global $wpdb;

        $sid = InSeriesInternal::make_int($sid);
        if(is_null($sid))
            { return NULL; }

        return InSeriesInternal::GetSeries($sid);
    }

    /**
     * @access public
     * @since 3.0
     * @param int $pid The post to map to series
     * @return array Keys: series_id, values: objects, order: none
     *
     * Returns an array of objects, representing all of the series that the post
     * represented by $pid is part of. If the post is not in any series,  an
     * array with no elements is returned.
     *
     * The objects in the array have the following members:
     *   - series_id: The ID of the series
     *   - owner_id: The author ID of the author who created the series
     *   - series_name: The title of the series
     *
     */
    function adv_PostToSeries($pid) {
        $pid = InSeriesInternal::make_int($pid);
        if(is_null($pid))
            { return array(); }

        $all_series =& InSeriesInternal::PostToSeries($pid);

        $retval = array();
        foreach($all_series as $series) {
            $retval["{$series->series_id}"] = InSeries::adv_SidToSeries($series->series_id);
        }

        return $retval;
    }
}

?>
