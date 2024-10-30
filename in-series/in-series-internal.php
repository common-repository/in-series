<?php
/*

Copyright 2007 Travis Snoozy (ai2097@users.sourceforge.net)
Released under the terms of the GNU GPL v2

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

*/

load_plugin_textdomain('in_series', 'wp-content/plugins/in-series');
require_once("in-series-legacy.php");

/**
 * @access private
 *
 * Internal functions that do the bulk of the behind-the-scenes plugin work.
 */
class InSeriesInternal {

    /**
     * Installs tables and options, and upgrades data from prior In Series
     * versions to the format of this version.
     */
    function activate() {
        require_once("in-series-init.php");
        // Initialize and upgrade series metadata
        InSeriesInit::initialize_3_0_database();
        InSeriesInit::initialize_3_0_6_database();
        InSeriesInit::initialize_3_0_11_database();
        InSeriesInit::do_pre_2_0_metadata_convert();
        InSeriesInit::do_2_x_to_3_0_metadata_convert();
        InSeriesInit::do_3_0_to_3_0_6_metadata_convert();
        InSeriesInit::do_3_0_6_to_3_0_11_metadata_convert();

        // Initialize and upgrade plugin options
        InSeriesInit::initialize_options();
    }

    function compat_hooks() {
        global $wp_version;

        if(!empty($wp_version)) {
            // Before 1.5
            if(version_compare("1.5", $wp_version) > 0)
                { exit(__("In Series requires at least WordPress 1.5 to operate.", "in_series")); }
            // 1.5 to 2.0
            else if(version_compare("2.0", $wp_version) > 0) {

                // 1.5.x does not have a plugin activation action. Emulate it.
                $active_plugins = get_option("active_plugins");
                if($_GET['activate'] == "true" &&
                   in_array("in-series/in-series.php", $active_plugins)) {
                    // Not perfect; runs whenever any plugin is activated, and
                    // In Series is active. Still fairly infrequent, though. :)
                    InSeriesInternal::activate();
                }

                add_action('edit_form_advanced', array('InSeriesInternal','add_write_post_sidebar'));
                // 1.5.x fires save_post only when creating a new post, not when
                // saving an existing post -- so we have to hook edit post.
                add_action('edit_post', array('InSeriesInternal','process_save'));
            }
            // 2.0 or later
            else {
                add_action('activate_in-series/in-series.php', array('InSeriesInternal','activate'));
                add_action('dbx_post_sidebar', array('InSeriesInternal','add_write_post_sidebar'));
            }
        }
        else
            { exit(__("Could not get WordPress version.", "in_series")); }
    }

    function load_widgets() {
        if(function_exists("register_sidebar_widget")) {
            require_once("in-series-widgets.php");
        }

    }

    /**************************************************************************\
     *                        General Helper Functions                        *
    \**************************************************************************/

    /**
     * @param string $table the table name to add a prefix to
     * @return string $table with the table prefix prepended
     */
    function get_table_name($table) {
        global $wpdb;
        global $table_prefix;

        $prefix = "";

        if(!empty($wpdb->prefix))
            { $prefix = $wpdb->prefix; }
        else
            { $prefix = $table_prefix; }

        return $prefix . $table;
    }

    /**
     * @return string The name of the series table 
     */
    function get_series_table_name() {
        return InSeriesInternal::get_table_name("in_series_3_0_11_series");
    }

    /**
     * @return string The name of the authorization table 
     */
    function get_auth_table_name() {
        return InSeriesInternal::get_table_name("in_series_3_0_11_auth");
    }

    /**
     * @return string The name of the entries table 
     */
    function get_entry_table_name() {
        return InSeriesInternal::get_table_name("in_series_3_0_11_entries");
    }

    /**
     * @param string $table The complete table name to look up
     * @return bool Whether $table exists in the database
     */
    function has_table($table) {
        global $wpdb;

        return $wpdb->get_var("SHOW TABLES LIKE '{$table}'") == $table;
    }

    /**
     * @return int|NULL The current series ID hint, or NULL if there is none.
     */
    function get_series_hint() {
        return InSeriesInternal::make_int($_GET['sid']);
    }

    /**
     * @return int|NULL The current series ID, or NULL
     * @see adv_CurrentSeries
     */
    function get_current_series_id($pid = false) {
        $hint = InSeriesInternal::get_series_hint();

        if($pid !== false)
            { $id = InSeriesInternal::make_int($pid); }
        else
            { $id = InSeriesInternal::make_int(InSeriesInternal::get_the_ID()); }

        if (is_null($id))
            { return NULL; }

        $series_set =& InSeriesInternal::PostToSeries($id);

        if(empty($series_set))
            { return NULL; }
        else if(count($series_set) == 1)
            { return $series_set[0]->series_id; }
        else if(!empty($hint)) {
            foreach($series_set as $series) {
                if($series->series_id == $hint)
                    { return $series->series_id; }
            }
            // Fall through on no match
        }
        
        return NULL;
    }

    /**
     * @param mixed $value Int or a string with an int value
     * @return int|NULL A proper int, or (if no sane conversion could be made)
     * NULL
     *
     * "Sane conversion" means that either $value is already an integer (in
     * which case, it is returned), or $value is a string representing an
     * integer value (not floating point, or an integer with any extra stuff at
     * the end).
     */
    function make_int($value) {
        if(is_int($value))
            { return $value; }

        // Sift out strings with non-numeric parts
        if(!is_numeric($value))
            { return NULL; }

        // Do a loose cast; this sifts out strings that are floats.
        $value += 0; 

        // If we don't have an int at this point, we never will.
        if(!is_int($value))
            { return NULL; }

        return $value;
    }

    function make_select($check, $reference = '') {
        $retval = "selected='selected'";
        // If $check is a bool, !$check indicates if we use the default.
        if(is_bool($check) && $check)
            { $retval = ""; }
        // Otherwise, equality of $check and $reference indicate selection.
        else if (strtolower($check) != strtolower($reference))
            { $retval = ""; }

        return $retval;
    }

    function make_check($value) {
        $retval = "checked='checked'";
        if(empty($value))
            { $retval = ""; }
        return $retval;
    }

    /**
     * @return int|NULL a value indicating the current post ID.
     *
     * Leave wiggle-room, just in case things change in the future.
     *
     */
    function get_the_ID() {
        global $post;
        global $wp_query;

        $retval = NULL;

        if(function_exists("get_the_ID"))
            { $retval = get_the_ID(); }

        if(empty($retval) && !empty($post) && !empty($post->ID))
            { $retval = $post->ID; }

        if(empty($retval) && !empty($wp_query) && !empty($wp_query->post) && !empty($wp_query->post->ID))
            { $retval = $wp_query->post->ID; }

        return $retval;
    }

    /**
     * @return string the filtered post title.
     *
     * Always ensure that the post title is filtered through both
     * get_the_title's internal filter, and the general hook.
     *
     */
    function get_the_title($pid) {
        global $wp_version;

        $retval = get_the_title($pid);

        // We need to manually apply the filters for the title in pre 2.3
        if(version_compare("2.3", $wp_version) > 0) {
            // This is -exactly- how the_title works (tack on the get_the_title
            // cruft, -then- filter), so it's a feature, not a bug :)
            $retval = apply_filters("the_title", $retval, '', '');
        }

        return $retval;
    }

    /**
     * @return bool whether the given author is allowed to edit the given
     * series.
     */
    function can_alter_series($sid, $aid) {
        $sid = InSeriesInternal::make_int($sid);
        $aid = InSeriesInternal::make_int($aid);
        if(is_null($sid) || is_null($aid))
            { return false; }

        $series = InSeries::adv_SidToSeries($sid);
        if(empty($series))
            { return false; }

        // TODO: work off the auth table, too.
        return $series->owner_id == $aid;
    }


    /**************************************************************************\
     *                                Caching                                 *
    \**************************************************************************/

    function &GetEntriesCachedSid() {
        static $entries_cached_sid = false;
        return $entries_cached_sid;
    }

    function &GetEntriesForSeries($sid) {
        static $entries_cached_results = NULL;
        $entries_cached_sid =& InSeriesInternal::GetEntriesCachedSid();
        global $wpdb;

        $sid = InSeriesInternal::make_int($sid);
        if(is_null($sid))
            { return NULL; }

        if($sid !== $entries_cached_sid) {
            $entry_table = InSeriesInternal::get_entry_table_name();
            $temp_results = $wpdb->get_results("SELECT * FROM {$entry_table} WHERE series_id='$sid'");
            $entries_cached_results = array();
            foreach($temp_results as $result) {
                $entries_cached_results["{$result->post_id}"] = $result;
                if($result->prev_post_id == NULL)
                    { $entries_cached_results["first"] = $result; }
                else if ($result->next_post_id == NULL)
                    { $entries_cached_results["last"] = $result; }
            }
            $entries_cached_sid = $sid;
        }

        return $entries_cached_results;
    }

    function &GetSeriesCachedResults() {
        static $series_cached_results = array();
        return $series_cached_results;
    }
    function &GetSeries($sid) {
        $series_cached_results =& InSeriesInternal::GetSeriesCachedResults();
        global $wpdb;

        $sid = InSeriesInternal::make_int($sid);
        if(is_null($sid))
            { return NULL; }

        if(!isset($series_cached_results["$sid"])) {
            $series_table = InSeriesInternal::get_series_table_name();

            $series_cached_results["$sid"] =
                $wpdb->get_row("SELECT * FROM {$series_table} WHERE series_id='$sid'");
        }
        return $series_cached_results["$sid"];
    }
    
    function &GetPostSeriesCachedResults() {
        static $post_series_cached_results = array();
        return $post_series_cached_results;
    }
    function &PostToSeries($pid) {
        $post_series_cached_results =& InSeriesInternal::GetPostSeriesCachedResults();
        global $wpdb;
        $pid = InSeriesInternal::make_int($pid);
        if(is_null($pid))
            { return NULL; }

        if(!isset($post_series_cached_results["$pid"])) {
            $entry_table = InSeriesInternal::get_entry_table_name();

            $sids = $wpdb->get_results("SELECT * FROM {$entry_table} WHERE post_id='{$pid}'");
            if(empty($sids))
                { $sids = array(); }
            $post_series_cached_results["$pid"] = $sids;
        }

        return $post_series_cached_results["$pid"];
    }

    function InvalidateCache($sid, $pid) {
        $post_series_cached_results =& InSeriesInternal::GetPostSeriesCachedResults();
        $series_cached_results =& InSeriesInternal::GetSeriesCachedResults();
        $entries_cached_sid =& InSeriesInternal::GetEntriesCachedSid();
        unset($post_series_cached_results["$pid"]);
        unset($series_cached_results["$sid"]);
        if($entries_cached_sid == $sid)
            { $entries_cached_sid = false; }
    }


    /**************************************************************************\
     *                           Basic API back-end                           *
    \**************************************************************************/

    function NextHtml() {
        $options = get_option("in_series");
        $options = $options['active_config'];

        $series = InSeries::adv_CurrentSeries();
        $pid = InSeries::adv_NextInSeries($series->series_id);

        if(is_null($series))
            { return NULL; }

        $format = NULL;
        if(is_null($pid))
            { $format = $options['format_next_last']; }
        else
            { $format = $options['format_next']; }

        return InSeriesInternal::FormatHtml($format, $pid);
    }

    function PrevHtml() {
        $options = get_option("in_series");
        $options = $options['active_config'];

        $series = InSeries::adv_CurrentSeries();
        $pid = InSeries::adv_PrevInSeries($series->series_id);

        if(is_null($series))
            { return NULL; }

        $format = NULL;
        if(is_null($pid))
            { $format = $options['format_prev_first']; }
        else
            { $format = $options['format_prev']; }

        return InSeriesInternal::FormatHtml($format, $pid);
    }

    function FirstHtml() {
        $options = get_option("in_series");
        $options = $options['active_config'];

        $series = InSeries::adv_CurrentSeries();
        $pid = InSeries::adv_FirstInSeries($series->series_id);

        if(is_null($series) || is_null($pid))
            { return NULL; }

        $format = NULL;
        if(InSeriesInternal::get_the_ID() == $pid)
            { $format = $options['format_first_active']; }
        else
            { $format = $options['format_first']; }

        return InSeriesInternal::FormatHtml($format, $pid);
    }

    function LastHtml() {
        $options = get_option("in_series");
        $options = $options['active_config'];

        $series = InSeries::adv_CurrentSeries();
        $pid = InSeries::adv_LastInSeries($series->series_id);

        if(is_null($series) || is_null($pid))
            { return NULL; }

        $format = NULL;
        if(InSeriesInternal::get_the_ID() == $pid)
            { $format = $options['format_last_active']; }
        else
            { $format = $options['format_last']; }

        return InSeriesInternal::FormatHtml($format, $pid);
    }

    function ToCHtml($format = false) {
        $options = get_option('in_series');
        $options = $options['active_config'];
        $sid = InSeries::adv_CurrentSeries();
        if(is_null($sid))
            { return NULL; }
        $sid = $sid->series_id;
        $pid = InSeries::adv_FirstInSeries($sid);
        $current_pid = InSeriesInternal::get_the_ID();

        if(is_null($sid) || is_null($pid) || is_null($current_pid))
            { return NULL; }
        
        $active_entry_format = $options['format_toc_active_entry'];
        $entry_format = $options['format_toc_entry'];
        $toc_format = $options['format_toc_block'];

        if(is_array($format)) {
            $active_entry_format = isset($format['format_toc_active_entry']) ? $format['format_toc_active_entry'] : $active_entry_format;
            $entry_format = isset($format['format_toc_entry']) ? $format['format_toc_entry'] : $entry_format;
            $toc_format = isset($format['format_toc_block']) ? $format['format_toc_block'] : $toc_format;
        }

        $entries = "";
        while(!is_null($pid)) {
            if($pid == $current_pid)
                { $entries .= InSeriesInternal::FormatHtml($active_entry_format, $pid); }
            else
                { $entries .= InSeriesInternal::FormatHtml($entry_format, $pid); }
            $pid = InSeries::adv_NextInSeries($sid, $pid);
        }

        $output = InSeriesInternal::FormatHtml($toc_format, $current_pid);
        $tokens = array();
        $tokens[] = array("location" => "outside", "token" => "%entries", "expansion" => $entries);
        $output = InSeriesInternal::do_token_replace($tokens, $output);
        return $output;
    }

    function SeriesListHtml($format = false) {
        $options = get_option('in_series');
        $options = $options['active_config'];

        $limit = 0;
        $order_by = "creation";
        $list_entry_format = $options['format_series_list_entry'];
        $list_series_format = $options['format_series_list_block'];

        if(is_array($format)) {
            $limit = isset($format['limit']) ? InSeriesInternal::make_int($format['limit']) : $limit;
            if(is_null($limit))
                { $limit = 0; }
            $order_by = $format['order_by'];
            $list_entry_format = isset($format['format_series_list_entry']) ? $format['format_series_list_entry'] : $list_entry_format;
            $list_series_format = isset($format['format_series_list_block']) ? $format['format_series_list_block'] : $list_series_format;
        }

        switch($order_by) {
            case "latest_post":
                $series = InSeries::adv_SeriesList_BySeriesRecentPost();
                break;
            case "name":
                $series = InSeries::adv_SeriesList_BySeriesName();
                break;
            case "creation": // Fall through
            default:
                $series = InSeries::adv_SeriesList_BySeriesCreation();
                break;
        }

        $entries = "";
        $count = 0;
        foreach($series as $a_series) {
            if($limit > 0 && $count >= $limit)
                { break; }
            $pid = InSeries::adv_FirstInSeries($a_series->series_id);
            $post_count = InSeries::adv_CountPostsInSeries($a_series->series_id);
            $new_entry = InSeriesInternal::FormatHtml($list_entry_format, $pid);
            $tokens = array();
            $tokens[] = array("location" => "inside", "token" => "%count", "expansion" => $post_count);
            $tokens[] = array("location" => "outside", "token" => "%count", "expansion" => $post_count);
            $new_entry = InSeriesInternal::do_token_replace($tokens, $new_entry);
            $entries .= $new_entry;
            $count++;
        }

        $output = InSeriesInternal::FormatHtml($list_series_format, InSeriesInternal::get_the_ID());
        $tokens = array();
        $tokens[] = array("location" => "outside", "token" => "%entries", "expansion" => $entries);
        $output = InSeriesInternal::do_token_replace($tokens, $output);
        return $output;
    }

    function FormatHtml($format, $pid) {
        $title = InSeriesInternal::get_the_title($pid);
        $series = InSeries::adv_CurrentSeries($pid);
        $series = stripslashes($series->series_name);
        $value_safe_title = wp_specialchars(strip_tags($title), ENT_QUOTES);
        $value_safe_series = wp_specialchars(strip_tags($series), ENT_QUOTES);
        $url = get_permalink($pid);

        $tokens = array();
        $tokens[] = array("location" => "inside", "token" => "%title", "expansion" => $value_safe_title);
        $tokens[] = array("location" => "inside", "token" => "%series", "expansion" => $value_safe_series);
        $tokens[] = array("location" => "inside", "token" => "%url", "expansion" => $url);
        $tokens[] = array("location" => "outside", "token" => "%title", "expansion" => $title);
        $tokens[] = array("location" => "outside", "token" => "%series", "expansion" => $series);
        $tokens[] = array("location" => "outside", "token" => "%url", "expansion" => $url);
        $format = InSeriesInternal::do_token_replace($tokens, $format);

        return $format;

    }

    /**************************************************************************\
     *                         Token replacement logic                        *
    \**************************************************************************/
    // None of this is thread safe, but it shouldn't matter.

    // Set this before replacing tokens (preg_replace_callback doesn't allow
    // extra parameters).
    function &get_token_replace_tokens_cache() {
        static $cache;
        return $cache;
    }

    // This is the function that wraps up all the safe replacement logic.
    function do_token_replace($tokens, $format) {
        $regex_inside = "/<[^]*[^>]*>/";
        $regex_outside = "/(^|>)[^<]*/";
        $cache_tokens =& InSeriesInternal::get_token_replace_tokens_cache();
        $locations = array("inside" => $regex_inside, "outside" => $regex_outside);

        foreach($locations as $location => $regex) {
            $cache_tokens = InSeriesInternal::split_token_subset($tokens, "location", $location);
            $format = preg_replace_callback($regex, array('InSeriesInternal', 'do_token_replace_callback'), $format);
        }

        return $format;
    }

    // for preg_replace_callback; adapt the one-parameter callback to a
    // two-parameter function call, using a static to fill in the second
    // argument.
    function do_token_replace_callback($match) {
        $tokens =& InSeriesInternal::get_token_replace_tokens_cache();
        return InSeriesInternal::do_token_replace_work($match, $tokens);
    }

    // This is what actually expands all the tokens
    function do_token_replace_work($match, $tokens) {
        $retval = $match[0];
        $patterns = array();
        $replacements = array();
        static $special = array("\\", "\$");
        static $escaped = array("\\\\", "\\\$");

        foreach($tokens as $key => $value) {
            $patterns[] = "/{$value['token']}/";
            $replacements[] = str_replace($special, $escaped, $value['expansion']);
        }
        return preg_replace($patterns, $replacements, $retval);
    }

    // Strip a set of elements from one array, returning them in another array.
    function split_token_subset(&$tokens, $a_key, $a_value) {
        $retval = array();
        reset($tokens);
        while(list($key, $value) = each($tokens)) {
            if($value[$a_key] === $a_value) {
                $retval[] = $value;
                unset($tokens[$key]);
            }
        }
        return $retval;
    }

    /**************************************************************************\
     *                     Series Manipulation Functions                      *
    \**************************************************************************/

    function AddToSeries($sid, $pid, $start) {
        global $wpdb;

        $sid = InSeriesInternal::make_int($sid);
        $pid = InSeriesInternal::make_int($pid);
        
        if(is_null($sid) || is_null($pid))
            { return; }

        $entry_table = InSeriesInternal::get_entry_table_name();

        if($start)
            { $start = "prev"; }
        else
            { $start = "next"; }

        $check = $wpdb->get_var("
                SELECT post_id FROM {$entry_table}
                WHERE series_id='{$sid}' AND
                post_id='{$pid}'");

        // Don't allow double-adds.
        if(!empty($check))
        { return; }

        $opid = $wpdb->get_var("
                SELECT post_id FROM {$entry_table}
                WHERE series_id='{$sid}' AND
                {$start}_post_id IS NULL");

        // Should never happen (adding to empty series)
        if(empty($opid))
            { return; }

        // Blast the cache
        InSeriesInternal::InvalidateCache($sid, $pid);

        // Link the old head/tail to the new head/tail
        $wpdb->query("
UPDATE {$entry_table}
  SET {$start}_post_id='{$pid}'
  WHERE series_id='{$sid}' AND
    {$start}_post_id IS NULL");

    if($start == "next")
        { $ins = "'{$opid}',NULL"; }
    else
        { $ins = "NULL,'{$opid}'"; }

        // Do the actual insert.
        // ($ins is properly quoted, and contains SQL syntax)
        $wpdb->query("
INSERT INTO {$entry_table} (series_id,post_id,prev_post_id,next_post_id)
  VALUES ('{$sid}','{$pid}',{$ins})");

    }

    function RemoveFromSeries($sid, $pid) {
        global $wpdb;

        $sid = InSeriesInternal::make_int($sid);
        $pid = InSeriesInternal::make_int($pid);
        
        if(is_null($sid) || is_null($pid))
            { return; }

        $entry_table = InSeriesInternal::get_entry_table_name();

        // Get the entries before and after the entry being removed
        $need_update = $wpdb->get_results("
SELECT post_id,prev_post_id FROM {$entry_table}
  WHERE (series_id='{$sid}' AND prev_post_id='{$pid}') OR
        (series_id='{$sid}' AND next_post_id='{$pid}')
  LIMIT 2");

        // Something's unusual...
        if(empty($need_update)) {
            $post = $wpdb->get_var("
SELECT post_id FROM {$entry_table}
  WHERE series_id='{$sid}' AND
    post_id='{$pid}'");

            // Post is not in the series
            if(empty($post))
                { return; }

            // Blast the cache
            InSeriesInternal::InvalidateCache($sid, $pid);

            // Post is the only post in the series
            $series_table = InSeriesInternal::get_series_table_name();
            $wpdb->query("
DELETE FROM {$entry_table}
  WHERE series_id='{$sid}'");
            $wpdb->query("
DELETE FROM {$series_table}
  WHERE series_id='{$sid}'");
            return;
        }

        // Should never happen.
        if(count($need_update) > 2)
            { return; }

        $ppid = NULL;
        $npid = NULL;

        // For each entry, figure out if it came before or after the entry being
        // removed.
        foreach($need_update as $entry) {
            if($entry->prev_post_id == $pid)
                { $npid = InSeriesInternal::make_int($entry->post_id); }
            else
                { $ppid = InSeriesInternal::make_int($entry->post_id); }
        }

        if(is_null($ppid))
            { $ppid = "NULL"; }
        if(is_null($npid))
            { $npid = "NULL"; }

        // If there was a prior entry, point it at the next entry (or NULL)
        if($ppid != "NULL") {
            // ($npid can be NULL)
            $wpdb->query("
UPDATE {$entry_table}
  SET next_post_id={$npid}
  WHERE series_id='{$sid}' AND
    post_id='{$ppid}'");
        }

        // If there was a next entry, point it at the prior entry (or NULL)
        if($npid != "NULL") {
            // ($ppid can be NULL)
            $wpdb->query("
UPDATE {$entry_table}
  SET prev_post_id={$ppid}
  WHERE series_id='{$sid}' AND
    post_id='{$npid}'");
        }

        // Do the actual removal now.
        $wpdb->query("
DELETE FROM {$entry_table}
  WHERE series_id='{$sid}' AND
    post_id='{$pid}'");

    }

    function InsertIntoSeries($sid, $pid, $ppid) {
        global $wpdb;

        $sid = InSeriesInternal::make_int($sid);
        $pid = InSeriesInternal::make_int($pid);
        $ppid = InSeriesInternal::make_int($ppid);

        if(is_null($sid) || is_null($pid) || is_null($ppid))
            { return; }

        $entry_table = InSeriesInternal::get_entry_table_name();

        $check = $wpdb->get_var("
SELECT post_id FROM {$entry_table}
  WHERE series_id='{$sid}' AND
    post_id='{$pid}'");

        // Don't allow double-adds.
        if(!is_null($check))
            { return; }

        // Get the series we're inserting AFTER
        $check = $wpdb->get_results("
SELECT post_id,next_post_id FROM {$entry_table}
  WHERE series_id='{$sid}' AND
    post_id='{$ppid}'");

        // The post to insert after does not exist.
        if(empty($check))
            { return; }

        // Should never happen.
        if(count($check) > 1)
            { return; }

        // Blast the cache
        InSeriesInternal::InvalidateCache($sid, $pid);

        $npid = $check[0]->next_post_id;
        if(empty($npid))
            { $npid = "NULL"; }

        // Do the forward link
        $wpdb->query("
UPDATE {$entry_table}
  SET next_post_id='{$pid}'
  WHERE series_id='{$sid}' AND
    post_id='{$ppid}'");

        // And the backward link
        // (this is a no-op if no backward link needs to be updated)
        $wpdb->query("
UPDATE {$entry_table}
  SET prev_post_id='{$pid}'
  WHERE series_id='{$sid}' AND
    prev_post_id='{$ppid}'");

        // Do the actual insertion now
        // ($npid can be NULL)
        $wpdb->query("
INSERT INTO {$entry_table} (series_id,post_id,prev_post_id,next_post_id)
  VALUES ('{$sid}','{$pid}','{$ppid}',{$npid})");

    }

    /**************************************************************************\
     *                           UI for Series Data                           *
    \**************************************************************************/

    function display_manage_posts_column($colname, $id) {
        if($colname != 'in_series')
            { return; }
        $in_series = InSeries::adv_PostToSeries($id);
        $sep = "";
        foreach($in_series as $series) {
            $series_name = stripslashes($series->series_name);
            echo "{$sep}{$series_name}";
            $sep = ", ";
        }
    }

    function add_manage_posts_column($posts_columns) {

        $posts_columns['in_series'] = __('Series', 'in_series');

        return $posts_columns;

    }

    function add_write_post_sidebar() {
        require_once('in-series-edit.php');
    }

    function process_save($epid) {
        global $wpdb;

        $series_table = InSeriesInternal::get_series_table_name();
        $entry_table = InSeriesInternal::get_entry_table_name();
        $clear_tags = false;

        $epid = InSeriesInternal::make_int($epid);
        if(is_null($epid))
            { return; }

        $post = get_post($epid);
        if(empty($post))
            { return; }

        // Look for <!--Series-*--> tags; we need to remove them if they're
        // present, even if we don't interpret them.
        $series = preg_match_all(
            "/<!--Series-(name|order):((?:[^-]|[^-]->|-[^-]|--[^>])*)-->/i",
            $post->post_content, 
            $matches, 
            PREG_SET_ORDER);
        $clear_tags = count($matches) > 0;

        // Interpret all recognized <!--Series-*--> tags if needed
        if(empty($_POST['in_series']) ||
           (empty($_POST['in_series']['add_to_series']['series']) &&
            empty($_POST['in_series']['add_to_series']['new_series']))) {
            $series_delta = array();
            $matches = array();
            foreach($matches as $match) {
                switch(strtolower($match[1])) {
                    case "name":
                        $series_delta['add_to_series']['new_series'] = trim($match[2]);
                        break;
                    case "order":
                        $series_delta['add_to_series']['position'] = trim($match[2]);
                        break;
                    default:
                        // Some unknown Series- tag.
                        break;
                }
            }
        }
        else
            { $series_delta = $_POST['in_series']; }

        // Always get rid of <!--Series-*--> tags (prevent series changes on
        // subsequent saves)
        $post->post_content = preg_replace("/<!--Series-(?:name|order):(?:(?:[^-]|[^-]->|-[^-]|--[^>])*)-->/i", "", $post->post_content);

        if(isset($series_delta['add_to_series'])) {
            if(empty($series_delta['add_to_series']['series']) &&
               !empty($series_delta['add_to_series']['new_series'])) {
                $series_name = $series_delta['add_to_series']['new_series'];
                $series_name = $wpdb->escape($series_name);
                $aid = $post->post_author;
                $exists = $wpdb->get_var("
SELECT series_id FROM {$series_table}
  WHERE series_name='{$series_name}' AND
    owner_id='{$aid}'");

                if(empty($exists)) {
                    $wpdb->query("
INSERT INTO {$series_table} (series_id,owner_id,series_name)
  VALUES (DEFAULT,'{$aid}','{$series_name}')");
                    $sid = $wpdb->get_var("
SELECT series_id FROM {$series_table}
  WHERE series_name='{$series_name}' AND
    owner_id='{$aid}'");
                    $wpdb->query("
INSERT INTO {$entry_table} (series_id,post_id,next_post_id,prev_post_id)
  VALUES ('{$sid}','{$epid}',NULL,NULL)");
                }
                else {
                    $series_delta['add_to_series']['series'] = $exists;
                    unset($series_delta['add_to_series']['new_series']);
                }

            }
            
            if(empty($series_delta['add_to_series']['new_series']) &&
               !empty($series_delta['add_to_series']['series'])) {
                $sid = InSeriesInternal::make_int($series_delta['add_to_series']['series']);
                if(!is_null($sid)) {
                    if(InSeriesInternal::can_alter_series($sid, $post->post_author)) {
                        $at_start = $series_delta['add_to_series']['position'] == "start";
                        InSeriesInternal::AddToSeries($sid, $epid, $at_start);
                    }
                }
            }
        }

        if(isset($series_delta['existing'])) {
            foreach($series_delta['existing'] as $sid => $pid) {
                // No change; don't bother doing anything.
                if(empty($pid))
                    { continue; }
                if($pid == "NULL") {
                    InSeriesInternal::RemoveFromSeries($sid, $epid);
                    InSeriesInternal::AddToSeries($sid, $epid, true);
                    continue;
                }
                if($pid == "delete") {
                    InSeriesInternal::RemoveFromSeries($sid, $epid);
                    continue;
                }
                $pid = InSeriesInternal::make_int($pid);

                // Should never happen
                if(is_null($pid))
                    { continue; }

                    InSeriesInternal::RemoveFromSeries($sid, $epid);
                    InSeriesInternal::InsertIntoSeries($sid, $epid, $pid);
            }
        }

        // Do a second save to get any <!--Series-*--> tags out of the database
        if($clear_tags) {
            wp_insert_post($post);
        }
    }

    function process_delete($pid) {
        $pid = InSeriesInternal::make_int($pid);
        if(is_null($pid))
            { return; }

        $in_series = InSeries::adv_PostToSeries($pid);
        if(empty($in_series))
            { return; }

        foreach($in_series as $series) {
            InSeriesInternal::RemoveFromSeries($series->series_id, $pid);
        }
    }

    /******************************************************************************\
     * Hooks & UI for automatic series hyperlink insertion                        *
    \******************************************************************************/

    function insert_doc_rel_links() {
        if(!is_single())
            { return; }

        $options = get_option("in_series");
        if(!$options['meta_links'])
            { return; }
        $series = InSeries::adv_CurrentSeries();
        if(is_null($series))
            { return; }
        $sid = $series->series_id;
        $npid = InSeries::adv_NextInSeries($sid);
        $ppid = InSeries::adv_PrevInSeries($sid);

        if(!is_null($npid)) {
            $next_url = get_permalink($npid);
            $next_title = htmlspecialchars(strip_tags(get_the_title($npid)));
            $output .= "<link rel='next' href='{$next_url}' title='{$next_title}' />";
        }
        if(!is_null($ppid)) {
            $prev_title = htmlspecialchars(strip_tags(get_the_title($ppid)));
            $prev_url = get_permalink($ppid);
            $output .= "<link rel='prev' href='{$prev_url}' title='{$prev_title}' />";
        }

        echo $output;
    }

    function in_series_linker_content_filter($content) {
        $options = get_option('in_series');
        $options = $options['active_config'];

        // If the post isn't in ANY series, we don't need to do anything.
        $series = InSeries::adv_PostToSeries(InSeriesInternal::get_the_ID());
        if(empty($series))
             { return $content; }

        $format = null;
        if($options['format_post_multi_switch'] && !is_single())
            { $format = $options['format_post_multi']; }
        else
            { $format = $options['format_post']; }

        $output = InSeriesInternal::FormatHtml($format, InSeriesInternal::get_the_ID());
        $tokens = array();
        $tokens[] = array("location" => "outside", "token" => "%prev", "expansion" => InSeriesInternal::PrevHtml());
        $tokens[] = array("location" => "outside", "token" => "%next", "expansion" => InSeriesInternal::NextHtml());
        $tokens[] = array("location" => "outside", "token" => "%first", "expansion" => InSeriesInternal::FirstHtml());
        $tokens[] = array("location" => "outside", "token" => "%last", "expansion" => InSeriesInternal::LastHtml());
        $tokens[] = array("location" => "outside", "token" => "%toc", "expansion" => InSeriesInternal::ToCHtml());
        $tokens[] = array("location" => "outside", "token" => "%content", "expansion" => $content);
        $output = InSeriesInternal::do_token_replace($tokens, $output);
        return $output;
    }

    // For playing nice with the_excerpt and friends
    function in_series_linker_deactivation($text) {
        remove_filter('the_content', array('InSeriesInternal','in_series_linker_content_filter'));
        $text = wp_trim_excerpt($text);
        add_filter('the_content', array('InSeriesInternal','in_series_linker_content_filter'));
        return $text;
    }


    /**************************************************************************\
     * In Series Configuration Hook                                           *
    \**************************************************************************/

    function add_admin_panels() {
        global $wp_version;

        $level = "manage_options";
        // Use a level number if running in pre-2.0
        if(version_compare("2.0",$wp_version) > 0)
            { $level = 8; }
        add_options_page(__('In Series Configuration', 'in_series'), __('Series', 'in_series'), $level, 'in-series-config', array('InSeriesInternal', 'display_admin_panels'));
    }

    function display_admin_panels() {
        require_once('in-series-config.php');
    }

    function style_admin_panels() {
        echo "
<style type='text/css'><!--
.in_series_config textarea {
    width: 80%;
}
--></style>
";
    }

}

add_filter('manage_posts_columns', array('InSeriesInternal','add_manage_posts_column'));
add_action('manage_posts_custom_column', array('InSeriesInternal','display_manage_posts_column'), 10, 2);
add_action('save_post', array('InSeriesInternal','process_save'));
add_action('delete_post', array('InSeriesInternal', 'process_delete'));
add_action('wp_head', array('InSeriesInternal','insert_doc_rel_links'));
add_filter('the_content', array('InSeriesInternal','in_series_linker_content_filter'));
add_filter('get_the_excerpt', array('InSeriesInternal','in_series_linker_deactivation'));
remove_filter('get_the_excerpt', "wp_trim_excerpt");
add_action('admin_menu', array('InSeriesInternal','add_admin_panels'));
add_action('init', array('InSeriesInternal','load_widgets'));
add_action('admin_head', array('InSeriesInternal', 'style_admin_panels'));
InSeriesInternal::compat_hooks();

?>
