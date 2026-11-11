<?php

namespace App\Services;

use App\Models\Faq;
use Illuminate\Support\Collection;

/**
 * Chatbot rule-based: cocokkan keyword (kata/frasa utuh) ke tabel faqs.
 */
class ChatbotService
{
    public function match(string $message): ?Faq
    {
        $text = ' '.$this->normalize($message).' ';

        if (trim($text) === '') {
            return null;
        }

        $best = null;
        $bestScore = 0;

        foreach ($this->faqs() as $faq) {
            $phrases = array_unique([...$faq->keywordList(), $this->normalize($faq->question)]);
            $score = 0;

            foreach ($phrases as $phrase) {
                if ($phrase !== '' && str_contains($text, ' '.$phrase.' ')) {
                    // Frasa yang lebih panjang lebih spesifik.
                    $score += 1 + substr_count($phrase, ' ');
                }
            }

            if ($score > $bestScore) {
                $best = $faq;
                $bestScore = $score;
            }
        }

        return $best;
    }

    /**
     * @return Collection<int, Faq>
     */
    public function quickQuestions(int $limit = 4): Collection
    {
        return $this->faqs()->take($limit);
    }

    /**
     * @return Collection<int, Faq>
     */
    private function faqs(): Collection
    {
        return Faq::where('is_active', true)->orderBy('id')->get();
    }

    private function normalize(string $text): string
    {
        $text = mb_strtolower($text);
        // Pertahankan huruf, angka, & (untuk "j&t"); sisanya jadi spasi.
        $text = preg_replace('/[^\p{L}\p{N}&]+/u', ' ', $text);

        return trim(preg_replace('/\s+/', ' ', $text));
    }
}
