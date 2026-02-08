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
 * Report for competency.
 *
 * @package   local_yetkinlik
 * @copyright 2026 Hakan Çiğci {@link https://hakancigci.com.tr}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later*/

defined('MOODLE_INTERNAL') || die();

/**
 * Ana çağrılan fonksiyon
 */
function local_yetkinlik_generate_comment(array $stats, $context = 'student') {
    if (!get_config('local_yetkinlik','enable_ai')) {
        return local_yetkinlik_rule_based_comment($stats);
    }
    // AI yorum fonksiyonu çağrılır
    return local_yetkinlik_ai_comment($stats, $context);
}

/**
 * Kurallı (AI kapalıyken) - öğrenciye yönelik yorumlar
 */
function local_yetkinlik_rule_based_comment(array $stats) {
    $red = []; $orange = []; $blue = []; $green = [];

    foreach ($stats as $k => $rate) {
        if ($rate <= 39) { $red[] = $k; }
        else if ($rate >= 40 && $rate <= 59) { $orange[] = $k; }
        else if ($rate >= 60 && $rate <= 79) { $blue[] = $k; }
        else if ($rate >= 80) { $green[] = $k; }
    }

    $text = get_string('generalcomment','local_yetkinlik').":<br>";

    if ($red) {
        $text .= '<span style="color:red;">'.get_string('comment_red','local_yetkinlik', implode(', ', $red)).'</span><br>';
    }
    if ($orange) {
        $text .= '<span style="color:orange;">'.get_string('comment_orange','local_yetkinlik', implode(', ', $orange)).'</span><br>';
    }
    if ($blue) {
        $text .= '<span style="color:blue;">'.get_string('comment_blue','local_yetkinlik', implode(', ', $blue)).'</span><br>';
    }
    if ($green) {
        $text .= '<span style="color:green;">'.get_string('comment_green','local_yetkinlik', implode(', ', $green)).'</span><br>';
    }

    return $text;
}

/**
 * AI tabanlı (plugin config kullanarak gerçek OpenAI çağrısı)
 * $context parametresi: 'student' veya 'school'
 */
function local_yetkinlik_ai_comment(array $stats, $context = 'student') {
    global $CFG;
    require_once($CFG->libdir.'/filelib.php');

    $apikey = get_config('local_yetkinlik', 'apikey');
    $model  = get_config('local_yetkinlik', 'model');

    if (empty($apikey) || empty($model)) {
        return get_string('ai_not_configured', 'local_yetkinlik');
    }

    // Prompt seçimi
    if ($context === 'school') {
        $prompt = get_string('ai_prompt_school','local_yetkinlik')."\n";
    } else {
        $prompt = get_string('ai_prompt_student','local_yetkinlik')."\n";
    }

    foreach ($stats as $k => $v) {
        $prompt .= "$k: %$v\n";
    }

    $curl = new \curl();
    $headers = [
        "Authorization: Bearer {$apikey}",
        "Content-Type: application/json"
    ];
    $postdata = json_encode([
        "model" => $model,
        "messages" => [
            ["role" => "system", "content" => get_string('ai_system_prompt','local_yetkinlik')],
            ["role" => "user", "content" => $prompt]
        ]
    ]);

    $options = [
        'httpheader' => $headers,
        'timeout' => 30,
        'followlocation' => true,
        'returntransfer' => true
    ];

    $response = $curl->post("https://api.openai.com/v1/chat/completions", $postdata, $options);
    $data = json_decode($response, true);

    if (!empty($data['choices'][0]['message']['content'])) {
        return $data['choices'][0]['message']['content'];
    }

    return get_string('ai_failed','local_yetkinlik');
}

/**
 * Yapılandırılmış yorum fonksiyonu (kural tabanlı)
 */
function local_yetkinlik_structured_comment(array $stats) {
    $text = "<b>".get_string('generalcomment','local_yetkinlik').":</b><br>";

    foreach ($stats as $shortname => $rate) {
        if ($rate <= 39) {
            $text .= "<span style='color:red;'>".get_string('structured_red','local_yetkinlik', ['shortname'=>$shortname,'rate'=>$rate])."</span><br>";
        } else if ($rate >= 40 && $rate <= 59) {
            $text .= "<span style='color:orange;'>".get_string('structured_orange','local_yetkinlik', ['shortname'=>$shortname,'rate'=>$rate])."</span><br>";
        } else if ($rate >= 60 && $rate <= 79) {
            $text .= "<span style='color:blue;'>".get_string('structured_blue','local_yetkinlik', ['shortname'=>$shortname,'rate'=>$rate])."</span><br>";
        } else if ($rate >= 80) {
            $text .= "<span style='color:green;'>".get_string('structured_green','local_yetkinlik', ['shortname'=>$shortname,'rate'=>$rate])."</span><br>";
        }
    }

    return $text;
}
