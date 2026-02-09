<?php
/**
 * Student Comparison Report (Fully Internationalized).
 * @package    local_yetkinlik
 */

require_once(__DIR__ . '/../../config.php');

$courseid = required_param('courseid', PARAM_INT);
$course   = $DB->get_record('course', array('id' => $courseid), '*', MUST_EXIST);
$context  = context_course::instance($courseid);

// Güvenlik ve Kurs Kaydı Kontrolü
require_login($course);

global $DB, $USER, $PAGE, $OUTPUT;

// Sayfa Yapılandırması
$PAGE->set_url('/local/yetkinlik/student_class.php', ['courseid' => $courseid]);
$PAGE->set_context($context);
$PAGE->set_title(get_string('studentanalysis', 'local_yetkinlik'));
$PAGE->set_heading(get_string('analysisfor', 'local_yetkinlik', $course->fullname));
$PAGE->set_pagelayout('course');

echo $OUTPUT->header();

// 1. VERİ SORGULARI
$courseSql = "SELECT c.id, c.shortname, 
                     CAST(SUM(qa.maxfraction) AS DECIMAL(12,1)) AS attempts, 
                     CAST(SUM(qas.fraction) AS DECIMAL(12,1)) AS correct
              FROM {quiz_attempts} quiza
              JOIN {quiz} quiz ON quiz.id = quiza.quiz
              JOIN {question_usages} qu ON qu.id = quiza.uniqueid
              JOIN {question_attempts} qa ON qa.questionusageid = qu.id
              JOIN {local_yetkinlik_qmap} m ON m.questionid = qa.questionid
              JOIN {competency} c ON c.id = m.competencyid
              JOIN (SELECT MAX(fraction) AS fraction, questionattemptid FROM {question_attempt_steps} GROUP BY questionattemptid) qas ON qas.questionattemptid = qa.id
              WHERE quiz.course = :courseid AND quiza.state = 'finished'
              GROUP BY c.id, c.shortname";

$courseData = $DB->get_records_sql($courseSql, ['courseid' => $courseid]);

$classData = [];
$studentData = [];

if (!empty($courseData)) {
    // Sınıf Ortalaması (Departman bazlı)
    if (!empty($USER->department)) {
        $classSql = "SELECT c.id, CAST(SUM(qa.maxfraction) AS DECIMAL(12,1)) AS attempts, CAST(SUM(qas.fraction) AS DECIMAL(12,1)) AS correct
                     FROM {quiz_attempts} quiza
                     JOIN {user} u ON quiza.userid = u.id
                     JOIN {quiz} quiz ON quiz.id = quiza.quiz
                     JOIN {question_usages} qu ON qu.id = quiza.uniqueid
                     JOIN {question_attempts} qa ON qa.questionusageid = qu.id
                     JOIN {local_yetkinlik_qmap} m ON m.questionid = qa.questionid
                     JOIN {competency} c ON c.id = m.competencyid
                     JOIN (SELECT MAX(fraction) AS fraction, questionattemptid FROM {question_attempt_steps} GROUP BY questionattemptid) qas ON qas.questionattemptid = qa.id
                     WHERE quiz.course = :courseid AND u.department = :dept AND quiza.state = 'finished'
                     GROUP BY c.id";
        $classData = $DB->get_records_sql($classSql, ['courseid' => $courseid, 'dept' => $USER->department]);
    }

    // Öğrencinin Kendi Verisi
    $studentSql = "SELECT c.id, CAST(SUM(qa.maxfraction) AS DECIMAL(12,1)) AS attempts, CAST(SUM(qas.fraction) AS DECIMAL(12,1)) AS correct
                   FROM {quiz_attempts} quiza
                   JOIN {quiz} quiz ON quiz.id = quiza.quiz
                   JOIN {question_usages} qu ON qu.id = quiza.uniqueid
                   JOIN {question_attempts} qa ON qa.questionusageid = qu.id
                   JOIN {local_yetkinlik_qmap} m ON m.questionid = qa.questionid
                   JOIN {competency} c ON c.id = m.competencyid
                   JOIN (SELECT MAX(fraction) AS fraction, questionattemptid FROM {question_attempt_steps} GROUP BY questionattemptid) qas ON qas.questionattemptid = qa.id
                   WHERE quiz.course = :courseid AND quiza.userid = :userid AND quiza.state = 'finished'
                   GROUP BY c.id";
    $studentData = $DB->get_records_sql($studentSql, ['courseid' => $courseid, 'userid' => $USER->id]);
}

// 2. EKRAN ÇIKTISI
if (empty($courseData)) {
    echo $OUTPUT->notification(get_string('nodatafound', 'local_yetkinlik'), 'info');
} else {
    // Bilgi Kutusu
    $info_text = get_string('compareinfo', 'local_yetkinlik');
    if (!empty($USER->department)) {
        $info_text .= ' ' . get_string('classinfo', 'local_yetkinlik', $USER->department);
    }
    echo html_writer::div($info_text, 'alert alert-info border-0 shadow-sm mb-4');
    
    // Tablo
    echo html_writer::start_tag('table', ['class' => 'generaltable table-hover mt-3 shadow-sm', 'style' => 'width:100%']);
    echo '<thead><tr>';
    echo html_writer::tag('th', get_string('competencyname', 'local_yetkinlik'));
    echo html_writer::tag('th', get_string('courseavg', 'local_yetkinlik'), ['class' => 'text-center']);
    echo html_writer::tag('th', get_string('classavg', 'local_yetkinlik'), ['class' => 'text-center']);
    echo html_writer::tag('th', get_string('myavg', 'local_yetkinlik'), ['class' => 'text-center']);
    echo '</tr></thead><tbody>';

    $labels = []; $courseRates = []; $classRates = []; $myRates = [];

    foreach ($courseData as $cid => $c) {
        $courseRate = $c->attempts ? round(($c->correct / $c->attempts) * 100, 1) : 0;
        $classRate  = (isset($classData[$cid]) && $classData[$cid]->attempts) ? round(($classData[$cid]->correct / $classData[$cid]->attempts) * 100, 1) : 0;
        $myRate     = (isset($studentData[$cid]) && $studentData[$cid]->attempts) ? round(($studentData[$cid]->correct / $studentData[$cid]->attempts) * 100, 1) : 0;

        $color_class = ($myRate >= $courseRate) ? 'text-success' : 'text-danger';

        echo '<tr>';
        echo html_writer::tag('td', html_writer::tag('strong', $c->shortname));
        echo html_writer::tag('td', '%' . $courseRate, ['class' => 'text-center text-muted']);
        echo html_writer::tag('td', '%' . $classRate, ['class' => 'text-center text-muted']);
        echo html_writer::tag('td', '%' . $myRate, ['class' => 'text-center font-weight-bold ' . $color_class, 'style' => 'font-size:1.1em']);
        echo '</tr>';

        $labels[] = $c->shortname;
        $courseRates[] = $courseRate;
        $classRates[] = $classRate;
        $myRates[] = $myRate;
    }
    echo '</tbody></table>';

    // Grafik
    echo html_writer::div('<canvas id="compareChart" height="120"></canvas>', 'card mt-4 p-4 shadow-sm border-0 bg-light');
?>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    var ctx = document.getElementById('compareChart').getContext('2d');
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: <?php echo json_encode($labels); ?>,
            datasets: [
                { label: '<?php echo get_string('courseavg', 'local_yetkinlik'); ?>', data: <?php echo json_encode($courseRates); ?>, backgroundColor: 'rgba(156, 39, 176, 0.4)', borderRadius: 5 },
                { label: '<?php echo get_string('classavg', 'local_yetkinlik'); ?>', data: <?php echo json_encode($classRates); ?>, backgroundColor: 'rgba(76, 175, 80, 0.4)', borderRadius: 5 },
                { label: '<?php echo get_string('myavg', 'local_yetkinlik'); ?>', data: <?php echo json_encode($myRates); ?>, backgroundColor: 'rgba(33, 150, 243, 0.8)', borderColor: '#1976d2', borderWidth: 1, borderRadius: 5 }
            ]
        },
        options: { 
            responsive: true,
            scales: { y: { beginAtZero: true, max: 100, ticks: { callback: function(value) { return '%' + value; } } } }
        }
    });
});
</script>

<?php
}
echo $OUTPUT->footer();