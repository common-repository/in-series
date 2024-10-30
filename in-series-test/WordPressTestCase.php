<?php
/*

Copyright 2007 Travis Snoozy (ai2097@users.sourceforge.net)
Released under the terms of the GNU GPL v2

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

*/

require_once('simpletest/extensions/dom_tester.php');

SimpleTestOptions::ignore("WordPressTestCase");
class WordPressTestCase extends DomTestCase {
    var $m_WPRootUrl;
    var $m_HttpUsername;
    var $m_HttpPassword;
    var $m_WPUsername;
    var $m_WPPassword;
    var $m_WPLoggedIn;
    var $m_WPVersion;
    
    function WordPressTestCase($label = false, $rootUrl = false, $username = false, $password = false, $version = null) {
        parent::WebTestCase($label);
        $browser = new SimpleBrowser();
        ($rootUrl !== false) ||
            trigger_error("Must pass \$rootUrl", E_USER_ERROR);
        (strrpos($rootUrl, '/') === strlen($rootUrl) - 1) ||
            trigger_error("\$rootUrl must be a directory, and end in '/' ($rootUrl).", E_USER_ERROR);
        if($browser->get($rootUrl) === false) {
            ($browser->authenticate($username, $password) !== false) ||
                trigger_error("Could not retrieve the page at \$rootUrl ($rootUrl) with $username:$password.", E_USER_ERROR);
        }
        ($browser->get($rootUrl."wp-login.php") !== false) ||
            trigger_error("\$rootURL ($rootUrl) does not appear to be the root of a WordPress installation.", E_USER_ERROR);
        $this->m_WPRootUrl = $rootUrl;
        $this->m_HttpUsername = $username;
        $this->m_HttpPassword = $password;
        $this->m_WPLoggedIn = false;
        $this->m_WPVersion = $version;
    }

    function wpLogin($username, $password) {
        if($this->get($this->m_WPRootUrl."wp-login.php") == false) {
            $this->assertFalse($this->authenticate($this->m_HttpUsername, $this->m_HttpPassword) === false);
        }
        $this->assertResponse(200);

        if($this->setFieldById("log", $username)) {
            $this->assertTrue($this->setFieldById("pwd", $password));
        }
        else {
            //WP 2.0+ went and changed the ID. Arg!
            $this->assertTrue($this->setFieldById("user_login", $username));
            $this->assertTrue($this->setFieldById("user_pass", $password));
        }

        if(!$this->clickSubmitById("submit")) {
            // WP 2.2 went and changed the ID. Arg!
            $this->assertTrue($this->clickSubmitById("wp-submit"));
        }
        $this->assertResponse(200);

        $this->m_WPLoggedIn = $this->assertText("Dashboard", "Login failed with $username:$password.");
        $this->m_WPUsername = $username;
        $this->m_WPPassword = $password;

        // FIXME: Could not get this to work -- I just enabled it in my default
        // DB so I wouldn't have to switch it on here.
        /*
        // Try to automatically switch on advanced editing for WP 1.5 sites
        $this->get($this->m_WPRootUrl."wp-admin/options-writing.php");
        if($this->setFieldByName("advanced_edit", 1)) {
            $this->assertTrue($this->clickSubmitByName("Submit"));
        }
        */
    }

    function wpLogout() {
        $username = $this->m_WPUsername;
        $password = $this->m_WPPassword;
        unset($this->m_WPUsername);
        unset($this->m_WPPassword);
        $this->assertTrue($this->get($this->m_WPRootUrl."wp-login.php?action=logout"));
        $this->assertResponse(200);
        $this->assertText("Username:");
        $this->assertText("Password:");
        $this->m_WPLoggedIn = false;
        return array($username, $password);
    }

    function wpIsLoggedIn() {
        return $this->m_WPLoggedIn;
    }

    function wpGetVersion() {
        return $this->m_WPVersion;
    }

    function wpEditNewPost() {
        $this->get($this->m_WPRootUrl."wp-admin/post-new.php");
        $status = $this->getBrowser()->getResponseCode();
        
        if($status >= 400 && $status < 600) {
            // WP 1.5 sites need to default to "advanced editing" mode for this
            // to work.
            $this->assertTrue($this->get($this->m_WPRootUrl."wp-admin/post.php"));
            $this->assertResponse(200);
        }
    }

    function wpEditPost($pid) {
        $retval = $this->get($this->m_WPRootUrl."wp-admin/post.php?action=edit&post={$pid}");
        $this->assertResponse(200);
        return $retval;
    }

    function wpPublishPost($pid, $publish = "publish") {
        $retval = true;
        $this->assertTrue($this->wpEditPost($pid));
        switch($publish) {
            case "pending":
                $retval = version_compare("2.3", $this->wpGetVersion(), "<=");
                if(!$this->assertTrue($retval, "'pending' state only valid in versions 2.3 and later ({$this->wpGetVersion()})")) {
                    break;
                }
            case "publish":
            case "draft":
            case "private":
                $retval &= $this->assertTrue($this->setFieldByName("post_status", $publish));
                break;
            default:
                $this->assertTrue(false, "Invalid post status requested ($publish)");
                $retval = false;
                break;
        }
        $this->wpSavePost();

        return $retval;
    }

    function wpDeletePost($pid) {
        if(!$this->wpEditPost($pid)) {
            return false;
        }
        $this->assertTrue($this->clickSubmitById("deletepost"));
        return $this->clickSubmit("Yes");
    }

    function wpSavePost() {
        //FIXME: For some reason 2.0 goes to a different page when we save an
        //       already-existent post (this problem crops up when attempting
        //       a wpPublishPost operation)
        $pid = $this->getBrowser()->getFieldByName("post_ID");
        $this->clickSubmitById("save");
        $this->assertResponse(200);
        if(!$pid) {
            $pid = $this->getBrowser()->getFieldByName("post_ID");
        }
        $this->assertTrue($pid, "Couldn't get a post ID after saving the post.");
        return $pid;
    }

    function wpSetPlugin($name, $set = true) {
        $toggle = $set ? "Activate" : "Deactivate";
        $html = $this->get($this->m_WPRootUrl."wp-admin/plugins.php");
        $this->assertResponse(200);
        $this->assertTrue($html);
        $present_regex = ":>$name</a>:is";
        $toggle_regex = ":$name</a>.*?href='(plugins.php[^']*)'[^>]*>(.*?)</a>:is";
        $match; 
        $this->assertEqual(preg_match($present_regex, $html), 1, "Could not find plugin; is it present?");
        if(preg_match($toggle_regex, $html, $match) && $match[2] == $toggle) {
            $link = html_entity_decode($match[1]);
            $this->assertTrue($this->get($this->m_WPRootUrl."wp-admin/$link"));
            $this->assertResponse(200);
        }
    }

    function wpViewPost($pid) {
        $this->assertTrue($this->get($this->wpPostLink($pid)));
        $this->assertResponse(200);
    }

    function wpPostLink($pid) {
        return $this->m_WPRootUrl."?p=$pid";
    }
}

?>
