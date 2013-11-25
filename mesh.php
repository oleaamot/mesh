<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
    "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xmlns:v="urn:schemas-microsoft-com:vml">
  <head>
    <meta http-equiv="content-type" content="text/html; charset=utf-8"/>
    <title>Find nearest geographical nodes for 'N' nodes</title>
    <script src="http://maps.google.com/maps?file=api&amp;v=2&amp;key=ABQIAAAAB73G9h9hzrihvhNvqUub7hQ_u28zFqDZptWETgeTYZ8_kUk3BhQON5dM2w1CnP_30L2DvY7VDNh99w"
            type="text/javascript"></script>
        <!--link rel="stylesheet" href="http://dev.openlayers.org/releases/OpenLayers-2.13.1/theme/default/style.css" type="text/css"-->
        <!--link rel="stylesheet" href="http://dev.openlayers.org/releases/OpenLayers-2.13.1/examples/style.css" type="text/css" -->
  </head>
  <body onload="init()">
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
  $n = (int)(1)+(int)($_GET['nodes']);
  if ($n > sizeof($z)) {
    $n = sizeof($z);
  }
} else {
  $n = 3;
}

$keys = array_slice($z, 0, $n, true);

?>
<table>
<tr>
<td valign="top">

<h1>Find nearest geographical mesh nodes</h1>

<p><b>Based on data from Petter Reinholdtsen's <a href="https://github.com/petterreinholdtsen/meshfx-node">meshfx-node</a> module on <a href="https://github.com/">github.com</a></b></p>

<form method="get" action="mesh.php">
<table>
<tr>
<th>Lon</th><td><input size=7 type="text" value="<? echo $location->longitude; ?>" name="lon" /></td>
</tr>
<tr>
<th>Lat</th><td><input size=7 type="text" value="<? echo $location->latitude; ?>" name="lat" /></td>
</tr>
<tr>
<th>Hops</th><td><input size=5 type="text" value="<? echo $n; ?>" name="nodes" /></td>
</tr>
<tr><td>&nbsp;</td><td><input type="submit" value="Find nearest node" /></td></tr>
</table>
</form>

<?
print "<table cellspacing=5 cellpadding=5 border=1><tr><th>comment</th><th>last_seen</th><th>latitude</th><th>longitude</th><th>mac</th></tr>";

foreach($keys as $node => $key) {
  $m = new Mesh;
  $m->k = $node;
  foreach($list as $host) {
    if ($host->k == $m->k) {
      $m = $host;
      print "<tr><td>" . $m->comment . "</td><td>" . $m->last_seen . "</td><td><a href='?nodes=" . $n . "&lat=" . $m->latitude . "&lon=" . $m->longitude . "'>" . $m->latitude . "</a></td><td><a href='?nodes=" . $n . "&lat=" . $m->latitude . "&lon=" . $m->longitude . "'>" . $m->longitude . "</td><td>" . $m->mac . "</td></tr>\n";
    }
  }
}
exit(0);
?>
