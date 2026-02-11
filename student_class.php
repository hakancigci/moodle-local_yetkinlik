<?php
// Öğrenci Karşılaştırma Raporu.
// Bu dosya öğrenci yetkinliklerini ders ve sınıf ortalamalarıyla karşılaştırır.
// @package    local_yetkinlik
// @copyright  2026 Hakan Çiğci {@link https://hakancigci.com.tr}
// @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later

// Moodle ana yapılandırma dosyasını yükle.
require_once(__DIR__ . '/../../config.php');

// Global nesne tanımlamaları ve girdi parametreleri.
global $DB, $USER, $PAGE, $OUTPUT;

$courseid = required_param('courseid', PARAM_INT);
$course   = $DB->get_record('course', ['id' => $courseid], '*', MUST_EXIST);
$context  = context_course::instance($courseid);

// Güvenlik kontrolü ve kurs kaydı doğrulaması.
require_login($course);

// Sayfa URL ve başlık yapılandırması.
$PAGE->set_url('/local/yetkinlik/student_class.php', ['courseid' => $courseid]);
$PAGE->set_context($context);
$PAGE->set_title(get_string('studentanalysis', 'local_yetkinlik'));
$PAGE->set_heading(get_string('analysisfor', 'local_yetkinlik', $course->fullname));
$PAGE->set_pagelayout('course');

echo $OUTPUT->header();

// Veri tabanı üzerinden yetkinlik puanlarını hesaplayan SQL sorgusu.
$coursesql = "SELECT c.id, c.shortname,
                     CAST(SUM(qa.maxfraction) AS DECIMAL(12, 1)) AS attempts,
                     CAST(SUM(qas.fraction) AS DECIMAL(12, 1)) AS correct
              FROM {quiz_attempts} quiza
              JOIN {quiz} quiz ON quiz.id = quiza.quiz
              JOIN {question_usages} qu ON qu.id = quiza.uniqueid
              JOIN {question_attempts} qa ON qa.questionusageid = qu.id
              JOIN {local_yetkinlik_qmap} m ON m.questionid = qa.questionid
              JOIN {competency} c ON c.id = m.competencyid
              JOIN (
                  SELECT MAX(fraction) AS fraction, questionattemptid
                  FROM {question_attempt_steps}
                  GROUP BY questionattemptid
              ) qas ON qas.questionattemptid = qa.id
              WHERE quiz.course = :courseid AND quiza.state = 'finished'
              GROUP BY c.id, c.shortname";

$coursedata = $DB->get_records_sql($coursesql, ['courseid' => $courseid]);

$classdata = [];
$studentdata = [];

if (!empty($coursedata)) {
    // Sınıf bazlı ortalamaların departmana göre çekilmesi.
    if (!empty($USER->department)) {
        $classsql = "SELECT c.id,
                            CAST(SUM(qa.maxfraction) AS DECIMAL(12, 1)) AS attempts,
                            CAST(SUM(qas.fraction) AS DECIMAL(12, 1)) AS correct
                     FROM {quiz_attempts} quiza
                     JOIN {user} u ON quiza.userid = u.id
                     JOIN {quiz} quiz ON quiz.id = quiza.quiz
                     JOIN {question_usages} qu ON qu.id = quiza.uniqueid
                     JOIN {question_attempts} qa ON qa.questionusageid = qu.id
                     JOIN {local_yetkinlik_qmap} m ON m.questionid = qa.questionid
                     JOIN {competency} c ON c.id = m.competencyid
                     JOIN (
                         SELECT MAX(fraction) AS fraction, questionattemptid
                         FROM {question_attempt_steps}
                         GROUP BY questionattemptid
                     ) qas ON qas.questionattemptid = qa.id
                     WHERE quiz.course = :courseid AND u.department = :dept AND quiza.state = 'finished'
                     GROUP BY c.id";
        $classdata = $DB->get_records_sql($classsql, ['courseid' => $courseid, 'dept' => $USER->department]);
    }

    // Aktif öğrencinin kendi performans verileri.
    $studentsql = "SELECT c.id,
                          CAST(SUM(qa.maxfraction) AS DECIMAL(12, 1)) AS attempts,
                          CAST(SUM(qas.fraction) AS DECIMAL(12, 1)) AS correct
                    FROM {quiz_attempts} quiza
                    JOIN {quiz} quiz ON quiz.id = quiza.quiz
                    JOIN {question_usages} qu ON qu.id = quiza.uniqueid
                    JOIN {question_attempts} qa ON qa.questionusageid = qu.id
                    JOIN {local_yetkinlik_qmap} m ON m.questionid = qa.questionid
                    JOIN {competency} c ON c.id = m.competencyid
                    JOIN (
                        SELECT MAX(fraction) AS fraction, questionattemptid
                        FROM {question_attempt_steps}
                        GROUP BY questionattemptid
                    ) qas ON qas.questionattemptid = qa.id
                    WHERE quiz.course = :courseid AND quiza.userid = :userid AND quiza.state = 'finished'
                    GROUP BY c.id";
    $studentdata = $DB->get_records_sql($studentsql, ['courseid' => $courseid, 'userid' => $USER->id]);
}

// Görsel raporlama ve tablo çıktısı.
if (empty($coursedata)) {
    echo $OUTPUT->notification(get_string('nodatafound', 'local_yetkinlik'), 'info');
} else {
    // Bilgilendirme kutusu.
    $infotext = get_string('compareinfo', 'local_yetkinlik');
    if (!empty($USER->department)) {
        $infotext .= ' ' . get_string('classinfo', 'local_yetkinlik', $USER->department);
    }
    echo html_writer::div($infotext, 'alert alert-info border-0 shadow-sm mb-4');

    // Karşılaştırmalı veri tablosu oluşturma.
    echo html_writer::start_tag('table', ['class' => 'generaltable table-hover mt-3 shadow-sm', 'style' => 'width:100%']);
    echo '<thead><tr>';
    echo html_writer::tag('th', get_string('competencyname', 'local_yetkinlik'));
    echo html_writer::tag('th', get_string('courseavg', 'local_yetkinlik'), ['class' => 'text-center']);
    echo html_writer::tag('th', get_string('classavg', 'local_yetkinlik'), ['class' => 'text-center']);
    echo html_writer::tag('th', get_string('myavg', 'local_yetkinlik'), ['class' => 'text-center']);
    echo '</tr></thead><tbody>';

    $labels = [];
    $courserates = [];
    $classrates = [];
    $myrates = [];

    foreach ($coursedata as $cid => $c) {
        $courserate = $c->attempts ? round(($c->correct / $c->attempts) * 100, 1) : 0;
        $classrate  = (isset($classdata[$cid]) && $classdata[$cid]->attempts)
            ? round(($classdata[$cid]->correct / $classdata[$cid]->attempts) * 100, 1) : 0;
        $myrate     = (isset($studentdata[$cid]) && $studentdata[$cid]->attempts)
            ? round(($studentdata[$cid]->correct / $studentdata[$cid]->attempts) * 100, 1) : 0;

        $colorclass = ($myrate >= $courserate) ? 'text-success' : 'text-danger';

        echo '<tr>';
        echo html_writer::tag('td', html_writer::tag('strong', $c->shortname));
        echo html_writer::tag('td', '%' . $courserate, ['class' => 'text-center text-muted']);
        echo html_writer::tag('td', '%' . $classrate, ['class' => 'text-center text-muted']);
        echo html_writer::tag('td', '%' . $myrate, [
            'class' => 'text-center font-weight-bold ' . $colorclass,
            'style' => 'font-size:1.1em',
        ]);
        echo '</tr>';

        $labels[] = $c->shortname;
        $courserates[] = $courserate;
        $classrates[] = $classrate;
        $myrates[] = $myrate;
    }
    echo '</tbody></table>';

    // Grafik alanı için canvas oluşturulması.
    echo html_writer::div('<canvas id="compareChart" height="120"></canvas>', 'card mt-4 p-4 shadow-sm border-0 bg-light');

    // JavaScript verilerinin PHP üzerinden JSON formatına dönüştürülmesi.
    $labelsjson = json_encode($labels);
    $courseratesjson = json_encode($courserates);
    $classratesjson = json_encode($classrates);
    $myratesjson = json_encode($myrates);
    $courseavglabel = get_string('courseavg', 'local_yetkinlik');
    $classavglabel = get_string('classavg', 'local_yetkinlik');
    $myavglabel = get_string('myavg', 'local_yetkinlik');
    ?>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// Sayfa yüklendiğinde grafiği başlatan fonksiyon.
document.addEventListener('DOMContentLoaded', function() {
    var ctx = document.getElementById('compareChart').getContext('2d');
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: <?php // Grafik etiketleri.
                echo $labelsjson; ?>,
            datasets: [
                {
                    label: '<?php // Kurs ortalaması etiketi.
                        echo $courseavglabel; ?>',
                    data: <?php // Kurs ortalaması verisi.
                        echo $courseratesjson; ?>,
                    backgroundColor: 'rgba(156, 39, 176, 0.4)',
                    borderRadius: 5
                },
                {
                    label: '<?php // Sınıf ortalaması etiketi.
                        echo $classavglabel; ?>',
                    data: <?php // Sınıf ortalaması verisi.
                        echo $classratesjson; ?>,
                    backgroundColor: 'rgba(76, 175, 80, 0.4)',
                    borderRadius: 5
                },
                {
                    label: '<?php // Kişisel ortalama etiketi.
                        echo $myavglabel; ?>',
                    data: <?php // Kişisel ortalama verisi.
                        echo $myratesjson; ?>,
                    backgroundColor: 'rgba(33, 150, 243, 0.8)',
                    borderColor: '#1976d2',
                    borderWidth: 1,
                    borderRadius: 5
                }
            ]
        },
        options: {
            responsive: true,
            scales: {
                y: {
                    beginAtZero: true,
                    max: 100,
                    ticks: {
                        callback: function(value) {
                            return '%' + value;
                        }
                    }
                }
            }
        }
    });
});
</script>

    <?php
}

// Sayfa altbilgisini yazdır ve sonlandır.
echo $OUTPUT->footer();
