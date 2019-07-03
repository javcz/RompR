<?php
chdir('../..');
include ("includes/vars.php");
include ("includes/functions.php");
include ("international.php");
include ("getid3/getid3.php");

$fname = $_POST['file'];
$fname = preg_replace('/local:track:/','',$fname);
$fname = preg_replace('#file://#','',$fname);
$fname = 'prefs/MusicFolders/'.$fname;
$artist = $_POST['artist'];
$song = $_POST['song'];

$getID3 = new getID3;
$output = null;
logger::mark("LYRICS", "Looking for lyrics in",$fname);
logger::log("LYRICS", "  Artist is",$artist);
logger::log("LYRICS", "  Song is",$artist);

if (file_exists($fname)) {
	logger::log("LYRICS", "    File Exists");
	$tags = $getID3->analyze($fname);
	getid3_lib::CopyTagsToComments($tags);

	if (array_key_exists('comments', $tags) &&
			array_key_exists('lyrics', $tags['comments'])) {
		$output = $tags['comments']['lyrics'][0];
	} else if (array_key_exists('comments', $tags) &&
				array_key_exists('unsynchronised_lyric', $tags['comments'])) {
		$output = $tags['comments']['unsynchronised_lyric'][0];
	} else if (array_key_exists('quicktime', $tags) &&
				array_key_exists('moov', $tags['quicktime']) &&
				array_key_exists('subatoms', $tags['quicktime']['moov'])) {
		read_apple_awfulness($tags['quicktime']['moov']['subatoms']);
	}
}

if ($output == null) {
	$uri = "http://lyrics.wikia.com/api.php?func=getSong&artist=".urlencode($artist)."&song=".urlencode($song)."&fmt=xml";
	logger::mark("LYRICS", "Trying",$uri);
	$d = new url_downloader(array(
		'url' => $uri,
		'cache' => 'lyrics',
		'return_data' => true
	));
	if ($d->get_data_to_file()) {
		$l = simplexml_load_string($d->get_data());
		if ($l->url) {
			logger::log("LYRICS", "  Now Getting",html_entity_decode($l->url));
			$d2 = new url_downloader(array(
				'url' => html_entity_decode($l->url),
				'cache' => 'lyrics',
				'return_data' => true
			));
			if ($d2->get_data_to_file()) {
				if (preg_match('/\<div class=\'lyricbox\'\>\<script\>.*?\<\/script\>(.*?)\<\!--/', $d2->get_data(), $matches)) {
					$output = html_entity_decode($matches[1]);
				} else if (preg_match('/\<div class=\'lyricbox\'\>(.*?)\<div class=\'lyricsbreak\'\>/', $d2->get_data(), $matches)) {
					$output = html_entity_decode($matches[1]);
				} else {
					logger::mark("LYRICS", "    Could Not Find Lyrics");
				}
			}
		} else {
			logger::mark("LYRICS", "  Nope, nothing there");
		}
	}
} else {
	logger::mark("LYRICS", "  Got lyrics from file");
}

if ($output == null) {
	$output = '<h3 align=center>'.get_int_text("lyrics_nonefound").'</h3><p>'.get_int_text("lyrics_info").'</p>';
}

print $output;

function read_apple_awfulness($a) {
	// Whoever came up with this was on something.
	// All we want to do is read some metadata...
	// why do you have to store it in such a horrible, horrible, way?
	global $output;
	foreach ($a as $atom) {
		if (array_key_exists('name', $atom)) {
			if (preg_match('/lyr$/', $atom['name'])) {
				$output = preg_replace( '/^.*?data/', '', $atom['data']);
				break;
			}
		}
		if (array_key_exists('subatoms', $atom)) {
			read_apple_awfulness($atom['subatoms']);
		}
	}
}

?>
