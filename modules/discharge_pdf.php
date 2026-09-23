<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
requireLogin();

// ---------- Universal Dompdf Loader ----------
$loaded = false;

// 1) Composer install
if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
    require_once __DIR__ . '/../vendor/autoload.php';
    $loaded = true;
}
// 2) Manual install (lib/dompdf/)
elseif (file_exists(__DIR__ . '/../lib/dompdf_autoload.php')) {
    require_once __DIR__ . '/../lib/dompdf_autoload.php';
    $loaded = true;
}
// 3) Legacy Dompdf v1 (dompdf/autoload.inc.php)
elseif (file_exists(__DIR__ . '/../dompdf/autoload.inc.php')) {
    require_once __DIR__ . '/../dompdf/autoload.inc.php';
    $loaded = true;
}
// 4) Dompdf v2 manual (src/Autoloader.php)
elseif (file_exists(__DIR__ . '/../lib/dompdf/src/Autoloader.php')) {
    require_once __DIR__ . '/../lib/dompdf/src/Autoloader.php';
    \Dompdf\Autoloader::register();
    $loaded = true;
}

if (!$loaded) {
    die("
    <h3 style='font-family:sans-serif;color:#dc2626;padding:20px'>Dompdf library not found</h3>
    <p style='font-family:sans-serif;padding:0 20px'>Please install it using one of these methods:</p>
    <ol style='font-family:sans-serif'>
        <li><strong>Composer (recommended):</strong> Run <code>composer require dompdf/dompdf</code> in the project root.</li>
        <li><strong>Manual:</strong> Download <code>dompdf_X.X.X.zip</code> from 
            <a href='https://github.com/dompdf/dompdf/releases' target='_blank'>GitHub releases</a>
            and extract to <code>lib/dompdf/</code>. Also create <code>lib/dompdf_autoload.php</code>.</li>
    </ol>
    ");
}

use Dompdf\Dompdf;
use Dompdf\Options;

$id = intval($_GET['id'] ?? 0);
$d = $conn->query("SELECT d.*, p.name, p.uro_id, p.age, p.sex, p.address, p.mobile, p.unit, p.ward, p.bed, p.blood_group 
                   FROM discharge_summary d 
                   JOIN patients p ON p.id = d.patient_id 
                   WHERE d.id=$id")->fetch_assoc();
if (!$d) die("Discharge summary not found");

$hospital_name = 'Department of Urology';
$hospital_sub  = 'Urology Patient Registry & Clinical Database';

$html = '
<!DOCTYPE html>
<html><head><meta charset="UTF-8"><style>
    @page { margin: 25mm 18mm; }
    body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #1e293b; }
    .header { text-align: center; border-bottom: 3px double #2563eb; padding-bottom: 8px; margin-bottom: 15px; }
    .header h1 { margin: 0; color: #2563eb; font-size: 18px; letter-spacing: .5px; }
    .header p { margin: 2px 0; font-size: 11px; color: #64748b; }
    .section { margin-bottom: 12px; page-break-inside: avoid; }
    .section h6 { background: #eef2ff; color: #1e40af; padding: 5px 10px; margin: 0 0 6px 0; font-size: 11px; border-left: 3px solid #2563eb; }
    .section p { margin: 4px 0; line-height: 1.5; }
    table.info { width: 100%; border-collapse: collapse; }
    table.info td { padding: 3px 6px; font-size: 10.5px; vertical-align: top; }
    table.info td:first-child { font-weight: bold; color: #475569; width: 130px; }
    .signature { margin-top: 50px; text-align: right; }
    .signature .line { display: inline-block; border-top: 1px solid #1e293b; padding-top: 4px; width: 200px; font-size: 10px; }
    .footer { position: fixed; bottom: -10mm; left: 0; right: 0; text-align: center; font-size: 8px; color: #94a3b8; }
    .badge-id { display:inline-block; background:#2563eb; color:#fff; padding: 2px 10px; font-size: 10px; border-radius: 10px; }
</style></head>
<body>

<div class="header">
    <h1>'.htmlspecialchars($hospital_name).'</h1>
    <p>'.htmlspecialchars($hospital_sub).'</p>
    <p><strong>Discharge Summary</strong></p>
</div>

<table class="info">
    <tr><td>Patient Name:</td><td>'.htmlspecialchars($d['name']).'</td>
        <td>URO ID:</td><td><span class="badge-id">'.htmlspecialchars($d['uro_id']).'</span></td></tr>
    <tr><td>Age / Sex:</td><td>'.htmlspecialchars($d['age'].' / '.$d['sex']).'</td>
        <td>Mobile:</td><td>'.htmlspecialchars($d['mobile']).'</td></tr>
    <tr><td>Address:</td><td colspan="3">'.htmlspecialchars($d['address']).'</td></tr>
    <tr><td>Unit:</td><td>'.htmlspecialchars($d['unit']).'</td>
        <td>Ward / Bed:</td><td>'.htmlspecialchars($d['ward'].' / '.$d['bed']).'</td></tr>
    <tr><td>Blood Group:</td><td>'.htmlspecialchars($d['blood_group']).'</td>
        <td>Admission Date:</td><td>'.htmlspecialchars($d['admission_date']).'</td></tr>
    <tr><td>Discharge Date:</td><td>'.htmlspecialchars($d['discharge_date']).'</td>
        <td>Follow-up Date:</td><td>'.htmlspecialchars($d['followup_date'] ?: '—').'</td></tr>
</table>

<div class="section"><h6>Diagnosis</h6><p>'.nl2br(htmlspecialchars($d['diagnosis'])).'</p></div>
<div class="section"><h6>Relevant Investigations</h6><p>'.nl2br(htmlspecialchars($d['relevant_investigations'])).'</p></div>

<div class="section"><h6>Operation Details</h6>
    <table class="info">
        <tr><td>Operation:</td><td>'.htmlspecialchars($d['operation_name']).'</td></tr>
        <tr><td>Date / Time:</td><td>'.htmlspecialchars($d['operation_date'].'  '.$d['operation_time']).'</td></tr>
        <tr><td>Laterality:</td><td>'.htmlspecialchars($d['operation_laterality']).'</td></tr>
        <tr><td>Findings:</td><td>'.nl2br(htmlspecialchars($d['operative_findings'])).'</td></tr>
        <tr><td>Procedure:</td><td>'.nl2br(htmlspecialchars($d['procedure_details'])).'</td></tr>
    </table>
</div>

<div class="section"><h6>Medication on Discharge</h6><p>'.nl2br(htmlspecialchars($d['medication'])).'</p></div>
<div class="section"><h6>Advice</h6><p>'.nl2br(htmlspecialchars($d['advice'])).'</p></div>
<div class="section"><h6>Follow-up Instructions</h6><p>'.nl2br(htmlspecialchars($d['followup_instructions'])).'</p></div>

<div class="signature"><div class="line">Doctor\'s Signature</div></div>
<div class="footer">Generated on '.date('d-m-Y H:i').' — Urology Patient Registry</div>

</body></html>';

$options = new Options();
$options->set('isRemoteEnabled', true);
$options->set('isHtml5ParserEnabled', true);
$options->set('defaultFont', 'DejaVu Sans');

$dompdf = new Dompdf($options);
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();

$filename = 'Discharge_' . $d['uro_id'] . '_' . $d['discharge_date'] . '.pdf';
$dompdf->stream($filename, ['Attachment' => true]);
exit;
?>