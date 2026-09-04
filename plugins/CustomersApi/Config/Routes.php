<?php

namespace Config;

$routes = Services::routes();

$customers_api_namespace = ['namespace' => 'CustomersApi\Controllers'];

$routes->get('/customersapi/settings', 'CustomersApi::settings', $customers_api_namespace);
$routes->post('/customersapi/save_settings', 'CustomersApi::saveSettings', $customers_api_namespace);

// Api requests for Customers
$routes->group('customersapi', $customers_api_namespace, function ($routes) {
    
    // Authentication
    $routes->post('login', 'RestApiController::login'); // Customer Login
    $routes->post('register', 'RestApiController::register'); // Register New Customer
    $routes->post('forget-password', 'RestApiController::forgetPassword'); // Forgot Password

    // Dashboard
    $routes->get('overview', 'DashboardController::overview'); // Get Overview Data
    $routes->get('dashboard', 'DashboardController::dashboard'); // Get Dashboard Data

    // Profile
    $routes->get('profile', 'RestApiController::profile'); // Get Customer Data

    // Privacy Policy (public content, no auth required)
    $routes->get('privacy-policy', 'RestApiController::privacyPolicy');

    // Projects
    $routes->get('projects', 'ProjectsController::projects'); // Get Projects List
    $routes->get('projects/(:num)', 'ProjectsController::showProject/$1'); // Get Project by Id
    $routes->get('projects/(:num)/tasks', 'ProjectsController::getProjectTasks/$1'); // Get Project Tasks
    $routes->get('projects/(:num)/invoices', 'ProjectsController::getProjectInvoices/$1'); // Get Project Invoices

    // Contracts
    $routes->get('contracts', 'ContractsController::contracts'); // Get Contracts List
    $routes->get('contracts/(:num)', 'ContractsController::showContract/$1'); // Get Contract by Id

    // Proposals
    $routes->get('proposals', 'ProposalsController::proposals'); // Get Proposals List
    $routes->get('proposals/(:num)', 'ProposalsController::showProposal/$1'); // Get Proposal by Id

    // Estimates
    $routes->get('estimates', 'EstimatesController::estimates'); // Get Estimates List
    $routes->get('estimates/(:num)', 'EstimatesController::showEstimate/$1'); // Get Estimate by Id

    // Invoices
    $routes->get('invoices', 'InvoicesController::invoices'); // Get Invoices List
    $routes->get('invoices/(:num)', 'InvoicesController::showInvoice/$1'); // Get Invoice by Id

    // Invoices
    $routes->get('payments', 'PaymentsController::payments'); // Get Payments List
    $routes->get('payments/(:num)', 'PaymentsController::showPayment/$1'); // Get Payment by Id

    // Tickets
    $routes->get('tickets', 'TicketsController::tickets'); // Get Tickets List
    $routes->get('tickets/(:num)', 'TicketsController::showTicket/$1'); // Get Ticket by Id
    $routes->get('tickets/ticket_types', 'TicketsController::getTicketTypes'); // Get Ticket Types
    $routes->post('tickets', 'TicketsController::storeTickets'); // Create Ticket
    $routes->post('tickets/(:num)/mark-as-closed', 'TicketsController::markTicketAsClosed/$1'); // Mark Ticket As Closed
    $routes->post('tickets/(:num)/mark-as-opened', 'TicketsController::markTicketAsOpened/$1'); // Mark Ticket As Opened
    $routes->post('tickets/(:num)/comment', 'TicketsController::storeTicketComment/$1'); // Add Comment to Ticket
});

