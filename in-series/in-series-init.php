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
 * @access private
 *
 * Contains all of the plugin initialization/upgrade code.
 */
class InSeriesInit {

    /**
     * Convert the old metadata format (prior to 2.1) to the format used by 2.1
     * and 2.2.
     */
    function do_pre_2_0_metadata_convert() {
        global $wpdb;

        $wpdb->query(
"UPDATE {$wpdb->postmeta}
  SET meta_key='_series_name'
  WHERE meta_key='series_name'");

        $wpdb->query(
"UPDATE {$wpdb->postmeta}
  SET meta_key='_series_order'
  WHERE meta_key='series_order'");

    }

    /**
     * Initializes/upgrades the In Series options
     */
    function initialize_options() {
        $in_series_opts = get_option('in_series');
        $value = InSeriesInit::get_default_options();
        if(empty($in_series_opts)) {
            $desc = __('Determines how In Series stores and displays series data', 'in_series');
            add_option('in_series', $value, $desc);
        }
        else {
            InSeriesInit::do_2_2_to_3_0_option_convert();
            InSeriesInit::do_3_0_to_3_1_option_convert();
            // Refresh the options
            $in_series_opts = get_option('in_series');

            // Merge any missing (new, and with no backwards-mapping) options
            foreach($value as $key => $item) {
                if(empty($in_series_opts["$key"]))
                    { $in_series_opts["$key"] = $item; }
            }
            $in_series_opts["in_series_version"] = $value["in_series_version"];
            update_option("in_series", $in_series_opts);
        }
    }

    /**
     * Returns the default options for the current version.
     */
    function get_default_options() {
        // %title - Post title
        // %series - Series name
        // %url - A permalink url to the appropriate post
        // %prev - The previous link
        // %next - The next link
        // %toc - The table of contents
        // %content - The contents of the post
        // %entries - Recurring entries
        $value = array();
        $prev_text = __("Previous in series", "in_series");
        $next_text = __("Next in series", "in_series");
        $first_text = __("First in series", "in_series");
        $last_text = __("Last in series", "in_series");
        $toc_header = __("Table of contents for %series", "in_series");
        $value['in_series_version'] = '3.1';
        $value['adv_config_mode'] = 0; // Basic config mode

        // Basic configuration default settings
        $value['basic_config'] = array();
        $value['basic_config']['show_toc'] = true;
        $value['basic_config']['toc_visibility'] = "single"; // all, single
        $value['basic_config']['toc_position'] = "top"; // top, bottom
        $value['basic_config']['show_links'] = true;
        $value['basic_config']['link_types'] = "prev_next"; // prev_next, first_prev_next_last
        $value['basic_config']['link_visibility'] = "all"; // all, single
        $value['basic_config']['link_position'] = "bottom"; // top, bottom, both
        $value['basic_config']['toc_title_text'] = $toc_header;
        $value['basic_config']['first_link_text'] = $first_text;
        $value['basic_config']['prev_link_text'] = $prev_text;
        $value['basic_config']['next_link_text'] = $next_text;
        $value['basic_config']['last_link_text'] = $last_text;
        $value['basic_config']['show_series_post_count'] = true;
        $value['basic_config']['series_post_count_position'] = 'after'; // before, after
        $value['basic_config']['meta_links'] = true;

        // Advanced configuration default settings (matches basic defaults)
        $value['advanced_config'] = array();
        $value['advanced_config']['format_next'] = "<a href='%url' title='%title'>{$next_text}</a>";
        $value['advanced_config']['format_next_last'] = "";
        $value['advanced_config']['format_prev'] = "<a href='%url' title='%title'>{$prev_text}</a>";
        $value['advanced_config']['format_prev_first'] = "";
        $value['advanced_config']['format_first'] = "<a href='%url' title='%title'>{$first_text}</a>";
        $value['advanced_config']['format_first_active'] = "";
        $value['advanced_config']['format_last'] = "<a href='%url' title='%title'>{$last_text}</a>";
        $value['advanced_config']['format_last_active'] = "";
        $value['advanced_config']['format_toc_block'] = "<h3>{$toc_header}</h3><ol>%entries</ol>";
        $value['advanced_config']['format_toc_entry'] = "<li><a href='%url' title='%title'>%title</a></li>";
        $value['advanced_config']['format_toc_active_entry'] = "<li>%title</li>";
        $value['advanced_config']['format_post'] = "<div class='series_toc'>%toc</div> %content <div class='series_links'>%prev %next</div>";
        $value['advanced_config']['format_post_multi_switch'] = true;
        $value['advanced_config']['format_post_multi'] = "%content <div class='series_links'>%prev %next</div>";
        $value['advanced_config']['format_series_list_block'] = "<div class='series_list'><ul>%entries</ul></div>";
        $value['advanced_config']['format_series_list_entry'] = "<li><a href='%url' title='%title'>%series</a></li>";
        $value['advanced_config']['meta_links'] = true;

        // Active settings
        $value['active_config'] = array();
        $value['active_config']['format_next'] = $value['advanced_config']['format_next'];
        $value['active_config']['format_next_last'] = $value['advanced_config']['format_next_last'];
        $value['active_config']['format_prev'] = $value['advanced_config']['format_prev'];
        $value['active_config']['format_prev_first'] = $value['advanced_config']['format_prev_first'];
        $value['active_config']['format_first'] = $value['advanced_config']['format_first'];
        $value['active_config']['format_first_active'] = $value['advanced_config']['format_first_active'];
        $value['active_config']['format_last'] = $value['advanced_config']['format_last'];
        $value['active_config']['format_last_active'] = $value['advanced_config']['format_last_active'];
        $value['active_config']['format_toc_block'] = $value['advanced_config']['format_toc_block'];
        $value['active_config']['format_toc_entry'] = $value['advanced_config']['format_toc_entry'];
        $value['active_config']['format_toc_active_entry'] = $value['advanced_config']['format_toc_active_entry'];
        $value['active_config']['format_post'] = $value['advanced_config']['format_post'];
        $value['active_config']['format_post_multi_switch'] = $value['advanced_config']['format_post_multi_switch'];
        $value['active_config']['format_post_multi'] = $value['advanced_config']['format_post_multi'];
        $value['active_config']['format_series_list_block'] = $value['advanced_config']['format_series_list_block'];
        $value['active_config']['format_series_list_entry'] = $value['advanced_config']['format_series_list_entry'];
        $value['active_config']['meta_links'] = $value['basic_config']['meta_links'];

        return $value;
    }

    /**
     * Convert the 3.0 option information to the new 3.1 format.
     */
    function do_3_0_to_3_1_option_convert() {
        $options = get_option('in_series');
        // We can handle only version 3.0 data.
        if(empty($in_series_opts) || $in_series_opts['in_series_version'] != '3.0')
            { return; }
        $new_options = get_default_options(); //XXX: Needs to be the 3_0 defaults!
        $change = false;
        foreach($new_options['advanced_config'] as $key => $value) {
            if(isset($options["$key"])) {
                $change = $options["$key"] == $value;
                $new_options['advanced_config']["$key"] = $options["$key"];
            }
        }
        if($change)
            { $new_options['adv_config_mode'] = true; }
        update_option('in_series', $new_options);
    }

    /**
     * Convert the 2.2 option information to the new 3.0 format.
     */
    function do_2_2_to_3_0_option_convert() {
        $in_series_opts = get_option('in_series');
        // We can handle only version 2.2 data.
        if(empty($in_series_opts) || $in_series_opts['in_series_version'] != '2.2')
            { return; }
        //$value['in_series_version'] = '2.2';
        $in_series_opts['in_series_version'] = '3.0';
        //$value['meta_links'] = empty($series);
        //(no conversion needed)
        //$value['title_prefix'] = false;
        //(no conversion needed)
        //$value['toc'] = empty($series);
        if(!$in_series_opts['toc'])
            { $in_series_opts['format_toc_block'] = ""; }
        unset($in_series_opts['toc']);
        //$value['single_view_links'] = empty($series);
        //(no conversion)
        unset($in_series_opts['single_view_links']);
        //$value['multi_view_links'] = empty($series);
        //(no conversion)
        unset($in_series_opts['multi_view_links']);
        //$value['toc_title'] = __('Article Series - %series', 'in_series');
        $in_series_opts['format_toc_block'] = "<h3>{$in_series_opts['toc_title']}</h3><ol>%entries</ol>";
        unset($in_series_opts['toc_title']);
        //$value['toc_position'] = 'bottom';
        if($in_series_opts['toc_position'] == 'bottom') {
            $in_series_opts['format_post'] = "%content <div class='series_toc'>%toc</div> <div class='series_links'>%prev %next</div>";
        }
        unset($in_series_opts['toc_position']);
        //$value['prev_text'] = __('Previous in series', 'in_series');
        $in_series_opts['format_prev'] = "<a href='%url' title='%title'>{$in_series_opts['prev_text']}</a>";
        unset($in_series_opts['prev_text']);
        //$value['next_text'] = __('Next in series', 'in_series');
        $in_series_opts['format_next'] = "<a href='%url' title='%title'>{$in_series_opts['next_text']}</a>";
        unset($in_series_opts['next_text']);
        update_option('in_series', $in_series_opts);

    }


    function initialize_3_0_11_database() {
        global $wpdb;

        // Starting with WordPress 2.2-something, WordPress no longer uses the
        // default database charset and collation. In Series needs to create
        // tables with the same charset/collation as WordPress, otherwise things
        // will break.
        $charset_collate = '';

        if(version_compare(mysql_get_server_info(), '4.1.0', '>=')) {
            if(!empty($wpdb->charset))
                { $charset_collate = "DEFAULT CHARACTER SET $wpdb->charset"; }
            if(!empty($wpdb->collate))
                { $charset_collate .= " COLLATE $wpdb->collate"; }
        }

        $series_table_name = InSeriesInternal::get_table_name("in_series_3_0_11_series");
        $entries_table_name = InSeriesInternal::get_table_name("in_series_3_0_11_entries");
        $auth_table_name = InSeriesInternal::get_table_name("in_series_3_0_11_auth");

        if(!InSeriesInternal::has_table($series_table_name)) {
            $wpdb->query("
CREATE TABLE {$series_table_name}
(
  series_id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  owner_id BIGINT(20) UNSIGNED NOT NULL,
  series_name VARCHAR(250),
  INDEX (series_name(25))
) $charset_collate");
        }

        if(!InSeriesInternal::has_table($entries_table_name)) {
            $wpdb->query("
CREATE TABLE {$entries_table_name}
(
  series_id BIGINT(20) UNSIGNED NOT NULL,
  post_id BIGINT(20) UNSIGNED NOT NULL,
  next_post_id BIGINT(20) UNSIGNED,
  prev_post_id BIGINT(20) UNSIGNED,
  CONSTRAINT PRIMARY KEY (series_id,post_id),
  INDEX (post_id)
) $charset_collate");
        }

        if(!InSeriesInternal::has_table($auth_table_name)) {
            $wpdb->query("
CREATE TABLE {$auth_table_name}
(
  series_id BIGINT(20) UNSIGNED NOT NULL,
  author_id BIGINT(20) UNSIGNED NOT NULL,
  CONSTRAINT PRIMARY KEY (series_id,author_id)
) $charset_collate");
        }
    }

    function do_3_0_6_to_3_0_11_metadata_convert() {
        global $wpdb;

        $old_series_table_name = InSeriesInternal::get_table_name("in_series_3_0_6_series");
        $old_entries_table_name = InSeriesInternal::get_table_name("in_series_3_0_6_entries");
        $old_auth_table_name = InSeriesInternal::get_table_name("in_series_3_0_6_auth");

        $series_table_name = InSeriesInternal::get_table_name("in_series_3_0_11_series");
        $entries_table_name = InSeriesInternal::get_table_name("in_series_3_0_11_entries");
        $auth_table_name = InSeriesInternal::get_table_name("in_series_3_0_11_auth");

        if(InSeriesInternal::has_table($old_series_table_name)) {
            $wpdb->query("
INSERT INTO {$series_table_name} (series_id,owner_id,series_name)
  SELECT series_id,owner_id,series_name FROM {$old_series_table_name}");
            $wpdb->query("
DROP TABLE {$old_series_table_name}");
        }

        if(InSeriesInternal::has_table($old_entries_table_name)) {
            $wpdb->query("
INSERT INTO {$entries_table_name} (series_id,post_id,next_post_id,prev_post_id)
  SELECT series_id,post_id,next_post_id,prev_post_id FROM {$old_entries_table_name}");
            $wpdb->query("
DROP TABLE {$old_entries_table_name}");
        }

        if(InSeriesInternal::has_table($old_auth_table_name)) {
            $wpdb->query("
INSERT INTO {$auth_table_name} (series_id,author_id)
  SELECT series_id,author_id FROM {$old_auth_table_name}");
            $wpdb->query("
DROP TABLE {$old_auth_table_name}");
        }
    }

    /**
     * Create the new tables for the 3.0.6 version of the series metadata.
     */
    function initialize_3_0_6_database() {
        global $wpdb;

        $series_table_name = InSeriesInternal::get_table_name("in_series_3_0_6_series");
        $entries_table_name = InSeriesInternal::get_table_name("in_series_3_0_6_entries");
        $auth_table_name = InSeriesInternal::get_table_name("in_series_3_0_6_auth");

        if(!InSeriesInternal::has_table($series_table_name)) {
            $wpdb->query("
CREATE TABLE {$series_table_name}
(
  series_id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  owner_id BIGINT(20) UNSIGNED NOT NULL,
  series_name VARCHAR(250),
  INDEX (series_name(25))
)");
        }

        if(!InSeriesInternal::has_table($entries_table_name)) {
            $wpdb->query("
CREATE TABLE {$entries_table_name}
(
  series_id BIGINT(20) UNSIGNED NOT NULL,
  post_id BIGINT(20) UNSIGNED NOT NULL,
  next_post_id BIGINT(20) UNSIGNED,
  prev_post_id BIGINT(20) UNSIGNED,
  CONSTRAINT PRIMARY KEY (series_id,post_id),
  INDEX (post_id)
)");
        }

        if(!InSeriesInternal::has_table($auth_table_name)) {
            $wpdb->query("
CREATE TABLE {$auth_table_name}
(
  series_id BIGINT(20) UNSIGNED NOT NULL,
  author_id BIGINT(20) UNSIGNED NOT NULL,
  CONSTRAINT PRIMARY KEY (series_id,author_id)
)");
        }
    }

    /**
     * Convert the old 3.0 series metadata to the new 3.0.6 format.
     */
    function do_3_0_to_3_0_6_metadata_convert() {
        global $wpdb;

        $old_series_table_name = InSeriesInternal::get_table_name("in_series_3_0_series");
        $old_entries_table_name = InSeriesInternal::get_table_name("in_series_3_0_entries");
        $old_auth_table_name = InSeriesInternal::get_table_name("in_series_3_0_auth");

        $series_table_name = InSeriesInternal::get_table_name("in_series_3_0_6_series");
        $entries_table_name = InSeriesInternal::get_table_name("in_series_3_0_6_entries");
        $auth_table_name = InSeriesInternal::get_table_name("in_series_3_0_6_auth");

        if(InSeriesInternal::has_table($old_series_table_name)) {
            $wpdb->query("
INSERT INTO {$series_table_name} (series_id,owner_id,series_name)
  SELECT series_id,owner_id,series_name FROM {$old_series_table_name}");
            $wpdb->query("
DROP TABLE {$old_series_table_name}");
        }

        if(InSeriesInternal::has_table($old_entries_table_name)) {
            $wpdb->query("
INSERT INTO {$entries_table_name} (series_id,post_id,next_post_id,prev_post_id)
  SELECT series_id,post_id,next_post_id,prev_post_id FROM {$old_entries_table_name}");
            $wpdb->query("
DROP TABLE {$old_entries_table_name}");
        }

        if(InSeriesInternal::has_table($old_auth_table_name)) {
            $wpdb->query("
INSERT INTO {$auth_table_name} (series_id,author_id)
  SELECT series_id,author_id FROM {$old_auth_table_name}");
            $wpdb->query("
DROP TABLE {$old_auth_table_name}");
        }
    }

    /**
     * Create the new tables for the 3.0 version of the series metadata.
     */
    function initialize_3_0_database() {
        global $wpdb;

        $series_table_name = InSeriesInternal::get_table_name("in_series_3_0_series");
        $entries_table_name = InSeriesInternal::get_table_name("in_series_3_0_entries");
        $auth_table_name = InSeriesInternal::get_table_name("in_series_3_0_auth");

        if(!InSeriesInternal::has_table($series_table_name)) {
            $wpdb->query("
CREATE TABLE {$series_table_name}
(
  series_id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  owner_id BIGINT(20) UNSIGNED NOT NULL,
  series_name VARCHAR(250)
)");
        }

        if(!InSeriesInternal::has_table($entries_table_name)) {
            $wpdb->query("
CREATE TABLE {$entries_table_name}
(
  series_id BIGINT(20) UNSIGNED NOT NULL,
  post_id BIGINT(20) UNSIGNED NOT NULL,
  next_post_id BIGINT(20) UNSIGNED,
  prev_post_id BIGINT(20) UNSIGNED
)");
        }

        if(!InSeriesInternal::has_table($auth_table_name)) {
            $wpdb->query("
CREATE TABLE {$auth_table_name}
(
  series_id BIGINT(20) UNSIGNED NOT NULL,
  author_id BIGINT(20) UNSIGNED NOT NULL
)");
        }
    }

    /**
     * Convert the old 2.1/2.2 series metadata to the new 3.0 format.
     */
    function do_2_x_to_3_0_metadata_convert() {
        global $wpdb;

        $series_table = InSeriesInternal::get_series_table_name();
        $entries_table = InSeriesInternal::get_entry_table_name();

        // Get all of the defined series names.
        $all_series = $wpdb->get_col("
SELECT meta_value FROM {$wpdb->postmeta}
  WHERE meta_key='_series_name'
  GROUP BY meta_value");

        if(empty($all_series)) {
            return;
        }

        // Process each series name
        foreach($all_series as $series_name) {

            // Get all the post IDs that are tied to this series name
            $all_posts_of_series = $wpdb->get_col("
SELECT post_id FROM {$wpdb->postmeta}
  WHERE meta_key='_series_name' AND
   meta_value='{$series_name}'");

            // Convert the array of post IDs to a SQL list
            $all_posts_of_series = implode(",", $all_posts_of_series);

            // Generate a list of authors who have a series named $series_name
            $authors = $wpdb->get_col("
SELECT post_author FROM {$wpdb->posts}
  WHERE ID IN ({$all_posts_of_series})
  GROUP BY post_author");

            // Process a discrete series (author + series name)
            foreach($authors as $author) {

                // Pick out the ordered list of posts that constitute a series
                $posts = $wpdb->get_col("
SELECT post_id,meta_value FROM {$wpdb->posts} JOIN {$wpdb->postmeta}
  ON {$wpdb->posts}.ID = {$wpdb->postmeta}.post_id
  WHERE {$wpdb->postmeta}.meta_key = '_series_order' AND
    {$wpdb->posts}.post_author = '{$author}' AND
    {$wpdb->postmeta}.post_id IN ({$all_posts_of_series})
  ORDER BY CAST({$wpdb->postmeta}.meta_value AS SIGNED)");

                // Create the series
                $wpdb->query("INSERT INTO {$series_table} VALUES (DEFAULT,'{$author}','{$series_name}')");
                $series_id = $wpdb->get_var("
SELECT series_id FROM {$series_table}
  WHERE series_name='{$series_name}' AND
    owner_id='{$author}'
  ORDER BY series_id DESC
  LIMIT 1");

                // Add the entries to the series
                $prev = "NULL";
                $curr = "NULL";
                foreach($posts as $next) {
                    if($curr != "NULL")
                    {
                        // ($prev can be NULL)
                        $wpdb->query("INSERT INTO {$entries_table} VALUES ('{$series_id}','{$curr}','{$next}',{$prev})");
                    }
                    $prev = $curr;
                    $curr = $next;
                }
                // ($prev can be NULL)
                $wpdb->query("INSERT INTO {$entries_table} VALUES ('{$series_id}','{$curr}',NULL,{$prev})");
            }
        }
        // Conversion complete. Remove the old metadata.
        $wpdb->query("DELETE FROM {$wpdb->postmeta} WHERE meta_key='_series_order' OR meta_key='_series_name'");
    }
}

?>
