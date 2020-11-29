<?php
include("settings.inc");
$db = new PDO("sqlite:$dbFilePath");

$db->exec("CREATE TABLE IF NOT EXISTS songdb (song_id integer PRIMARY KEY AUTOINCREMENT, artist text, title  TEXT, combined TEXT UNIQUE)"); 
$db->exec("CREATE TABLE IF NOT EXISTS state (accepting bool, serial integer NOT NULL)");
$db->exec("INSERT OR IGNORE INTO state (rowid,accepting,serial) VALUES(0,0,1)");
$db->exec("CREATE UNIQUE INDEX IF NOT EXISTS idx_songstrings ON songdb(combined)");
$db->exec("CREATE TABLE IF NOT EXISTS requests (request_id integer PRIMARY KEY AUTOINCREMENT, artist TEXT, title TEXT, singer TEXT, request_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP)");

$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$songdbtable = "songdb";
$requeststable = "requests";

if (isset($_SERVER['REFERER'])) $referer = $_SERVER['REFERER']; 
function siteheader($title) 
{
	global $venueName;
	global $screensize;
	echo "<html><head>
		<link href='https://fonts.googleapis.com/css?family=Audiowide' rel='stylesheet'>
		<link href='https://fonts.googleapis.com/css?family=Roboto' rel='stylesheet'>
		<title>$venueName Songbook</title>
		<link rel=stylesheet type=\"text/css\" href=venuestyle.css />
		<script type=\"text/javascript\">
    			function submitreq(varid){
        			window.location = \"./submitreq.php?id=\" + varid;
    			}
		</script>
	      </head><body>";
}

function sitefooter() {
	echo "<a href='https://github.com/OpenKJ/StandaloneRequestServer'>Developed by OpenKJ</a></div></body></html>";
}

function navbar($backurl) 
{
	if ($backurl == "")
		$backurl = index.php;
	global $screensize;
	global $venueName;
	echo "<div class=navbar>
	<span class=title>$venueName Songbook</span>
	</div><div class=mainbody><span class=backbtn><a class=button href=\"$backurl\" class=navbar id=backlink>Back</a></span>";
}

function setAccepting($accepting)
{
	global $db;
	if ($accepting == 1)
	{
		echo("setting accepting to 1");
		$db->exec("UPDATE state SET accepting=1");
	}
	else
	{
		echo("setting accepting to 0");
		$db->exec("UPDATE state SET accepting=0");
	}
}

function getAccepting()
{
	global $db;
	$accepting = false;
        foreach ($db->query("SELECT accepting FROM state LIMIT 1") as $row)
        {
                $accepting = $row['accepting'];
        }
	return $accepting;
}

function searchform() 
{
	global $db;
	global $venue_id;
	if (!getAccepting())
	{
		echo "<br><br><h2>Sorry, requests are not being accepted at this time</h2>";
	}
	else
	{
		global $url_name;
		global $screensize;
		echo "<br><p><form method=get action=search.php>Song search: <input type=text name=q autofocus autocomplete=off><input type=submit value=Search></form></p>";
		echo '<p class=info>You may enter any part of the artist and/or title of the song.  Partial words are allowed.</p>
			<p class=info>For example "pai bla stone" would match "Rolling Stones, The - Paint it black".</p>';
	}
}



function getSerial()
{
	global $db;
	$serial = -1;
	foreach ($db->query("SELECT serial FROM state LIMIT 1") as $row)
        {
        	$serial = (int)$row['serial'];
        }
	return $serial;
}

function newSerial()
{
        global $db;
        $serial = getSerial();
        $newSerial = mt_rand(0,99999);
        while ($newSerial == $serial)
        {
                $newSerial = mt_rand(0,99999);
        }
        $db->exec("UPDATE state SET serial=$newSerial");
        return $newSerial;
}

function getVenue()
{
	// We don't really do multiple venues in standalone, just fake it
        global $db;
	global $venueName;
        $serial = -1;
	$venue = array();
	$venue['venue_id'] = $venue_id;
        $venue['accepting'] = getAccepting();
	$venue['name'] = $venueName;
	$venue['url_name'] = "none";
	return $venue;
}

function getVenues()
{
	// We don't really do multiple venues in standalone, just fake it
	global $db;
	global $venueName;
	$venues = array();
	$venue['venue_id'] = 0;
        $venue['accepting'] = getAccepting();
        $venue['name'] = $venueName;
	$venue['url_name'] = "none";
	$venues['venues'][] = $venue;
	return $venues;
}

function getRequests()
{
        global $db;
        $requests = array();
        $result = $db->query("SELECT request_id,artist,title,singer,strftime('%s', request_time) AS unixtime FROM requests");
	if ($result)
	{
        	foreach ($result as $row)
        	{
        	        $request['request_id'] = (int)$row['request_id'];
        	        $request['artist'] = $row['artist'];
        	        $request['title'] = $row['title'];
        	        $request['singer'] = $row['singer'];
        	        $request['request_time'] = (int)$row['unixtime'];
        	        $requests['requests'][] = $request;
        	}
	}
        return $requests;
}


?>
