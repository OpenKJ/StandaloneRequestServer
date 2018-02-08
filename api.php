<?php
include_once("global.inc");
$json = file_get_contents("php://input");
$data = json_decode($json,true);
$command = $data['command'];

if ($command == '')
{
	exit();
}

// API stuff for songbook mobile apps

if ($command == "venueExists")
{
	$venueUrlName = $data['venueUrlName'];
	$exists = venueExists($venueUrlName);
	$output = array('command'=>$command,'error'=>'false', 'exists'=>$exists);
        print(json_encode($output,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES));
        exit();

}

if ($command == "venueAccepting")
{
	if (getAccepting())
        	$output = array('command'=>$command,'accepting'=>true);
	else
		$output = array('command'=>$command,'accepting'=>false);
        header('Content-type: application/json');
        print(json_encode($output,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES));
        exit();
}

if ($command == "submitRequest")
{
	$songId = $data['songId'];
	$singerName = $data['singerName'];
	$sql = "SELECT artist,title FROM songdb WHERE song_id = $songId";
	foreach ($db->query($sql) as $row) {
        	$artist = $row['artist'];
        	$title = $row['title'];
	}
	$stmt = $db->prepare("INSERT INTO requests (singer,artist,title) VALUES(:singerName, :artist, :title)");
	$stmt->execute(array(":singerName" => $singerName, ":artist" => $artist, ":title" => $title));
	newSerial();
	$output = array('command'=>$command,'error'=>'false', 'success'=>true);
        print(json_encode($output,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES));
	exit();
}

if ($command == "search")
{
	$terms = explode(' ',$data['searchString']);
	$no = count($terms);
	$wherestring = '';
	if ($no == 1) {
	        $wherestring = "WHERE (combined LIKE \"%" . $terms[0] . "%\")";
	} elseif ($no >= 2) { 
	        foreach ($terms as $i => $term) {
		     if ($i == 0) {
	                $wherestring .= "WHERE ((combined LIKE \"%" . $term . "%\")";
	            }
	            if (($i > 0) && ($i < $no - 1)) {
	                $wherestring .= " AND (combined LIKE \"%" . $term . "%\")";
	            }
	            if ($i == $no - 1) {
	                $wherestring .= " AND (combined LIKE \"%" . $term . "%\") AND(artist<>'DELETED'))";
	            }
	        }
	} else {
	        $wherestring = "";
	}
	$entries = null;
	$res = array();
	$sql = "SELECT song_id,artist,title,combined FROM songdb $wherestring ORDER BY UPPER(artist), UPPER(title)";
	foreach ($db->query($sql) as $row)
	{
	    if ((stripos($row['combined'],'wvocal') === false) && (stripos($row['combined'],'w-vocal') === false) && (stripos($row['combined'],'vocals') === false)) {
	            $res[] = array('song_id'=>$row['song_id'],'artist'=>$row['artist'],'title'=>$row['title']);
	    }
	}
	$output = array("command" => "search", "songs" => $res);
	header('Content-type: application/json');
	print(json_encode($output,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES));
	exit();
}





// API stuff for OpenKJ application

if ($command == "clearDatabase")
{
	$db->exec("DELETE FROM songdb");
	$db->exec("DELETE FROM requests");
	$newSerial = newSerial();
	$output = array('command'=>$command,'error'=>'false', 'serial'=>newSerial());
	print(json_encode($output,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES));
	exit();
}

function error($error_string) {
        header('Content-type: application/json');
        print(json_encode(array('command'=>$command,'error'=>'true','errorString'=>$error_string),JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES));
	exit();
}

if ($command == "clearRequests")
{
	$db->exec("DELETE FROM requests");
        $output = array('command'=>$command,'error'=>'false', 'serial'=>newSerial());
        print(json_encode($output,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES));
	exit();
}

if ($command == "deleteRequest")
{
	$request_id = $data['request_id'];
	$stmt = $db->prepare("DELETE FROM requests WHERE request_id = :requestId");
	$stmt->execute(array(":requestId" => $request_id));
        $output = array('command'=>$command,'error'=>'false', 'serial'=>newSerial());
        print(json_encode($output,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES));
	exit();
}

if ($command == "connectionTest")
{
	header('Content-type: application/json');
	$output = array('command'=>$command,'connection'=>'ok');
	print(json_encode($output,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES));
	exit();
}
if ($command == "addSongs")
{
	$stmt = $db->prepare("INSERT OR IGNORE INTO songdb (artist, title, combined) VALUES (:artist, :title, :combined)");
	$db->beginTransaction();
	$errors = array();
	$count = 0;
	$artist = "";
	$title = "";
	$combined = "";
	$error = "false";
	foreach ($data['songs'] as $song)
	{
		$artist = $song['artist'];
		$title = $song['title'];
		$combined = $artist . " " . $title;
		$inarray = array(":artist" => $artist, ":title" => $title, ":combined" => $combined);
		$result = $stmt->execute($inarray);
		if ($result === false)
		{
			$errors[] = $db->errorInfo();
			$error = "true";
		}
		$count++;
	}
	$result = $db->commit();
	if ($result == false)
		$errors[] = $db->errorInfo();
	$output['command'] = $command;
	$output['error'] = $error;
	$output['errors'] = $errors;
	$output['entries processed'] = $count;
	$output['last_artist'] = $artist;
	$output['last_title'] = $title;
	header('Content-type: application/json');
	print(json_encode($output,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES));
	exit();
}

if ($command == "getSerial")
{
	$output = array('command'=>$command,'serial'=>getSerial(),'error'=>'false');
	header('Content-type: application/json');
	print(json_encode($output,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES));
	exit();
}

if ($command == "getAccepting")
{
	$accepting = getAccepting();
	$output = array('command'=>$command,'accepting'=>$venue['accepting'],'venue_id'=>0);
	header('Content-type: application/json');
	print(json_encode($output,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES));
	exit();
}

if ($command == "setAccepting")
{
	$accepting = (bool)$data['accepting'];
        setAccepting($accepting);
	$newSerial = newSerial();
	$output = array('command'=>$command,'error'=>'false','venue_id'=>0,'accepting'=>$accepting,'serial'=>$newSerial);
        print(json_encode($output,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES));
        exit();
}

if ($command == "getVenues")
{
	$output = getVenues();
	$output['command'] = $command;
	$output['error'] = 'false';
	header('Content-type: application/json');
        print(json_encode($output,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES));
	exit();
}

if ($command == "getRequests")
{
	$serial = getSerial();
	$output = getRequests();
	$output['command'] = $command;
	$output['error'] = 'false';
	$output['serial'] = $serial;
	header('Content-type: application/json');
	print(json_encode($output,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES));
	exit();
}

?>
