<?php
/*

Copyright 2007 Travis Snoozy (ai2097@users.sourceforge.net)
Released under the terms of the GNU GPL v2

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

*/

define("SIMPLE_TEST", "./simpletest/");
require_once(constant("SIMPLE_TEST") . "web_tester.php");
require_once(constant("SIMPLE_TEST") . "reporter.php");

#define("IST_HTTP_USERNAME", "");
#define("IST_HTTP_PASSWORD", "");
define("IST_HTTP_USERNAME", false);
define("IST_HTTP_PASSWORD", false);
define("IST_URL_COMMON_PART", "http://localhost/wp");

$setups =
    array(
        array("version" => "2.3", "postfix" => "2.3/"),
        array("version" => "2.2", "postfix" => "2.2/"),
        array("version" => "2.1", "postfix" => "2.1/"),
        array("version" => "2.0", "postfix" => "2.0/"),
        array("version" => "1.5", "postfix" => "1.5/")
    );

require_once("test-in-series-edit.php");
require_once("test-in-series-config.php");
require_once("test-in-series-update.php");


foreach($setups as $setup) {
    $test = new TestSuite("All tests (" . $setup["version"] . ")");
    $rootUrl = constant("IST_URL_COMMON_PART") . $setup["postfix"];

    $testcase =& new TestInSeriesPostEditUiPresence(
        $setup["version"],
        $rootUrl,
        constant("IST_HTTP_USERNAME"),
        constant("IST_HTTP_PASSWORD"));
    $test->addTestCase($testcase);
    $testcase =&  new TestInSeriesPostEditUiManipulation(
        $setup["version"],
        $rootUrl,
        constant("IST_HTTP_USERNAME"),
        constant("IST_HTTP_PASSWORD"));
    $test->addTestCase($testcase);
    $testcase =& new TestInSeriesConfigurationUi(
        $setup["version"],
        $rootUrl,
        constant("IST_HTTP_USERNAME"),
        constant("IST_HTTP_PASSWORD"));
    $test->addTestCase($testcase);
    $testcase =& new TestInSeriesUpdate(
        $setup["version"],
        $rootUrl,
        constant("IST_HTTP_USERNAME"),
        constant("IST_HTTP_PASSWORD"));
    $test->addTestCase($testcase);

    $test->run(new TextReporter());
}
?>
