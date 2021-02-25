<?php

// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Cloudinary module admin settings and defaults
 *
 * @package    mod_cloudinary
 */

defined('MOODLE_INTERNAL') || die;

if ($ADMIN->fulltree) {
    require_once("$CFG->libdir/resourcelib.php");

    //--- general settings -----------------------------------------------------------------------------------
    $settings->add(new admin_setting_configtext('cloudinary/cloudname', "Cloudname", 'Your cloudinary cloud name', null, PARAM_RAW));
    $settings->add(new admin_setting_configtext('cloudinary/api_key', "API Key", 'Your cloudinary api key name', null, PARAM_RAW));
    $settings->add(new admin_setting_configtext('cloudinary/api_secret', "API Secret", 'Your cloudinary api key name', null, PARAM_RAW));
    $settings->add(new admin_setting_configcheckbox('cloudinary/secure', "Secure mode", 'Cloudinary secure mode', true));
}
