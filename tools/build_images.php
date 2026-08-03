<?php
/**
 * Kupiana storefront imagery builder.
 *
 * Turns the photographs in tools/source/ into the watermarked, correctly sized
 * JPEGs the storefront serves, then prints a manifest.
 *
 * Usage:
 *     /Applications/XAMPP/bin/php tools/build_images.php            # build everything
 *     /Applications/XAMPP/bin/php tools/build_images.php products   # one group
 *     /Applications/XAMPP/bin/php tools/build_images.php --sheet    # QA contact sheet
 *
 * REPLACING THESE WITH YOUR OWN PHOTOGRAPHY
 * -----------------------------------------
 * Drop a photo into tools/source/, point the recipe's `src` at it and set
 * `cover => true` to centre-crop the whole frame. Re-run this script and the
 * files the database already references are overwritten in place — no SQL
 * changes needed. That is the whole reason this is a script and not a one-off.
 *
 * @package Kupiana\Tools
 */

require __DIR__.'/image_lib.php';

$ROOT   = dirname(__DIR__);
$SRC    = __DIR__.'/source';
$OUT    = $ROOT.'/public/uploads';
$LOGO   = $ROOT.'/public/assets/images/kupiana-logo-512.png';

/*
| Crop map -------------------------------------------------------------------
|
| `crop` is [centreX, centreY, width, height] in SOURCE pixel space, so the
| numbers stay meaningful no matter what output size is requested.
| `cover` centre-crops the whole frame instead (for photos that are already
| composed). `focus` nudges the vertical centre of a cover crop, 0..1.
|
| The spice products all crop out of ONE flatlay photograph. That is
| deliberate: identical lighting, background and white balance across the whole
| product grid is what makes a catalogue look art-directed rather than
| assembled from stock.
*/
$recipes = array(

	/* ---- Products: 1000x1000 -------------------------------------- */
	'products/organic-lakadong-turmeric-powder.jpg' => array(
		'src' => 'flatlay.jpg', 'crop' => array(4582, 2557, 760, 760), 'w' => 1000, 'h' => 1000),
	'products/malabar-black-pepper-whole.jpg' => array(
		'src' => 'flatlay.jpg', 'crop' => array(3090, 2430, 620, 620), 'w' => 1000, 'h' => 1000),
	'products/kashmiri-chilli-powder.jpg' => array(
		'src' => 'flatlay.jpg', 'crop' => array(4275, 3300, 720, 720), 'w' => 1000, 'h' => 1000),
	'products/organic-garam-masala-blend.jpg' => array(
		'src' => 'flatlay.jpg', 'crop' => array(3555, 3600, 600, 600), 'w' => 1000, 'h' => 1000),
	'products/organic-cumin-seeds.jpg' => array(
		'src' => 'flatlay.jpg', 'crop' => array(3450, 1807, 580, 580), 'w' => 1000, 'h' => 1000),
	'products/ginger-garlic-spice-mix.jpg' => array(
		'src' => 'flatlay.jpg', 'crop' => array(1027, 1890, 1620, 1620), 'w' => 1000, 'h' => 1000),
	'products/green-cardamom-pods.jpg' => array(
		'src' => 'cardamom.jpg', 'cover' => TRUE, 'w' => 1000, 'h' => 1000),

	// Coconut oil is shot as its source ingredient, which is both accurate and
	// visually distinct. Groundnut and sesame still share one bottle shoot
	// (wide vs. tight crop) — flagged in PROJECT_STATUS.md as the remaining
	// gap needing real product photography.
	'products/cold-pressed-groundnut-oil.jpg' => array(
		'src' => 'oilbottle.jpg', 'crop' => array(1500, 1900, 2100, 2100), 'w' => 1000, 'h' => 1000),
	'products/wood-pressed-sesame-oil.jpg' => array(
		'src' => 'oilbottle.jpg', 'crop' => array(1470, 1080, 1150, 1150), 'w' => 1000, 'h' => 1000),
	'products/virgin-coconut-oil.jpg' => array(
		'src' => 'coconut.jpg', 'crop' => array(1150, 880, 1500, 1500), 'w' => 1000, 'h' => 1000),

	/* ---- Categories: 800x600 -------------------------------------- */
	'categories/organic-spices.jpg' => array(
		'src' => 'flatlay.jpg', 'crop' => array(3400, 2000, 3600, 2700), 'w' => 800, 'h' => 600),
	'categories/cold-pressed-oils.jpg' => array(
		'src' => 'oilbottle.jpg', 'crop' => array(1500, 1900, 2600, 1950), 'w' => 800, 'h' => 600),
	'categories/whole-spices.jpg' => array(
		'src' => 'flatlay.jpg', 'crop' => array(2600, 1500, 2200, 1650), 'w' => 800, 'h' => 600),
	'categories/ground-masalas.jpg' => array(
		'src' => 'flatlay.jpg', 'crop' => array(4100, 3200, 2200, 1650), 'w' => 800, 'h' => 600),
	'categories/turmeric-ginger.jpg' => array(
		'src' => 'flatlay.jpg', 'crop' => array(1100, 1900, 2000, 1500), 'w' => 800, 'h' => 600),
	'categories/pepper-cardamom.jpg' => array(
		'src' => 'flatlay.jpg', 'crop' => array(3200, 2350, 1800, 1350), 'w' => 800, 'h' => 600),
	'categories/sesame-groundnut-oils.jpg' => array(
		'src' => 'oilbottle.jpg', 'crop' => array(1500, 1200, 2200, 1650), 'w' => 800, 'h' => 600),
	'categories/coconut-mustard-oils.jpg' => array(
		'src' => 'flatlay.jpg', 'crop' => array(2790, 1050, 1500, 1125), 'w' => 800, 'h' => 600),
	'categories/herbal-blends.jpg' => array(
		'src' => 'flatlay.jpg', 'crop' => array(2445, 3500, 1900, 1425), 'w' => 800, 'h' => 600),
	'categories/pantry-staples.jpg' => array(
		'src' => 'market.jpg', 'cover' => TRUE, 'focus' => 0.42, 'w' => 800, 'h' => 600),

	/* ---- Banners: 2000x760 ---------------------------------------- */
	'banners/organic-spices-hero.jpg' => array(
		'src' => 'flatlay.jpg', 'crop' => array(3300, 2050, 5000, 1900), 'w' => 2000, 'h' => 760,
		'mark' => 'bottom-left', 'markpct' => 0.13),
	'banners/cold-pressed-oils.jpg' => array(
		'src' => 'oilbottle.jpg', 'crop' => array(1500, 1900, 2900, 1102), 'w' => 2000, 'h' => 760,
		'mark' => 'bottom-left', 'markpct' => 0.13),
	'banners/cleaner-pantry.jpg' => array(
		'src' => 'market.jpg', 'crop' => array(1500, 2000, 2900, 1102), 'w' => 2000, 'h' => 760,
		'mark' => 'bottom-left', 'markpct' => 0.13),
);

/* Storefront hero lives in assets, not uploads. */
$assetRecipes = array(
	'store-hero.jpg' => array(
		'src' => 'flatlay.jpg', 'crop' => array(3500, 2100, 3800, 2850), 'w' => 1400, 'h' => 1050,
		'mark' => 'bottom-right', 'markpct' => 0.20),
	'og-default.jpg' => array(
		'src' => 'flatlay.jpg', 'crop' => array(3300, 2050, 4800, 2520), 'w' => 1200, 'h' => 630,
		'mark' => 'bottom-right', 'markpct' => 0.18),
);

// ---------------------------------------------------------------------------

$only  = NULL;
$sheet = FALSE;

foreach (array_slice($argv, 1) as $arg) {
	if ($arg === '--sheet') { $sheet = TRUE; } else { $only = $arg; }
}

$logoKeyed = img_key_white(img_load($LOGO));

$built = array();

/**
 * Render one recipe to disk.
 */
function build_one($name, $conf, $SRC, $destDir, $logoKeyed, &$built)
{
	$srcPath = $SRC.'/'.$conf['src'];
	$src     = img_load($srcPath);

	if ( ! empty($conf['cover'])) {
		$im = img_cover($src, $conf['w'], $conf['h'], isset($conf['focus']) ? $conf['focus'] : 0.5);
	} else {
		list($cx, $cy, $cw, $ch) = $conf['crop'];
		$im = img_crop_resize($src, $cx, $cy, $cw, $ch, $conf['w'], $conf['h']);
	}

	img_polish($im);

	img_watermark(
		$im,
		$logoKeyed,
		isset($conf['mark']) ? $conf['mark'] : 'bottom-right',
		isset($conf['markpct']) ? $conf['markpct'] : 0.24
	);

	$dest  = $destDir.'/'.$name;
	$bytes = img_save_jpeg($im, $dest, 86);

	$built[$name] = array('bytes' => $bytes, 'w' => $conf['w'], 'h' => $conf['h']);

	printf("  %-52s %4dx%-4d %6.1f KB\n", $name, $conf['w'], $conf['h'], $bytes / 1024);

	imagedestroy($im);
	imagedestroy($src);
}

echo "Building storefront imagery\n";
echo str_repeat('-', 78), "\n";

foreach ($recipes as $name => $conf) {
	if ($only !== NULL && strpos($name, $only) !== 0) {
		continue;
	}

	build_one($name, $conf, $SRC, $OUT, $logoKeyed, $built);
}

if ($only === NULL || $only === 'assets') {
	foreach ($assetRecipes as $name => $conf) {
		build_one($name, $conf, $SRC, dirname(__DIR__).'/public/assets/images', $logoKeyed, $built);
	}
}

echo str_repeat('-', 78), "\n";
printf("%d images written, %.1f MB total\n", count($built), array_sum(array_column($built, 'bytes')) / 1048576);

/* Optional QA contact sheet so the whole set can be eyeballed at once. */
if ($sheet) {
	$files = array();

	foreach (array_keys($recipes) as $n) { $files[$n] = $OUT.'/'.$n; }
	foreach (array_keys($assetRecipes) as $n) { $files[$n] = dirname(__DIR__).'/public/assets/images/'.$n; }

	$cols = 5; $cell = 240; $pad = 6; $lab = 26;
	$rows = (int) ceil(count($files) / $cols);
	$W = $cols * ($cell + $pad) + $pad;
	$H = $rows * ($cell + $pad + $lab) + $pad;

	$s = imagecreatetruecolor($W, $H);
	imagefill($s, 0, 0, imagecolorallocate($s, 248, 248, 248));
	$blk = imagecolorallocate($s, 20, 20, 20);

	$i = 0;

	foreach ($files as $n => $p) {
		if ( ! is_file($p)) { continue; }

		$im = img_load($p);
		$t  = img_cover($im, $cell, $cell);
		$c = $i % $cols; $r = (int) ($i / $cols);
		$x = $pad + $c * ($cell + $pad); $y = $pad + $r * ($cell + $pad + $lab);

		imagecopy($s, $t, $x, $y, 0, 0, $cell, $cell);
		imagestring($s, 3, $x + 2, $y + $cell + 2, substr(basename($n, '.jpg'), 0, 34), $blk);
		imagestring($s, 2, $x + 2, $y + $cell + 14, dirname($n), $blk);

		imagedestroy($im); imagedestroy($t);
		$i++;
	}

	imagejpeg($s, '/tmp/kupiana_images.jpg', 88);
	echo "QA sheet: /tmp/kupiana_images.jpg\n";
}
