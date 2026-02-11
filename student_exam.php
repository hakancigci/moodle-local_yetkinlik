<?php
// Öğrenci Sınav Yetkinlik Analiz Raporu.
// Bu dosya öğrencinin girdiği sınavlara göre yetkinlik başarısını raporlar.
// @package    local_yetkinlik
// @copyright  2026 Hakan Çiğci {@link https://hakancigci.com.tr}
// @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later

// Moodle ana yapılandırma dosyasını yükle.
require_once(__DIR__ . '/../../config.php');

// Gerekli parametrelerin alınması.
$courseid = required_param('courseid', PARAM_INT);
$quizid   = optional_param('quizid', 0, PARAM_INT);

// Kullanıcı oturum ve kurs erişim kontrolü.
require_login($courseid);

// Global nesne tanımlamaları.
global $DB, $USER, $OUTPUT, $PAGE;

$context = context_course::instance($courseid);

// Sayfa URL ve tema yapılandırması.
$PAGE->set_url('/local/yetkinlik/student_exam.php', ['courseid' => $courseid]);
$PAGE->set_title(get_string('studentexam', 'local_yetkinlik'));
$PAGE->set_heading(get_string('studentexam', 'local_yetkinlik'));
$PAGE->set_pagelayout('course');

echo $OUTPUT->header();

// Öğrencinin bu kursta tamamladığı sınavları getir.
$quizzes = $DB->get_records_sql("
    SELECT DISTINCT q.id, q.name
      FROM {quiz} q
      JOIN {quiz_attempts} qa ON qa.quiz = q.id
     WHERE qa.userid = ? AND q.course = ?
  ORDER BY q.name
", [$USER->id, $courseid]);

// Seçim formu alanı.
echo html_writer::start_tag('div', ['class' => 'card mb-4']);
echo html_writer::start_tag('div', ['class' => 'card-body']);

echo html_writer::start_tag('form', ['method' => 'get', 'class' => 'form-inline']);
echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'courseid', 'value' => $courseid]);

$options = [0 => get_string('selectquiz', 'local_yetkinlik')];
foreach ($quizzes as $q) {
    $options[$q->id] = $q->name;
}

echo html_writer::label(get_string('selectquiz', 'local_yetkinlik'), 'quizid', false, ['class' => 'mr-2']);
echo html_writer::select($options, 'quizid', $quizid, false, ['class' => 'form-control mr-2', 'id' => 'quizid']);
echo html_writer::tag('button', get_string('show', 'local_yetkinlik'), ['type' => 'submit', 'class' => 'btn btn-primary']);

echo html_writer::end_tag('form');
echo html_writer::end_tag('div');
echo html_writer::end_tag('div');

// Eğer bir sınav seçilmişse yetkinlik verilerini hesapla.
if ($quizid) {
    $sql = "
    SELECT
      c.shortname,
      c.description,
      SUM(qa.maxfraction) AS attempts,
      SUM(qas.fraction) AS correct
    FROM {quiz_attempts} quiza
    JOIN {user} u ON quiza.userid = u.id
    JOIN {question_usages} qu ON qu.id = quiza.uniqueid
    JOIN {question_attempts} qa ON qa.questionusageid = qu.id
    JOIN {quiz} quiz ON quiz.id = quiza.quiz
    JOIN {local_yetkinlik_qmap} m ON m.questionid = qa.questionid
    JOIN {competency} c ON c.id = m.competencyid
    JOIN (
        SELECT MAX(fraction) AS fraction, questionattemptid
        FROM {question_attempt_steps}
        GROUP BY questionattemptid
    ) qas ON qas.questionattemptid = qa.id
    WHERE quiz.id = ? AND u.id = ?
    GROUP BY c.shortname, c.description
    ORDER BY c.shortname
    ";

    $rows = $DB->get_records_sql($sql, [$quizid, $USER->id]);

    // Veri varsa tablo ve grafik oluştur.
    if ($rows) {
        $table = new html_table();
        $table->head = [
            get_string('competencycode', 'local_yetkinlik'),
            get_string('competency', 'local_yetkinlik'),
            get_string('success', 'local_yetkinlik'),
        ];
        $table->attributes['class'] = 'generaltable mt-3';

        $labels = [];
        $chartdata = [];
        $bgcolors = [];

        foreach ($rows as $r) {
            $rate = $r->attempts ? number_format(($r->correct / $r->attempts) * 100, 1) : 0;
            $labels[] = $r->shortname;
            $chartdata[] = $rate;

            // Başarı oranına göre renk belirleme.
            if ($rate >= 80) {
                $color = 'green';
            } else if ($rate >= 60) {
                $color = 'blue';
            } else if ($rate >= 40) {
                $color = 'orange';
            } else {
                $color = 'red';
            }

            $bgcolors[] = $color;

            $formattedrate = html_writer::tag('span', "%{$rate}", [
                'style' => "color: $color; font-weight: bold;",
            ]);

            $table->data[] = [$r->shortname, $r->description, $formattedrate];
        }

        echo html_writer::table($table);

        // JavaScript verilerini PHP tarafında hazırla.
        $labelsjs = json_encode($labels);
        $datajs = json_encode($chartdata);
        $colorsjs = json_encode($bgcolors);
        $chartlabel = get_string('successpercent', 'local_yetkinlik') . ' (%)';
        ?>

        <div class="chart-container mt-4" style="position: relative; height:40vh; width:100%">
            <canvas id="studentexamchart"></canvas>
        </div>

        <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
        <script>
        // Sayfa hazır olduğunda sınav grafiğini çiz.
        document.addEventListener('DOMContentLoaded', function() {
            const ctx = document.getElementById('studentexamchart').getContext('2d');
            new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: <?php // Grafik etiketleri.
                        echo $labelsjs; ?>,
                    datasets: [{
                        label: '<?php // Grafik başlığı.
                            echo $chartlabel; ?>',
                        data: <?php // Başarı verileri.
                            echo $datajs; ?>,
                        backgroundColor: <?php // Dinamik renkler.
                            echo $colorsjs; ?>
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: { beginAtZero: true, max: 100 }
                    }
                }
            });
        });
        </script>

        <?php
    } else {
        // Sınav verisi bulunamadığında uyarı göster.
        echo $OUTPUT->notification(get_string('noexamdata', 'local_yetkinlik'), 'info');
    }
}

// Sayfa altbilgisini yazdır.
echo $OUTPUT->footer();
