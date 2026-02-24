<?php

namespace App\Filament\Resources\Wallet\Schemas;

use Filament\Forms;
use Filament\Forms\Form;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class WalletForm
{
    public static function schema(): array
    {
        return [
            Forms\Components\Select::make('investor_id')
                ->label('Investor')
                ->unique()
                ->options(function () {
                    $user = Auth::user();
                    if ($user->role === 'Super Admin') {
                        return User::where('role', 'Investor')->pluck('name', 'id');
                    } elseif ($user->role === 'Agency Owner') {
                        return User::where('role', 'Investor')
                            ->where('invited_by', $user->id)
                            ->pluck('name', 'id');
                    }
                    return [];
                })
                ->getSearchResultsUsing(function (string $search) {
                    $user = Auth::user();
                    if ($user->role === 'Super Admin') {
                        return User::where('role', 'Investor')
                            ->where('name', 'like', "%{$search}%")
                            ->pluck('name', 'id');
                    } elseif ($user->role === 'Agency Owner') {
                        return User::where('role', 'Investor')
                            ->where('invited_by', $user->id)
                            ->where('name', 'like', "%{$search}%")
                            ->pluck('name', 'id');
                    }
                    return [];
                })
                ->required()
                ->searchable(),
            Forms\Components\TextInput::make('total_deposits')
                ->label('Deposit Amount')
                ->numeric()
                ->minValue(0)
                ->required()
                ->helperText('This will be added to lifetime deposits'),
            Forms\Components\Select::make('slip_type')
                ->options([
                    'bank_transfer' => 'Bank Transfer',
                    'cash' => 'Cash',
                    'check' => 'Check',
                ])
                ->required(),
           Forms\Components\FileUpload::make('slip_path')
                ->label('Deposit Slip')
                ->disk('public')  // Explicitly set to public disk
                ->directory('wallet-slips')
                ->visibility('public')  // Make files publicly accessible
                ->downloadable()
                ->openable()
                ->preserveFilenames(),  // Keep original filenames
            Forms\Components\TextInput::make('reference')
                ->label('Reference/Check #')
                ->maxLength(255)
                ->helperText('Optional: Enter reference number or check details'),
            Forms\Components\DatePicker::make('deposited_at')
                ->label('Deposit Date')
                ->default(now()),
        ];
    }
}
