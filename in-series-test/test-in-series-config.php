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

class TestInSeriesConfigurationUi extends InSeriesTestCase {

    const PrevPostTitle = "== Previous post ==";
    const PrevPostContent = "Blah blah blah";
    const PostTitle = "** This is the TITLE **";
    const PostContent = "++ This is the CONTENT ++";
    const NextPostTitle = "~~ Next post ~~";
    const NextPostContent = "Halb halb halb";
    const SeriesName = "Configuration Test Series";

    var $m_PrevPostPid;
    var $m_PostPid;
    var $m_NextPostPid;
    var $m_FieldTest;
    var $m_OriginalFieldValues;
    var $m_Initialized;

    function TestInSeriesConfigurationUi($version, $rootUrl, $username = false, $password = false) {
        parent::InSeriesTestCase(
            "Test configuration presence and functionality for In Series",
            $version,
            $rootUrl,
            $username,
            $password);
        
        $this->m_FieldCheck = array();
        $this->m_Initialized = false;
    }

    function setUp() {
        parent::setUp();
        if(!$this->m_Initialized) {
            $this->m_Initialized = true;
            $this->m_PrevPostPid = 
              $this->createAndSavePost(
                self::PrevPostTitle,
                self::PrevPostContent,
                self::SeriesName,
                "end",
                "--- New Series ---",
                "publish");
            $this->m_PostPid = 
              $this->createAndSavePost(
                self::PostTitle,
                self::PostContent,
                "",
                "end",
                self::SeriesName,
                "publish");
            $this->m_NextPostPid = 
              $this->createAndSavePost(
                self::NextPostTitle,
                self::NextPostContent,
                "",
                "end",
                self::SeriesName,
                "publish");
        }
        $this->istEditConfiguration();
        if(empty($this->m_OriginalFieldValues)) {
            $this->doFieldTest("format_post");
            $this->doFieldTest("format_next");
            $this->doFieldTest("format_prev");
            $this->doFieldTest("format_toc_block");
            $this->doFieldTest("format_toc_entry");
            $this->doFieldTest("format_toc_active_entry");
            $this->doFieldTest("format_series_list_block");
            $this->doFieldTest("format_series_list_entry");
            $this->doFieldTest("meta_links");
            $this->m_FieldTest["meta_links"] = (bool)($this->m_FieldTest["meta_links"]);
            $this->m_OriginalFieldValues = $this->m_FieldTest;
        }
    }

    function tearDown() {
        $this->istEditConfiguration();
        foreach($this->m_OriginalFieldValues as $field => $value) {
            $this->setFieldByName("in_series[$field]", $value);
        }
        $this->istSaveConfiguration();
        parent::tearDown();
    }

    function refContent() {
        if(version_compare("2.0", $this->wpGetVersion()) > 0) {
            return "\t" . self::PostContent . "\n\n";
        }
        else if(version_compare("2.1", $this->wpGetVersion()) > 0) {
            return self::PostContent . "\n\n";
        }
        else {
            return self::PostContent . "\n";
        }
    }

    function refNext() {
        return "Next in series";
    }

    function refPrev() {
        return "Previous in series";
    }

    function refTocHeader() {
        return "Table of contents for ".self::SeriesName;
    }

    function refTocEntries() {
        return self::PrevPostTitle . self::PostTitle . self::NextPostTitle;
    }

    function doFieldTest($field) {
        $this->m_FieldTest[$field] = $this->getBrowser()->getFieldByName("in_series[$field]");
        $this->m_FieldTest[$field] = html_entity_decode($this->m_FieldTest[$field], ENT_QUOTES);
        $this->assertTrue(!is_null($this->m_FieldTest[$field]), "$field did not come back as expected.");
    }

    function doTextFieldTest($field) {
        $this->doFieldTest($field);
        $this->assertTrue($this->setFieldByName("in_series[$field]", "Test $field"), "Failed to set $field.");
    }

    function doBoolFieldTest($field) {
        $this->doFieldTest($field);
        $this->m_FieldTest[$field] = (bool)($this->m_FieldTest[$field]);
        $this->assertTrue($this->setFieldByName("in_series[$field]", !($this->m_FieldTest[$field])));
    }

    function checkFieldAsNonAttribute($field, $token, $expecting, $subselect = '', $recursed = false) {
        $target = "c".mt_rand();
        $arr_expect = $expecting;
        if(!is_array($expecting)) {
            $arr_expect = array($expecting);
        }
        $this->assertTrue($this->setFieldByName("in_series[$field]", "<div class='$target'>$token</div>"));
        $this->istSaveConfiguration();
        $this->wpViewPost($this->m_PostPid);
        $this->assertElementsBySelector("div[class=\"$target\"] $subselect", $arr_expect);
        if($recursed === false) {
            $this->istEditConfiguration();
            if(!is_array($expecting)) {
                $new_expect = $expecting . $expecting;
                if(!empty($subselect)) {
                    $new_expect = array($expecting, $expecting);
                }
            }
            else {
                $new_expect = array();
                foreach($expecting as $key => $value) {
                    $new_expect[$key] = $value.$value;
                }
            }
            $this->checkFieldAsNonAttribute($field, $token . $token, $new_expect, $subselect, true);
        }
    }

    function checkFieldAsInvalidAttribute($field, $token) {
        $value = "".mt_rand();

        $this->assertTrue($this->setFieldByName("in_series[$field]", "<div class='$token'>$value</div>"));
        $this->istSaveConfiguration();
        $this->wpViewPost($this->m_PostPid);
        $this->assertElementsBySelector("div[class=\"$token\"]", array($value));
    }

    function checkFieldAsAttribute($field, $token, $expecting, $subselect = '', $count = 1) {
        $value = "".mt_rand();
        $expecting = addslashes($expecting);

        $val_arr = array();
        for($i = 0; $i < $count; $i++) {
            $val_arr[] = $value;
        }

        $this->assertTrue($this->setFieldByName("in_series[$field]", "<div class='$token'>$value</div>"));
        $this->istSaveConfiguration();
        $this->wpViewPost($this->m_PostPid);
        $this->assertElementsBySelector("div[class=\"$expecting\"] $subselect", $val_arr);
    }

    function testSetting() {
        $this->doTextFieldTest("format_post");
        $this->doTextFieldTest("format_next");
        $this->doTextFieldTest("format_prev");
        $this->doTextFieldTest("format_toc_block");
        $this->doTextFieldTest("format_toc_entry");
        $this->doTextFieldTest("format_toc_active_entry");
        $this->doTextFieldTest("format_series_list_block");
        $this->doTextFieldTest("format_series_list_entry");
        $this->doBoolFieldTest("meta_links");

        $this->istSaveConfiguration();
        foreach($this->m_FieldTest as $index => $value) {
            $current_value = $this->getBrowser()->getFieldByName("in_series[$index]");
            if(is_bool($value)) {
                $this->assertTrue($current_value != $value, "Expected '$index' to be $value, but was $current_value.");
            }
            else {
                $this->assertTrue($current_value == "Test $index", "'$current_value' != 'Test $index'.");
            }
        }
    }

    function testFormatPostSanity() {
        $target = "testFormatPostSanity_target";
        $value = "testFormatPostSanity succeeded";

        $this->assertTrue($this->setFieldByName("in_series[format_post]", "<div id='$target'>$value</div>"));
        $this->istSaveConfiguration();
        $this->wpViewPost($this->m_PostPid);
        $this->assertElementsBySelector("div#$target", array($value));
    }

    function testFormatPostContentNotAttribute() {
        $this->checkFieldAsNonAttribute("format_post", "%content", $this->refContent());
    }

    function testFormatPostContentAsAttribute() {
        $this->checkFieldAsInvalidAttribute("format_post", "%content");
    }

    function testFormatPostNextNotAttribute() {
        $this->checkFieldAsNonAttribute("format_post", "%next", $this->refNext());
    }

    function testFormatPostNextAsAttribute() {
        $this->checkFieldAsInvalidAttribute("format_post", "%next");
    }

    function testFormatPostPrevNotAttribute() {
        $this->checkFieldAsNonAttribute("format_post", "%prev", $this->refPrev());
    }

    function testFormatPostPrevAsAttribute() {
        $this->checkFieldAsInvalidAttribute("format_post", "%prev");
    }

    function testFormatPostTocNonAttribute() {
        $this->checkFieldAsNonAttribute("format_post", "%toc", $this->refTocHeader(), " > h3");
    }

    function testFormatPostTocAsAttribute() {
        $this->checkFieldAsInvalidAttribute("format_post", "%toc");
    }

    function testFormatNextSeriesNonAttribute() {
        $this->checkFieldAsNonAttribute("format_next", "%series", self::SeriesName);
    }

    function testFormatNextSeriesAsAttribute() {
        $this->checkFieldAsAttribute("format_next", "%series", self::SeriesName);
    }

    function testFormatNextTitleNonAttribute() {
        $this->checkFieldAsNonAttribute("format_next", "%title", self::NextPostTitle);
    }

    function testFormatNextTitleAsAttribute() {
        $this->checkFieldAsAttribute("format_next", "%title", self::NextPostTitle);
    }

    function testFormatNextUrlNonAttribute() {
        $this->checkFieldAsNonAttribute("format_next", "%url", $this->wpPostLink($this->m_NextPostPid));
    }

    function testFormatNextUrlAsAttribute() {
        $this->checkFieldAsAttribute("format_next", "%url", $this->wpPostLink($this->m_NextPostPid));
    }

    function testFormatPrevSeriesNonAttribute() {
        $this->checkFieldAsNonAttribute("format_prev", "%series", self::SeriesName);
    }

    function testFormatPrevSeriesAsAttribute() {
        $this->checkFieldAsAttribute("format_prev", "%series", self::SeriesName);
    }

    function testFormatPrevTitleNonAttribute() {
        $this->checkFieldAsNonAttribute("format_prev", "%title", self::PrevPostTitle);
    }

    function testFormatPrevTitleAsAttribute() {
        $this->checkFieldAsAttribute("format_prev", "%title", self::PrevPostTitle);
    }

    function testFormatPrevUrlNonAttribute() {
        $this->checkFieldAsNonAttribute("format_prev", "%url", $this->wpPostLink($this->m_PrevPostPid));
    }

    function testFormatPrevUrlAsAttribute() {
        $this->checkFieldAsAttribute("format_prev", "%url", $this->wpPostLink($this->m_PrevPostPid));
    }

    function testFormatTocEntriesNonAttribute() {
        $this->checkFieldAsNonAttribute("format_toc_block", "%entries", $this->refTocEntries());
    }

    function testFormatTocEntriesAsAttribute() {
        $this->checkFieldAsInvalidAttribute("format_toc_block", "%entries");
    }

    function testFormatTocSeriesNonAttribute() {
        $this->checkFieldAsNonAttribute("format_toc_block", "%series", self::SeriesName);
    }

    function testFormatTocSeriesAsAttribute() {
        $this->checkFieldAsAttribute("format_toc_block", "%series", self::SeriesName);
    }

    function testFormatTocTitleNonAttribute() {
        $this->checkFieldAsNonAttribute("format_toc_block", "%title", self::PostTitle);
    }

    function testFormatTocTitleAsAttribute() {
        $this->checkFieldAsAttribute("format_toc_block", "%title", self::PostTitle);
    }

    function testFormatTocEntrySeriesNonAttribute() {
        $this->checkFieldAsNonAttribute("format_toc_entry", "%series", array(self::SeriesName, self::SeriesName));
    }

    function testFormatTocEntrySeriesAsAttribute() {
        $this->checkFieldAsAttribute("format_toc_entry", "%series", self::SeriesName, '', 2);
    }

    function testFormatTocEntryTitleNonAttribute() {
        $this->checkFieldAsNonAttribute("format_toc_entry", "%title", array(self::PrevPostTitle, self::NextPostTitle));
    }

    function testFormatTocEntryTitleAsAttribute() {
        $this->checkFieldAsAttribute("format_toc_entry", "%title", self::PrevPostTitle);
        $this->istEditConfiguration();
        $this->checkFieldAsAttribute("format_toc_entry", "%title", self::NextPostTitle);
    }

    function testFormatTocEntryUrlNonAttribute() {
        $prev = $this->wpPostLink($this->m_PrevPostPid);
        $next = $this->wpPostLink($this->m_NextPostPid);

        $this->checkFieldAsNonAttribute("format_toc_entry", "%url", array($prev, $next));
    }

    function testFormatTocEntryUrlAsAttribute() {
        $prev = $this->wpPostLink($this->m_PrevPostPid);
        $next = $this->wpPostLink($this->m_NextPostPid);

        $this->checkFieldAsAttribute("format_toc_entry", "%url", $prev);
        $this->istEditConfiguration();
        $this->checkFieldAsAttribute("format_toc_entry", "%url", $next);
    }

    function testFormatTocActiveEntrySeriesNonAttribute() {
        $this->checkFieldAsNonAttribute("format_toc_active_entry", "%series", self::SeriesName);
    }

    function testFormatTocActiveEntrySeriesAsAttribute() {
        $this->checkFieldAsAttribute("format_toc_active_entry", "%series", self::SeriesName);
    }

    function testFormatTocActiveEntryTitleNonAttribute() {
        $this->checkFieldAsNonAttribute("format_toc_active_entry", "%title", self::PostTitle);
    }

    function testFormatTocActiveEntryTitleAsAttribute() {
        $this->checkFieldAsAttribute("format_toc_active_entry", "%title", self::PostTitle);
    }

    function testFormatTocActiveEntryUrlNonAttribute() {
        $this->checkFieldAsNonAttribute("format_toc_active_entry", "%url", $this->wpPostLink($this->m_PostPid));
    }

    function testFormatTocActiveEntryUrlAsAttribute() {
        $this->checkFieldAsAttribute("format_toc_active_entry", "%url", $this->wpPostLink($this->m_PostPid));
    }

    function testMetaLinksOn() {
        $prev = $this->wpPostLink($this->m_PrevPostPid);
        $curr = $this->wpPostLink($this->m_PostPid);
        $next = $this->wpPostLink($this->m_NextPostPid);

        $this->assertTrue($this->setFieldByName("in_series[meta_links]", true));
        $this->istSaveConfiguration();

        $this->wpViewPost($this->m_PrevPostPid);
        $this->assertElementsBySelector("link[rel=\"prev\"]", array());
        $this->assertElementsBySelector("link[rel=\"next\"][href=\"$curr\"]", array(""));

        $this->wpViewPost($this->m_PostPid);
        $this->assertElementsBySelector("link[rel=\"prev\"][href=\"$prev\"]", array(""));
        $this->assertElementsBySelector("link[rel=\"next\"][href=\"$next\"]", array(""));

        $this->wpViewPost($this->m_NextPostPid);
        $this->assertElementsBySelector("link[rel=\"prev\"][href=\"$curr\"]", array(""));
        $this->assertElementsBySelector("link[rel=\"next\"]", array());
    }

    function testMetaLinksOff() {
        $prev = $this->wpPostLink($this->m_PrevPostPid);
        $curr = $this->wpPostLink($this->m_PostPid);
        $next = $this->wpPostLink($this->m_NextPostPid);

        $this->assertTrue($this->setFieldByName("in_series[meta_links]", false));
        $this->istSaveConfiguration();

        $this->wpViewPost($this->m_PrevPostPid);
        $this->assertElementsBySelector("link[rel=\"prev\"]", array());
        $this->assertElementsBySelector("link[rel=\"next\"]", array());

        $this->wpViewPost($this->m_PostPid);
        $this->assertElementsBySelector("link[rel=\"prev\"]", array());
        $this->assertElementsBySelector("link[rel=\"next\"]", array());

        $this->wpViewPost($this->m_NextPostPid);
        $this->assertElementsBySelector("link[rel=\"prev\"]", array());
        $this->assertElementsBySelector("link[rel=\"next\"]", array());
    }
}

?>
