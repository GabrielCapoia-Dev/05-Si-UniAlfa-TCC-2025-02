<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserService
{
    /**
     * Metodo publico que retorna o usuário pelo email
     */
    public function getUserByEmail($email)
    {
        return User::where('email', $email)->first();
    }

    /**
     * Metodo publico que cria um usuário com os dados do google
     */
    public function createGoogleUser($data)
    {
        if (!$data) {
            return null;
        }

        $user = User::create([
            'name' => $data->getName() ?? 'Usuário Sem Nome',
            'email' => $data->getEmail(),
            'password' => bcrypt(Str::random(16)),
            'email_approved' => false,
            'email_verified_at' => null,
        ]);

        return $user;
    }

    /**
     * Metodo publico que atualiza a senha do usuário
     */
    public function updatePassword($data): bool
    {
        // Buscar o usuário pelo e-mail
        $user = $this->getUserByEmail($data['email']);
        if (!$user) {
            return false;
        }

        // Verificar se a senha atual está correta
        if (!Hash::check($data['password'], $user->password)) {
            return false;
        }

        // Verificar se a nova senha e a confirmação coincidem
        if ($data['new_password'] !== $data['new_password_confirmation']) {
            return false;
        }

        // Atualizar a senha do usuário
        $user->password = Hash::make($data['new_password']);
        $user->save();

        return true;
    }
}