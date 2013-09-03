<?php
//    Pastèque Web back office, Restaurant module
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

namespace BaseRestaurant;

function init() {
    global $MENU;
    $MENU->addSection("restaurant", "Restaurant", PLUGIN_NAME);
    //TODO: changes image button
    $MENU->registerModuleEntry("restaurant", PLUGIN_NAME, "menu_customers.png", "Place sales", "place_sales_report");
    $MENU->registerModuleEntry("restaurant", PLUGIN_NAME, "menu_map.png", "Floors", "floors");
    \Pasteque\register_i18n(PLUGIN_NAME);
}
\Pasteque\hook("module_load", __NAMESPACE__ . "\init");
?>
