<?php

namespace App\Services;

use App\Models\Event;
use App\Models\Work;
use App\Models\WorkAuthor;
use Illuminate\Support\Str;

class AbntWorkReference
{
    public static function forWork(Work $work): ?string
    {
        $work->loadMissing(['event', 'authors']);
        $event = $work->event;

        if (! $event || ! $event->hasAnnalsPublication()) {
            return null;
        }

        if (! $work->qualifiesForAnnalsCertificate()) {
            return null;
        }

        $authors = $work->authors->sortBy('author_order')->values();
        if ($authors->isEmpty()) {
            return null;
        }

        $authorText = self::authors($authors->all());
        $title = trim($work->displayTitleForPresentationCertificate());
        if ($title === '') {
            return null;
        }

        $year = $event->annals_uploaded_at?->year
            ?? $event->end_date?->year
            ?? now()->year;
        $eventTitle = mb_strtoupper(trim((string) $event->title));
        $kind = $event->annals_kind === Event::ANNALS_KIND_EBOOK ? 'E-book' : 'Anais';
        $institution = trim($event->certificateInstitutionDisplay());

        return $authorText.'. '.$title.'. In: '.$eventTitle.', '.$year.', Belém. '
            .$kind.' [...]. Belém: '.$institution.', '.$year.'.';
    }

    /**
     * @param  list<WorkAuthor>  $authors
     */
    private static function authors(array $authors): string
    {
        $shown = array_slice($authors, 0, 3);
        $parts = array_map(fn (WorkAuthor $author) => self::person((string) $author->author_name), $shown);
        $text = implode('; ', $parts);

        if (count($authors) > 3) {
            $text .= ' et al.';
        }

        return $text;
    }

    private static function person(string $name): string
    {
        $clean = trim(preg_replace('/\s+/u', ' ', $name) ?? '');
        if ($clean === '') {
            return 'AUTOR';
        }

        $parts = preg_split('/\s+/u', $clean) ?: [];
        $surname = mb_strtoupper((string) array_pop($parts));
        $given = $parts === [] ? '' : Str::title(implode(' ', $parts));

        return $given === '' ? $surname : $surname.', '.$given;
    }
}
