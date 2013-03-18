<?php
//    Pastèque Web back office
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

// Index is the entry point for everything.
namespace Pasteque;

const ABSPATH = __DIR__; // Base path. Also to check if a call
                         // originates from index.php
// Load
require_once(ABSPATH . "/inc/load.php");

function index_run() {
    tpl_open();
    url_content();
    tpl_close();
}

// Check user authentication
if (!is_user_logged_in()) {
    show_login_page();
} else {
    require_once(ABSPATH . "/inc/load_logged.php");
    // Check install
    require_once(ABSPATH . "/install.php");
    if (isset($_GET[URL_ACTION_PARAM])) {
        switch($_GET[URL_ACTION_PARAM]) {
        case "img":
            require_once(ABSPATH . "/dbimg.php");
            break;
        case "report":
            require_once(ABSPATH . "/report.php");
            break;
        case "print":
            require_once(ABSPATH . "/print.php");
            break;
        default:
            index_run();
            break;
        }
    } else {
        index_run();
    }    
}

?>
