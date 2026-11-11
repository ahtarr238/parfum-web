<?php

namespace App\Livewire\Account;

use App\Services\AddressBook;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Layout('layouts.app')]
class Addresses extends Component
{
    #[Url(as: 'kembali', except: '')]
    public string $back = '';

    public bool $showForm = false;

    public ?int $editingId = null;

    public function create(): void
    {
        $this->editingId = null;
        $this->showForm = true;
    }

    public function edit(int $id): void
    {
        auth()->user()->addresses()->findOrFail($id);
        $this->editingId = $id;
        $this->showForm = true;
    }

    public function closeForm(): void
    {
        $this->showForm = false;
        $this->editingId = null;
    }

    #[On('address-saved')]
    public function saved(): void
    {
        $this->closeForm();
        session()->flash('status', 'Alamat tersimpan.');
    }

    public function makeDefault(int $id, AddressBook $book): void
    {
        $book->makeDefault(auth()->user()->addresses()->findOrFail($id));
    }

    public function delete(int $id, AddressBook $book): void
    {
        $book->remove(auth()->user()->addresses()->findOrFail($id));
    }

    public function render(): View
    {
        return view('livewire.account.addresses', [
            'addresses' => auth()->user()->addresses()->orderByDesc('is_default')->latest()->get(),
            'redirectTo' => $this->back === 'checkout' ? route('checkout') : null,
        ])->title('Alamat');
    }
}
