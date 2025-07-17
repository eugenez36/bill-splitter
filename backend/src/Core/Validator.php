<?php

namespace App\Core;

class Validator
{
    public function validateUser(array $data): array
    {
        $errors = [];

        if (empty($data['name'])) {
            $errors['name'] = 'Name is required';
        } else {
            $name = $data['name'];
            $length = mb_strlen($name);

            if ($length < 2) {
                $errors['name'] = 'Name must be at least 2 characters';
            } elseif ($length > 50) {
                $errors['name'] = 'Name must be less than 50 characters';
            } elseif (!preg_match("/^[a-zA-Z]*$/", $name)) {
                $errors['name'] = 'Name contains invalid characters';
            }
        }

        if (empty($data['email'])) {
            $errors['email'] = 'Email is required';
        } else {
            $email = $data['email'];

            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $errors['email'] = 'Invalid email format';
            } elseif (mb_strlen($data['email']) > 256) {
                $errors['email'] = 'Email is to long';
            }
        }

        return $errors;
    }
}