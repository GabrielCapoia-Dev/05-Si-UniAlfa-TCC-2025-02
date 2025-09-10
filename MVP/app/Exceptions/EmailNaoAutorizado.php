<?php

namespace App\Exceptions;

use Exception;

class EmailNaoAutorizado extends Exception
{
    public function __construct($message = "Email não permitido para cadastro")
    {
        parent::__construct($message);
    }

    public function render($request)
    {
        \Filament\Notifications\Notification::make()
            ->title('Acesso não autorizado')
            ->body($this->getMessage())
            ->danger()
            ->send();

        return redirect()->route('filament.admin.auth.login')
            ->with('error', 'Email não é permitido para cadastro, entre em contato com o suporte.');
    }
}
