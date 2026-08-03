<?php
/**
 * Re-download the source photography used by tools/build_images.php.
 *
 * The source frames are large (~20 MB) and stay out of git, so this script
 * makes them reproducible. Run it once after a fresh clone, then run
 * build_images.php.
 *
 *     /Applications/XAMPP/bin/php tools/fetch_sources.php
 *     /Applications/XAMPP/bin/php tools/build_images.php --sheet
 *
 * Licensing for every entry is recorded in tools/source/CREDITS.md.
 *
 * @package Kupiana\Tools
 */

if (PHP_SAPI !== 'cli') {
	exit("CLI only.\n");
}

$dir = __DIR__.'/source';

if ( ! is_dir($dir)) {
	mkdir($dir, 0755, TRUE);
}

/*
| Unsplash serves any width from one photo id, so the id plus a width is the
| whole recipe. Widths are chosen so the tightest crop in build_images.php
| still has enough pixels for its output size without upscaling badly.
*/
$sources = array(
	'flatlay.jpg'    => array('unsplash', '1596040033229-a9821ebd058d', 6000),
	'oilbottle.jpg'  => array('unsplash', '1474979266404-7eaacbcd87c5', 3000),
	'market.jpg'     => array('unsplash', '1532336414038-cf19250c5757', 3000),
	'coconut.jpg'    => array('unsplash', '1580984969071-a8da5656c2fb', 2600),
	'herbs.jpg'      => array('unsplash', '1615485500704-8e990f9900f7', 2400),
	'spoons.jpg'     => array('unsplash', '1509358271058-acd22cc93898', 3000),
	'chillibowl.jpg' => array('unsplash', '1621939514649-280e2ee25f60', 3000),

	// CC0, found via Openverse and pinned to its direct URL — Openverse search
	// results are not stable enough to re-resolve by query.
	// "Fresh green cardamom pods", Bigul Malayi, CC0.
	// https://wordpress.org/photos/photo/8669274741/
	'cardamom.jpg'   => array('url',
		'https://pd.w.org/2025/11/8669274741e0c540.49865717-2048x2048.jpeg'),
);

$fails = 0;

foreach ($sources as $name => $spec) {
	$dest = $dir.'/'.$name;

	if (is_file($dest) && @getimagesize($dest)) {
		printf("  %-16s already present\n", $name);
		continue;
	}

	$url = ($spec[0] === 'unsplash')
		? 'https://images.unsplash.com/photo-'.$spec[1].'?w='.$spec[2].'&q=90'
		: $spec[1];

	$ch = curl_init($url);
	curl_setopt_array($ch, array(
		CURLOPT_RETURNTRANSFER => TRUE,
		CURLOPT_FOLLOWLOCATION => TRUE,
		CURLOPT_TIMEOUT        => 90,
		CURLOPT_HTTPHEADER     => array('User-Agent: Kupiana image pipeline (local dev)'),
	));

	$body = curl_exec($ch);
	$code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
	curl_close($ch);

	if ($code !== 200 || ! $body) {
		printf("  %-16s FAILED (HTTP %d)\n", $name, $code);
		$fails++;
		continue;
	}

	file_put_contents($dest, $body);
	$size = @getimagesize($dest);

	if ( ! $size) {
		printf("  %-16s FAILED (not an image)\n", $name);
		unlink($dest);
		$fails++;
		continue;
	}

	printf("  %-16s %dx%d  %.1f MB\n", $name, $size[0], $size[1], strlen($body) / 1048576);
}

echo $fails ? "\n$fails source(s) failed — see CREDITS.md for origins.\n" : "\nAll sources present.\n";
