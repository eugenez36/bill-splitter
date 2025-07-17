<?php
declare(strict_types=1);

require_once __DIR__ . '/Core/Database.php';
require_once __DIR__ . '/Core/Validator.php';
require_once __DIR__ . '/Repository/UserRepository.php';
require_once __DIR__ . '/Service/UserService.php';
require_once __DIR__ . '/Controller/UserController.php';

use App\Controller\UserController;
use App\Core\Database;
use App\Core\Validator;
use App\Repository\UserRepository;
use App\Service\UserService;

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, DELETE, PUT, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json, charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

$config = [
    'host' => getenv('MYSQL_HOST') ?: 'bill_db',
    'dbname' => getenv('MYSQL_DATABASE') ?: 'bill_splitter',
    'user' => getenv('MYSQL_USER') ?: 'root',
    'password' => getenv('MYSQL_PASSWORD') ?: 'bill_splitter_passwd',
];

try {
    $dataBase = new Database($config);
    $validator = new Validator();
    $userRepository = new UserRepository($dataBase);
    $userService = new UserService($userRepository, $validator);
    $controller = new UserController($userService);

    $method = $_SERVER['REQUEST_METHOD'];

    $uri = parse_url($_SERVER['REQUEST_METHOD'], PHP_URL_PATH);

    $controller->handleRequest();
} catch (Throwable $exception) {
    error_log('Application error: ' . $exception->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'internal Server Error']);
}