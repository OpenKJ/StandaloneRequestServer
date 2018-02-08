<?php 
include('global.inc');
#header("Refresh: 15; URL=");
siteheader("Song Submitted");



$songid = $_GET['songid'];
$singer = $_GET['singer'];
if ($singer == '') {
	navbar($_SERVER['HTTP_REFERER']);
	echo "<p>Sorry, you must input a singer name.  Please go back and try again.</p>";
	die();

}
navbar("index.php");
$entries = null;
$wherestring = null;
$artist = '';
$title = '';
$sql = "SELECT artist,title FROM songdb WHERE song_id = $songid";
foreach ($db->query($sql) as $row) {
	$artist = $row['artist'];
	$title = $row['title'];
}
$stmt = $db->prepare("INSERT INTO requests (singer,artist,title) VALUES(:singer, :artist, :title)");
$stmt->execute(array(":singer" => $singer, ":artist" => $artist, ":title" => $title));
newSerial();
echo "<p>Song: $artist - $title</p>
      <p>Submitted for singer: $singer</p>
	<br><p>Please press back to return to the main screen</p>
";

sitefooter();
?> 
