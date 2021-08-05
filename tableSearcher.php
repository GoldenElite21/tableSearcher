<?php
/* This script is meant to be used as an include. Provide the $scriptDir and the $dataFile before including
Example:

<?php
$scriptDir = 'whereami';
$dataFile = 'enrollmentStatus.txt';
include $scriptDir . 'php/tableSearcher.php';
?>
*/

// INI
if (!isset($dataFile) or !isset($scriptDir)){
  http_response_code(404);
  echo '<h1>404 Not Found</h1><p>The document/file requested was not found on this server.</p>';
  die();
}

$title = $title ?: (basename(dirname(__FILE__)) . ' ' . ucfirst(basename(__FILE__, '.php')));

$ini = parse_ini_file($scriptDir . 'php/php.ini');

// Error reporting
ini_set('display_errors',1);
error_reporting(E_ALL);

include_once('CAS.php');
phpCAS::client(CAS_VERSION_2_0,'cas.host.here',443,'cas');
phpCAS::setNoCasServerValidation();
phpCAS::forceAuthentication();
/* should now be logged in */

$username = phpCAS::getUser() or die("Unable to validate user");
$username = strtolower($username);

// Add logic for 'logout'
if ((isset($_REQUEST['logout'])) || !in_array($username, $ini['access'])) {
  echo 'Processing logout..<br>';
  session_destroy();
  phpCAS::logout();
} elseif(session_id() == '' || !isset($_SESSION)) {
  session_start();
}

$js_sources = array(
  '//code.jquery.com/jquery-3.5.1.js',
  '//maxcdn.bootstrapcdn.com/bootstrap/3.2.0/js/bootstrap.min.js',
  '//cdn.datatables.net/1.10.21/js/jquery.dataTables.min.js',
  '//cdn.datatables.net/1.10.21/js/dataTables.bootstrap4.min.js',
);

$css_sources = array(
  '//maxcdn.bootstrapcdn.com/bootstrap/3.2.0/css/bootstrap.min.css',
);


echo '<html><body><head>
   <meta charset="UTF-8">
   <meta name="viewport" content="width=device-width, initial-scale=1">
   <title>' . $title . '</title>';

foreach ($js_sources as $js_source){
  echo '<script type="text/javascript" src="' . $js_source . '"></script>';
}

echo <<<tableSearcherJS
<script>
$(document).ready(function () {
  $('.display').each(function() {
    entry = this.id;

    // Setup - add a text input to each footer cell
    $('#' + entry + ' tfoot th').each(function () {
      var title = $('#' + entry + ' thead th').eq($(this).index()).text();
      $(this).html('<input type="text" placeholder="Search ' + title + '" />');
    });

    // DataTable
    var table = $('#' + entry).DataTable({
      "lengthMenu": [[10, 25, 50, -1], [10, 25, 50, "All"]],
      "pagingType": "full_numbers"
    });

    // Apply the search
    table.columns().eq(0).each(function (colIdx) {
      $('input', table.column(colIdx).footer()).on('keyup change', function () {
        table.column(colIdx).search(this.value).draw();
      });
    });
  });
});
</script>
tableSearcherJS;

foreach ($css_sources as $css_source){
  echo '<link rel="stylesheet" type="text/css" href="' . $css_source . '">';
}

echo <<<tableSearcherCSS
<style>

th, td {
  text-align: left;
  padding: 8px;
    white-space: -moz-pre-wrap !important;  /* Mozilla, since 1999 */
    white-space: -webkit-pre-wrap;          /* Chrome & Safari */ 
    white-space: -pre-wrap;                 /* Opera 4-6 */
    white-space: -o-pre-wrap;               /* Opera 7 */
    white-space: pre-wrap;                  /* CSS3 */
    word-wrap: break-word;                  /* Internet Explorer 5.5+ */
    word-break: break-all;
    white-space: normal;
}

tr:nth-child(even){background-color: #f2f2f2}

th {
  background-color: #00529b;
  color: #c8daea;
}

tfoot {
  display: table-header-group;
}
</style>
tableSearcherCSS;

echo '</head>';

$table = '<table id="example" class="table display table-striped table-bordered">';
$line_num = 0;
$header = '';

$f = fopen($scriptDir . $dataFile, 'r');
while (($line = fgetcsv($f)) !== false) {
  if($line_num == 0){
    foreach ($line as $cell) {
      $header .= '<th>' . htmlspecialchars($cell) . '</th>';
    }
    $table .= '<thead><tr>' . $header . '</tr></thead><tfoot><tr>' . $header . '</tr></foot><tbody>';
  } else {
    $table .= '<tr>';
    foreach ($line as $cell) {
      $table .= '<td>';
      if (strpos($cell, '@') !== FALSE){ #list of emails
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

$table .= '</tbody></table>';
echo $table . '</body></html>'

?>

