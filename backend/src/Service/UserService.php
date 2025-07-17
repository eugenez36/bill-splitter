<?php

namespace App\Service;

use App\Repository\UserRepository;
use App\Core\Validator;
use DomainException;

class UserService
{
    private UserRepository $userRepository;

    private Validator $validator;

    public function __construct(
        UserRepository $userRepository,
        Validator      $validator
    )
    {
        $this->userRepository = $userRepository;
        $this->validator = $validator;
    }

    public function addUser(array $data): array
    {
        $errors = $this->validator->validateUser($data);

        if (!empty($errors)) {
            throw new DomainException(json_encode(['errors' => $errors]), 422);
        }

        $duplicates = $this->userRepository->findDuplicates($data);

        if (!empty($duplicates)) {
            $errors = [];

            foreach ($duplicates as $duplicate) {
                if ($duplicate['name'] === $data['name']) {
                    $errors['name'] = 'Name already exists';
                }
                if ($duplicate['email'] === $data['email']) {
                    $errors['email'] = 'Email already exists';
                }
            }

            throw new DomainException(json_encode(['errors' => $errors]), 409);
        }

        return $this->userRepository->create($data);
    }

    public function getAllUsers(): array
    {
        $users = $this->userRepository->findAll();
        $total = count($users);
        $share = $total > 0 ? 100 / $total : 0;

        return array_map(function ($user) use ($share) {
            return [
                'id' => $user['id'],
                'name' => $user['name'],
                'email' => $user['email'],
                'share' => round($share, 2)
            ];
        }, $users);
    }

    public function resetAll(): void
    {
        $this->userRepository->deleteAll();
    }

    public function checkFieldExists(string $field, string $value): bool
    {
        return $this->userRepository->checkFieldExists($field, $value);
    }
}