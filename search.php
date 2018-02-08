<?php 
include('global.inc');
siteheader('Search Results');
navbar("index.php");

if ($_GET['q'] == '') {
        echo "<p>You must enter at least one search term</p>";
        die();
}

if (strlen($_GET['q']) < 3)
{
	echo '<p>Your search string was too short, please try again</p>';
	die();
}

echo '<br><p>Search Results<br>Tap a song to submit it</p>';

$terms = explode(' ',$_GET['q']);
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
                $wherestring .= " AND (combined LIKE \"%" . $term . "%\") AND(artist != 'DELETED'))";
            }
        }

} else {
	echo "<li>You must enter at least one search term</li>";
	die();
}

$entries = null;
$res = array();
    $sql = "SELECT song_id,artist,title,combined FROM songdb $wherestring ORDER BY UPPER(artist), UPPER(title)";
    foreach ($db->query($sql) as $row)
        {
	if ((stripos($row['combined'],'wvocal') === false) && (stripos($row['combined'],'w-vocal') === false) && (stripos($row['combined'],'vocals') === false)) {
		$res[$row['song_id']] = $row['artist'] . " - " . $row['title'];
	}
        }
    $db = null;

$unique = array_unique($res);

foreach ($unique as $key => $val) {
	$entries[] = "<tr><td class=result onclick=\"submitreq(${key})\">" . $val . "</td></tr>";
}
if (count($unique) > 0) {
	echo '<table border=1>';
	foreach ($entries as $song) {
		echo $song;
	}
	echo '</table>';
} else {
	echo "<p>Sorry, no match found.</p>";
}

sitefooter();
?> 
