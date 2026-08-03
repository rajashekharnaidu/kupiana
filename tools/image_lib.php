<?php
/**
 * Kupiana image toolkit.
 *
 * Shared GD routines for the storefront imagery pipeline: crop, resize,
 * logo watermarking and JPEG output.
 *
 * CLI only — never routed through the web application.
 *
 * @package Kupiana\Tools
 */

if (PHP_SAPI !== 'cli') {
	exit("This toolkit is CLI only.\n");
}

/**
 * Load an image from disk regardless of format (JPEG/PNG/WebP/GIF).
 *
 * @param  string $path
 * @return resource|GdImage
 */
function img_load($path)
{
	if ( ! is_file($path)) {
		throw new RuntimeException("Source not found: $path");
	}

	$im = @imagecreatefromstring(file_get_contents($path));

	if ( ! $im) {
		throw new RuntimeException("Unreadable image: $path");
	}

	return $im;
}

/**
 * Crop a square (or arbitrary aspect) region around a centre point, then
 * resample to the requested output size.
 *
 * Coordinates are given in SOURCE pixel space so the crop map stays readable
 * and independent of the output dimensions.
 *
 * @param  resource|GdImage $src
 * @param  int   $cx      Centre X in source pixels.
 * @param  int   $cy      Centre Y in source pixels.
 * @param  int   $cw      Crop width in source pixels.
 * @param  int   $ch      Crop height in source pixels.
 * @param  int   $outW    Output width.
 * @param  int   $outH    Output height.
 * @return resource|GdImage
 */
function img_crop_resize($src, $cx, $cy, $cw, $ch, $outW, $outH)
{
	$sw = imagesx($src);
	$sh = imagesy($src);

	// Keep the requested region inside the source bounds.
	$cw = min($cw, $sw);
	$ch = min($ch, $sh);
	$x  = (int) max(0, min($sw - $cw, $cx - $cw / 2));
	$y  = (int) max(0, min($sh - $ch, $cy - $ch / 2));

	$out = imagecreatetruecolor($outW, $outH);
	imageinterlace($out, 1);
	imagecopyresampled($out, $src, 0, 0, $x, $y, $outW, $outH, (int) $cw, (int) $ch);

	return $out;
}

/**
 * Cover-fit an entire image into the output box (centre crop, no distortion).
 *
 * @param  resource|GdImage $src
 * @param  int $outW
 * @param  int $outH
 * @param  float $focusY 0..1 vertical focal point; 0.5 is centre.
 * @return resource|GdImage
 */
function img_cover($src, $outW, $outH, $focusY = 0.5)
{
	$sw = imagesx($src);
	$sh = imagesy($src);

	$scale = max($outW / $sw, $outH / $sh);
	$cw    = (int) round($outW / $scale);
	$ch    = (int) round($outH / $scale);

	$x = (int) round(($sw - $cw) / 2);
	$y = (int) round(($sh - $ch) * $focusY);

	$out = imagecreatetruecolor($outW, $outH);
	imageinterlace($out, 1);
	imagecopyresampled($out, $src, 0, 0, $x, $y, $outW, $outH, $cw, $ch);

	return $out;
}

/**
 * Turn a logo with a solid light background into one with transparency.
 *
 * The supplied Kupiana logo is an opaque PNG on white, so it cannot simply be
 * composited over a photo. Every pixel brighter than $threshold on all three
 * channels becomes transparent, and near-threshold pixels get partial alpha so
 * the curved edges of the mark stay smooth instead of jagged.
 *
 * @param  resource|GdImage $logo
 * @param  int $threshold
 * @return resource|GdImage
 */
function img_key_white($logo, $threshold = 232)
{
	$w = imagesx($logo);
	$h = imagesy($logo);

	$out = imagecreatetruecolor($w, $h);
	imagealphablending($out, FALSE);
	imagesavealpha($out, TRUE);
	imagefill($out, 0, 0, imagecolorallocatealpha($out, 0, 0, 0, 127));

	// Feather band: pixels between $soft and $threshold fade out gradually.
	$soft = $threshold - 26;

	for ($y = 0; $y < $h; $y++) {
		for ($x = 0; $x < $w; $x++) {
			$rgb = imagecolorat($logo, $x, $y);
			$r = ($rgb >> 16) & 0xFF;
			$g = ($rgb >> 8) & 0xFF;
			$b = $rgb & 0xFF;

			$lum = max($r, $g, $b);

			if ($lum >= $threshold) {
				continue; // fully transparent
			}

			$alpha = 0;

			if ($lum > $soft) {
				$alpha = (int) round(127 * (($lum - $soft) / ($threshold - $soft)));
			}

			imagesetpixel($out, $x, $y, imagecolorallocatealpha($out, $r, $g, $b, $alpha));
		}
	}

	return $out;
}

/**
 * Scale an image preserving its alpha channel.
 *
 * @param  resource|GdImage $src
 * @param  int $w
 * @param  int $h
 * @return resource|GdImage
 */
function img_scale_alpha($src, $w, $h)
{
	$out = imagecreatetruecolor($w, $h);
	imagealphablending($out, FALSE);
	imagesavealpha($out, TRUE);
	imagefill($out, 0, 0, imagecolorallocatealpha($out, 0, 0, 0, 127));
	imagecopyresampled($out, $src, 0, 0, 0, 0, $w, $h, imagesx($src), imagesy($src));

	return $out;
}

/**
 * Draw a filled rounded rectangle with alpha directly onto an image.
 *
 * @param  resource|GdImage $im
 * @param  int   $x
 * @param  int   $y
 * @param  int   $w
 * @param  int   $h
 * @param  int   $radius
 * @param  array $rgb   [r,g,b]
 * @param  int   $alpha 0 (opaque) .. 127 (invisible)
 * @return void
 */
function img_rounded_rect($im, $x, $y, $w, $h, $radius, array $rgb, $alpha)
{
	$c = imagecolorallocatealpha($im, $rgb[0], $rgb[1], $rgb[2], $alpha);
	$d = $radius * 2;

	imagefilledrectangle($im, $x + $radius, $y, $x + $w - $radius, $y + $h, $c);
	imagefilledrectangle($im, $x, $y + $radius, $x + $w, $y + $h - $radius, $c);
	imagefilledellipse($im, $x + $radius, $y + $radius, $d, $d, $c);
	imagefilledellipse($im, $x + $w - $radius, $y + $radius, $d, $d, $c);
	imagefilledellipse($im, $x + $radius, $y + $h - $radius, $d, $d, $c);
	imagefilledellipse($im, $x + $w - $radius, $y + $h - $radius, $d, $d, $c);
}

/**
 * Composite the Kupiana logo onto an image as a brand watermark.
 *
 * The mark sits on a soft translucent plate. Without it the dark-brown logo
 * disappears against dark photography and the green leaves vanish against the
 * herbs — the plate guarantees legibility on any background while still
 * reading as a deliberate brand device rather than a stamp.
 *
 * @param  resource|GdImage $canvas
 * @param  resource|GdImage $logoKeyed Logo with transparency (see img_key_white).
 * @param  string $position  bottom-right|bottom-left|top-right|top-left|bottom-center
 * @param  float  $widthPct  Logo width as a fraction of canvas width.
 * @return void
 */
function img_watermark($canvas, $logoKeyed, $position = 'bottom-right', $widthPct = 0.20)
{
	$cw = imagesx($canvas);
	$ch = imagesy($canvas);

	$lw = (int) round($cw * $widthPct);
	$lw = max(64, min($lw, (int) round($cw * 0.42)));
	$lh = (int) round($lw * imagesy($logoKeyed) / imagesx($logoKeyed));

	$plotPad = (int) round($lw * 0.10);

	// Pad the PLATE, not the logo — measuring from the logo leaves the plate
	// flush against the image edge.
	$pad = (int) round($cw * 0.030) + $plotPad;

	switch ($position) {
		case 'bottom-left':   $x = $pad; $y = $ch - $lh - $pad; break;
		case 'top-right':     $x = $cw - $lw - $pad; $y = $pad; break;
		case 'top-left':      $x = $pad; $y = $pad; break;
		case 'bottom-center': $x = (int) (($cw - $lw) / 2); $y = $ch - $lh - $pad; break;
		default:              $x = $cw - $lw - $pad; $y = $ch - $lh - $pad;
	}

	imagealphablending($canvas, TRUE);

	// Translucent plate behind the mark.
	img_rounded_rect(
		$canvas,
		$x - $plotPad, $y - $plotPad,
		$lw + $plotPad * 2, $lh + $plotPad * 2,
		(int) round($lw * 0.09),
		array(255, 253, 248),
		42
	);

	$scaled = img_scale_alpha($logoKeyed, $lw, $lh);
	imagecopy($canvas, $scaled, $x, $y, 0, 0, $lw, $lh);
	imagedestroy($scaled);
}

/**
 * Gently lift contrast and warmth so images from different shoots sit together.
 *
 * @param  resource|GdImage $im
 * @return void
 */
function img_polish($im)
{
	// Kept deliberately light. Several product crops are enlarged from a
	// region of a larger frame, and a heavier contrast curve on an upscaled
	// crop reads as over-processed rather than crisp.
	imagefilter($im, IMG_FILTER_CONTRAST, -3);
	imagefilter($im, IMG_FILTER_BRIGHTNESS, 3);
}

/**
 * Write a JPEG, creating the directory if needed.
 *
 * @param  resource|GdImage $im
 * @param  string $path
 * @param  int    $quality
 * @return int Bytes written.
 */
function img_save_jpeg($im, $path, $quality = 86)
{
	$dir = dirname($path);

	if ( ! is_dir($dir)) {
		mkdir($dir, 0755, TRUE);
	}

	imagejpeg($im, $path, $quality);

	return filesize($path);
}
