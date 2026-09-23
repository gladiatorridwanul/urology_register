<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
requireLogin();

if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
    require_once __DIR__ . '/../vendor/autoload.php';
} elseif (file_exists(__DIR__ . '/../dompdf/autoload.inc.php')) {
    require_once __DIR__ . '/../dompdf/autoload.inc.php';
} else { die("Dompdf not installed"); }

use Dompdf\Dompdf;
use Dompdf\Options;

$patient_id = intval($_GET['patient_id'] ?? 0);
$p = $conn->query("SELECT * FROM patients WHERE id=$patient_id")->fetch_assoc();
if (!$p) die("Patient not found");

$records = $conn->query("SELECT * FROM discharge_summary WHERE patient_id=$patient_id ORDER BY id");

$html = '<html><head><meta charset="UTF-8"><style>
body { font-family: DejaVu Sans; font-size: 11px; }
.page-break { page-break-after: always; }
h1 { color:#2563eb; text-align:center; }
h6 { background:#eef2ff; padding:5px; color:#1e40af; margin-top:12px; }
table.info { width:100%; }
table.info td { padding:3px 6px; font-size:10.5px; }
table.info td:first-child { font-weight:bold; width:130px; color:#475569; }
</style></head><body>';

$first = true;
while ($d = $records->fetch_assoc()) {
    if (!$first) $html .= '<div class="page-break"></div>';
    $first = false;
    $html .= '
    <h1>Discharge Summary</h1>
    <table class="info">
        <tr><td>Name:</td><td>'.htmlspecialchars($p['name']).'</td><td>URO ID:</td><td>'.htmlspecialchars($p['uro_id']).'</td></tr>
        <tr><td>Age / Sex:</td><td>'.$p['age'].' / '.$p['sex'].'</td><td>Mobile:</td><td>'.htmlspecialchars($p['mobile']).'</td></tr>
        <tr><td>Admission:</td><td>'.$d['admission_date'].'</td><td>Discharge:</td><td>'.$d['discharge_date'].'</td></tr>
    </table>
    <h6>Diagnosis</h6><p>'.nl2br(htmlspecialchars($d['diagnosis'])).'</p>
    <h6>Investigations</h6><p>'.nl2br(htmlspecialchars($d['relevant_investigations'])).'</p>
    <h6>Operation</h6>
    <table class="info">
        <tr><td>Operation:</td><td>'.htmlspecialchars($d['operation_name']).'</td></tr>
        <tr><td>Date:</td><td>'.$d['operation_date'].'</td></tr>
        <tr><td>Findings:</td><td>'.nl2br(htmlspecialchars($d['operative_findings'])).'</td></tr>
    </table>
    <h6>Medication</h6><p>'.nl2br(htmlspecialchars($d['medication'])).'</p>
    <h6>Advice</h6><p>'.nl2br(htmlspecialchars($d['advice'])).'</p>';
}
$html .= '</body></html>';

$options = new Options();
$options->set('defaultFont', 'DejaVu Sans');
$dompdf = new Dompdf($options);
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();
$dompdf->stream('All_Discharges_'.$p['uro_id'].'.pdf', ['Attachment' => true]);
exit;
?>