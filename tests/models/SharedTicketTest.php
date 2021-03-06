<?php
//    Pasteque server testing
//
//    Copyright (C) 2012 Scil (http://scil.coop)
//
//    This file is part of Pasteque.
//
//    Pasteque is free software: you can redistribute it and/or modify
//    it under the terms of the GNU General Public License as published by
//    the Free Software Foundation, either version 3 of the License, or
//    (at your option) any later version.
//
//    Pasteque is distributed in the hope that it will be useful,
//    but WITHOUT ANY WARRANTY; without even the implied warranty of
//    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
//    GNU General Public License for more details.
//
//    You should have received a copy of the GNU General Public License
//    along with Pasteque.  If not, see <http://www.gnu.org/licenses/>.
namespace Pasteque;

require_once(dirname(dirname(__FILE__)) . "/common_load.php");

class SharedTicketTest extends \PHPUnit_Framework_TestCase {

    public static function setUpBeforeClass() {
    }

    protected function tearDown() {
    }

    public static function tearDownAfterClass() {
    }

    public function testConstruct() {
        $tkt = new SharedTicket("Label", 1, 2, 3, 4, 0.5);
        $this->assertEquals("Label", $tkt->label);
        $this->assertEquals(1, $tkt->customerId);
        $this->assertEquals(2, $tkt->custCount);
        $this->assertEquals(3, $tkt->tariffAreaId);
        $this->assertEquals(4, $tkt->discountProfileId);
        $this->assertEquals(0.5, $tkt->discountRate);
    }

    public function testBuild() {
        $tkt = SharedTicket::__build(2, "Label", 1, 5, 12, 3, 0.5);
        $this->assertEquals(2, $tkt->id);
        $this->assertEquals("Label", $tkt->label);
        $this->assertEquals(1, $tkt->customerId);
        $this->assertEquals(5, $tkt->custCount);
        $this->assertEquals(12, $tkt->tariffAreaId);
        $this->assertEquals(3, $tkt->discountProfileId);
        $this->assertEquals(0.5, $tkt->discountRate);
    }

    public function testConstructLine() {
        $this->markTestIncomplete();
    }

    public function testBuildLine() {
        $this->markTestIncomplete();
    }

    /** @depends testConstructLine */
    public function testAddLine() {
        $this->markTestIncomplete();
    }

}