<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * CSV and Excel-compatible export helper.
 */
class Export
{
	/**
	 * Download rows as CSV. Excel opens UTF-8 CSV files directly.
	 *
	 * @param string $filename
	 * @param array $headers
	 * @param array $rows
	 * @return void
	 */
	public function csv($filename, array $headers, array $rows)
	{
		if (ob_get_level()) { ob_end_clean(); }
		header('Content-Type: text/csv; charset=utf-8');
		header('Content-Disposition: attachment; filename="'.preg_replace('/[^a-z0-9_.-]/i', '-', $filename).'"');
		$out = fopen('php://output', 'w');
		fputcsv($out, $headers);
		foreach ($rows as $row) { fputcsv($out, $row); }
		fclose($out);
		exit;
	}
}
