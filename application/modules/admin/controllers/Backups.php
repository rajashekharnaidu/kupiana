<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Backups extends Admin_Controller
{
	protected $active_menu = 'settings.backup';
	protected $required_permission = 'backups.manage';

	public function index()
	{
		$this->breadcrumb('Backups');
		$this->render('backups', array(
			'page_title' => 'Backups',
			'backups' => $this->db->from('backups')->where('deleted_at IS NULL', NULL, FALSE)->order_by('created_at', 'DESC')->get()->result(),
		));
	}

	public function create()
	{
		$config = $this->config->item('upload', 'app');
		$dir = rtrim($config['base_path'].$config['paths']['backups'], '/').'/';
		if ( ! is_dir($dir)) { mkdir($dir, 0755, TRUE); }
		$filename = 'kupiana-db-'.date('Ymd-His').'.sql';
		$path = $dir.$filename;
		$database = $this->db->database;
		$username = $this->db->username;
		$password = $this->db->password;
		$hostname = $this->db->hostname;
		$cmd = '/Applications/XAMPP/bin/mysqldump --single-transaction --quick --host='.escapeshellarg($hostname).' --user='.escapeshellarg($username);
		if ($password !== '') { $cmd .= ' --password='.escapeshellarg($password); }
		$cmd .= ' '.escapeshellarg($database).' > '.escapeshellarg($path).' 2>&1';
		$started = date('Y-m-d H:i:s');
		$status = 'completed'; $error = NULL;
		exec($cmd, $output, $code);
		if ($code !== 0 || ! is_file($path)) { $status = 'failed'; $error = substr(implode("\n", $output), 0, 500); }
		$this->db->insert('backups', array(
			'filename' => $filename,
			'path' => $path,
			'size_bytes' => is_file($path) ? filesize($path) : 0,
			'type' => 'database',
			'backup_status' => $status,
			'error_message' => $error,
			'started_at' => $started,
			'completed_at' => date('Y-m-d H:i:s'),
			'status' => 'active',
			'created_at' => $started,
			'updated_at' => date('Y-m-d H:i:s'),
			'created_by' => (int) $this->session->userdata('user_id') ?: NULL,
			'updated_by' => (int) $this->session->userdata('user_id') ?: NULL,
		));
		$this->audit->log('backup_create', 'backups', (int) $this->db->insert_id(), 'Database backup created.', array('status' => $status));
		$this->session->set_flashdata($status === 'completed' ? 'success' : 'error', $status === 'completed' ? 'Backup created.' : 'Backup failed.');
		redirect('admin/backups');
	}

	public function download($id = NULL)
	{
		$backup = $this->backup((int) $id);
		if ( ! $backup || ! is_file($backup->path)) { show_404(); }
		$this->audit->log('backup_download', 'backups', $backup->id, 'Backup downloaded.');
		$this->output->set_content_type('application/sql')->set_header('Content-Disposition: attachment; filename="'.$backup->filename.'"')->set_output(file_get_contents($backup->path));
	}

	public function restore($id = NULL)
	{
		$backup = $this->backup((int) $id);
		if ( ! $backup || ! is_file($backup->path)) { show_404(); }
		if ((string) $this->input->post('confirm', TRUE) !== $backup->filename)
		{
			$this->session->set_flashdata('error', 'Type the backup filename to confirm restore.');
			redirect('admin/backups');
		}
		$cmd = '/Applications/XAMPP/bin/mysql --host='.escapeshellarg($this->db->hostname).' --user='.escapeshellarg($this->db->username);
		if ($this->db->password !== '') { $cmd .= ' --password='.escapeshellarg($this->db->password); }
		$cmd .= ' '.escapeshellarg($this->db->database).' < '.escapeshellarg($backup->path).' 2>&1';
		exec($cmd, $output, $code);
		$this->audit->log('backup_restore', 'backups', $backup->id, 'Backup restore attempted.', array('success' => $code === 0));
		$this->session->set_flashdata($code === 0 ? 'success' : 'error', $code === 0 ? 'Backup restored.' : 'Backup restore failed.');
		redirect('admin/backups');
	}

	protected function backup($id)
	{
		return $this->db->from('backups')->where('id', (int) $id)->where('deleted_at IS NULL', NULL, FALSE)->get()->row();
	}
}
