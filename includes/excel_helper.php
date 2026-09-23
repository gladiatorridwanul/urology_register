<?php
/**
 * Excel Export Helper — uses SimpleXLSXGen
 * Single-file library, no Composer, no dependencies.
 */

require_once __DIR__ . '/../lib/SimpleXLSXGen.php';

use Shuchkin\SimpleXLSXGen;

/**
 * Null-safe display.
 */
function excelVal($v, $dash = '—') {
    if (is_null($v) || $v === '') return $dash;
    return (string)$v;
}

/**
 * Sanitize a value for Excel (strips leading =, +, -, @ to avoid formula injection).
 */
function excelSafe($v) {
    if ($v === null || $v === '') return '';
    $v = (string)$v;
    // Strip dangerous prefix
    if (preg_match('/^[=+\-@]/', $v)) {
        $v = "'" . $v;
    }
    return $v;
}

/**
 * Send headers and stream the XLSX file to browser.
 *
 * @param array  $sheets    Array of ['Sheet Name' => [ [...row1...], [...row2...] ]]
 * @param string $filename  Output filename (without .xlsx)
 */
function downloadXlsx($sheets, $filename) {
    if (ob_get_length()) ob_end_clean();

    // Build the XLSX object
    $xlsx = SimpleXLSXGen::fromArray([]);

    // SimpleXLSXGen supports multi-sheet via ->addSheet()
    $first = true;
    foreach ($sheets as $sheetName => $rows) {
        if ($first) {
            $xlsx = SimpleXLSXGen::fromArray($rows, $sheetName);
            $first = false;
        } else {
            $xlsx->addSheet($rows, $sheetName);
        }
    }

    $xlsx->downloadAs($filename . '.xlsx');
    exit;
}

/**
 * Build a title/metadata row for sheet header.
 */
function excelTitleBlock($title, $subtitle = '', $meta = '') {
    $rows = [
        [$title],
    ];
    if ($subtitle) $rows[] = [$subtitle];
    if ($meta)     $rows[] = [$meta];
    $rows[] = [];   // blank
    return $rows;
}
?>