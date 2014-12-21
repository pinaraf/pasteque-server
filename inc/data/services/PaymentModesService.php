<?php
//    POS-Tech API
//
//    Copyright (C) 2012 Scil (http://scil.coop)
//
//    This file is part of POS-Tech.
//
//    POS-Tech is free software: you can redistribute it and/or modify
//    it under the terms of the GNU General Public License as published by
//    the Free Software Foundation, either version 3 of the License, or
//    (at your option) any later version.
//
//    POS-Tech is distributed in the hope that it will be useful,
//    but WITHOUT ANY WARRANTY; without even the implied warranty of
//    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
//    GNU General Public License for more details.
//
//    You should have received a copy of the GNU General Public License
//    along with POS-Tech.  If not, see <http://www.gnu.org/licenses/>.

namespace Pasteque;

class PaymentModesService extends AbstractService {

    protected static $dbTable = "PAYMENTMODES";
    protected static $dbIdField = "ID";
    protected static $fieldMapping = array(
            "ID" => "id",
            "CODE" => "code",
            "NAME" => "label",
            "FLAGS" => "flags",
            "ACTIVE" => array("type" => DB::BOOL, "attr" => "active"),
            "SYSTEM" => array("type" => DB::BOOL, "attr" => "system"),
            "CS" => array("type" => DB::BOOL, "attr" => "cs"),
            "DISPORDER" => "dispOrder"
    );

    protected function build($dbMode, $pdo = null) {
        if ($pdo === null) {
            // Don't do this
            return null;
        }
        $db = DB::get();
        // Get rules
        $rules = array();
        $stmt = $pdo->prepare("SELECT * FROM PAYMENTMODES_RULES "
                . "WHERE PAYMENTMODE_ID = :id ORDER BY MIN ASC");
        $stmt->bindParam(":id", $dbMode['ID']);
        $stmt->execute();
        while ($row = $stmt->fetch()) {
            $rules[] = new PaymentModeRule($row['MIN'], $row['RULE']);
        }
        // Get values
        $values = array();
        $stmtVal = $pdo->prepare("SELECT * FROM PAYMENTMODES_VALUES "
                . "WHERE PAYMENTMODE_ID = :id ORDER BY DISPORDER ASC");
        $stmtVal->bindParam(":id", $dbMode['ID']);
        $stmtVal->execute();
        while ($row = $stmtVal->fetch()) {
            $values[] = new PaymentModeValue($row['VALUE'], $row['RESOURCE'],
                    $row['DISPORDER']);
        }
        // Build
        $mode = PaymentMode::__build($dbMode['ID'], $dbMode['CODE'],
                $dbMode['NAME'], $dbMode['FLAGS'], $dbMode['IMAGE'] !== null,
                $rules, $values, $db->readBool($dbMode['ACTIVE']),
                $db->readBool($dbMode['SYSTEM']), $db->readBool($dbMode['CS']),
                $dbMode['DISPORDER']);
        return $mode;
    }

    public function create($mode) {
        $pdo = PDOBuilder::getPdo();
        $newTransaction = !$pdo->inTransaction();
        if ($newTransaction) {
            $pdo->beginTransaction();
        }
        // Insert mode
        $id = parent::create($mode);
        if ($id === false) {
            if ($newTransaction) {
                $pdo->rollback();
                return false;
            }
        }
        // Insert rules
        $stmt = $pdo->prepare("INSERT INTO PAYMENTMODES_RULES "
                . "(PAYMENTMODE_ID, MIN, RULE) "
                . "VALUES (:pmId, :min, :rule);");
        $stmt->bindValue(":pmId", $id);
        foreach ($mode->rules as $rule) {
            $stmt->bindValue(":min", $rule->minVal);
            $stmt->bindValue(":rule", $rule->rule);
            if ($stmt->execute() === false) {
                if ($newTransaction) {
                    $pdo->rollback();
                }
                return false;
            }
        }
        // Insert values
        $stmtVal = $pdo->prepare("INSERT INTO PAYMENTMODES_VALUES "
                . "(PAYMENTMODE_ID, VALUE, RESOURCE, DISPORDER) "
                . "VALUES (:pmId, :val, :res, :dispOrder);");
        $stmtVal->bindValue(":pmId", $id);
        foreach ($mode->values as $value) {
            $stmtVal->bindValue(":val", $value->value);
            $stmtVal->bindValue(":res", $value->resource);
            $stmtVal->bindValue(":dispOrder", $value->dispOrder);
            if ($stmtVal->execute() === false) {
                if ($newTransaction) {
                    $pdo->rollback();
                }
                return false;
            }
        }
        if ($newTransaction) {
            $pdo->commit();
        }
        return $id;
    }

    public function delete($id) {
        $pdo = PDOBuilder::getPdo();
        $newTransaction = !$pdo->inTransaction();
        if ($newTransaction) {
            $pdo->beginTransaction();
        }
        // Delete rules
        $stmt = $pdo->prepare("DELETE FROM PAYMENTMODES_RULES "
                . "WHERE PAYMENTMODE_ID = :id;");
        $stmt->bindValue(":id", $id);
        if ($stmt->execute() === false) {
            if ($newTransaction) {
                $pdo->rollback();
            }
            return false;
        }
        // Delete values
        $stmtVal = $pdo->prepare("DELETE FROM PAYMENTMODES_VALUES "
                . "WHERE PAYMENTMODE_ID = :id;");
        $stmtVal->bindValue(":id", $id);
        if ($stmtVal->execute() === false) {
            if ($newTransaction) {
                $pdo->rollback();
            }
            return false;
        }
        // Delete mode
        $del = parent::delete($id);
        if ($del === false) {
            if ($newTransaction) {
                $pdo->rollback();
            }
            return false;
        } else {
            if ($newTransaction) {
                $pdo->commit();
            }
            return $del;
        }
    }

    static function getImage($id) {
        $pdo = PDOBuilder::getPDO();
        $db = DB::get();
        $stmt = $pdo->prepare("SELECT IMAGE FROM PAYMENTMODES WHERE ID = :id");
        $stmt->bindParam(":id", $id);
        if ($stmt->execute()) {
            if ($row = $stmt->fetch()) {
                return $db->readBin($row['IMAGE']);
            }
        }
        return null;
    }

}