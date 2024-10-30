<?php
/*

Copyright 2007 Travis Snoozy (ai2097@users.sourceforge.net)
Released under the terms of the GNU GPL v2

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

*/

require_once("InSeriesTestCase.php");

define("IST_WP_USERNAME", "Quandary");
define("IST_WP_PASSWORD", "GoKXL2CWfRz87");

class TestInSeriesPostEditUi extends InSeriesTestCase {
    function TestInSeriesPostEditUi($label, $version, $rootUrl, $username = false, $password = false) {
        parent::InSeriesTestCase(
            $label,
            $version,
            $rootUrl,
            $username,
            $password);
    }

    // Look for the dropdown, radio button set, and textbox that lets the user
    // add a new post to an existing series, or add a post to a new series.
    function checkPresenceOfAdd() {
        $this->assertFieldById("in_series__add_to_series__series", "");
        $this->assertFieldByName("in_series[add_to_series][position]", "end");
        $this->assertFieldById("in_series__add_to_series__new_series", "");
    }

    // Look for the label and dropdown that indicate the post's membership and
    // position in a given series.
    function checkPresenceOfAlter($series, $value = false) {
        if($value) {
            $this->assertField($series, $value);
        }
        else {
            $this->assertField("$series");
        }
    }

    function reorderPost($series, $pid, $goes_after) {
        $this->assertTrue($this->wpEditPost($pid));
        $this->assertTrue($this->setField($series, $goes_after), "Could not set field '$series' to '$goes_after' for post $pid");
        $this->assertTrue($this->clickSubmitById("save"));
        $this->assertResponse(200);
    }
}

class TestInSeriesPostEditUiPresence extends TestInSeriesPostEditUi {

    function TestInSeriesPostEditUiPresence($version, $rootUrl, $username = false, $password = false) {
        parent::TestInSeriesPostEditUi(
            "Test for basic presence of the post writing/editing UI for In Series",
            $version,
            $rootUrl,
            $username,
            $password); 
    }

    // New, unsaved posts should have the add block, but should NOT be part of
    // any post (and hence, should NOT have any alter blocks).
    function testPresenceOfAddInNewPost() {
        $this->wpEditNewPost();
        $this->checkPresenceOfAdd();
        // TODO: verify absence of alter blocks
    }

    // Existing posts that are not part of a series should have the add block,
    // and should NOT have any alter blocks.
    function testPresenceInExistingPost() {
        $title = "testPresenceInExistingPost";
        $content = "Checking for presence of In Series editing controls in existing posts.";
        $pid = $this->createAndSavePost($title, $content);
        $this->checkPresenceOfAdd();
        // TODO: verify absence of alter blocks
        $this->wpDeletePost($pid);
    }

    // For In Series 3.0, existing posts that are part of a series should NOT
    // have the add block, and should have one (and only one) alter block
    // corresponding to the series that the post is in.
    function testCreateSeriesInNewPost() {
        $title = "testCreateSeriesInNewPost";
        $content = "Verify that a new series can be created from a new post on the first save.";
        $series = "$title Series";
        $pid = $this->createAndSavePost($title, $content, $series);
        // FIXME: should go by label, not value.
        $this->checkPresenceOfAlter($series, ""); // "--- First ---" -> ""
        // TODO: verify absence of add block
        $this->wpDeletePost($pid);
    }
}

class TestInSeriesPostEditUiManipulation extends TestInSeriesPostEditUi {
    function TestInSeriesPostEditUiManipulation($version, $rootUrl, $username = false, $password = false) {
        parent::TestInSeriesPostEditUi(
            "Test the series manipulation functionality of the In Series post editing UI",
            $version,
            $rootUrl,
            $username,
            $password);
    }

    function createOrderingPost($id, $series, $pos, $new = false, $publish = "publish", $title = false) {
        $newseries = $series;
        $oldseries = "--- New Series ---";
        if(!$new) {
            $newseries = "";
            $oldseries = $series;
        }
        if($title === false) {
            $title = "Ordering <em>(ID$id)&hellip;</em>";
        }
        $content = "This is the post with ID $id. The ID has nothing to do with the final order of posts.";
        $pid = $this->createAndSavePost($title, $content, $newseries, $pos, $oldseries, $publish);
        return array("pid" => $pid, "title" => $title, "content" => $content);
    }

    function deleteSeries($reference) {
        foreach($reference as $post) {
            $this->wpDeletePost($post["pid"]);
        }
    }

    function doArrayReorder(&$post_list, $pid, $put_after) {
        ksort($post_list);
        $shift_up = false;
        $found_after = $put_after == "NULL";
        $post_index = null;
        $after_after = null;
        foreach($post_list as $index => $post) {
            if($post["pid"] == $pid) {
                $post_index = $index;
            }
            if($found_after && is_null($after_after)) {
                $after_after = $index;
            }
            if($post["pid"] == $put_after) {
                $found_after = true;
            }
        }

        // Bail out if we can't find what we need.
        if(is_null($post_index)) {
            return;
        }

        $shift_up = is_null($after_after) || $post_index < $after_after;

        $temp = null;
        if($shift_up) {
            $temp = $post_list[$post_index];
            $prev_index = null;
            foreach($post_list as $index => $post) {
                if(!is_null($prev_index)) {
                    $post_list[$prev_index] = $post;
                }
                if($index == $post_index || !is_null($prev_index)) {
                    $prev_index = $index;
                }
                if($post["pid"] == $put_after) {
                    break;
                }
            }
            $post_list[$prev_index] = $temp;
        }
        else {
            $temp = $post_list[$after_after];
            $target = $post_list[$post_index];
            $prev_index = null;
            foreach($post_list as $index => $post) {
                if(!is_null($prev_index)) {
                    $temp2 = $post_list[$index];
                    $post_list[$index] = $temp;
                    $temp = $temp2;
                    $prev_index = $index;
                }
                if($index == $after_after) {
                    $prev_index = $index;
                }
                if($index == $post_index) {
                    break;
                }
            }
            $post_list[$after_after] = $target;
        }

    }

    function doSeriesReorderWithArray($series, &$post_list, $pid, $put_after, &$full_list = null) {
        if(!is_null($full_list)) {
            $post_list_copy = $post_list;
            $this->doArrayReorder($full_list, $pid, $put_after);
            $post_list = array();
            foreach($full_list as $index => $value) {
                foreach($post_list_copy as $match_index => $match_value) {
                    if($match_value["pid"] == $value["pid"]) {
                        $post_list[$index] = $value;
                        unset($post_list_copy[$match_index]);
                        break;
                    }
                }
            }
        }
        else {
            $this->doArrayReorder($post_list, $pid, $put_after);
        }
        $this->reorderPost($series, $pid, $put_after);
        $this->checkSeries($series, $post_list, $full_list);
    }

    function doSeriesRemoveWithArray($series, &$post_list, $pid, &$full_list = null) {
        $target_index = null;
        $ftarget_index = null;
        foreach($post_list as $index => $value) {
            if($value["pid"] == $pid) {
                $target_index = $index;
                break;
            }
        }
        if(!is_null($full_list)) {
            foreach($full_list as $index => $value) {
                if($value["pid"] == $pid) {
                    $ftarget_index = $index;
                    break;
                }
            }
        }
        $this->reorderPost($series, $pid, "delete");
        unset($post_list[$target_index]);
        unset($full_list[$ftarget_index]);
        $this->checkSeries($series, $post_list, $full_list);
        $this->wpDeletePost($pid);
    }

    function doDeletePostWithArray($series, &$post_list, $pid, &$full_list = null) {
        foreach($post_list as $index => $value) {
            if($value["pid"] == $pid) {
                unset($post_list[$index]);
                break;
            }
        }
        if(!is_null($full_list)) {
            foreach($full_list as $index => $value) {
                if($value["pid"] == $pid) {
                    unset($full_list[$index]);
                    break;
                }
            }
        }
        $this->wpDeletePost($pid);
        $this->checkSeries($series, $post_list, $full_list);
    }

    function doPublishPostWithArray($series, &$post_list, &$full_list, $pid, $publish = "publish") {
        $target = null;
        foreach($full_list as $index => $post) {
            if($post["pid"] == $pid) {
                $target = $index;
                break;
            }
        }

        switch($publish) {
            case "publish":
                $post_list[$target] = $full_list[$target];
                break;
            case "draft":
            case "private":
                if(isset($post_list[$target])) {
                    unset($post_list[$target]);
                }
                break;
            default:
                // We don't know how to handle this!
                return false;
                break;
        }

        $this->wpPublishPost($pid, $publish);
        $this->checkSeries($series, $post_list, $full_list);
    }

    function checkUnpublishedManipulation($status) {
        $post_list = array();
        $full_list = array();
        $series = "Interspersed $status Series";

        // Series of drafts only
        // Adding to the beginning/end of a series
        $full_list[20] = $this->createOrderingPost(20, $series, "end", true, $status);
        $this->checkSeries($series, $post_list, $full_list);
        $full_list[15] = $this->createOrderingPost(15, $series, "start", false, $status);
        $this->checkSeries($series, $post_list, $full_list);
        $full_list[25] = $this->createOrderingPost(25, $series, "end", false, $status);
        $this->checkSeries($series, $post_list, $full_list);
        $full_list[10] = $this->createOrderingPost(10, $series, "start", false, $status);
        $this->checkSeries($series, $post_list, $full_list);
        $full_list[30] = $this->createOrderingPost(30, $series, "end", false, $status);
        $this->checkSeries($series, $post_list, $full_list);

        // Publish a draft between two other drafts
        $this->doPublishPostWithArray($series, $post_list, $full_list, $full_list[20]["pid"]);

        // Publish a draft before a draft
        $this->doPublishPostWithArray($series, $post_list, $full_list, $full_list[25]["pid"]);

        // Publish a draft after a draft
        $this->doPublishPostWithArray($series, $post_list, $full_list, $full_list[15]["pid"]);

        // Publish a draft at the start of a series
        $this->doPublishPostWithArray($series, $post_list, $full_list, $full_list[10]["pid"]);

        // Publish a draft at the end of a series
        $this->doPublishPostWithArray($series, $post_list, $full_list, $full_list[30]["pid"]);

        // Insert a draft at the start of a series (only draft)
        $full_list[5] = $this->createOrderingPost(5, $series, "start", false, $status);
        $this->checkSeries($series, $post_list, $full_list);

        // Put a draft in the middle of a series (only draft)
        $this->doSeriesReorderWithArray($series, $post_list, $full_list[5]["pid"], $full_list[15]["pid"], $full_list);
        $this->doPublishPostWithArray($series, $post_list, $full_list, $full_list[15]["pid"]);
        $this->doSeriesRemoveWithArray($series, $post_list, $full_list[15]["pid"], $full_list);

        // Insert a draft at the end of a series (only draft)
        $full_list[35] = $this->createOrderingPost(35, $series, "end", false, $status);
        $this->checkSeries($series, $post_list, $full_list);
        $this->doSeriesRemoveWithArray($series, $post_list, $full_list[35]["pid"], $full_list);

        $full_list[2] = $this->createOrderingPost(2, $series, "start", false, $status);
        $this->checkSeries($series, $post_list, $full_list);
        $full_list[35] = $this->createOrderingPost(35, $series, "end", false, $status);
        $this->checkSeries($series, $post_list, $full_list);
        $full_list[26] = $this->createOrderingPost(26, $series, "end", false, $status);
        $this->doSeriesReorderWithArray($series, $post_list, $full_list[26]["pid"], $full_list[25]["pid"], $full_list);
        $this->checkSeries($series, $post_list, $full_list);

        // Remove a draft in the middle of a series
        $this->doSeriesRemoveWithArray($series, $post_list, $full_list[26]["pid"], $full_list);

        // Remove a draft at the start of a series
        $this->doSeriesRemoveWithArray($series, $post_list, $full_list[2]["pid"], $full_list);

        // Remove a draft at the end of a series
        $this->doSeriesRemoveWithArray($series, $post_list, $full_list[35]["pid"], $full_list);

        $this->doSeriesRemoveWithArray($series, $post_list, $full_list[25]["pid"], $full_list);
        $this->doSeriesRemoveWithArray($series, $post_list, $full_list[5]["pid"], $full_list);
        $this->doSeriesRemoveWithArray($series, $post_list, $full_list[30]["pid"], $full_list);
        $this->doSeriesRemoveWithArray($series, $post_list, $full_list[10]["pid"], $full_list);
        $this->doSeriesRemoveWithArray($series, $post_list, $full_list[20]["pid"], $full_list);
    }

    function testBasicManipulation() {
        $post_list = array();
        $series = "Manipulation Series";

        // Adding to the beginning/end of a series
        $post_list[5] = $this->createOrderingPost(5, $series, "end", true);
        $this->checkSeries($series, $post_list);
        $post_list[4] = $this->createOrderingPost(4, $series, "start");
        $this->checkSeries($series, $post_list);
        $post_list[6] = $this->createOrderingPost(6, $series, "end");
        $this->checkSeries($series, $post_list);
        $post_list[3] = $this->createOrderingPost(3, $series, "start");
        $this->checkSeries($series, $post_list);
        $post_list[7] = $this->createOrderingPost(7, $series, "end");
        $this->checkSeries($series, $post_list);

        // Reorder from the end to the middle
        $this->doSeriesReorderWithArray($series, $post_list, $post_list[7]["pid"], $post_list[4]["pid"]);

        // Reorder from the end to the start
        $this->doSeriesReorderWithArray($series, $post_list, $post_list[7]["pid"], "NULL");

        // Reorder from the middle to the start
        $this->doSeriesReorderWithArray($series, $post_list, $post_list[5]["pid"], "NULL");

        // Reorder from the middle to the end
        $this->doSeriesReorderWithArray($series, $post_list, $post_list[5]["pid"], $post_list[7]["pid"]);

        // Reorder from the middle to the middle
        $this->doSeriesReorderWithArray($series, $post_list, $post_list[4]["pid"], $post_list[6]["pid"]);

        // Reorder from the start to the middle
        $this->doSeriesReorderWithArray($series, $post_list, $post_list[3]["pid"], $post_list[5]["pid"]);

        // Reorder from the start to the end
        $this->doSeriesReorderWithArray($series, $post_list, $post_list[3]["pid"], $post_list[7]["pid"]);

        // Remove a post from the middle of the series
        $this->doSeriesRemoveWithArray($series, $post_list, $post_list[5]["pid"]);

        // Remove the post at the start of the series
        $this->doSeriesRemoveWithArray($series, $post_list, $post_list[3]["pid"]);

        // Remove the post at the end of the series
        $this->doSeriesRemoveWithArray($series, $post_list, $post_list[7]["pid"]);

        // Remove all the posts from the series
        $this->doSeriesRemoveWithArray($series, $post_list, $post_list[4]["pid"]);
        $this->doSeriesRemoveWithArray($series, $post_list, $post_list[6]["pid"]);
    }

    function testDeletion() {
        $post_list = array();
        $series = "Deletion Series";

        $post_list[1] = $this->createOrderingPost(1, $series, "end", true);
        $this->checkSeries($series, $post_list);
        $post_list[2] = $this->createOrderingPost(2, $series, "end");
        $this->checkSeries($series, $post_list);
        $post_list[3] = $this->createOrderingPost(3, $series, "end");
        $this->checkSeries($series, $post_list);
        $post_list[4] = $this->createOrderingPost(4, $series, "end");
        $this->checkSeries($series, $post_list);
        $post_list[5] = $this->createOrderingPost(5, $series, "end");
        $this->checkSeries($series, $post_list);
        $post_list[6] = $this->createOrderingPost(6, $series, "end");
        $this->checkSeries($series, $post_list);
        $post_list[7] = $this->createOrderingPost(7, $series, "end");
        $this->checkSeries($series, $post_list);
        $post_list[8] = $this->createOrderingPost(8, $series, "end");
        $this->checkSeries($series, $post_list);
        $post_list[9] = $this->createOrderingPost(9, $series, "end");
        $this->checkSeries($series, $post_list);

        // Delete a post from the middle
        $this->doDeletePostWithArray($series, $post_list, $post_list[5]["pid"]);

        // Delete a post from the start
        $this->doDeletePostWithArray($series, $post_list, $post_list[1]["pid"]);

        // Delete a post from the end
        $this->doDeletePostWithArray($series, $post_list, $post_list[9]["pid"]);

        // Delete a post right before the start
        $this->doDeletePostWithArray($series, $post_list, $post_list[3]["pid"]);

        // Delete a post right before the end
        $this->doDeletePostWithArray($series, $post_list, $post_list[7]["pid"]);

        // Delete the remaining posts
        $this->deleteSeries($post_list);
    }

    function testDraftManipulation() {
        $this->checkUnpublishedManipulation("draft");
    }

    function testPrivateManipulation() {
        $this->istSetLoggedOutChecking(true);
        $this->checkUnpublishedManipulation("private");
        $this->istSetLoggedOutChecking(false);
    }

    function testPendingManipulation() {
        // Applies only to 2.3+
        if(version_compare("2.3", $this->m_WPVersion, "<=")) {
            $this->checkUnpublishedManipulation("pending");
        }
    }

    function testTexturedTitles() {
        $series = '"They\'re Textured," the Series...';
        $posts[5] = $this->createOrderingPost(5, $series, "end", true, "publish", '"That\'s all, folks..."');
        $posts[5]["title"] = "&#8220;That&#8217;s all, folks&#8230;&#8221;";
        $posts[6] = $this->createOrderingPost(6, $series, "end", false, "publish", 'What -- No Cheesecake??');
        $posts[6]["title"] = "What &#8212; No Cheesecake??";
        $posts[7] = $this->createOrderingPost(7, $series, "end", false, "publish", 'I\'m all out of textures!');
        $posts[7]["title"] = "I&#8217;m all out of textures!";
        $this->checkSeries($series, $posts);
        $this->deleteSeries($posts);
    }
}

?>
