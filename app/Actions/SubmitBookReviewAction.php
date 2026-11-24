<?php

namespace App\Actions;

use App\Models\Book;
use App\Models\KarmaMovement;
use App\Models\Review;
use App\Models\User;

class SubmitBookReviewAction
{
    /**
     * Crea o actualiza una reseña y otorga +10 Puntos Nova como máximo una vez por usuario y obra.
     *
     * @return array{review: Review, karma_awarded: int}
     */
    public function execute(Book $book, User $user, int $rating, string $comentario, bool $esSpoiler): array
    {
        $review = Review::updateOrCreate(
            ['book_id' => $book->id, 'user_id' => $user->id],
            [
                'rating' => $rating,
                'comentario' => $comentario,
                'es_spoiler' => $esSpoiler,
                'visible' => true,
            ]
        );

        $karmaOtorgado = 0;

        if ($user->reader) {
            $yaRecibioBono = KarmaMovement::where('reader_id', $user->reader->id)
                ->where('referencia_tipo', 'review_book')
                ->where('referencia_id', $book->id)
                ->exists();

            if (! $yaRecibioBono) {
                $user->reader->registrarKarma(
                    10,
                    "Primera reseña en «{$book->titulo}»",
                    'review_book',
                    $book->id
                );
                $karmaOtorgado = 10;
            }
        }

        Book::limpiarCacheCatalogo();

        return [
            'review' => $review,
            'karma_awarded' => $karmaOtorgado,
        ];
    }
}
