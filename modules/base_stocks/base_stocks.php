<?php
//    Pastèque Web back office, Stocks module
//
//    Copyright (C) 2013 Scil (http://scil.coop)
//
//    This file is part of Pastèque.
//
//    Pastèque is free software: you can redistribute it and/or modify
//    it under the terms of the GNU General Public License as published by
//    the Free Software Foundation, either version 3 of the License, or
//    (at your option) any later version.
//
//    Pastèque is distributed in the hope that it will be useful,
//    but WITHOUT ANY WARRANTY; without even the implied warranty of
//    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
//    GNU General Public License for more details.
//
//    You should have received a copy of the GNU General Public License
//    along with Pastèque.  If not, see <http://www.gnu.org/licenses/>.

namespace BaseStocks;

function init() {
    global $MENU;
    $MENU->addSection("stock", "Stocks", PLUGIN_NAME);
    $MENU->registerModuleEntry("stock", PLUGIN_NAME, "menu_stock_move.png", "Moves", "moves");
    $MENU->registerModuleEntry("stock", PLUGIN_NAME, "menu_stock_inventory.png", "Inventory", "inventory");
    $MENU->registerModuleReport("stock", PLUGIN_NAME, "menu_stock_inventory.png", "AlertReport", "alert");
    $MENU->registerModuleEntry("stock", PLUGIN_NAME, "menu_stock_check.png", "Check", "check");
    \Pasteque\register_i18n(PLUGIN_NAME);
}
\Pasteque\hook("module_load", __NAMESPACE__ . "\init");

?>
