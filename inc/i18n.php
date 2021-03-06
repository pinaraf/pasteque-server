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

namespace Pasteque;

class I18N {
    private $entries;
    private $module_entries;

    public function __construct() {
        $this->entries = array();
        $this->module_entries = array();
    }

    public function loadFile($file) {
        $data = file_get_contents($file);
        $entries = json_decode($data, true);
        $this->entries = array_merge($this->entries, $entries);
    }

    public function loadModuleFile($module, $file) {
        $data = file_get_contents($file);
        $entries = json_decode($data, true);
        if (!isset($this->module_entries[$module])) {
            $this->module_entries[$module] = array();
        }
        $this->module_entries[$module] = array_merge(
                $this->module_entries[$module], $entries);
    }

    public function get($label, $module = NULL, $args = array()) {
        if ($module == NULL) {
            if (isset($this->entries[$label])) {
                if (count($args) > 0) {
                    return vsprintf($this->entries[$label], $args);
                } else {
                    return $this->entries[$label];
                }
            } else {
                if (count($args) > 0) {
                    return vsprintf($label, $args);
                } else {
                    return $label;
                }
            }
        } else {
            if (isset($this->module_entries[$module])
                    && isset($this->module_entries[$module][$label])) {
                return vsprintf($this->module_entries[$module][$label], $args);
            } else {
                return vsprintf($label, $args);
            }
        }
    }

    public function currency($amount) {
        $srv = new CurrenciesService();
        $currency = $srv->getDefault();
        return $currency->format($amount);
    }
    public function date($timestamp) {
        if ($timestamp) {
            return strftime($this->entries['date'], $timestamp);
        } else {
            return "";
        }
    }
    public function datetime($timestamp) {
        if ($timestamp) {
            return strftime($this->entries['datetime'], $timestamp);
        } else {
            return "";
        }
    }
    /** Convert a string date to a timestamp */
    public function revDate($date) {
        return timefstr($this->entries['date'], $date);
    }
    public function revDatetime($datetime) {
        return timefstr($this->entries['datetime'], $datetime);
    }
    /** Return a string number format whith correct format 
     * parsed whith 2 decimals*/
    public function formatFloat($float) {
        return number_format($float, 2, \i18n("Dec point"), \i18n("Thousands sep"));
    }
    /** Convert a float number to integer */
    public function formatInteger($float) {
        return sprintf("%d", $float);
    }

}
global $I18N;
$I18N = new I18N();

function __($label, $module = NULL, $args = array()) {
    global $I18N;
    return $I18N->get($label, $module, $args);
}
function __d($timestamp) {
    global $I18N;
    return $I18N->date($timestamp);
}
function __dt($timestamp) {
    global $I18N;
    return $I18N->datetime($timestamp);
}
function __rd($date) {
    global $I18N;
    return $I18N->revDate($date);
}
function __rdt($datetime) {
    global $I18N;
    return $I18N->revDatetime($datetime);
}
function __cur($amount) {
    global $I18N;
    return $I18N->currency($amount);
}
function __flt($float) {
    global $I18N;
    return $I18N->formatFloat($float);
}
function __int($float) {
    global $I18N;
    return $I18N->formatInteger($float);
}

global $i18n_modules;
$i18n_modules = array();

/** Load generic i18n files */
function load_base_i18n($language = NULL) {
    global $I18N;
    $I18N->loadFile(PT::$ABSPATH . "/languages/default.locale");
    if ($language !== NULL) {
        $base_i18n = PT::$ABSPATH . "/languages/" . $language . ".locale";
        if (file_exists($base_i18n) && is_readable($base_i18n)) {
            $I18N->loadFile($base_i18n);
        } else if (strpos($language, "-") !== FALSE) {
            // Check parent language
            $parent = substr($language, 0, 2);
            $file = PT::$ABSPATH . "/languages/" . $parent . ".locale";
            if (file_exists($file) && is_readable($file)) {
                $I18N->loadFile($file);
            }
        }
    }
}

/** Load module i18n files for a given language. */
function load_modules_i18n($language = NULL) {
    // Load default i18n
    global $i18n_modules;
    global $I18N;
    foreach ($i18n_modules as $module) {
        $file = PT::$ABSPATH . "/modules/" . $module . "/languages/default.locale";
        if (file_exists($file) && is_readable($file)) {
            $I18N->loadModuleFile($module, $file);
        }
    }
    // Override with requested language
    if ($language !== NULL) {
        foreach ($i18n_modules as $module) {
            $file = PT::$ABSPATH . "/modules/" . $module . "/languages/"
                    . $language . ".locale";
            if (file_exists($file) && is_readable($file)) {
                $I18N->loadModuleFile($module, $file);
            } else if (strpos($language, "-") !== FALSE) {
                // Check parent language
                $parent = substr($language, 0, 2);
                $file = PT::$ABSPATH . "/modules/" . $module . "/languages/"
                        . $parent . ".locale";
                if (file_exists($file) && is_readable($file)) {
                    $I18N->loadModuleFile($module, $file);
                }
            }
        }
    }
}

/** Register a module to load i18n file. */
function register_i18n($module_name) {
    global $i18n_modules;
    $i18n_modules[] = $module_name;
}

function detect_preferred_language() {
    if (! isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
        return NULL;
    }
    $languages = explode(";", $_SERVER['HTTP_ACCEPT_LANGUAGE']);
    $sublanguage = explode(",", $languages[0]);
    $preferred = $sublanguage[0];
    if (strpos($preferred, "-") !== FALSE) {
        $split = explode("-", $preferred);
        $preferred = strtolower($split[0]) . "-" . strtoupper($split[1]);
    }
    return $preferred;
}
?>
