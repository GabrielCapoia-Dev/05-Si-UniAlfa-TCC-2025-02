<?php

namespace App\Services;

use App\Models\User;
use Filament\Facades\Filament;
use Laravel\Socialite\Contracts\User as SocialiteUserContract;
use Illuminate\Support\Str;
use Google\Client as GoogleClient;
use Illuminate\Support\Facades\DB;

class GoogleService
{
    public function registrarOuLogar(SocialiteUserContract $oauthUser): User|null
    {
        if ($this->validaUser($oauthUser)) {
            return $this->loginGoogle($oauthUser);
        }
        return $this->registroGoogle($oauthUser);
    }

    private function validaUser(SocialiteUserContract $oauthUser): bool
    {
        $user = User::where('email', $oauthUser->getEmail())->first();

        if ($user && $user->email_approved) {
            $this->salvarTokens($user, $oauthUser);
            return true;
        }
        return false;
    }

    private function loginGoogle(SocialiteUserContract $oauthUser): User|null
    {
        $user = User::where('email', $oauthUser->getEmail())->first();

        // Atualiza tokens no login
        $this->salvarTokens($user, $oauthUser);

        \Filament\Notifications\Notification::make()
            ->title('Acesso Permitido')
            ->body('Bem-vindo de volta!')
            ->success()
            ->send();

        return $user;
    }

    private function registroGoogle(SocialiteUserContract $oauthUser): User
    {
        $user = User::create([
            'name' => $oauthUser->getName() ?? 'Usuário Sem Nome',
            'email' => $oauthUser->getEmail(),
            'password' => bcrypt(Str::random(16)),
            'email_approved' => false,
            'email_verified_at' => null,
        ]);

        $this->salvarTokens($user, $oauthUser);
        DB::afterCommit(function () use ($user) {
            $user->canAccessPanel(Filament::getPanel(), true);
        });

        return $user;
    }

    /**
     * Salva ou atualiza tokens do Google para o usuário.
     */
    private function salvarTokens(User $user, SocialiteUserContract $oauthUser): void
    {
        $refresh = $oauthUser->refreshToken ?? $user->google_refresh_token;

        $expiresIn = $oauthUser->expiresIn ?? 3600;
        $expiresAt = now()->addSeconds(max(60, (int) $expiresIn - 60));

        $user->forceFill([
            'google_token' => $oauthUser->token,
            'google_refresh_token' => $refresh,
            'google_token_expires_in' => $expiresAt,
        ])->save();
    }
    /**
     * Retorna um Google Client autenticado para o usuário.
     */
    public function getGoogleClient(User $user): GoogleClient
    {
        $client = new GoogleClient();
        $client->setClientId(config('services.google.client_id'));
        $client->setClientSecret(config('services.google.client_secret'));
        $client->setRedirectUri(config('services.google.redirect'));
        $client->setAccessToken([
            'access_token'  => $user->google_token,
            'refresh_token' => $user->google_refresh_token,
            'expires_in'    => $user->google_token_expires_in,
        ]);

        if ($client->isAccessTokenExpired() && $user->google_refresh_token) {
            $newToken = $client->fetchAccessTokenWithRefreshToken($user->google_refresh_token);
            $user->update([
                'google_token' => $newToken['access_token'] ?? null,
                'google_token_expires_in' => $newToken['expires_in'] ?? null,
            ]);
        }

        return $client;
    }
}