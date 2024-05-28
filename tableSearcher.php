<?php
/* This script is meant to be used as an include. Provide the $scriptDir and the $dataFile before including
Example:

<?php
$scriptDir = 'whereami';
$dataFile = 'enrollmentStatus.txt';
include $scriptDir . 'php/tableSearcher.php';
?>
*/

// Check for required variables
if (!isset($dataFile) || !isset($scriptDir)) {
    http_response_code(404);
    echo '<h1>404 Not Found</h1><p>The document/file requested was not found on this server.</p>';
    die();
}

// Set title if not provided
$title = $title ?? (basename(dirname(__FILE__)) . ' ' . ucfirst(basename(__FILE__, '.php')));

// Load configuration
$ini = parse_ini_file($scriptDir . 'php/php.ini');

// Error reporting
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Initialize CAS
include_once 'CAS.php';
phpCAS::client(CAS_VERSION_2_0, 'cas.host.here', 443, 'cas');
phpCAS::setNoCasServerValidation();
phpCAS::forceAuthentication();

// Validate user
$username = phpCAS::getUser() ?? die("Unable to validate user");
$username = strtolower($username);

// Handle logout
if (isset($_REQUEST['logout']) || !in_array($username, $ini['access'])) {
    echo 'Processing logout...<br>';
    session_destroy();
    phpCAS::logout();
} elseif (session_id() == '' || !isset($_SESSION)) {
    session_start();
}

// JS and CSS sources
$js_sources = [
    '//code.jquery.com/jquery-3.5.1.js',
    '//maxcdn.bootstrapcdn.com/bootstrap/3.2.0/js/bootstrap.min.js',
    '//cdn.datatables.net/1.10.21/js/jquery.dataTables.min.js',
    '//cdn.datatables.net/1.10.21/js/dataTables.bootstrap4.min.js',
];

$css_sources = [
    '//maxcdn.bootstrapcdn.com/bootstrap/3.2.0/css/bootstrap.min.css',
];

echo '<html><head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>' . htmlspecialchars($title) . '</title>';

foreach ($js_sources as $js_source) {
    echo '<script type="text/javascript" src="' . htmlspecialchars($js_source) . '"></script>';
}

echo <<<JS
<script>
$(document).ready(function () {
    $('.display').each(function() {
        var entry = this.id;

        $('#' + entry + ' tfoot th').each(function () {
            var title = $('#' + entry + ' thead th').eq($(this).index()).text();
            $(this).html('<input type="text" placeholder="Search ' + title + '" />');
        });

        var table = $('#' + entry).DataTable({
            "lengthMenu": [[10, 25, 50, -1], [10, 25, 50, "All"]],
            "pagingType": "full_numbers"
        });

        table.columns().eq(0).each(function (colIdx) {
            $('input', table.column(colIdx).footer()).on('keyup change', function () {
                table.column(colIdx).search(this.value).draw();
            });
        });
    });
});
</script>
JS;

foreach ($css_sources as $css_source) {
    echo '<link rel="stylesheet" type="text/css" href="' . htmlspecialchars($css_source) . '">';
}

echo <<<CSS
<style>
th, td {
    text-align: left;
    padding: 8px;
    white-space: pre-wrap;
    word-wrap: break-word;
}

tr:nth-child(even) {
    background-color: #f2f2f2;
}

th {
    background-color: #00529b;
    color: #c8daea;
}

tfoot {
    display: table-header-group;
}
</style>
CSS;

echo '</head><body>';

$table = '<table id="example" class="table display table-striped table-bordered">';
$line_num = 0;
$header = '';

if (($f = fopen($scriptDir . $dataFile, 'r')) !== false) {
    while (($line = fgetcsv($f)) !== false) {
        if ($line_num == 0) {
            foreach ($line as $cell) {
                $header .= '<th>' . htmlspecialchars($cell) . '</th>';
            }
            $table .= '<thead><tr>' . $header . '</tr></thead><tfoot><tr>' . $header . '</tr></tfoot><tbody>';
        } else {
            $table .= '<tr>';
            foreach ($line as $cell) {
                $table .= '<td>';
                if (strpos($cell, '@') !== false) {
                    $table .= "<a href='mailto:" . htmlspecialchars($cell) . "'>" . htmlspecialchars($cell) . '</a>';
                } else {
                    $table .= htmlspecialchars($cell);
                }
                $table .= '</td>';
            }
            $table .= '</tr>';
        }
        $line_num++;
    }
    fclose($f);
}

$table .= '</tbody></table>';
echo $table . '</body></html>';
?>
