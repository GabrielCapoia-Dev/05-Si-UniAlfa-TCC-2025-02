<?php

namespace Database\Seeders;

use App\Models\Escola;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

class SecretariosPorEscolaSeeder extends Seeder
{
    public function run(): void
    {
        // garante que a role exista
        $role = Role::firstOrCreate(['name' => 'Secretario']);

        Escola::query()->orderBy('id')->chunkById(100, function ($escolas) use ($role) {
            foreach ($escolas as $escola) {
                // monta e-mail: nomedaescola@teste.com (sem acento/espaço)
                $slug = Str::slug(Str::ascii($escola->nome));
                $local = $slug !== '' ? $slug : 'secretario';
                $email = "{$local}@teste.com";

                // evita colisão de e-mail (nomes iguais): adiciona +id
                if (User::where('email', $email)->exists()) {
                    $email = "{$local}+{$escola->id}@teste.com";
                }

                // cria (ou atualiza) o usuário secretário da escola
                $user = User::updateOrCreate(
                    ['email' => $email],
                    [
                        'id_escola'         => $escola->id,
                        'name'              => "Secretário {$escola->nome}",
                        'password'          => Hash::make('123456'),
                        'email_approved'    => true,
                        'email_verified_at' => now(),
                    ]
                );

                // garante a role
                if (! $user->hasRole('Secretario')) {
                    $user->assignRole($role);
                }
            }
        });
    }
}
