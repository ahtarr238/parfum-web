<?php

namespace App\Livewire\Account;

use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class Addresses extends Component
{
    public function render(): View
    {
        return view('livewire.account.addresses')->title('Alamat');
    }
}
