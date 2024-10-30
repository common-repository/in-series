<?php
/*

Copyright 2007 Travis Snoozy (ai2097@users.sourceforge.net)
Released under the terms of the GNU GPL v2

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

*/

class InSeriesWidgets {
    /**************************************************************************\
     *                              Series List                               *
    \**************************************************************************/

    function widget_series_list($args, $classname, $index) {
        $options = get_option('in_series');
        $options = $options['series_list_widget'][$index];
        $format = array();
        $format['limit'] = $options['limit'];
        $format['order_by'] = $options['order_by'];
        $list = InSeriesInternal::SeriesListHtml($format);
//        $format['format_series_list_entry'] = ;
//        $format['format_series_list_block'] = ;
        $output = $args['before_widget'];
        if(!empty($list)) {
            $output .= $args['before_title'] . $options['title'] . $args['after_title'];
            $output .= $list;
        }
        else
            { $output .= "&nbsp;"; }
        $output .= $args['after_widget'];
        echo $output;
    }

    function widget_series_list_control($index) {
        $all_options = get_option('in_series');
        if(!is_array($all_options['series_list_widget']))
            { $all_options['series_list_widget'] = array(); }
        if(!is_array($all_options['series_list_widget'][$index])) {
            $all_options['series_list_widget'][$index] = array();
            $all_options['series_list_widget'][$index]['order'] = "creation";
            $all_options['series_list_widget'][$index]['limit'] = "5";
            $all_options['series_list_widget'][$index]['title'] = __("Latest Series", "in_series");
            update_option("in_series", $all_options);
        }
        $options =& $all_options['series_list_widget'][$index];

        if(isset($_POST["series-list-submit-$index"])) {
            $options['limit'] = InSeriesInternal::make_int($_POST["series-list-limit-$index"]);
            if(is_null($options['limit']) || $options['limit'] < 0)
                { $options['limit'] = 0; }
            $options['order_by'] = $_POST["series-list-order-by-$index"];
            $options['title'] = $_POST["series-list-title-$index"];
            update_option('in_series', $all_options);
        }

        $default_limit = $options['limit'] ? $options['limit'] : 0;
        $default_title = htmlspecialchars($options['title']);
        $default_order_by_name = InSeriesInternal::make_select($options['order_by'], "name");
        $default_order_by_latest_post = InSeriesInternal::make_select($options['order_by'], "latest_post");
        $default_order_by_creation = InSeriesInternal::make_select($default_order_by_name || $default_order_by_latest_post);
        $title_label = __("Widget title: ", "in_series");
        $limit_label = __("Number of series to post: ", "in_series");
        $order_by_label = __("Series ordered by: ", "in_series");
        $order_by_name_label = __("Series name", "in_series");
        $order_by_creation_label = __("Series creation", "in_series");
        $order_by_latest_post_label = __("Most recent post", "in_series");

        $output = "
<p><label>{$title_label}<input type='text' name='series-list-title-$index' id='series-list-title-$index' value='{$default_title}'/></label></p>
<p><label>{$limit_label}<input type='text' name='series-list-limit-$index' id='series-list-limit-$index' value='{$default_limit}'/></label></p>
<p>
<label>{$order_by_label}
  <select name='series-list-order-by-$index' id='series-list-order-by-$index'>
    <option value='name' {$default_order_by_name}>{$order_by_name_label}</option>
    <option value='creation' {$default_order_by_creation}>{$order_by_creation_label}</option>
    <option value='latest_post' {$default_order_by_latest_post}>{$order_by_latest_post_label}</option>
  </select>
</label>
</p>
<input type='hidden' name='series-list-submit-$index' value='1' />
";
        echo $output;
    }

    function widget_series_list_register() {
        $id = ""; // #, if we want to do more than 1 in the future.
        $name = __("Series List{$id}");
        register_sidebar_widget($name, array("InSeriesWidgets", 'widget_series_list'), "is_series_list", 0 /* $id */);
        register_widget_control($name, array("InSeriesWidgets", 'widget_series_list_control'), 460, 350, 0 /* $id */);
    }

    /**************************************************************************\
     *                           Table of Contents                            *
    \**************************************************************************/

    function widget_series_toc($args, $classname, $index) {
        $options = get_option('in_series');
        $options = $options['series_toc_widget'][$index];
        $format = array();
//        $format['format_series_list_entry'] = ;
        $format['format_toc_block'] = "<ol>%entries</ol>";
        $toc = InSeriesInternal::ToCHtml($format);
        $output = $args['before_widget'];
        if(!empty($toc) && is_single()) {
            $output .= $args['before_title'] . $options['title'] . $args['after_title'];
            $output .= $toc;
        }
        else
            { $output .= "&nbsp;"; }
        $output .= $args['after_widget'];
        echo $output;
    }

    function widget_series_toc_control($index) {
        $all_options = get_option('in_series');
        if(!is_array($all_options['series_toc_widget']))
            { $all_options['series_toc_widget'] = array(); }
        if(!is_array($all_options['series_toc_widget'][$index])) {
            $all_options['series_toc_widget'][$index] = array();
            $all_options['series_toc_widget'][$index]['title'] = __("Table of Contents: ", "in_series");
            update_option("in_series", $all_options);
        }
        $options =& $all_options['series_toc_widget'][$index];

        if(isset($_POST["series-toc-submit-$index"])) {
            $options['title'] = $_POST["series-toc-title-$index"];
            update_option('in_series', $all_options);
        }

        $default_title = htmlspecialchars($options['title']);
        $title_label = __("Widget title: ", "in_series");

        $output = "
<p><label>{$title_label}<input type='text' name='series-toc-title-$index' id='series-toc-title-$index' value='{$default_title}'/></label></p>
<input type='hidden' name='series-toc-submit-$index' value='1' />
";
        echo $output;
    }

    function widget_series_toc_register() {
        $id = ""; // #, if we want to do more than 1 in the future.
        $name = __("Series Table of Contents{$id}", "in_series");
        register_sidebar_widget($name, array("InSeriesWidgets", 'widget_series_toc'), "is_series_toc", 0 /* $id */);
        register_widget_control($name, array("InSeriesWidgets", 'widget_series_toc_control'), 460, 350, 0 /* $id */);
    }
}

InSeriesWidgets::widget_series_list_register();
InSeriesWidgets::widget_series_toc_register();

?>
