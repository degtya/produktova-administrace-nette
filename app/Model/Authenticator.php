<?php
namespace App\Model;

use Nette;
use Nette\Security\SimpleIdentity;
use Nette\Security\AuthenticationException;
use Nette\Security\Passwords;

/**
 * Služba pro autentizaci uživatelů na základě údajů v databázi.
 */
class Authenticator implements Nette\Security\Authenticator
{
    public function __construct(
        private Nette\Database\Explorer $database,
        private Passwords $passwords,
    ) {}

    public function authenticate(string $user, string $password): SimpleIdentity
    {
        $row = $this->database->table('users')
            ->where('email', $user)
            ->fetch();

        if (!$row) {
            throw new AuthenticationException('Uživatel nenalezen.');
        }

        if (!$this->passwords->verify($password, $row->password)) {
            throw new AuthenticationException('Špatné heslo.');
        }

        return new SimpleIdentity(
            $row->id, 
            null, 
            ['email' => $row->email]
        );
    }
}