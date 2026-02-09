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
 * Version details for the local_yetkinlik plugin.
 *
 * @package    local_yetkinlik
 * @copyright  2026 Hakan Çiğci {@link https://hakancigci.com.tr}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/** @var stdClass $plugin */
$plugin->component = 'local_yetkinlik';       // Eklentinin tam adı.
$plugin->version   = 2026020900;              // Versiyon numarası (YYYYMMDDXX).
$plugin->requires  = 2025041400;              // Minimum Moodle 5.0 gereksinimi.
$plugin->maturity  = MATURITY_STABLE;         // Kararlı sürüm.
$plugin->release   = '1.1.1';                 // İnsan tarafından okunabilir versiyon adı.

// Bağımlılıklar (Bu eklenti çalışmadan önce yüklü olması gereken diğer eklentiler).
$plugin->dependencies = [
    'qbank_yetkinlik' => 2026020900,
];
