<?php
namespace Config;

use CodeIgniter\Config\Services;

// Create a new instance of our RouteCollection class.
$routes = Services::routes();

// Load the system's routing file first, so that the app and ENVIRONMENT
// can override as needed.

if (file_exists(SYSTEMPATH . 'Config/Routes.php')) {
    require SYSTEMPATH . 'Config/Routes.php';
}

/*
 * --------------------------------------------------------------------
 * Router Setup
 * --------------------------------------------------------------------
 */
$routes->setDefaultNamespace('App\Controllers');
$routes->setDefaultController('Users');
$routes->setDefaultMethod('index');
$routes->setTranslateURIDashes(false);
$routes->set404Override();
$routes->setAutoRoute(true);

$routes->get('google-login', 'Auth::googleLogin');
$routes->get('callback', 'Auth::callback');
$routes->get('google-logout', 'Auth::googleLogout');

/*
 * --------------------------------------------------------------------
 * Route Definitions
 * --------------------------------------------------------------------
 */

// We get a performance increase by specifying the default
// route since we don't have to scan directories.
$routes->get('/', 'Home::index');

// Chat Module Routes (must be before auto-routes to take priority)
$routes->get('chat', 'Chat::index');
$routes->get('chat/debug', 'Chat::debug');
$routes->get('api/token', 'Chat::getToken');
$routes->post('api/token/refresh', 'Chat::refreshToken');

// Chat API Routes (temporary CodeIgniter implementation until OpenResty is configured)
$routes->group('api/chat', ['namespace' => 'App\Controllers\Api'], function($routes) {
    $routes->get('channels', 'ChatApi::channels');
    $routes->post('channels', 'ChatApi::channels');
    $routes->get('messages', 'ChatApi::messages');
    $routes->post('messages', 'ChatApi::messages');
    $routes->get('health', 'ChatApi::health');
});

// Debug permissions route
$routes->get('debug-permissions', 'DebugPermissions::index');
$routes->get('debug_permissions', 'DebugPermissions::index');

// Common AJAX search endpoints (used by all modules)
$routes->group('common', function ($routes) {
    $routes->get('test', 'CommonAjax::test');
    $routes->get('searchEmployees', 'CommonAjax::searchEmployees');
    $routes->get('searchCustomers', 'CommonAjax::searchCustomers');
    $routes->get('searchContacts', 'CommonAjax::searchContacts');
    $routes->get('searchProjects', 'CommonAjax::searchProjects');
    $routes->get('searchTasks', 'CommonAjax::searchTasks');
    $routes->get('searchUsers', 'CommonAjax::searchUsers');
    $routes->get('searchBusinesses', 'CommonAjax::searchBusinesses');
    $routes->get('searchCategories', 'CommonAjax::searchCategories');
    $routes->get('searchSprints', 'CommonAjax::searchSprints');
    $routes->get('searchTemplates', 'CommonAjax::searchTemplates');
    $routes->get('searchRoles', 'CommonAjax::searchRoles');
    $routes->get('searchTags', 'CommonAjax::searchTags');
    $routes->get('searchServices', 'CommonAjax::searchServices');
    $routes->get('searchPurchaseInvoices', 'CommonAjax::searchPurchaseInvoices');
    $routes->get('searchSalesInvoices', 'CommonAjax::searchSalesInvoices');
    $routes->get('searchDomains', 'CommonAjax::searchDomains');
    $routes->get('searchWorkOrders', 'CommonAjax::searchWorkOrders');
    $routes->get('searchProjectJobs', 'CommonAjax::searchProjectJobs');
    $routes->get('searchProjectJobPhases', 'CommonAjax::searchProjectJobPhases');
});

// Project Jobs routes
$routes->group('project_jobs', function ($routes) {
    $routes->get('/', 'ProjectJobs::index');
    $routes->get('index', 'ProjectJobs::index');
    $routes->get('edit/(:segment)', 'ProjectJobs::edit/$1');
    $routes->get('edit', 'ProjectJobs::edit');
    $routes->post('update', 'ProjectJobs::update');
    $routes->get('delete/(:segment)', 'ProjectJobs::delete/$1');
    $routes->get('jobsList', 'ProjectJobs::jobsList');
    $routes->get('byProject/(:segment)', 'ProjectJobs::byProject/$1');
    $routes->post('assign/(:segment)', 'ProjectJobs::assign/$1');
    $routes->post('updateProgress/(:segment)', 'ProjectJobs::updateProgress/$1');
});

// Project Job Phases routes
$routes->group('project_job_phases', function ($routes) {
    $routes->get('index/(:segment)', 'ProjectJobPhases::index/$1');
    $routes->get('phasesList/(:segment)', 'ProjectJobPhases::phasesList/$1');
    $routes->get('edit/(:segment)/(:segment)', 'ProjectJobPhases::edit/$1/$2');
    $routes->get('edit/(:segment)', 'ProjectJobPhases::edit/$1');
    $routes->post('update', 'ProjectJobPhases::update');
    $routes->get('delete/(:segment)', 'ProjectJobPhases::delete/$1');
    $routes->get('phasesByJob/(:segment)', 'ProjectJobPhases::phasesByJob/$1');
    $routes->post('updateStatus/(:segment)', 'ProjectJobPhases::updateStatus/$1');
    $routes->get('checkDependencies/(:segment)', 'ProjectJobPhases::checkDependencies/$1');
    $routes->post('reorder', 'ProjectJobPhases::reorder');
});

// Project Job Scheduler routes
$routes->group('project_job_scheduler', function ($routes) {
    $routes->get('calendar', 'ProjectJobScheduler::calendar');
    $routes->get('getEvents', 'ProjectJobScheduler::getEvents');
    $routes->post('createEvent', 'ProjectJobScheduler::createEvent');
    $routes->post('updateEvent/(:segment)', 'ProjectJobScheduler::updateEvent/$1');
    $routes->post('deleteEvent/(:segment)', 'ProjectJobScheduler::deleteEvent/$1');
    $routes->post('dragDrop', 'ProjectJobScheduler::dragDrop');
});

$routes->get('users/delete/(:num)', 'Users::delete/$1');
//$routes->post('users/update/(:num)', 'Users::update');

//Ping function
$routes->get('/api/v1/ping', 'Home::ping');

//API V2
//Authentication - Token endpoint
$routes->post('api/v2/token', 'Auth::apiToken');

//Users Request API
$routes->resource('api/v2/users');
$routes->resource('api/v2/timeslips');
$routes->resource('api/v2/timesheets');
$routes->post('api/v2/timesheets/start', 'Api\V2\Timesheets::startTimer');
$routes->post('api/v2/timesheets/(:segment)/stop', 'Api\V2\Timesheets::stopTimer/$1');
$routes->resource('api/v2/webpages');
$routes->resource('api/v2/tasks');
$routes->resource('api/v2/customers');
$routes->resource('api/v2/businesses');
$routes->resource('api/v2/contacts');
$routes->resource('api/v2/menu');
$routes->resource('api/v2/categories');
$routes->resource('api/v2/projects');
$routes->resource('api/v2/employees');
$routes->resource('api/v2/sprints');
$routes->resource('api/v2/userbusiness');
$routes->resource('api/v2/documents'); //
$routes->resource('api/v2/media');     //
$routes->resource('api/v2/enquiries');
$routes->resource('api/v2/taxes');
$routes->resource('api/v2/purchase_invoices');
$routes->resource('api/v2/sales_invoices');
$routes->resource('api/v2/work_orders');
$routes->resource('api/v2/purchase_orders');
$routes->resource('api/v2/blocks');
$routes->resource('api/v2/secrets');
$routes->resource('api/v2/create_domain');
$routes->resource('api/v2/services');
$routes->resource('api/v2/companies');
$routes->resource('api/v2/vm', ['controller' => 'Api\v2\VmController']);
$routes->resource('api/v2/incidents');
$routes->resource('api/v2/knowledge-base', ['controller' => 'Api\v2\KnowledgeBase']);
$routes->resource('api/v2/email-campaigns', ['controller' => 'Api\v2\EmailCampaigns']);
$routes->resource('api/v2/tags');
$routes->resource('api/v2/roles');
$routes->resource('api/v2/vat-returns', ['controller' => 'Api\v2\VatReturns']);
$routes->resource('api/v2/deployments');
$routes->get('api/v2/deployments/stats', 'Api\V2\Deployments::stats');
$routes->resource('api/v2/launchpad');
$routes->post('api/v2/launchpad/click/(:segment)', 'Api\V2\Launchpad::click/$1');
$routes->post('api/v2/launchpad/share', 'Api\V2\Launchpad::share');
$routes->get('api/v2/launchpad/recent', 'Api\V2\Launchpad::recent');
$routes->resource('scim/v2/Users', ['controller' => 'Api\V2\ScimUserController']);
$routes->resource('scim/v2/Groups', ['controller' => 'Api\V2\ScimGroupController']);

// New API v2 endpoints - added for complete menu coverage
$routes->resource('api/v2/tenants');
$routes->resource('api/v2/domains');
$routes->resource('api/v2/blog-comments', ['controller' => 'Api\v2\BlogComments']);
$routes->resource('api/v2/jobs');
$routes->resource('api/v2/job-applications', ['controller' => 'Api\v2\JobApplications']);
$routes->resource('api/v2/templates');
$routes->resource('api/v2/interviews');
$routes->resource('api/v2/products');
$routes->resource('api/v2/payments');
$routes->resource('api/v2/receipts');

// Project Jobs API endpoints
$routes->resource('api/v2/project_jobs');
$routes->resource('api/v2/project_job_phases');
$routes->resource('api/v2/project_job_scheduler');

// Hospital Management System APIs
$routes->resource('api/v2/hospital_staff');
$routes->resource('api/v2/patient_logs');
$routes->get('api/v2/patient_logs/timeline/(:num)', 'Api\V2\PatientLogs::timeline/$1');
$routes->get('api/v2/patient_logs/flagged', 'Api\V2\PatientLogs::flagged');
$routes->get('api/v2/patient_logs/medications/(:num)', 'Api\V2\PatientLogs::medications/$1');
$routes->get('api/v2/patient_logs/vital-signs/(:num)', 'Api\V2\PatientLogs::vitalSigns/$1');

// List project by business Id
$routes->get('api/v2/business/(:segment)/projects', 'Api\V2\Projects::projectsByBId/$1');
// Timeslip by UUID Id
$routes->get('api/v2/business/(:segment)/employee/(:segment)/tasks/(:segment)/timeslip', 'Api\V2\Timeslips::timeslipByTaskId/$1/$2/$3');
// List Task by business Id
$routes->get('api/v2/business/(:segment)/projects/(:segment)/employee/(:segment)/tasks', 'Api\V2\Tasks::tasksByPId/$1/$2/$3');
// List Tasks and Status by Employee Id
$routes->get('api/v2/business/(:segment)/employee/(:segment)/tasks-status', 'Api\V2\Tasks::tasksStatusByEId/$1/$2');
// Update Task Status by Task ID
$routes->put('api/v2/business/(:segment)/projects/(:segment)/tasks/update-status', 'Api\V2\Tasks::updateStatusByUuid');
// Add enquiry by business code
$routes->post('api/v2/enquiries/business-enqury', 'Api\V2\Enquiries::addEnquiryByBCode');
// List webpages by contacts
$routes->get('api/v2/business/(:segment)/contact/(:segment)/webpages', 'Api\V2\Webpages::getWebPages/$1/$2');
$routes->get('api/v2/business/(:segment)/contact/(:segment)/blogs', 'Api\V2\Webpages::getBlogsByCategory/$1/$2');
$routes->get('api/v2/business/(:segment)/contact/(:segment)/blog/(:segment)', 'Api\V2\Webpages::getBlogsByCode/$1/$2/$3');
$routes->get('api/v2/business/(:segment)/public/blogs', 'Api\V2\Webpages::getPublicBlogs/$1');
$routes->get('api/v2/business/(:segment)/public/blog/(:segment)', 'Api\V2\Webpages::getPublicBlog/$1/$2');

// API Documentation Routes
$routes->get('swagger', 'Swagger::index');
$routes->get('swagger/json', 'Swagger::json');
$routes->get('swagger/yaml', 'Swagger::yaml');
$routes->get('api-docs', 'Swagger::ui');
$routes->get('api/docs', 'Swagger::ui');

// Document Preview and Download Routes
$routes->get('documents/preview/(:segment)', 'Documents::preview/$1');
$routes->get('documents/download/(:segment)', 'Documents::download/$1');

// Accounting Module Routes
$routes->group('accounts', function ($routes) {
    $routes->get('/', 'Accounts::index');
    $routes->get('edit/(:segment)', 'Accounts::edit/$1');
    $routes->get('edit', 'Accounts::edit');
    $routes->post('update', 'Accounts::update');
    $routes->post('delete/(:segment)', 'Accounts::delete/$1');
    $routes->get('accountsList', 'Accounts::accountsList');
    $routes->post('initializeChartOfAccounts', 'Accounts::initializeChartOfAccounts');
});

// Journal Entries Routes
$routes->group('journal-entries', function ($routes) {
    $routes->get('/', 'JournalEntries::index');
    $routes->get('edit/(:segment)', 'JournalEntries::edit/$1');
    $routes->get('edit', 'JournalEntries::edit');
    $routes->post('update', 'JournalEntries::update');
    $routes->post('post/(:segment)', 'JournalEntries::post/$1');
    $routes->post('delete/(:segment)', 'JournalEntries::delete/$1');
    $routes->get('journalEntriesList', 'JournalEntries::journalEntriesList');
});

// Accounting Periods Routes
$routes->group('accounting-periods', function ($routes) {
    $routes->get('/', 'AccountingPeriods::index');
    $routes->get('edit/(:segment)', 'AccountingPeriods::edit/$1');
    $routes->get('edit', 'AccountingPeriods::edit');
    $routes->post('update', 'AccountingPeriods::update');
    $routes->post('set-current/(:segment)', 'AccountingPeriods::setCurrent/$1');
    $routes->post('close-period/(:segment)', 'AccountingPeriods::closePeriod/$1');
    $routes->get('periodsList', 'AccountingPeriods::periodsList');
});

// Alternative route with underscores (for backward compatibility)
$routes->group('accounting_periods', function ($routes) {
    $routes->get('/', 'AccountingPeriods::index');
    $routes->get('edit/(:segment)', 'AccountingPeriods::edit/$1');
    $routes->get('edit', 'AccountingPeriods::edit');
    $routes->post('update', 'AccountingPeriods::update');
    $routes->post('set-current/(:segment)', 'AccountingPeriods::setCurrent/$1');
    $routes->post('close-period/(:segment)', 'AccountingPeriods::closePeriod/$1');
    $routes->get('periodsList', 'AccountingPeriods::periodsList');
});

// Payments Routes
$routes->group('payments', function ($routes) {
    $routes->get('/', 'Payments::index');
    $routes->get('edit/(:segment)', 'Payments::edit/$1');
    $routes->get('edit', 'Payments::edit');
    $routes->post('update', 'Payments::update');
    $routes->post('delete/(:segment)', 'Payments::delete/$1');
    $routes->get('paymentsList', 'Payments::paymentsList');
    $routes->post('post/(:segment)', 'Payments::post/$1');
    $routes->get('pdf/(:segment)', 'Payments::printRemittance/$1');
    $routes->get('download/(:segment)', 'Payments::downloadPDF/$1');
});

// Receipts Routes
$routes->group('receipts', function ($routes) {
    $routes->get('/', 'Receipts::index');
    $routes->get('edit/(:segment)', 'Receipts::edit/$1');
    $routes->get('edit', 'Receipts::edit');
    $routes->post('update', 'Receipts::update');
    $routes->post('delete/(:segment)', 'Receipts::delete/$1');
    $routes->get('receiptsList', 'Receipts::receiptsList');
    $routes->post('post/(:segment)', 'Receipts::post/$1');
    $routes->get('pdf/(:segment)', 'Receipts::printReceipt/$1');
    $routes->get('download/(:segment)', 'Receipts::downloadPDF/$1');
});
