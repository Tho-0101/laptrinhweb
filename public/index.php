<?php

declare(strict_types=1);

use App\Config;
use App\Controllers\AuthController;
use App\Controllers\BrandController;
use App\Controllers\ConversationController;
use App\Controllers\DatabaseController;
use App\Controllers\ListingController;
use App\Controllers\UploadController;
use App\Controllers\UserController;
use App\Request;
use App\Response;

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS');

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'OPTIONS') {
    http_response_code(204);
    exit;
}

require_once __DIR__ . '/../src/Config.php';
require_once __DIR__ . '/../src/Database.php';
require_once __DIR__ . '/../src/Request.php';
require_once __DIR__ . '/../src/Response.php';
require_once __DIR__ . '/../src/Auth.php';
require_once __DIR__ . '/../src/Controllers/AuthController.php';
require_once __DIR__ . '/../src/Controllers/BrandController.php';
require_once __DIR__ . '/../src/Controllers/ConversationController.php';
require_once __DIR__ . '/../src/Controllers/DatabaseController.php';
require_once __DIR__ . '/../src/Controllers/ListingController.php';
require_once __DIR__ . '/../src/Controllers/UploadController.php';
require_once __DIR__ . '/../src/Controllers/UserController.php';

Config::load(dirname(__DIR__));

$method = Request::method();
$path = Request::path();

// Support apps hosted in subfolder, e.g. /bike-shop1/public/api/health
$projectBase = '/bike-shop1/public';
if (str_starts_with($path, $projectBase)) {
    $path = substr($path, strlen($projectBase));
    $path = $path === '' ? '/' : $path;
}

// Support non-rewrite URLs like /bike-shop1/public/index.php/api/health
if (str_starts_with($path, '/index.php')) {
    $path = substr($path, strlen('/index.php'));
    $path = $path === '' ? '/' : $path;
}

if ($method === 'GET' && $path === '/api/health') {
    Response::json(['ok' => true, 'message' => 'Bike Marketplace API is running', 'data' => null, 'meta' => null]);
    exit;
}

if ($method === 'POST' && $path === '/api/register') {
    AuthController::register();
    exit;
}

if ($method === 'POST' && $path === '/api/login') {
    AuthController::login();
    exit;
}

if ($method === 'GET' && $path === '/api/me') {
    UserController::me();
    exit;
}

if ($method === 'PUT' && $path === '/api/me') {
    UserController::updateMe();
    exit;
}

if ($method === 'PUT' && $path === '/api/me/password') {
    UserController::changePassword();
    exit;
}

if ($method === 'POST' && $path === '/api/upload/image') {
    UploadController::image();
    exit;
}

if ($method === 'GET' && $path === '/api/brands') {
    BrandController::index();
    exit;
}

if ($method === 'GET' && $path === '/api/conversations') {
    ConversationController::index();
    exit;
}

if ($method === 'GET' && $path === '/api/conversations/unread-count') {
    ConversationController::unreadCount();
    exit;
}

if ($method === 'POST' && $path === '/api/conversations') {
    ConversationController::create();
    exit;
}

if ($method === 'POST' && preg_match('#^/api/conversations/(\d+)/mark-read$#', $path, $matches) === 1) {
    ConversationController::markRead((int)$matches[1]);
    exit;
}

if ($method === 'GET' && preg_match('#^/api/conversations/(\d+)/messages$#', $path, $matches) === 1) {
    ConversationController::messages((int)$matches[1]);
    exit;
}

if ($method === 'POST' && preg_match('#^/api/conversations/(\d+)/messages$#', $path, $matches) === 1) {
    ConversationController::sendMessage((int)$matches[1]);
    exit;
}

if ($method === 'GET' && $path === '/api/listings') {
    ListingController::index();
    exit;
}

if ($method === 'GET' && $path === '/api/database/all') {
    DatabaseController::all();
    exit;
}

if ($method === 'POST' && $path === '/api/listings') {
    ListingController::store();
    exit;
}

if ($method === 'PUT' && preg_match('#^/api/listings/(\d+)$#', $path, $matches) === 1) {
    ListingController::update((int)$matches[1]);
    exit;
}

if ($method === 'DELETE' && preg_match('#^/api/listings/(\d+)$#', $path, $matches) === 1) {
    ListingController::delete((int)$matches[1]);
    exit;
}

if ($method === 'GET' && preg_match('#^/api/listings/(\d+)$#', $path, $matches) === 1) {
    ListingController::show((int)$matches[1]);
    exit;
}

Response::json([
    'ok' => false,
    'message' => 'Route not found',
    'errors' => null,
    'path' => $path,
], 404);
