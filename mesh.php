<pre>
<?
/*
  dugnadsnett.no - Find nearest geographical nodes for 'N' nodes

  TODO:

  - Use real distance calculations, not just absolute difference in linear 2D space.
  http://www.linuxjournal.com/magazine/work-shell-calculating-distance-between-two-latitudelongitude-points

*/

class Mesh {
  var $comment;
  var $k;
  var $last_seen;
  var $latitude;
  var $longitude;
  var $mac;
}

$file = file_get_contents("https://raw.github.com/petterreinholdtsen/meshfx-node/master/oslo-nodes.csv");
$list = array();

$data = str_getcsv($file, "\n"); //parse the rows
foreach($data as &$row) {
  $row = str_getcsv($row, "\t"); //parse the items in rows
  if ($row[0]=="longitude") {
    continue;
  } else {
    $m = new Mesh;
    $m->comment = $row[3];
    $m->k = sha256($row[0].$row[1].$row[2].$row[3].$row[4]);
    $m->last_seen = $row[4];
    $m->latitude = $row[1];
    $m->longitude = $row[0];
    $m->mac = $row[2];
    $r = array_push($list,$m);
  }
}

if (isset($_GET['lat']) && isset($_GET['lon'])) {
  $location->latitude = $_GET['lat'];
  $location->longitude = $_GET['lon'];
} else {
  $location->latitude = '59.91911';
  $location->longitude = '10.75206';
}

foreach($list as $node) {
  $x[$node->k] = abs((float)$location->latitude-(float)$node->latitude);
  $y[$node->k] = abs((float)$location->longitude-(float)$node->longitude);
  $z[$node->k] = abs((float)($x[$node->k])+(float)($y[$node->k]));
}

asort($z, SORT_NUMERIC);

if (isset($_GET['nodes'])) {
  $n = $_GET['nodes'];
  if ($n > sizeof($z)) {
    $n = sizeof($z);
  }
} else {
  $n = sizeof($z);
}

$keys = array_slice($z, 0, $n, true);

foreach($keys as $node => $key) {
  $m = new Mesh;
  $m->k = $node;
  foreach($list as $host) {
    if ($host->k == $m->k) {
      $m = $host;
      print $m->comment . "\t" . $m->last_seen . "\t" . $m->latitude . "\t" . $m->longitude . "\t" . $m->mac . "\n";
    }
  }
}
exit(0);
?>
</pre>
