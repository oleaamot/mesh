<?

/*
  Earth

  http://nssdc.gsfc.nasa.gov/planetary/factsheet/earthfact.html
  Equatorial radius (km)          6378.1    
  Polar radius (km)               6356.8         
  Volumetric mean radius (km)     6371.0

  http://en.wikipedia.org/wiki/Earth_radius
  For Earth, the authalic radius is 6,371.0072 kilometers (3,958.7603 mi). [1]

  [1] Moritz, H. (March 2000). "Geodetic Reference System 1980". Journal of Geodesy 74 (1): 128.133.
  Bibcode:2000JGeod..74..128.. doi:10.1007/s001900050278

*/

$earth_radius = 6371.0072 * 0.6214; /* 1 km = 0.6214 mi */

function torads($degrees) {
	return ($degrees * (M_PI / 180));
}


function miles2kms($miles) { 
	$ratio = 1.609344;  /* 1 mile = 1.609344 km */
	$kms = $miles * $ratio; 
	return $kms;
} 

?>


<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
    "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xmlns:v="urn:schemas-microsoft-com:vml">
  <head>
    <meta http-equiv="content-type" content="text/html; charset=utf-8"/>
    <title>Find nearest geographical nodes for 'N' nodes</title>
        <!--link rel="stylesheet" href="http://dev.openlayers.org/releases/OpenLayers-2.13.1/theme/default/style.css" type="text/css" / -->
        <!-- link rel="stylesheet" href="http://dev.openlayers.org/releases/OpenLayers-2.13.1/examples/style.css" type="text/css" / -->
    <style type="text/css">
    <!--
	 table { font-family: Verdana; font-size: 9px; }
         tr { text-align: left; }
    -->
    </style>
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

  $lat1 = torads($location->latitude);
  $long1 = torads($location->longitude);

  $lat2 = torads($node->latitude);
  $long2 = torads($node->longitude);

  $dlat = abs($lat2 - $lat1);
  $dlong = abs($long2 - $long1);

  $a = sin($dlat/2) * sin($dlat/2) + cos($lat1) * cos($lat2) * sin($dlong/2) * sin($dlong/2);
  $c[$node->k] = 2 * atan2(sqrt($a), sqrt(1-$a));

  $l[$node->k] = $earth_radius * $c[$node->k];
  

}

asort($l, SORT_NUMERIC);

if (isset($_GET['nodes'])) {
  $n = ($_GET['nodes']);
  if ($n > sizeof($z)) {
    $n = sizeof($z);
  }
} else {
  $n = sizeof($z);
}

$keys = array_slice($l, 0, $n, true);

?>
<table valign=top>
<tr>
<td valign="top">

<h1>Find nearest geographical mesh nodes</h1>

<p><b><a href="https://github.com/oleaamot/mesh">mesh</a> is based on data from Petter Reinholdtsen<? echo "'";?>s <a href="https://github.com/petterreinholdtsen/meshfx-node">meshfx-node</a> module on <a href="https://github.com/">github.com</a></b>.</p>

<form method="get" action="mesh.php">
<table>
<tr>
<td>Nodes: <input size=5 type="text" value="<? echo $n; ?>" name="nodes" /></td>
<td>Lon: <input size=7 type="text" value="<? echo $location->longitude; ?>" name="lon" /></td>
<td>Lat: <input size=7 type="text" value="<? echo $location->latitude; ?>" name="lat" /></td>
<td><input type="submit" value="Update" /></td></tr>
</table>
</form>

<table valign=top>
<tr>
<td valign=top>
<h2>Nearest Nodes</h2>

<table><tr><th>comment</th><th>last_seen</th><th>lon</th><th>lat</th><th>MAC</th></th><th>distance</tr>
<?
foreach($keys as $node => $key) {
  $m = new Mesh;
  $m->k = $node;
  foreach($list as $host) {
    if ($host->k == $m->k) {
      $m = $host;
      print "  <tr>\n    <td>" . $m->comment . "</td><td>" . $m->last_seen . "</td><td><a href='?nodes=" . $n . "&lat=" . $m->latitude . "&lon=" . $m->longitude . "'>" . $m->longitude . "</a></td><td><a href='?nodes=" . $n . "&lat=" . $m->latitude . "&lon=" . $m->longitude . "'>" . $m->latitude . "</td><td>" . $m->mac . "</td><td>~"; printf("%2.0f", (1000*miles2kms($l[$m->k]))); print " meters</td>\n  </tr>\n";
    }
  }
}
?>
  </table>
  <div id="mapdiv" style="width: 600px; height: 300px"></div>
  <script src="http://www.openlayers.org/api/OpenLayers.js"></script>
  <script>
    map = new OpenLayers.Map("mapdiv");
    map.addLayer(new OpenLayers.Layer.OSM());
    
    epsg4326 =  new OpenLayers.Projection("EPSG:4326"); //WGS 1984 projection
    projectTo = map.getProjectionObject(); //The map projection (Spherical Mercator)
   
var lonLat = new OpenLayers.LonLat( <? echo $location->longitude; ?> ,<? echo $location->latitude; ?> ).transform(epsg4326, projectTo);
          
    
    var zoom=14;
    map.setCenter (lonLat, zoom);

    var vectorLayer = new OpenLayers.Layer.Vector("Overlay");
    

<?
// print "<table cellspacing=5 cellpadding=5 border=1><tr><th>comment</th><th>last_seen</th><th>latitude</th><th>longitude</th><th>MAC</th></tr>";

foreach($keys as $node => $key) {
  $m = new Mesh;
  $m->k = $node;
  foreach($list as $host) {
    if ($host->k == $m->k) {
      $m = $host;

      // echo $m->k;

      echo "var feature = new OpenLayers.Feature.Vector(
            new OpenLayers.Geometry.Point( " . $m->longitude . ", " . $m->latitude . ").transform(epsg4326, projectTo),
            {description:'comment:" . $m->comment . "<br />MAC:" . $m->mac . "<br />last_seen:" . $m->last_seen . "'} ,
            {externalGraphic: 'mesh.png', graphicHeight: 25, graphicWidth: 21, graphicXOffset:-12, graphicYOffset:-25  }
        ); vectorLayer.addFeatures(feature);\n";
      
      // print "<tr><td>" . $m->comment . "</td><td>" . $m->last_seen . "</td><td><a href='?nodes=" . $n . "&lat=" . $m->latitude . "&lon=" . $m->longitude . "'>" . $m->latitude . "</a></td><td><a href='?nodes=" . $n . "&lat=" . $m->latitude . "&lon=" . $m->longitude . "'>" . $m->longitude . "</td><td>" . $m->mac . "</td></tr>\n";
    }
  }
    }
?>

    map.addLayer(vectorLayer);
 
    //Add a selector control to the vectorLayer with popup functions
    var controls = {
      selector: new OpenLayers.Control.SelectFeature(vectorLayer, { onSelect: createPopup, onUnselect: destroyPopup })
    };

    function createPopup(feature) {
      feature.popup = new OpenLayers.Popup.FramedCloud("pop",
          feature.geometry.getBounds().getCenterLonLat(),
          null,
          '<div class="markerContent">'+feature.attributes.description+'</div>',
          null,
          true,
          function() { controls['selector'].unselectAll(); }
      );
      //feature.popup.closeOnMove = true;
      map.addPopup(feature.popup);
    }

    function destroyPopup(feature) {
      feature.popup.destroy();
      feature.popup = null;
    }
    
    map.addControl(controls['selector']);
    controls['selector'].activate();
      
  </script>
</td>
<td valign="top">

<h2>Full Node List</h2>

<table><tr><th>comment</th><th>last_seen</th><th>lon</th><th>lat</th><th>MAC</th></tr>
<?
foreach($list as $host) {
  $node = new Mesh;
  $node = $host;
  print "<tr><td>" . $node->comment . "</td><td>" . $node->last_seen . "</td><td><a href='?nodes=" . $n . "&lon=" . $node->longitude . "&lat=" . $node->latitude . "'>" . $node->longitude . "</a></td><td><a href='?nodes=" . $n . "&lon=" . $node->longitude . "&lat=" . $node->latitude . "'>" . $node->latitude . "</a></td><td>" . $node->mac . "</td></tr>\n";
}
?>
</table>
</td>
</tr>
</table>
  </body>
</html>
<?
exit(0);
?>
