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

class TestInSeriesUpdate extends InSeriesTestCase {

    function TestInSeriesUpdate($version, $rootUrl, $username = false, $password = false) {
        parent::InSeriesTestCase(
            "Test In Series data upgrading functionality",
            $version,
            $rootUrl,
            $username,
            $password);
    }

    function setUp() {
        parent::setUp();
        $this->wpSetPlugin("In Series", false);
    }

    function tearDown() {
        parent::tearDown();
    }

    function createOrderingPost($id, $series, $pos, $publish = "publish", $title = false) {
        if($title === false) {
            $title = "Ordering <em>(ID$id)&hellip;</em>";
        }
        $content = "This is the post with ID $id. The ID has nothing to do with the final order of posts.";
        $this->wpEditNewPost();
        $this->assertTrue($this->setFieldById("title", $title));
        $this->assertTrue($this->setFieldById("content", $content));
        $this->assertTrue($this->setFieldById("metakeyinput", "series_name"));
        $this->assertTrue($this->setFieldById("metavalue", $series));
        $pid = $this->wpSavePost();
        $this->assertTrue($pid);
        $this->assertTrue($this->wpEditPost($pid));
        $this->assertTrue($this->setFieldById("metakeyinput", "series_order"));
        $this->assertTrue($this->setFieldById("metavalue", $pos));
        $this->wpSavePost();
        if($publish) {
            $this->assertTrue($this->wpPublishPost($pid, $publish));
        }
        return array("pid" => $pid, "title" => $title, "content" => $content);
    }

    function createUpgradeSeries($series, $items) {
        $post_list = array();

        foreach($items as $position) {
            $post_list[$position] = $this->createOrderingPost($position, $series, $position);
        }

        $this->wpSetPlugin("In Series");
        $this->checkSeries($series, $post_list);

        foreach($post_list as $post) {
            $this->wpDeletePost($post["pid"]);
        }
    }

    function testSimpleUpgrade() {
        $this->createUpgradeSeries("Simple Upgrade Test", array(1));
    }

    function testSimpleUpgrade2() {
        $this->createUpgradeSeries("Simple Upgrade Test 2", array(1,2));
    }

    function testSimpleUpgrade3() {
        $this->createUpgradeSeries("Simple Upgrade Test 3", array(1,2,3));
    }

    function testNegativeUpgrade() {
        $this->createUpgradeSeries("Negative Upgrade Test", array(-3, -1, -2, -5));
    }

    function testNegativeAndPositiveUpgrade() {
        $this->createUpgradeSeries("Multiple Sign Upgrade Test", array(-8, 4, -2, 6));
    }

    function testMultiDigitUpgrade() {
        $this->createUpgradeSeries("Single- and Double-digit Upgrade Test", array(-10, -3, 1,15));
    }
}

?>
