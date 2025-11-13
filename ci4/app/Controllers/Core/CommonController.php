<?php

namespace App\Controllers\Core;

use App\Controllers\BaseController;
use CodeIgniter\Database\BaseBuilder;
use CodeIgniter\Controller;
use App\Models\Core\Common_model;
use App\Models\Amazon_s3_model;
use App\Models\Customers_model;

use App\Models\Users_model;
use PHPUnit\Framework\Constraint\FileExists;
use Config\Services;
use \Firebase\JWT\JWT;
use Firebase\JWT\Key;
use App\Libraries\UUID;

class CommonController extends BaseController
{
	protected $table;
	protected $model;
	protected $businessUuid;
	protected $whereCond;
	protected $Amazon_s3_model;
	protected $rawTblName;
	protected $menucode;
	protected $notAllowedFields = array();

	function __construct()
	{
		parent::__construct();
		$this->session = Services::session();
        if(!$this->session->get('uuid')){
            header('Location:/');die();
        }; 
		$this->businessUuid = session('uuid_business');
		$this->whereCond['uuid_business_id'] = $this->businessUuid;

		$this->model = new Common_model();
		$this->Amazon_s3_model = new Amazon_s3_model();
		$this->changeLanguage();

		$this->table = $this->getTableNameFromUri();
		$this->rawTblName =  substr($this->table, 0, -1);
		if (isset($_GET['cat']) && $_GET['cat'] == 'strategies') {
			$this->menucode = $this->getMenuCode("/" . $this->table . "?cat=strategies");
		} else {
			$this->menucode = $this->getMenuCode("/" . $this->table);
		}
		$this->session->set("menucode", $this->menucode);
		$this->notAllowedFields = array('uuid_business_id', "uuid");

		$permissions = $this->session->get('permissions');
		$uri = current_url(true);
		$currentPath = $uri->getPath();
		if (!empty($permissions)) {
			$user_permissions = array_map(function ($perm) {
				// Remove slashes and convert to lowercase
				// Also replace hyphens with underscores for comparison
				$processed = strtolower(str_replace(["/", "-"], ["", "_"], $perm['link']));
				return $processed;
			}, $permissions);

			// Normalize the table name by replacing hyphens with underscores
			$normalizedTable = str_replace("-", "_", $this->table);

			if (!in_array($normalizedTable, $user_permissions) && $currentPath !== "/dashboard") {
				echo view("errors/html/error_403");
				die;
			}
		} else {
			echo view("errors/html/error_404");
			die;
		}
		$key = Services::getSecretKey();
		try {
			
			$tokenArray = JWT::decode($this->session->get('jwt_token'), new Key($key, 'HS256'));
		}catch (\Firebase\JWT\ExpiredException $e) {
			//print "Error!: " . $e->getMessage();
			header('Location:/home/logout');
			die();
		}
	}

	/**
	 * Check granular permissions (read, create, update, delete)
	 *
	 * @param string $action The action to check: 'read', 'create', 'update', or 'delete'
	 * @return bool True if user has permission, false otherwise
	 */
	protected function checkPermission($action = 'read')
	{
		// Super admin bypass (user ID = 1)
		if (session('id') == 1) {
			return true;
		}

		$permissionMap = $this->session->get('permission_map');

		if (empty($permissionMap)) {
			return false;
		}

		$tableName = $this->table;

		// Get menu ID for this module
		$menu = $this->db->table('menu')
			->where('link', '/' . $tableName)
			->orWhere('link LIKE', '%' . str_replace('_', '-', $tableName) . '%')
			->get()
			->getRow();

		if (!$menu) {
			// Try with hyphen instead of underscore
			$tableNameWithHyphen = str_replace('_', '-', $tableName);
			$menu = $this->db->table('menu')
				->where('link', '/' . $tableNameWithHyphen)
				->orWhere('link LIKE', '%' . $tableNameWithHyphen . '%')
				->get()
				->getRow();
		}

		if (!$menu) {
			return false; // Module not found in menu
		}

		$menuId = $menu->id;

		if (!isset($permissionMap[$menuId])) {
			return false; // No permission entry for this module
		}

		// Default: read permission is always granted if module access exists
		// Other actions (create, update, delete) require explicit permission
		if ($action === 'read') {
			return true; // Default read access for any accessible module
		}

		return $permissionMap[$menuId][$action] ?? false;
	}

	public function changeLanguage()
	{
		$language = Services::language();

		// Priority 1: Check session for user's current language preference
		if ($this->session->has('app_language')) {
			$language->setLocale($this->session->get('app_language'));
			return;
		}

		// Priority 2: Check user's saved language preference in database
		$user_id = $this->session->get('uuid');
		$udata = $this->db->table('users')->select('language_code')->where("id", $user_id)->get()->getRowArray();
		if (!empty($udata) && !empty($udata['language_code'])) {
			$language->setLocale($udata['language_code']);
			$this->session->set('app_language', $udata['language_code']);
			return;
		}

		// Priority 3: Check business default language
		$business = $this->db->table('businesses')->where("uuid", $this->businessUuid)->get()->getRowArray();
		if (!empty($business['language_code'])) {
			$language->setLocale($business['language_code']);
			$this->session->set('app_language', $business['language_code']);
			return;
		}

		// Priority 4: Default to English
		$language->setLocale('en');
		$this->session->set('app_language', 'en');
	}

	public function getTableNameFromUri()
	{

		$uri = service('uri');
		$tableNameFromUri = $uri->getSegment(1);
		return $tableNameFromUri;
	}

	public function index()
	{
		// Check read permission
		if (!$this->checkPermission('read')) {
			echo view("errors/html/error_403");
			die;
		}

		$data['columns'] = $this->db->getFieldNames($this->table);
		$data['fields'] = array_diff($data['columns'], $this->notAllowedFields);
		$data[$this->table] = $this->model->getRows();
		$data['tableName'] = $this->table;
		$data['rawTblName'] = $this->rawTblName;
		$data['is_add_permission'] = 1;
		$data['identifierKey'] = 'id';

		// Pass granular permissions to view
		$data['can_create'] = $this->checkPermission('create');
		$data['can_update'] = $this->checkPermission('update');
		$data['can_delete'] = $this->checkPermission('delete');

		$viewPath = "common/list";
		if (file_exists(APPPATH . 'Views/' . $this->table . "/list.php")) {
			$viewPath = $this->table . "/list";
		}

		return view($viewPath, $data);
	}


	public function edit($uuid = 0)
	{
		// Check update permission for existing record, create permission for new record
		if ($uuid && !$this->checkPermission('update')) {
			echo view("errors/html/error_403");
			die;
		}

		if (!$uuid && !$this->checkPermission('create')) {
			echo view("errors/html/error_403");
			die;
		}

		$tableData =  $uuid ? $this->model->getExistsRowsByUUID($uuid)->getRow() : '';

		$customers = (new Customers_model())
    ->whereIn("id", function (BaseBuilder $subqueryBuilder) {
        return $subqueryBuilder->select("customers_id")->from("projects")->groupBy("customers_id");
    })
    ->where("uuid_business_id", session('uuid_business'))
    ->get()
    ->getResultArray();

		$data['tableName'] = $this->table;
		$data['rawTblName'] = $this->rawTblName;
		$data["users"] = $this->model->getUser();
		$data["contacts"] = $this->model->getContacts();
		$data["projects"] = $this->model->getProjects();
		$data["employees"] = $this->model->getEmployees();
		$data["sprints"] = $this->model->getSprints();
		$data[$this->rawTblName] = $tableData;
		$data["customers"] = $customers;
		// if there any special cause we can overried this function and pass data to add or edit view
		$data['additional_data'] = $this->getAdditionalData($uuid ? $tableData->id : '');
		echo view($this->table . "/edit", $data);
	}

	public function editrow($uuid = 0)
	{
		$data['tableName'] = $this->table;
		$data['rawTblName'] = $this->rawTblName;
		$data["users"] = $this->model->getUser();
		$data["contacts"] = $this->model->getContacts();
		$data[$this->rawTblName] = !empty($uuid)?$this->model->getRowsByUUID($uuid)->getRow():[];
		// if there any special cause we can overried this function and pass data to add or edit view
		if($this->rawTblName=='task'){
			$data['media_list'] = $this->getMediaItems('media_list',$uuid,'id,name');
			$data['documents'] = $this->getMediaItems('documents',$uuid,'id,file');
			//print_r($ddata); die;
		}
		$data['additional_data'] = $this->getAdditionalData($uuid);
		echo view($this->table . "/edit", $data);
	}

	public function update()
	{
		$id = $this->request->getPost('id');
		$uuid = $this->request->getPost('uuid');

		// Check permissions: update for existing records, create for new records
		if ($uuid && !$this->checkPermission('update')) {
			session()->setFlashdata('message', 'You do not have permission to update records in this module!');
			session()->setFlashdata('alert-class', 'alert-danger');
			return redirect()->to('/' . $this->table);
		}

		if (!$uuid && !$this->checkPermission('create')) {
			session()->setFlashdata('message', 'You do not have permission to create records in this module!');
			session()->setFlashdata('alert-class', 'alert-danger');
			return redirect()->to('/' . $this->table);
		}

		$data = $this->request->getPost();
		if (!$data['uuid'] || empty($data['uuid']) || !isset($data['uuid'])) {
			$data['uuid'] = UUID::v5(UUID::v4(), $this->table);
		}
		$response = $this->model->insertOrUpdateByUUID($uuid, $data);
		if (!$response) {
			session()->setFlashdata('message', 'Something wrong!');
			session()->setFlashdata('alert-class', 'alert-danger');
		}

		return redirect()->to('/' . $this->table);
	}

	public function deleteImage($id)
	{
		if (!empty($id)) {
			$data['image_logo'] = null;
			$response = $this->Amazon_s3_model->deleteFileFromS3($this->table, "image_logo");
			$this->model->updateColumn($this->table, $id, $data);

			if ($response) {
				session()->setFlashdata('message', 'Image deleted Successfully!');
				session()->setFlashdata('alert-class', 'alert-success');
			} else {
				session()->setFlashdata('message', 'Something wrong!');
				session()->setFlashdata('alert-class', 'alert-danger');
			}
		}
		return redirect()->to('/' . $this->table . '/edit/' . $id);
	}

	public function delete($id)
	{
		// Check delete permission
		if (!$this->checkPermission('delete')) {
			echo view("errors/html/error_403");
			die;
		}

		//echo $id; die;
		if (!empty($id)) {
			$response = $this->model->deleteData($id);
			if ($response) {
				session()->setFlashdata('message', 'Data deleted Successfully!');
				session()->setFlashdata('alert-class', 'alert-success');
			} else {
				session()->setFlashdata('message', 'Something wrong delete failed!');
				session()->setFlashdata('alert-class', 'alert-danger');
			}
		}

		return redirect()->to('/' . $this->table);
	}

	public function deleterow($uuid)
	{
		// Check delete permission
		if (!$this->checkPermission('delete')) {
			echo view("errors/html/error_403");
			die;
		}

		if (!empty($uuid)) {
			$response = $this->model->deleteDataByUUID($uuid);
			if ($response) {
				session()->setFlashdata('message', 'Data deleted Successfully!');
				session()->setFlashdata('alert-class', 'alert-success');
			} else {
				session()->setFlashdata('message', 'Something wrong delete failed!');
				session()->setFlashdata('alert-class', 'alert-danger');
			}
		}
		return redirect()->to('/' . $this->table);
	}

	// 
	public function status()
	{
		// Check update permission
		if (!$this->checkPermission('update')) {
			echo json_encode(['status' => 'error', 'message' => 'You do not have permission to update records']);
			return;
		}

		if (!empty($id = $this->request->getPost('id'))) {
			$data = array(
				'status' => $this->request->getPost('status')
			);
			$this->model->updateData($id, $data);
		}
		echo '1';
	}



	// only call if there additional data needed on edit view
	public function getAdditionalData($id)
	{
		return [];
	}

	public function upload($filename = null)
	{
		//echo $filename; die;
		$input = $this->validate([
			$filename => "uploaded[$filename]|max_size[$filename,1024]|ext_in[$filename,jpg,png,jpeg,docx,pdf],"
		]);

		if (!$input) { // Not valid
			return '';
		} else { // Valid

			if ($file = $this->request->getFile($filename)) {
				if ($file->isValid() && !$file->hasMoved()) {
					// Get file name and extension
					$name = $file->getName();
					$ext = $file->getClientExtension();

					// Get random file name
					$newName = $file->getRandomName();

					// Store file in public/uploads/ folder
					$file->move('../public/ckfinder/userfiles/files/', $newName);

					// File path to display preview
					return $filepath = base_url() . "/ckfinder/userfiles/files/" . $newName;
				}
			}
		}
	}

	public function getMenuCode($link)
	{

		return $this->model->getMenuCode($link);
	}


	public function uploadMediaFiles()
	{

		$folder = $this->request->getPost("mainTable");

		$response = $this->Amazon_s3_model->doUpload("file", $folder);

		if ($response["status"]) {

			$id = 0;
			$file_path = $response['filePath'];
			$status = 1;


			if (file_exists(APPPATH . "Views/" . $this->table . "/uploadedFileView.php")) {

				$file_views = view($this->table . "/uploadedFileView", array("file_path" => $file_path, "id" => $id));
			} else {

				$file_views = view("common/uploadedFileView", array("file_path" => $file_path, "id" => $id));
			}
			$msg = "success";
		} else {
			$status = 0;
			$file_views = '';
			$msg = "error";
		}

		echo json_encode(array("status" => $status, "file_path" => $file_views, "msg" => $msg));
	}


	public function exportPDF($uuid = 0, $view = '')
	{
		set_time_limit(60);
		$mpdf = new \App\Libraries\Generate_Pdf();
		$pdf = $mpdf->load_portait();

		$pdf_template_id = 0;
		$pdf_name_prefix = "workstation";
		$uuid_business_id = $this->session->get('uuid_business');
		$business = $this->db->table('businesses')->where("uuid", $uuid_business_id)->get()->getRowArray();

		if (!empty($uuid) && ($this->table == 'sales_invoices' || $this->table == 'purchase_invoices' || $this->table == 'purchase_orders' || $this->table == 'work_orders')) {

			$item_details = $this->getInvoiceItem($uuid);
			$pdf_name_prefix = $business['business_code'];

			if ($this->table == 'sales_invoices' || $this->table == 'purchase_invoices') {
				$pdf_template_id = $item_details->print_template_code;
				$pdf_name_prefix .= '-Invoice-' . $item_details->invoice_number . '-';
			} else if ($this->table == 'purchase_orders' || $this->table == 'work_orders') {
				$pdf_template_id = $item_details->template;
				$pdf_name_prefix .= '-Order-' . $item_details->order_number . '-';
			}
			$pdf_name_prefix .= date('M', $item_details->date) . '-' . date('Y', $item_details->date);
		} else if ($this->table == 'timeslips') {
			$employee_id = $_POST["employee"];
			$month = isset($_POST["monthpicker"]) ? $_POST["monthpicker"] : date('m');
			$year = isset($_POST["yearpicker"]) ? $_POST["yearpicker"] : date('Y');
			$employee_name = "All";
			if ($employee_id != "-1") {
				$employeeData = $this->db->table('employees')->select('CONCAT_WS(" ", saludation, first_name, surname) as name')->getWhere(array('id' => $employee_id))->getFirstRow();
				$employee_name = trim($employeeData->name);
			}
			$pdf_name_prefix = $business['business_code'] . '-Timesheet-' . $employee_name . '-' . date('M', mktime(0, 0, 0, $month, 10)) . '-' . $year;
			$pdf_template_id = isset($_POST["template_id"]) ? $_POST["template_id"] : 0;
		}

		if (empty($pdf_template_id)) {
			$template = $this->db->table('templates')->where("uuid_business_id", $uuid_business_id)->where('module_name', $this->table)->where('is_default', 1)->get()->getRowArray();
			$pdf_template_id = !empty($template) ? $template['id'] : 0;
		}

		//Find the template contenet and then search block by code

		$templates = $this->db->table('templates')->where("uuid_business_id", $uuid_business_id)->where('id', $pdf_template_id)->get()->getResultArray();


		// tmp PHP file generation directory
		$DYNAMIC_SCRIPTS_PATH = getenv('DYNAMIC_SCRIPTS_PATH');
		if (empty($DYNAMIC_SCRIPTS_PATH)) {
			$DYNAMIC_SCRIPTS_PATH = '/tmp';
		}
		$pdf_path = $DYNAMIC_SCRIPTS_PATH . '/' . $this->table;
		if (!file_exists($pdf_path)) {
			mkdir($pdf_path, 0755, true);
		}

		$template_html = "";
		if ($templates) {
			// Include all dynamic data like timeslips, sales orders etc and replace dynamic data variable with template user define variable
			if ($this->table == 'timeslips') {
				file_put_contents($pdf_path . "/dynamic_variables.php", $this->getTimesheetDataVariables($_POST));
				$template_html .= '<?php include("dynamic_variables.php"); ?>';
			} else {
				file_put_contents($pdf_path . "/dynamic_variables.php", $this->getInvoiceDataVariables($uuid));
				$template_html .= '<?php include("dynamic_variables.php"); ?>';
			}

			foreach ($templates as $template) {
				$template_html .= $template['template_content'];

				$landscape_block = '<*--show-pdf-landscape--*>';
				if (strpos($template_html, $landscape_block) !== false) {
					$pdf->AddPage(
						'',
						'',
						'',
						'',
						'',
						15, // margin_left
						15, // margin right
						10, // margin top
						15, // margin bottom
						8, // margin header
						1, // margin footer
						'',
						'',
						'',
						'',
						'',
						'',
						'',
						'',
						'',
						'A4-L' // L - landscape, P - portrait
					);
					$template_html = str_replace($landscape_block, '', $template_html);
				}

				$template_html = $this->templateReplaceStr($template_html);

				$block_pattern = "/<\*\-\-[A-Za-z0-9-_+*&@!()# ]+\-\-\*\>/i";
				if (preg_match_all($block_pattern, $template['template_content'], $blocks_code)) {
					$blocks_code = $blocks_code[0];
					$replace_str = ['<*--', '--*>'];
					foreach ($blocks_code as $template_content) {
						$block_html = "";
						$block_code = trim(str_replace($replace_str, '', $template_content));
						if (!empty($block_code)) {
							$blocks_list = $this->db->table('blocks_list')->where("uuid_business_id", $uuid_business_id)->where('code', $block_code)->where('status', 1)->get()->getResultArray();
							if ($blocks_list) {
								foreach ($blocks_list as $block) {
									$block_text = $block['text'];

									// Load Footer Data
									if (strpos($block_text, 'displayPageNumber();') !== false) {
										$pdf->SetHTMLFooter($this->displayPageNumber());
										$block_text = str_replace('displayPageNumber();', '', $block_text);
										$block_html .= $block_text;
									}

									// Load Body Data With Dynamic Content
									else if (strpos($block_text, 'displayTableItem();') !== false) {
										if ($this->table == 'timeslips') {
											$block_html .= $this->displayTimeslipItem($_POST);
										} else if ($this->table == 'sales_invoices' || $this->table == 'purchase_invoices' || $this->table == 'purchase_orders' || $this->table == 'work_orders') {
											$block_html .= $this->displayInvoiceItem($uuid);
										}
										$block_text = str_replace('displayTableItem();', '', $block_text);
										$block_html .= $block_text;
									}
									// Load Header Data
									else if (strpos($block_text, 'displayPDFHeader();') !== false) {
										$block_html .= $this->displayPDFHeader();
										$block_text = str_replace('displayPDFHeader();', '', $block_text);
										$block_html .= $block_text;
									} else {
										$block_html .= $this->getRecursiveHtmlFromBlock($block_text);
									}
								}
							} else {
								$block_html .= '<div class="alert alert-danger" role="alert" style="color:red;" >' . $block_code . ' Template block is inactive or does not exist!</div>';
							}
						}

						$template_html = str_replace($template_content, $block_html, $template_html);
					}
				}
			}
		}

		file_put_contents($pdf_path . "/dynamic_body.php", $template_html);
		ob_start();
		include($pdf_path . "/dynamic_body.php");
		$html = ob_get_contents();
		ob_end_clean();

		if ($view == 'view') {
			echo $html;
			die;
		}

		$pdf->WriteHTML($html);
		$pdf->Output(str_replace(' ', '-', $pdf_name_prefix) . ".pdf", "D");
	}


	public function templateReplaceStr($template_html)
	{

		// For item of array
		$tables = [];
		if ($this->table == 'timeslips') {
			array_push($tables, $this->table);
			$template_html = str_replace('<*--item-start-loop--*>', '<?php foreach(json_decode($dataVariables)->' . $this->table . ' as $' . $this->rawTblName . '){ ?>', $template_html);
			$template_html = str_replace('<*--timeslips#total#slip_hours--*>', '<?= @json_decode($dataVariables)->' . $this->table . '_total_slip_hours ?>', $template_html);
			$template_html = str_replace('<*--timeslips#total#slip_days--*>', '<?= @json_decode($dataVariables)->' . $this->table . '_total_slip_days ?>', $template_html);
			$template_html = str_replace('<*--timeslips#total#subtotal--*>', '<?= @json_decode($dataVariables)->' . $this->table . '_total_subtotal ?>', $template_html);
		} else {
			$item_table = $this->rawTblName . "_items";
			array_push($tables, $item_table);
			$template_html = str_replace('<*--item-start-loop--*>', '<?php foreach(json_decode($dataVariables)->' . $item_table . ' as $' . substr($item_table, 0, -1) . '){ ?>', $template_html);
			$template_html = str_replace('<*--' . $item_table . '#total#hours--*>', '<?= @json_decode($dataVariables)->' . $item_table . '_total_hours ?>', $template_html);
			$template_html = str_replace('<*--' . $item_table . '#total#days--*>', '<?= @json_decode($dataVariables)->' . $item_table . '_total_days ?>', $template_html);
			$template_html = str_replace('<*--' . $item_table . '#total#amount--*>', '<?= @json_decode($dataVariables)->' . $item_table . '_total_amount ?>', $template_html);
			$template_html = str_replace('<*--' . $item_table . '#total#qty--*>', '<?= @json_decode($dataVariables)->' . $item_table . '_total_qty ?>', $template_html);
			$template_html = str_replace('<*--' . $item_table . '#total#discount--*>', '<?= @json_decode($dataVariables)->' . $item_table . '_total_discount ?>', $template_html);
			$template_html = str_replace('<*--' . $item_table . '#total#amount_minus_discount--*>', '<?= @json_decode($dataVariables)->' . $item_table . '_total_amount_minus_discount ?>', $template_html);
		}
		$template_html = str_replace('<*--item-end-loop--*>', '<?php } ?>', $template_html);

		if ($this->table == 'sales_invoices' || $this->table == 'purchase_invoices') {
			$note_table = $this->rawTblName . "_notes";
			array_push($tables, $note_table);
			$template_html = str_replace('<*--' . $note_table . '-item-start-loop--*>', '<?php foreach(json_decode($dataVariables)->' . $note_table . ' as $' . substr($note_table, 0, -1) . '){ ?>', $template_html);
			$template_html = str_replace('<*--' . $note_table . '-item-end-loop--*>', '<?php } ?>', $template_html);
		}

		// Replace column name with variable for multi record table data
		$custom_fields = [
			'name_of_task',
			'employee_first_name',
			'employee_surname',
			'subtotal',
			(object) ['name' => 'slip_start_date_day', 'type' => 'int'],
			(object) ['name' => 'slip_end_date_day', 'type' => 'int']
		];

		foreach ($tables as $table) {
			$fields = $this->db->getFieldData($table);
			$fields = array_merge($fields, $custom_fields);
			foreach ($fields as $field) {
				if (isset($field->type)) {
					if (in_array($field->name, ['slip_start_date', 'slip_end_date', 'slip_start_date_day', 'slip_end_date_day'])) {
						if (strpos($field->name, '_day') !== false) {
							$template_html = str_replace('<*--' . $table . '#' . $field->name . '--*>', '<?= date("l",$' . substr($table, 0, -1) . '->' . substr($field->name, 0, -4) . ') ?>', $template_html);
						} else {
							$template_html = str_replace('<*--' . $table . '#' . $field->name . '--*>', '<?= date("d/m/Y",$' . substr($table, 0, -1) . '->' . $field->name . ') ?>', $template_html);
						}
					} else if ($field->type  == 'datetime') {
						$template_html = str_replace('<*--' . $table . '#' . $field->name . '--*>', '<?= date("d/m/Y",strtotime($' . substr($table, 0, -1) . '->' . $field->name . ')) ?>', $template_html);
					} else {
						$template_html = str_replace('<*--' . $table . '#' . $field->name . '--*>', '<?= $' . substr($table, 0, -1) . '->' . $field->name . ' ?>', $template_html);
					}
				} else {
					$template_html = str_replace('<*--' . $table . '#' . $field . '--*>', '<?= $' . substr($table, 0, -1) . '->' . $field . ' ?>', $template_html);
				}
			}
		}


		// For single item
		$tables = ['employees'];
		if ($this->table != 'timeslips') {
			array_push($tables, $this->table);
		}
		foreach ($tables as $table) {
			$fields = $this->db->getFieldData($table);
			foreach ($fields as $field) {
				if (isset($field->type)) {
					if (in_array($field->name, ['date', 'due_date', 'paid_date'])) {
						$template_html = str_replace('<*--' . $table . '#' . $field->name . '--*>', '<?= date("d/m/Y",(json_decode($dataVariables)->' . substr($table, 0, -1) . '->' . $field->name . ')) ?>', $template_html);
					} else if ($field->type  == 'datetime' || in_array($field->name, ['date', 'due_date', 'paid_date'])) {
						$template_html = str_replace('<*--' . $table . '#' . $field->name . '--*>', '<?= date("d/m/Y",strtotime(json_decode($dataVariables)->' . substr($table, 0, -1) . '->' . $field->name . ')) ?>', $template_html);
					} else {
						$template_html = str_replace('<*--' . $table . '#' . $field->name . '--*>', '<?= json_decode($dataVariables)->' . substr($table, 0, -1) . '->' . $field->name . ' ?>', $template_html);
					}
				} else {
					$template_html = str_replace('<*--' . $table . '#' . $field . '--*>', '<?= json_decode($dataVariables)->' . substr($table, 0, -1) . '->' . $field . ' ?>', $template_html);
				}
			}
		}
		return $template_html;
	}


	public function getRecursiveHtmlFromBlock($block_text)
	{
		$uuid_business_id = $this->session->get('uuid_business');

		// Recursively Search for a block inside block content
		$block_pattern = "/<\*\-\-[A-Za-z0-9-_+*&@!()# ]+\-\-\*\>/i";
		if (preg_match_all($block_pattern, $block_text, $blocks_code)) {
			$blocks_code = $blocks_code[0];
			$replace_str = ['<*--', '--*>'];
			foreach ($blocks_code as $template_content) {
				$block_code = trim(str_replace($replace_str, '', $template_content));
				if (!empty($block_code)) {
					$blocks_list = $this->db->table('blocks_list')->where("uuid_business_id", $uuid_business_id)->where('code', $block_code)->where('status', 1)->get()->getRowArray();
					if ($blocks_list) {
						$text = $blocks_list['text'];
						$block_text	= str_replace($template_content, $text, $block_text);
						$block_text = str_replace($template_content, "", $block_text);
					} else {
						$alert = '<div class="alert alert-danger" role="alert">' . $block_code . ' Template block is inactive or not exist!</div>';
						$block_text	= str_replace($template_content, $alert, $block_text);
					}
					return $this->getRecursiveHtmlFromBlock($block_text);
				}
			}
		}
		return $block_text;
	}


	public function displayPDFHeader()
	{
		if ($this->table == 'timeslips') {
			return view("timeslips/pdf_header");
		} else if ($this->table == 'sales_invoices' || $this->table == 'purchase_invoices' || $this->table == 'purchase_orders' || $this->table == 'work_orders') {
			return view("sales_invoices/pdf_header");
		}
		return;
	}

	public function displayPageNumber()
	{
		return view("timeslips/pdf_footer");
	}

	public function getTimesheetDataVariables($post_data)
	{
		$employee_id = $post_data["employee"];
		if ($employee_id == "-1") {
			$employeeData = $this->db->table('employees')->select('*')->getWhere(array('id' => 4))->getFirstRow();
		} else {
			$employeeData = $this->db->table('employees')->select('*')->getWhere(array('id' => $employee_id))->getFirstRow();
		}

		$viewArray["timeslips"] = $this->loadTimeslipItem($post_data);
		$viewArray["employee"] = $employeeData;
		$viewArray["timeslips_total_slip_hours"] = number_format($this->getTimeslipHours($post_data), 2);
		$viewArray["timeslips_total_slip_days"] = number_format($viewArray["timeslips_total_slip_hours"] / 8, 2);
		$viewArray["timeslips_total_subtotal"] = number_format($this->getTimeslipTotalSubtotal($post_data), 2);
		$viewArray = "'" . json_encode($viewArray, JSON_HEX_APOS) . "'";
		return '<?php $dataVariables =' . $viewArray . ';?>';
	}

	function getInvoiceDataVariables($uuid)
	{
		$viewArray[$this->rawTblName] = $this->getInvoiceItem($uuid);
		$item_table = $this->rawTblName . "_items";
		$viewArray[$item_table] = $this->db->table($item_table)->select('*')->where(array($this->table . '_uuid' => $uuid))->get()->getResultObject();

		if ($this->table == 'sales_invoices' || $this->table == 'purchase_invoices') {
			$note_table = $this->rawTblName . "_notes";
			$viewArray[$note_table] = $this->db->table($note_table)->select('*')->where(array($this->table . '_uuid' => $uuid))->get()->getResultObject();
			$viewArray[$item_table . '_total_hours'] = $this->db->table($item_table)->select('COALESCE(SUM(hours),0) as total_hours')->where(array($this->table . '_uuid' => $uuid))->get()->getRowObject()->total_hours;
			$viewArray[$item_table . '_total_days'] = $viewArray[$item_table . '_total_hours'] / 8;
			$viewArray[$item_table . '_total_amount'] = $this->db->table($item_table)->select('COALESCE(SUM(amount),0) as total_amount')->where(array($this->table . '_uuid' => $uuid))->get()->getRowObject()->total_amount;
		} else {
			$viewArray[$item_table . '_total_qty'] = $this->db->table($item_table)->select('COALESCE(SUM(qty),0) as total_qty')->where(array($this->table . '_uuid' => $uuid))->get()->getRowObject()->total_qty;
			$viewArray[$item_table . '_total_discount'] = $this->db->table($item_table)->select('COALESCE(SUM(discount),0) as total_discount')->where(array($this->table . '_id' => $uuid))->get()->getRowObject()->total_discount;
			$viewArray[$item_table . '_total_amount'] = $this->db->table($item_table)->select('COALESCE(SUM(amount),0) as total_amount')->where(array($this->table . '_id' => $uuid))->get()->getRowObject()->total_amount;
			$viewArray[$item_table . '_total_amount_minus_discount'] = $viewArray[$item_table . '_total_amount'] - $viewArray[$item_table . '_total_discount'];
		}

		$viewArray = "'" . json_encode($viewArray, JSON_HEX_APOS) . "'";
		return '<?php $dataVariables =' . $viewArray . ';?>';
	}

	public function displayTimeslipItem($post_data)
	{
		$employeeData = $this->db->table('employees')->select('*')->getWhere(array('id' => 4))->getFirstRow();
		// generate the PDF!
		$viewArray["timeslips"] = $this->loadTimeslipItem($post_data);
		$viewArray["employeeData"] = $employeeData;

		return view("timeslips/pdf_body", $viewArray);
	}

	public function loadTimeslipItem($post_data)
	{
		$employee_id = $post_data["employee"];
		$exportIds = $post_data['exportIds'];

		$builder = $this->db->table("timeslips");
		$builder->select("timeslips.*,truncate((IFNULL(timeslips.slip_hours, 0) * IFNULL(timeslips.slip_rate, 0)),2) as subtotal, tasks.name as name_of_task, employees.first_name as employee_first_name, employees.surname as employee_surname");
		$builder->join("tasks", "tasks.id = timeslips.task_name", "left");
		$builder->join("employees", "employees.id = timeslips.employee_name", "left");

		if (!empty($exportIds)) {
			$exportIds = json_decode(stripslashes($exportIds));
			$builder->whereIn("timeslips.uuid", $exportIds);
		} else {
			$requestMonth = $post_data["monthpicker"];
			$year = $post_data["yearpicker"];
			$firstDayOfCurrentMonth = strtotime($this->firstDay($requestMonth,  $year));
			$lastDayMonth = strtotime($this->lastday($requestMonth,  $year));
			$builder->where("timeslips.slip_start_date >=", $firstDayOfCurrentMonth);
			$builder->where("timeslips.slip_start_date <=", $lastDayMonth);
		}

		if ($employee_id != "-1") {
			$builder->where("timeslips.employee_name", $employee_id);
		}

		if (isset($post_data['order_by']) && $post_data['order_by']) {
			$builder->orderBy("timeslips.slip_start_date DESC");
		}

		$records = $builder->get()->getResultArray();
		return $records;
	}

	public function getTimeslipTotalSubtotal($post_data)
	{
		$employee_id = $post_data["employee"];
		$exportIds = $post_data['exportIds'];

		$builder = $this->db->table("timeslips");
		$builder->select("truncate(SUM(IFNULL(timeslips.slip_hours, 0) * IFNULL(timeslips.slip_rate, 0)),2) as total_subtotal");

		if (!empty($exportIds)) {
			$exportIds = json_decode(stripslashes($exportIds));
			$builder->whereIn("timeslips.uuid", $exportIds);
		} else {
			$requestMonth = $post_data["monthpicker"];
			$year = $_POST["yearpicker"];
			$firstDayOfCurrentMonth = strtotime($this->firstDay($requestMonth,  $year));
			$lastDayMonth = strtotime($this->lastday($requestMonth,  $year));
			$builder->where("timeslips.slip_start_date >=", $firstDayOfCurrentMonth);
			$builder->where("timeslips.slip_start_date <=", $lastDayMonth);
		}

		if ($employee_id != "-1") {
			$builder->where("timeslips.employee_name", $employee_id);
		}

		$records = $builder->get()->getRowArray();
		return empty($records['total_subtotal']) ? 0 : $records['total_subtotal'];
	}

	public function getTimeslipHours($post_data)
	{
		$employee_id = $post_data["employee"];
		$exportIds = $post_data['exportIds'];

		$builder = $this->db->table("timeslips");
		$builder->select("COALESCE(SUM(slip_hours),0) as total_slip_hours");

		if (!empty($exportIds)) {
			$exportIds = json_decode(stripslashes($exportIds));
			$builder->whereIn("timeslips.uuid", $exportIds);
		} else {
			$requestMonth = $post_data["monthpicker"];
			$year = $_POST["yearpicker"];
			$firstDayOfCurrentMonth = strtotime($this->firstDay($requestMonth,  $year));
			$lastDayMonth = strtotime($this->lastday($requestMonth,  $year));
			$builder->where("timeslips.slip_start_date >=", $firstDayOfCurrentMonth);
			$builder->where("timeslips.slip_start_date <=", $lastDayMonth);
		}

		if ($employee_id != "-1") {
			$builder->where("timeslips.employee_name", $employee_id);
		}

		$records = $builder->get()->getRowArray();
		return $records['total_slip_hours'];
	}

	function firstDay($month = '', $year = '')
	{
		if (empty($month)) {
			$month = date('m');
		}
		if (empty($year)) {
			$year = date('Y');
		}
		$result = strtotime("{$year}-{$month}-01");
		return date('Y-m-d', $result);
	}

	function lastday($month = '', $year = '')
	{
		if (empty($month)) {
			$month = date('m');
		}
		if (empty($year)) {
			$year = date('Y');
		}
		$result = strtotime("{$year}-{$month}-01");
		$result = strtotime('-1 second', strtotime('+1 month', $result));
		return date('Y-m-d', $result);
	}

	function displayInvoiceItem($id)
	{
		$viewArray["sales_invoice"] = $this->getInvoiceItem($id);
		return view($this->table . "/pdf_item", $viewArray);
	}

	function getInvoiceItem($uuid)
	{
		$builder = $this->db->table($this->table);
		$builder->select($this->table . '.*,customers.company_name,customers.contact_firstname,customers.contact_lastname');
		$builder->join('customers', 'customers.id=' . $this->table . '.client_id');
		$builder->where($this->table . '.uuid', $uuid);
		$records = $builder->get()->getRowArray();
		return (object)$records;
	}

	function getMediaItems($table,$uuid,$fields='*')
	{
		$builder = $this->db->table($table);
		$builder->select($fields);
		$builder->where('uuid_linked_table', $uuid);
		$records = $builder->get()->getResultArray();
		return $records;
	}
}
