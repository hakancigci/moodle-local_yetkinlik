<?php
// Yetkinlik Gelişim Zaman Çizelgesi Raporu.
// Bu dosya öğrencinin yetkinlik gelişimini zaman bazlı bir çizgi grafiği ile sunar.
// @package    local_yetkinlik
// @copyright  2026 Hakan Çiğci {@link https://hakancigci.com.tr}
// @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later

// Moodle ana yapılandırma dosyasını yükle.
require_once(__DIR__ . '/../../config.php');

// Gerekli parametrelerin ve filtrelerin alınması.
$courseid = required_param('courseid', PARAM_INT);
$days     = optional_param('days', 90, PARAM_INT);

// Kullanıcı oturumu ve kurs erişim kontrolü.
require_login($courseid);

// Global nesne tanımlamaları.
global $USER, $DB, $PAGE, $OUTPUT;

$userid = $USER->id;
$context = context_course::instance($courseid);

// Sayfa URL ve tema yapılandırması.
$PAGE->set_url('/local/yetkinlik/timeline.php', ['courseid' => $courseid]);
$PAGE->set_context($context);
$PAGE->set_title(get_string('timelineheading', 'local_yetkinlik'));
$PAGE->set_heading(get_string('timelineheading', 'local_yetkinlik'));
$PAGE->set_pagelayout('course');

echo $OUTPUT->header();

// Sorgu filtreleri ve SQL hazırlık aşaması.
$where = "quiz.course = :courseid AND u.id = :userid";
$params = ['courseid' => $courseid, 'userid' => $userid];

if ($days > 0) {
    $where .= " AND qas2.timecreated > UNIX_TIMESTAMP(DATE_SUB(NOW(), INTERVAL :days DAY))";
    $params['days'] = $days;
}

// Zaman bazlı yetkinlik performansını getiren SQL sorgusu.
$sql = "
SELECT
  c.shortname,
  FROM_UNIXTIME(qas2.timecreated, '%Y-%m') AS period,
  CAST(SUM(qa.maxfraction) AS DECIMAL(12,1)) AS attempts,
  CAST(SUM(qas.fraction) AS DECIMAL(12,1)) AS correct
FROM {quiz_attempts} quiza
JOIN {user} u ON quiza.userid = u.id
JOIN {question_usages} qu ON qu.id = quiza.uniqueid
JOIN {question_attempts} qa ON qa.questionusageid = qu.id
JOIN {quiz} quiz ON quiz.id = quiza.quiz
JOIN {local_yetkinlik_qmap} m ON m.questionid = qa.questionid
JOIN {competency} c ON c.id = m.competencyid
JOIN (
    SELECT questionattemptid, MAX(timecreated) AS timecreated
    FROM {question_attempt_steps}
    GROUP BY questionattemptid
) qas2 ON qas2.questionattemptid = qa.id
JOIN (
    SELECT MAX(fraction) AS fraction, questionattemptid
    FROM {question_attempt_steps}
    GROUP BY questionattemptid
) qas ON qas.questionattemptid = qa.id
WHERE $where AND quiza.state = 'finished'
GROUP BY c.shortname, period
ORDER BY period ASC
";

$rows = $DB->get_records_sql($sql, $params);

// Ham verilerin işlenmesi ve diziye aktarılması.
$data = [];
$periods = [];

foreach ($rows as $r) {
    $rate = $r->attempts ? number_format(($r->correct / $r->attempts) * 100, 1) : 0;
    $data[$r->shortname][$r->period] = $rate;
    $periods[$r->period] = true;
}

$periods = array_keys($periods);
sort($periods);

// Grafik için veri setlerinin hazırlanması.
$datasets = [];
$colors = ['#e53935', '#1e88e5', '#43a047', '#fb8c00', '#8e24aa', '#00897b'];
$i = 0;

foreach ($data as $comp => $vals) {
    $line = [];
    foreach ($periods as $p) {
        $line[] = isset($vals[$p]) ? $vals[$p] : 0;
    }
    $datasets[] = [
        'label' => $comp,
        'data' => $line,
        'borderColor' => $colors[$i % count($colors)],
        'backgroundColor' => $colors[$i % count($colors)],
        'fill' => false,
        'tension' => 0.1,
    ];
    $i++;
}

// JavaScript tarafında kullanılacak dil dizgileri ve veriler.
$labelsjs = json_encode($periods);
$datasetsjs = json_encode($datasets);
$successlabel = get_string('successrate', 'local_yetkinlik');
$filterlabel = get_string('filterlabel', 'local_yetkinlik');
$last30days = get_string('last30days', 'local_yetkinlik');
$last90days = get_string('last90days', 'local_yetkinlik');
$alltime = get_string('alltime', 'local_yetkinlik');
$showlabel = get_string('show', 'local_yetkinlik');

// Kullanıcı arayüzü form alanı.
?>

<div class="card mb-4">
    <div class="card-body">
        <form method="get" class="form-inline">
            <input type="hidden" name="courseid" value="<?php // Kurs ID verisi.
                echo $courseid; ?>">
            <label class="mr-2" for="days"><?php // Filtre etiketi.
                echo $filterlabel; ?></label>
            <select name="days" id="days" class="form-control mr-2">
                <option value="30" <?php // 30 Gün seçeneği.
                    echo ($days == 30) ? 'selected' : ''; ?>>
                    <?php echo $last30days; ?>
                </option>
                <option value="90" <?php // 90 Gün seçeneği.
                    echo ($days == 90) ? 'selected' : ''; ?>>
                    <?php echo $last90days; ?>
                </option>
                <option value="0" <?php // Tüm zamanlar seçeneği.
                    echo ($days == 0) ? 'selected' : ''; ?>>
                    <?php echo $alltime; ?>
                </option>
            </select>
            <button type="submit" class="btn btn-primary"><?php // Göster butonu.
                echo $showlabel; ?></button>
        </form>
    </div>
</div>

<div class="chart-container" style="position: relative; height:400px; width:100%">
    <canvas id="timeline"></canvas>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    // Sayfa yüklendiğinde zaman çizelgesi grafiğini başlat.
    document.addEventListener('DOMContentLoaded', function() {
        const ctx = document.getElementById('timeline').getContext('2d');
        
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: <?php // Grafik zaman etiketleri.
                    echo $labelsjs; ?>,
                datasets: <?php // Grafik veri setleri.
                    echo $datasetsjs; ?>
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        max: 100,
                        title: {
                            display: true,
                            text: '<?php // Başarı oranı metni.
                                echo $successlabel; ?> (%)'
                        }
                    }
                },
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });
    });
</script>

<?php
// Sayfa altbilgisini yazdır.
echo $OUTPUT->footer();
