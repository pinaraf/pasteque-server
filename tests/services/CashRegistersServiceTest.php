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

class CashRegistersServiceTest extends \PHPUnit_Framework_TestCase {

    public static function setUpBeforeClass() {
        // Install empty database
        Installer::install(null);
    }

    protected function tearDown() {
        // Restore database in its empty state
        $pdo = PDOBuilder::getPDO();
        if ($pdo->exec("DELETE FROM CASHREGISTERS") === false) {
            echo("[ERROR] Unable to restore db\n");
        }
    }

    public static function tearDownAfterClass() {
        // Erase database
        dropDatabase();
    }

    public function testCreate() {
        $type = get_db_type(get_user_id());
        $srv = new CashRegistersService();
        $cashReg = new CashRegister("CashReg", "0", 1);
        $id = $srv->create($cashReg);
        $this->assertNotEquals(false, $id, "Insertion failed");
        $pdo = PDOBuilder::getPDO();
        $sql = "SELECT * FROM CASHREGISTERS WHERE NAME = :name";
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(":name", $cashReg->label);
        $this->assertNotEquals($stmt->execute(), false, "Query failed");
        $row = $stmt->fetch();
        $this->assertEquals($id, $row['ID'], "Id mismatch");
        $this->assertEquals($cashReg->label, $row['NAME'], "Label mismatch");
        $this->assertEquals($cashReg->locationId, $row['LOCATION_ID'],
                "Location id mismatch");
        $this->assertEquals($cashReg->posId, $row['POS_ID'], "POS id mismatch");
    }

    /** @depends testCreate */
    public function testGet() {
        $srv = new CashRegistersService();
        $cashReg = new CashRegister("CashReg", "0", 1);
        $id = $srv->create($cashReg);
        $read = $srv->get($id);
        $this->assertEquals($id, $read->id, "Id mismatch");
        $this->assertEquals($cashReg->label, $read->label, "Label mismatch");
        $this->assertEquals($cashReg->locationId, $read->locationId,
                "Location id mismatch");
        $this->assertEquals($cashReg->posId, $read->posId, "POS id mismatch");
    }

    public function testGetInexistent() {
        $srv = new CashesService();
        $cashReg = $srv->get("none");
        $this->assertNull($cashReg, "Inexistent cash register found");
    }

    /** @depends testCreate */
    public function testUpdate() {
        $this->markTestIncomplete();
    }

    /** @depends testCreate */
    public function testDelete() {
        $this->markTestIncomplete();
    }
}

?>