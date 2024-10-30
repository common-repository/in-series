<?php
/*

Copyright 2007 Travis Snoozy (ai2097@users.sourceforge.net)
Released under the terms of the GNU GPL v2

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

*/

require_once("WordPressTestCase.php");

class InSeriesTestCase extends WordPressTestCase {
    function InSeriesTestCase($label, $version, $rootUrl, $username = false, $password = false) {
        parent::WordPressTestCase(
            "$label ($version -- $rootUrl)",
            $rootUrl,
            $username,
            $password,
            $version);
        $this->istSetLoggedOutChecking(false);
    }

    function setUp() {
        $this->wpLogin(constant("IST_WP_USERNAME"), constant("IST_WP_PASSWORD"));
        $this->wpSetPlugin("In Series");
    }

    function tearDown() {
        $this->wpLogout();
    }

    function createAndSavePost($title, $content, $newseries = "", $pos = "end", $series = "--- New Series ---", $publish = false) {
        $this->wpEditNewPost();
        $this->assertTrue($this->setFieldById("title", $title));
        $this->assertTrue($this->setFieldById("content", $content));
        $this->assertTrue($this->setFieldById("in_series__add_to_series__series", $series));
        $this->assertTrue($this->setFieldByName("in_series[add_to_series][position]", $pos));
        $this->assertTrue($this->setFieldById("in_series__add_to_series__new_series", $newseries));
        $pid = $this->wpSavePost();
        $this->assertTrue($pid);
        $this->assertTrue($this->wpEditPost($pid));
        if($publish) {
            $this->assertTrue($this->wpPublishPost($pid, $publish));
        }
        return $pid;
    }

    // Verify that each post in the series has a complete ToC, and (when
    // appropriate) previous/next links. Furthermore, verify that all links in
    // the ToC (and the previous/next links, if present) point to the proper
    // place (with the entry for the current post NOT having a link).
    function checkSeriesSinglePosts($series, &$reference) {
        ksort($reference);
        reset($reference);
        $toc_check = $reference;
        $prev = null;
        $curr = each($reference);
        $next = each($reference);
        $index = 1;
        $title_li_buildup = "";
        while($curr !== false) {
            $value = $curr["value"];
            $this->wpViewPost($value["pid"]);
            $sane_title = html_entity_decode(strip_tags($value['title']), ENT_QUOTES, "UTF-8");
            $this->assertElementsBySelector("ol > li:first-child$title_li_buildup", array($sane_title));
            $this->assertElementsBySelector("ol > li:first-child$title_li_buildup a", array());
            $this->assertText($value["content"]);
            $toc_li_buildup = "";
            $subindex = 1;
            foreach($toc_check as $toc_entry) {
                if($value["pid"] != $toc_entry["pid"]) {
                    $link = $this->wpPostLink($toc_entry["pid"]);
                    // FIXME: a:first-child[href="$link"] not used, because of a
                    // simpletest bug. Fix it here when it's fixed in
                    // simpletest.
                    $sane_entry_title = html_entity_decode(strip_tags($toc_entry['title']), ENT_QUOTES, "UTF-8");
                    $slashed_entry_title = addslashes(addslashes($sane_entry_title));
                    $this->assertElementsBySelector("ol > li:first-child$toc_li_buildup > a[href=\"$link\"][title=\"$slashed_entry_title\"]", array($sane_entry_title));
                }
                $toc_li_buildup .= " + li";
                $subindex++;
            }

            if($prev) {
                $prev_link = $this->wpPostLink($prev["value"]["pid"]);
                $this->assertLink("Previous in series", $prev_link);
                $this->assertElementsBySelector("link[rel=\"prev\"][href=\"$prev_link\"]", array(""));
            }
            else {
                $this->assertNoLink("Previous in series");
                $this->assertElementsBySelector("link[rel=\"prev\"]", array());
            }

            if($next !== false) {
                $next_link = $this->wpPostLink($next["value"]["pid"]);
                $this->assertLink("Next in series", $next_link);
                $this->assertElementsBySelector("link[rel=\"next\"][href=\"$next_link\"]", array(""));
            }
            else {
                $this->assertNoLink("Next in series");
                $this->assertElementsBySelector("link[rel=\"next\"]", array());
            }
            $prev = $curr;
            $curr = $next;
            $next = each($reference);
            $title_li_buildup .= " + li";
            $index++;
        }
    }

    // Verify that a series exists, by checking for its availability in the
    // series selector list. Also, go to the edit screen for each post in the
    // series, and verify the list of posts is ordered properly (and does NOT
    // contain the current post).
    //
    // TODO: Verify that the selected post in the drop-down is correct.
    function checkSeriesAvailability($series, $post_list) {
        ksort($post_list);
        reset($post_list);
        $this->wpEditNewPost();
        $exists = $this->setFieldById("in_series__add_to_series__series", $series);
        if(!empty($post_list)) {
            $this->assertTrue($exists);
        }
        else {
            $this->assertFalse($exists);
        }
        foreach($post_list as $post_data) {
            $this->wpEditPost($post_data["pid"]);
            $post_order = array();
            $post_order[] = "--- First ---";
            foreach($post_list as $subpost_data) {
                if($subpost_data["pid"] != $post_data["pid"]) {
                    $sane_title = strip_tags($subpost_data["title"]);
                    $this->assertTrue($this->setField($series, $sane_title));
                    $post_order[] = html_entity_decode($sane_title, ENT_QUOTES, "UTF-8");
                }
                else {
                    $this->assertFalse($this->setField($series, $subpost_data["title"]));
                }
            }
            $post_order[] = "--- Remove ---";
            if(!$this->assertElementsBySelector("fieldset#in_series_fieldset > div > label > select > option", $post_order)) {
                print_r($post_order);
            }
        }
    }

    function checkSeries($series, $post_list, $full_list = null) {
        if(is_null($full_list)) {
            $full_list =& $post_list;
        }

        $username = "";
        $password = "";

        // Log out if we're doing logged-out checks
        if($this->istIsLoggedOutChecking() && $this->wpIsLoggedIn()) {
            list($username, $password) = $this->wpLogout();
        }
        // If we're doing logged-in tests, check series availability
        if($this->wpIsLoggedIn()) {
            $this->checkSeriesAvailability($series, $full_list);
        }

        $this->checkSeriesSinglePosts($series, $post_list);

        // Log back in if needed
        if(!empty($username) && $this->istIsLoggedOutChecking()) {
            $this->wpLogin($username, $password);
        }
    }

    function istSetLoggedOutChecking($state) {
        if($state) {
            $this->m_ISTCheckLoggedOut = true;
        }
        else {
            $this->m_ISTCheckLoggedOut = false;
        }
    }

    function istIsLoggedOutChecking() {
        return $this->m_ISTCheckLoggedOut;
    }

    function istEditConfiguration() {
        $this->assertTrue($this->get($this->m_WPRootUrl."wp-admin/options-general.php?page=in-series-config"));
        $this->assertResponse(200);
    }

    function istSaveConfiguration() {
        $this->clickSubmitByName("in_series[submit]");
        $this->assertResponse(200);
    }
}

?>
