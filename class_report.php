// ... (üst kısımlar aynı kalacak)

// 3. Tablo ve Grafik Çıktısı.
echo html_writer::start_tag('table', ['class' => 'generaltable mt-4', 'style' => 'width:100%']);
echo html_writer::start_tag('thead');
echo html_writer::start_tag('tr');
echo html_writer::tag('th', 'Yetkinlik Adı');
echo html_writer::tag('th', 'Kurs Ort.');
echo html_writer::tag('th', 'Sınıf Ort.');
echo html_writer::tag('th', 'Öğrenci Ort.');
echo html_writer::end_tag('tr');
echo html_writer::end_tag('thead');
echo html_writer::start_tag('tbody');

$labels = [];
$courseRates = []; // Düzeltildi
$classRates = [];  // Düzeltildi
$studentRates = []; // Düzeltildi

foreach ($coursedata as $cid => $c) {
    $courseRate = $c->attempts ? round(($c->correct / $c->attempts) * 100, 1) : 0; // Düzeltildi
    $classRate  = (isset($classdata[$cid]) && $classdata[$cid]->attempts)
        ? round(($classdata[$cid]->correct / $classdata[$cid]->attempts) * 100, 1) : 0; // Düzeltildi
    $studRate   = (isset($studentdata[$cid]) && $studentdata[$cid]->attempts)
        ? round(($studentdata[$cid]->correct / $studentdata[$cid]->attempts) * 100, 1) : 0; // Düzeltildi

    echo html_writer::start_tag('tr');
    echo html_writer::tag('td', $c->shortname);
    echo html_writer::tag('td', '%' . $courseRate, ['class' => 'font-weight-bold']); // Düzeltildi
    echo html_writer::tag('td', '%' . $classRate, ['class' => 'text-muted']); // Düzeltildi
    echo html_writer::tag('td', '%' . $studRate, ['class' => 'text-primary font-weight-bold']); // Düzeltildi
    echo html_writer::end_tag('tr');

    $labels[] = $c->shortname;
    $courseRates[] = $courseRate; // Düzeltildi
    $classRates[] = $classRate;   // Düzeltildi
    $studentRates[] = $studRate;  // Düzeltildi
}
echo html_writer::end_tag('tbody');
echo html_writer::end_tag('table');

// Grafik Alanı.
echo html_writer::start_tag('div', ['class' => 'mt-5']);
echo html_writer::tag('canvas', '', ['id' => 'competencyChart', 'height' => '100']);
echo html_writer::end_tag('div');

// ChartJS Script.
$chartJsUrl = 'https://cdn.jsdelivr.net/npm/chart.js'; // Düzeltildi
echo html_writer::script('', $chartJsUrl); // Düzeltildi

$jsLabels = json_encode($labels); // Düzeltildi
$jsCourse = json_encode($courseRates); // Düzeltildi
$jsClass = json_encode($classRates);   // Düzeltildi
$jsStudent = json_encode($studentRates); // Düzeltildi

$script = "
document.addEventListener('DOMContentLoaded', function() {
    var ctx = document.getElementById('competencyChart').getContext('2d');
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: $jsLabels, // Düzeltildi
            datasets: [
                { label: 'Kurs Ort.', data: $jsCourse, backgroundColor: 'rgba(156, 39, 176, 0.6)' }, // Düzeltildi
                { label: 'Sınıf Ort.', data: $jsClass, backgroundColor: 'rgba(76, 175, 80, 0.6)' },  // Düzeltildi
                { label: 'Öğrenci Ort.', data: $jsStudent, backgroundColor: 'rgba(33, 150, 243, 0.6)' } // Düzeltildi
            ]
        },
        options: {
            responsive: true,
            scales: { y: { beginAtZero: true, max: 100 } }
        }
    });
});";
echo html_writer::script($script);

echo $OUTPUT->footer();
