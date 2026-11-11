<?php

namespace App\Livewire;

use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class Wishlist extends Component
{
    public function render(): View
    {
        return view('livewire.wishlist')->title('Wishlist');
    }
}
