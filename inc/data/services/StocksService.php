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

class StocksService {

    private static function buildDBLevel($dbLvl) {
        $lvl = StockLevel::__build($dbLvl['ID'], $dbLvl['PRODUCT'],
                $dbLvl['LOCATION'], $dbLvl['ATTRIBUTESETINSTANCE_ID'],
                $dbLvl['STOCKSECURITY'], $dbLvl['STOCKMAXIMUM'],
                $dbLvl['UNITS']);
        return $lvl;
    }

    static function getLevels($locationId) {
        $pdo = PDOBuilder::getPDO();
        $lvls = array();
        /* Start from LOCATIONS table to return a line with null everywhere
         * if there is no stocklevel and no line at all
         * if the location does not exist. */
        // Get security and max levels
        $sqlLvl = "SELECT STOCKLEVEL.ID, PRODUCT, STOCKSECURITY, STOCKMAXIMUM "
                . "FROM LOCATIONS "
                . "LEFT JOIN STOCKLEVEL ON STOCKLEVEL.LOCATION = LOCATIONS.ID "
                . "WHERE LOCATIONS.ID = :loc";
        $stmtLvl = $pdo->prepare($sqlLvl);
        $stmtLvl->bindParam(":loc", $locationId);
        $stmtLvl->execute();
        $locationExists = false;
        while ($row = $stmtLvl->fetch()) {
            $locationExists = true; 
            if ($row['PRODUCT'] !== null) {
                $lvls[$row['PRODUCT']] = array($row['ID'],
                        $row['STOCKSECURITY'], $row['STOCKMAXIMUM']);
            }
        }
        if (!$locationExists) {
            return null;
        }
        // Get quantities
        $qties = array();
        $sqlQty = "SELECT PRODUCT, ATTRIBUTESETINSTANCE_ID AS ATTR, UNITS "
                . "FROM STOCKCURRENT "
                . "WHERE LOCATION = :loc";
        $stmtQty = $pdo->prepare($sqlQty);
        $stmtQty->bindParam(':loc', $locationId);
        while ($row = $stmtQty->fetch()) {
            $prdId = $row['PRODUCT'];
            if (!isset($qties[$row['PRODUCT']])) {
                $qties[$prdId] = array();
            }
            $qties[$prdId][$row['ATTR']] = $row['UNITS'];
        }
        // Merge both ids
        $prdIds = array();
        foreach (array_keys($lvls) as $id) {
            $prdIds[] = $id;
        }
        foreach (array_keys($qties) as $id) {
            if (!in_array($id, $prdIds)) {
                $prdIds[] = $id;
            }
        }
        // Merge all data
        $levels = array();
        foreach ($prdIds as $id) {
            $row = array("PRODUCT" => $id, "LOCATION" => $locationId);
            if (isset($lvls[$id])) {
                $row['ID'] = $lvls[$id][0];
                $row['STOCKSECURITY'] = $lvls[$id][1];
                $row['STOCKMAXIMUM'] = $lvls[$id][2];
            } else {
                $row['ID'] = null;
                $row['STOCKSECURITY'] = null;
                $row['STOCKMAXIMUM'] = null;
            }
            if (isset($qties[$id])) {
                foreach ($qties[$id] as $attr => $qty) {
                    $row['ATTRIBUTESETINSTANCE_ID'] = $attr;
                    $row['UNITS'] = $qty;
                    $levels[] = StocksService::buildDBLevel($row);
                }
            } else {
                $row['ATTRIBUTESETINSTANCE_ID'] = null;
                $row['UNITS'] = null;
                $levels[] = StocksService::buildDBLevel($row);
            }
        }
        return $levels;
    }

    static function getLevel($productId, $locationId, $attrSetInstId = null) {
        $pdo = PDOBuilder::getPDO();
        /* Start from LOCATIONS table to return a line with null everywhere
         * if there is no stocklevel nor current stock and no line at all
         * if the location does not exist.
         * Check attribute set instance afterward to still get security and max
         * even if there is no stock for the attribute set instance. */
        $sql = "SELECT STOCKLEVEL.ID, LOCATIONS.ID AS LOCATION, "
                . "ATTRIBUTESETINSTANCE_ID, STOCKSECURITY, STOCKMAXIMUM, UNITS "
                . "FROM LOCATIONS "
                . "LEFT JOIN STOCKLEVEL ON STOCKLEVEL.LOCATION = LOCATIONS.ID "
                . "LEFT JOIN STOCKCURRENT ON "
                . "STOCKLEVEL.PRODUCT = STOCKCURRENT.PRODUCT "
                . "AND STOCKCURRENT.LOCATION = STOCKLEVEL.LOCATION "
                . "WHERE LOCATIONS.ID = :loc AND "
                . "(STOCKLEVEL.PRODUCT = :id OR STOCKCURRENT.PRODUCT = :id)";
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(":id", $productId);
        $stmt->bindParam(":loc", $locationId);
        $stmt->execute();
        while ($row = $stmt->fetch()) {
            // Explicitly product id set because two potential sources from db
            $row['PRODUCT'] = $productId;
            if ($row['ATTRIBUTESETINSTANCE_ID'] == $attrSetInstId) {
                // Found the line for the given attribute set instance id
                $lvl = StocksService::buildDBLevel($row);
                return $lvl;
            } else if ($row['ATTRIBUTESETINSTANCE_ID'] === null) {
                // Use the line without attribute for security and max if
                // there is no line with the requested attribute set instance
                $lvl = StocksService::buildDBLevel($row);
            }
        }
        if (isset($lvl)) {
            return $lvl;
        }
        return null;
    }

    /** Set security and maximum level for a product in a location.
     * Attribute set instance id is ignored.
     * To set quantities use stock moves.
     */
    static function createLevel($level) {
        $pdo = PDOBuilder::getPDO();
        $id = md5(time() . rand());
        $stmt = $pdo->prepare("INSERT INTO STOCKLEVEL (ID,PRODUCT, LOCATION, "
                . "STOCKSECURITY, STOCKMAXIMUM) VALUES (:id, :prd, :loc, :sec, "
                . ":max)");
        $stmt->bindParam(":id", $id);
        $stmt->bindParam(":prd", $level->productId);
        $stmt->bindValue(":loc", $level->locationId);
        $stmt->bindParam(":sec", $level->security);
        $stmt->bindParam(":max", $level->max);
        if ($stmt->execute()) {
            return $id;
        } else {
            return false;
        }
    }

    /** Update security and maximum levels for a product in a location.
     * Attribute set instance id is ignored.
     * To update quantities use stock moves.
     */
    static function updateLevel($level) {
        if (!isset($level->id)) {
            return false;
        }
        $pdo = PDOBuilder::getPDO();
        $stmt = $pdo->prepare("UPDATE STOCKLEVEL SET STOCKSECURITY = :sec, "
                . "STOCKMAXIMUM = :max WHERE ID = :id");
        $stmt->bindParam(":id", $level->id);
        $stmt->bindParam(":sec", $level->security);
        $stmt->bindParam(":max", $level->max);
        return $stmt->execute();
    }

    static function addMove($move) {
        $pdo = PDOBuilder::getPDO();
        $newTransaction = !$pdo->inTransaction();
        if ($newTransaction) {
            $pdo->beginTransaction();
        }
        $qty = StockMove::isIn($move->reason)
                ? $move->qty : $move->qty * -1;
        // Update STOCKCURRENT
        $stockSql = "UPDATE STOCKCURRENT SET UNITS = (UNITS + :qty) "
                . "WHERE LOCATION = :loc AND PRODUCT = :prd "
                . "AND ATTRIBUTESETINSTANCE_ID = :attrSetInstId";
        $stockStmt = $pdo->prepare($stockSql);
        $stockStmt->bindParam(":qty", $qty);
        $stockStmt->bindParam(":loc", $move->locationId);
        $stockStmt->bindParam(":prd", $move->productId);
        $stockStmt->bindParam(":attrSetInstId", $move->attrSetInstId);
        $exec = $stockStmt->execute();
        if ($exec !== false && $stockStmt->rowcount() == 0) {
            // Unable to update, insert
            $stockSql = "INSERT INTO STOCKCURRENT (LOCATION, PRODUCT, "
                    . "ATTRIBUTESETINSTANCE_ID, UNITS) "
                    . "VALUES (:loc, :prd, :attrSetInstId, :qty)";
            $stockStmt = $pdo->prepare($stockSql);
            $stockStmt->bindParam(":qty", $qty);
            $stockStmt->bindParam(":loc", $move->locationId);
            $stockStmt->bindParam(":prd", $move->productId);
            $stockStmt->bindParam(":attrSetInstId", $move->attrSetInstId);
            $stockStmt->execute();
        }
        if ($stockStmt->rowcount() == 0) {
            if ($newTransaction) {
                $pdo->rollback();
            }
            return false;
        }
        // Update STOCKDIARY
        $id = md5(time() . rand());
        $diarySql = "INSERT INTO STOCKDIARY (ID, DATENEW, REASON, LOCATION, "
                . "PRODUCT, ATTRIBUTESETINSTANCE_ID, UNITS, PRICE) "
                . "VALUES (:id, :date, :reason, :loc, :prd, :attrSetInstId, "
                . ":qty, :price)";
        $diaryStmt = $pdo->prepare($diarySql);
        $diaryStmt->bindParam(":id", $id);
        $diaryStmt->bindParam(":date", $move->date);
        $diaryStmt->bindParam(":reason", $move->reason);
        $diaryStmt->bindParam(":loc", $move->locationId);
        $diaryStmt->bindParam(":prd", $move->productId);
        $diaryStmt->bindParam(":attrSetInstId", $move->attrSetInstId);
        $diaryStmt->bindParam(":qty", $qty);
        $diaryStmt->bindValue(":price", $move->price);
        if ($diaryStmt->execute()) {
            if ($newTransaction) {
                $pdo->commit();
            }
            return true;
        } else {
            if ($newTransaction) {
                $pdo->rollback();
            }
            return false;
        }
    }
}