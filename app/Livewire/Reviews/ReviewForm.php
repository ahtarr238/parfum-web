<?php

namespace App\Livewire\Reviews;

use App\Exceptions\OrderException;
use App\Services\ReviewService;
use Illuminate\Contracts\View\View;
use Illuminate\Database\QueryException;
use Livewire\Attributes\Locked;
use Livewire\Component;

class ReviewForm extends Component
{
    #[Locked]
    public int $orderItemId;

    public bool $open = false;

    public int $rating = 0;

    public string $comment = '';

    public bool $done = false;

    public function save(ReviewService $reviews): void
    {
        $this->validate([
            'rating' => ['required', 'integer', 'between:1,5'],
            'comment' => ['nullable', 'string', 'max:1000'],
        ], [
            'rating.between' => 'Pilih 1 sampai 5 bintang.',
            'rating.required' => 'Pilih 1 sampai 5 bintang.',
        ]);

        try {
            $reviews->submit(auth()->user(), $this->orderItemId, $this->rating, $this->comment);
        } catch (OrderException $e) {
            $this->addError('rating', $e->getMessage());

            return;
        } catch (QueryException) {
            $this->addError('rating', 'Kamu sudah mengulas item ini.');

            return;
        }

        $this->done = true;
        $this->open = false;
    }

    public function render(): View
    {
        return view('livewire.reviews.review-form');
    }
}
