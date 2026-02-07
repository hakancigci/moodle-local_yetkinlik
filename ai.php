<?php
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
        $text .= '<span style="color:red;">Henüz kazanmadığın konular: '.implode(', ', $red).'</span><br>';
    }
    if ($orange) {
        $text .= '<span style="color:orange;">Kısmen öğrendiğin konular: '.implode(', ', $orange).'</span><br>';
    }
    if ($blue) {
        $text .= '<span style="color:blue;">Çoğunlukla öğrendiğin konular: '.implode(', ', $blue).'</span><br>';
    }
    if ($green) {
        $text .= '<span style="color:green;">Tamamen öğrendiğin konular: '.implode(', ', $green).'</span><br>';
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
        $prompt = "Aşağıdaki kazanım yüzdelerine göre okul genelinde pedagojik analiz ve geliştirme stratejisi yaz:\n";
    } else {
        $prompt = "Aşağıdaki kazanım yüzdelerine göre öğrenciye kısa pedagojik analiz yaz:\n";
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
            ["role" => "system", "content" => "Sen bir eğitim asistanısın. Öğrenciye veya okul geneline motive edici ve pedagojik yorumlar ver."],
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

    return "AI çağrısı başarısız oldu.";
}

/**
 * Yapılandırılmış yorum fonksiyonu (kural tabanlı)
 */
function local_yetkinlik_structured_comment(array $stats) {
    $text = "<b>".get_string('generalcomment','local_yetkinlik').":</b><br>";

    foreach ($stats as $shortname => $rate) {
        if ($rate <= 39) {
            $text .= "<span style='color:red;'>$shortname: Başarı oranı %$rate. 
            Bu konuda henüz yeterince ilerleme kaydedilmedi. Önerim: tekrar yap, ek kaynaklardan çalış ve öğretmenine sorularını yönelt.</span><br>";
        } else if ($rate >= 40 && $rate <= 59) {
            $text .= "<span style='color:orange;'>$shortname: Başarı oranı %$rate. 
            Kısmen öğrenilmiş durumda. Önerim: daha fazla pratik yap, örnek sorular çöz ve bilgini pekiştir.</span><br>";
        } else if ($rate >= 60 && $rate <= 79) {
            $text .= "<span style='color:blue;'>$shortname: Başarı oranı %$rate. 
            Çoğunlukla öğrenilmiş durumda. Önerim: tekrarlarla bilgini sağlamlaştır, eksik noktaları tamamla.</span><br>";
        } else if ($rate >= 80) {
            $text .= "<span style='color:green;'>$shortname: Başarı oranı %$rate. 
            Tam öğrenilmiş durumda. Önerim: ileri düzey etkinliklere geç, bilgini farklı durumlarda uygula.</span><br>";
        }
    }

    return $text;
}