<?php

namespace App\Controller;

use App\Service\UserService;

class UserController
{
    /** @var UserService */
    private UserService $userService;
    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
    }

    public function handleRequest(): void
    {
        $method = $_SERVER['REQUEST_METHOD'];

        try {
            switch ($method) {
                case 'POST':
                    $this->handleAddUser();
                    break;

                case 'GET':
                    $this->handleGetUsers();
                    break;

                case 'DELETE':
                    $this->handleResetUsers();
                    break;

                default:
                    http_response_code(405);
                    echo json_encode(['error' => 'Method Not Allowed']);
            }
        } catch (\DomainException $e) {
            $data = json_decode($e->getMessage(), true) ?? ['error' => $e->getMessage()];
            http_response_code($e->getCode());
            echo json_encode($data);
        } catch (\Throwable $e) {
            error_log("Controller Error: " . $e->getMessage());
            http_response_code(500);
            echo json_encode(['error' => 'Internal Server Error']);
        }
    }

    public function handleCheckRequest(): void
    {
        try {
            $field = $_GET['field'] ?? '';
            $value = $_GET['value'] ?? '';

            if (!in_array($field, ['name', 'email'])) {
                throw new \InvalidArgumentException('Invalid field name');
            }

            $exists = $this->userService->checkFieldExists($field, $value);
            echo json_encode(['exists' => $exists]);
        } catch (\Throwable $e) {
            http_response_code(400);
            echo json_encode(['error' => $e->getMessage()]);
        }
    }

    private function handleAddUser(): void
    {
        $data = json_decode(file_get_contents('php://input'), true) ?? [];
        $user = $this->userService->addUser($data);
        http_response_code(201);
        echo json_encode($user);
    }

    private function handleGetUsers(): void
    {
        $users = $this->userService->getAllUsers();
        echo json_encode($users);
    }

    private function handleResetUsers(): void
    {
        $this->userService->resetAll();
        echo json_encode(['message' => 'All users deleted']);
    }
}