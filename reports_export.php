
<?php
require_once __DIR__ . '/auth.php';

/*
|--------------------------------------------------------------------------
| MANAGER ACCESS ONLY
|--------------------------------------------------------------------------
*/
require_role(['Manager']);

/*
|--------------------------------------------------------------------------
| DATE FILTERS
|--------------------------------------------------------------------------
*/
$from = $_GET['from'] ?? date('Y-m-01');
$to   = $_GET['to'] ?? date('Y-m-d');

/*
|--------------------------------------------------------------------------
| VALIDATE DATE INPUTS
|--------------------------------------------------------------------------
*/
$fromDate = DateTime::createFromFormat('!Y-m-d', $from);
$toDate   = DateTime::createFromFormat('!Y-m-d', $to);

if (
    !$fromDate ||
    $fromDate->format('Y-m-d') !== $from ||
    !$toDate ||
    $toDate->format('Y-m-d') !== $to ||
    $from > $to
) {
    http_response_code(400);
    exit('Invalid date range.');
}

/*
|--------------------------------------------------------------------------
| CSV DOWNLOAD HEADERS
|--------------------------------------------------------------------------
*/
$filename = 'forgeflow-report-' . $from . '-' . $to . '.csv';

header('Content-Type: text/csv; charset=utf-8');
header(
    'Content-Disposition: attachment; filename="' . $filename . '"'
);
header('Cache-Control: no-store, no-cache, must-revalidate');
header('Pragma: no-cache');

/*
|--------------------------------------------------------------------------
| OPEN CSV OUTPUT
|--------------------------------------------------------------------------
*/
$out = fopen('php://output', 'w');

if ($out === false) {
    http_response_code(500);
    exit('Unable to generate CSV report.');
}

/*
|--------------------------------------------------------------------------
| REPORT DATE RANGE
|--------------------------------------------------------------------------
*/
fputcsv($out, [
    'Report',
    'From',
    $from,
    'To',
    $to
]);

fputcsv($out, []);

/*
|--------------------------------------------------------------------------
| INQUIRY REPORT
|--------------------------------------------------------------------------
*/
fputcsv($out, [
    'Inquiry ID',
    'Client ID',
    'Project Type',
    'Status',
    'Location',
    'Created'
]);

$stmt = $pdo->prepare(
    'SELECT
        id,
        client_id,
        project_type,
        status,
        location,
        created_at
     FROM inquiries
     WHERE DATE(created_at) BETWEEN ? AND ?
     ORDER BY created_at'
);

$stmt->execute([$from, $to]);

while ($row = $stmt->fetch(PDO::FETCH_NUM)) {
    fputcsv($out, $row);
}

fputcsv($out, []);

/*
|--------------------------------------------------------------------------
| PROJECT REPORT
|--------------------------------------------------------------------------
*/
fputcsv($out, [
    'Project ID',
    'Client ID',
    'Title',
    'Status',
    'Location',
    'Due Date'
]);

$stmt = $pdo->prepare(
    'SELECT
        id,
        client_id,
        title,
        status,
        location,
        due_date
     FROM projects
     WHERE DATE(created_at) BETWEEN ? AND ?
     ORDER BY created_at'
);

$stmt->execute([$from, $to]);

while ($row = $stmt->fetch(PDO::FETCH_NUM)) {
    fputcsv($out, $row);
}

/*
|--------------------------------------------------------------------------
| CLOSE CSV AND FINISH DOWNLOAD
|--------------------------------------------------------------------------
*/
fclose($out);
exit;
?>