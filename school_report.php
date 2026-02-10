<?php
/**
 * Report for competency.
 *
 * @package    local_yetkinlik
 * @copyright  2026 Hakan Çiğci {@link https://hakancigci.com.tr}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_login();

$context = context_system::instance();
require_capability('moodle/site:config', $context);

$PAGE->set_url('/local/yetkinlik/school_report.php');
$PAGE->set_title('Okul Genel Kazanım Raporu');
$PAGE->set_heading('Okul Genel Kazanım Raporu');

echo $OUTPUT->header();
global $DB;

// PDF Butonu - Üst Kısım
$pdfurl = new moodle_url('/local/yetkinlik/school_pdf.php');
echo $OUTPUT->single_button($pdfurl, 'Raporu PDF Olarak İndir', 'get', ['class' => 'btn btn-primary mb-3']);

/* Tüm okul yetkinlik başarısı SQL */
$sql = "
    SELECT c.id, c.shortname, c.description,
           CAST(SUM(qa.maxfraction) AS DECIMAL(12, 1)) AS attempts,
           CAST(SUM(qas.fraction) AS DECIMAL(12, 1)) AS correct
    FROM {quiz_attempts} quiza
    JOIN {question_usages} qu ON qu.id = quiza.uniqueid
    JOIN {question_attempts} qa ON qa.questionusageid = qu.id
    JOIN {local_yetkinlik_qmap} m ON m.questionid = qa.questionid
    JOIN {competency} c ON c.id = m.competencyid
    JOIN (
        SELECT MAX(fraction) AS fraction, questionattemptid
        FROM {question_attempt_steps}
        GROUP BY questionattemptid
    ) qas ON qas.questionattemptid = qa.id
    WHERE quiza.state = 'finished'
    GROUP BY c.id, c.shortname, c.description
    ORDER BY c.shortname ASC
";

$rows = $DB->get_records_sql($sql);

echo '<table class="generaltable" style="width:100%">';
echo '<thead><tr>
        <th>Kazanım Kodu</th>
        <th>Kazanım</th>
        <th>Çözülen</th>
        <th>Doğru</th>
        <th>Başarı</th>
      </tr></thead><tbody>';

$labels = [];
$data = [];
foreach ($rows as $r) {
    $rate = $r->attempts ? number_format(($r->correct / $r->attempts) * 100, 1) : 0;
    $labels[] = $r->shortname;
    $data[] = $rate;
    
    $color = $rate >= 70 ? '#28a745' : ($rate >= 50 ? '#ffc107' : '#dc3545');

    echo "<tr>
            <td><strong>{$r->shortname}</strong></td>
            <td>{$r->description}</td>
            <td>{$r->attempts}</td>
            <td>{$r->correct}</td>
            <td style='color: $color; font-weight: bold;'>%{$rate}</td>
          </tr>";
}
echo '</tbody></table>';

$labelsjs = json_encode($labels);
$datajs = json_encode($data);
?>

<div class="card mt-4">
    <div class="card-body">
        <canvas id="schoolchart" style="max-height: 400px;"></canvas>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    new Chart(document.getElementById('schoolchart'), {
        type: 'bar',
        data: {
            labels: <?php echo $labelsjs; ?>,
            datasets: [{
                label: 'Okul Başarı Yüzdesi (%)',
                data: <?php echo $datajs; ?>,
                backgroundColor: '#673ab7',
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            scales: {
                y: { beginAtZero: true, max: 100 }
            }
        }
    });
</script>

<?php
echo $OUTPUT->footer();
