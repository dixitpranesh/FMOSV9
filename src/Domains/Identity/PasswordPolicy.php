<?php

declare(strict_types=1);

namespace Fmos\Domains\Identity;

use Fmos\Core\Env;

final class PasswordPolicy
{
    /** @var list<string> */
    private const COMMON = [
        'password', 'password1', 'password123', '12345678', '123456789', 'qwerty123',
        'admin123', 'welcome1', 'letmein', 'passw0rd', 'iloveyou', 'abc12345',
        'password123!', 'changeme', 'changeme1', 'welcome123', 'football1',
        'monkey123', 'dragon123', 'master123', 'login123', 'princess1',
    ];

    /** @return array{ok:bool,message:?string} */
    public static function validate(string $password): array
    {
        $min = (int) (Env::get('PASSWORD_MIN_LENGTH', '12') ?? '12');
        if (strlen($password) < $min) {
            return ['ok' => false, 'message' => "Password must be at least {$min} characters."];
        }
        if (strlen($password) > 128) {
            return ['ok' => false, 'message' => 'Password is too long.'];
        }
        if (!preg_match('/[a-z]/', $password) || !preg_match('/[A-Z]/', $password)) {
            return ['ok' => false, 'message' => 'Password must include uppercase and lowercase letters.'];
        }
        if (!preg_match('/[0-9]/', $password)) {
            return ['ok' => false, 'message' => 'Password must include at least one number.'];
        }
        if (!preg_match('/[^A-Za-z0-9]/', $password)) {
            return ['ok' => false, 'message' => 'Password must include at least one special character.'];
        }
        $lower = strtolower($password);
        foreach (self::COMMON as $bad) {
            if ($lower === $bad) {
                return ['ok' => false, 'message' => 'Password is too common. Choose a stronger password.'];
            }
        }
        return ['ok' => true, 'message' => null];
    }
}
