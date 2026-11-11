<?php

namespace App\Services;

use App\Models\Activity;
use App\Models\Certificate;
use App\Models\Event;
use App\Models\EventPresence;
use App\Models\User;
use Carbon\Carbon;
use chillerlan\QRCode\Output\QROutputInterface;
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;

class CertificateDocument
{
    /**
     * Dados que o PDF e a página pública repetem.
     *
     * @return array{
     *     hours: string,
     *     period: string,
     *     activityPeriod: ?string,
     *     place: string,
     *     modality: string,
     *     matricula: ?string,
     *     presence: string,
     *     validationUrl: string,
     *     qrDataUri: string
     * }
     */
    public static function forCertificate(Certificate $certificate): array
    {
        $certificate->loadMissing(['user', 'event', 'activity', 'work']);

        $activity = $certificate->tipo === Certificate::TYPE_ACTIVITY ? $certificate->activity : null;
        $channel = $certificate->tipo === Certificate::TYPE_PARTICIPATION
            ? self::attendanceChannel(self::eventPresence($certificate), $certificate->event)
            : null;
        $url = self::validationUrl((string) $certificate->codigo_validacao);

        return [
            'hours' => self::hours($certificate),
            'period' => self::period($certificate->event),
            'activityPeriod' => $activity ? self::activityPeriod($activity) : null,
            'place' => $activity
                ? self::activityPlace($activity, $certificate->event)
                : self::attendancePlace($certificate->event, $channel),
            'modality' => self::modality($certificate->event),
            'matricula' => $certificate->user ? self::matricula($certificate->user) : null,
            'presence' => self::presenceText($certificate->tipo, $channel),
            'validationUrl' => $url,
            'qrDataUri' => self::qrDataUri($url),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function pdfFields(?User $user, Event $event, string $code, string $tipo, ?Activity $activity = null, ?string $attendanceChannel = null): array
    {
        $url = self::validationUrl($code);

        return [
            'period' => self::period($event),
            'activityPeriod' => $activity ? self::activityPeriod($activity) : null,
            'place' => $activity ? self::activityPlace($activity, $event) : self::attendancePlace($event, $attendanceChannel),
            'modality' => self::modality($event),
            'matricula' => $user ? self::matricula($user) : null,
            'presence' => self::presenceText($tipo, $attendanceChannel),
            'validationUrl' => $url,
            'qrDataUri' => self::qrDataUri($url),
        ];
    }

    public static function period(Event $event): string
    {
        $start = $event->start_date;
        if ($start === null) {
            return '-';
        }

        $end = $event->end_date ?? $start;
        $startTime = self::clock($event->start_time);
        $endTime = self::clock($event->end_time);

        if ($start->toDateString() === $end->toDateString()) {
            if ($startTime && $endTime) {
                return $start->format('d/m/Y').', '.$startTime.' às '.$endTime;
            }

            return $start->format('d/m/Y');
        }

        $startLabel = $start->format('d/m/Y').($startTime ? ' '.$startTime : '');
        $endLabel = $end->format('d/m/Y').($endTime ? ' '.$endTime : '');

        return $startLabel.' até '.$endLabel;
    }

    public static function activityPeriod(Activity $activity): ?string
    {
        if ($activity->start_at === null) {
            return null;
        }

        $start = Carbon::parse($activity->start_at);
        $end = $activity->end_at ? Carbon::parse($activity->end_at) : null;

        if ($end === null) {
            return $start->format('d/m/Y H:i');
        }

        if ($start->toDateString() === $end->toDateString()) {
            return $start->format('d/m/Y').', '.$start->format('H:i').' às '.$end->format('H:i');
        }

        return $start->format('d/m/Y H:i').' até '.$end->format('d/m/Y H:i');
    }

    public static function campusAddress(Event $event): string
    {
        $address = trim((string) ($event->address ?: ''));
        if ($address === '') {
            $address = trim((string) ($event->venue ?: Event::FIXED_ADDRESS));
        }

        if (! str_contains($address, 'Belém')) {
            $address .= ', Belém/PA';
        }

        return $address;
    }

    public static function place(Event $event): string
    {
        if ($event->modality === 'Online') {
            return 'Online';
        }

        $address = self::campusAddress($event);

        if ($event->modality === 'Híbrido') {
            return $address.' (participação online)';
        }

        return $address;
    }

    public static function attendancePlace(Event $event, ?string $channel): string
    {
        if ($channel === 'online') {
            return 'Online';
        }

        if ($channel === 'presencial') {
            return self::campusAddress($event);
        }

        if ($channel === 'ambos') {
            return self::campusAddress($event).' (participação online)';
        }

        return self::place($event);
    }

    public static function attendanceChannel(?EventPresence $presence, Event $event): ?string
    {
        if (! $presence || ! $presence->presente) {
            return null;
        }

        $inPerson = (bool) $presence->presente_presencial;
        $online = (bool) $presence->presente_online;

        if ($inPerson && $online) {
            return 'ambos';
        }

        if ($online) {
            return 'online';
        }

        if ($inPerson) {
            return 'presencial';
        }

        if ($event->modality === 'Online') {
            return 'online';
        }

        if ($event->modality === 'Híbrido') {
            return 'ambos';
        }

        return 'presencial';
    }

    private static function eventPresence(Certificate $certificate): ?EventPresence
    {
        if (! $certificate->user_id || ! $certificate->event_id) {
            return null;
        }

        return EventPresence::query()
            ->where('event_id', $certificate->event_id)
            ->where('user_id', $certificate->user_id)
            ->first();
    }

    public static function presenceText(string $tipo, ?string $channel = null): string
    {
        if ($tipo === Certificate::TYPE_PARTICIPATION) {
            return match ($channel) {
                'online' => 'Presença online registrada',
                'presencial' => 'Presença presencial registrada',
                'ambos' => 'Presença presencial e online registrada',
                default => 'Presença no evento registrada',
            };
        }

        return self::presence($tipo);
    }

    public static function activityPlace(Activity $activity, Event $event): string
    {
        if ($event->modality === 'Online') {
            return 'Online';
        }

        $location = trim((string) ($activity->location ?? ''));
        $eventPlace = self::place($event);

        if ($location === '') {
            return $eventPlace;
        }

        return $location.', '.$eventPlace;
    }

    public static function modality(Event $event): string
    {
        $modality = trim((string) ($event->modality ?? ''));

        return $modality !== '' ? $modality : '-';
    }

    public static function matricula(User $user): ?string
    {
        $profile = (string) $user->participant_profile;
        $isBelemDiscente = $profile === User::PROFILE_DISCENTE;
        $isOtherCampusDiscente = $profile === User::PROFILE_IFPA_OUTRO_CAMPUS
            && (string) $user->ifpa_bond === User::PROFILE_DISCENTE;
        if (! $isBelemDiscente && ! $isOtherCampusDiscente) {
            return null;
        }

        $matricula = trim((string) ($user->matricula ?? ''));

        return $matricula !== '' ? $matricula : null;
    }

    public static function presence(string $tipo): string
    {
        return match ($tipo) {
            Certificate::TYPE_ACTIVITY => 'Presença na atividade registrada',
            Certificate::TYPE_PRESENTATION => 'Presença na apresentação registrada',
            Certificate::TYPE_REVIEWER => 'Parecer concluído registrado',
            Certificate::TYPE_GUEST => 'Participação como convidado registrada',
            Certificate::TYPE_ORGANIZATION => 'Participação na organização registrada',
            Certificate::TYPE_COAUTHOR => 'Coautoria na apresentação registrada',
            Certificate::TYPE_PUBLICATION => 'Publicação nos anais registrada',
            default => 'Presença no evento registrada',
        };
    }

    public static function hours(Certificate $certificate): string
    {
        $certificate->loadMissing(['event', 'activity', 'work']);

        return match ($certificate->tipo) {
            Certificate::TYPE_ACTIVITY => self::hoursText($certificate->activity?->workload_hours),
            Certificate::TYPE_PRESENTATION, Certificate::TYPE_COAUTHOR => self::hoursText($certificate->work?->certificate_presentation_hours ?? 2),
            Certificate::TYPE_REVIEWER => self::hoursText($certificate->event?->certificate_reviewer_hours),
            Certificate::TYPE_GUEST => self::hoursText($certificate->event?->certificate_guest_hours),
            Certificate::TYPE_ORGANIZATION => self::hoursText($certificate->event?->certificate_organization_hours),
            Certificate::TYPE_PUBLICATION => self::hoursText($certificate->event?->certificate_publication_hours),
            default => self::hoursText($certificate->event?->certificate_total_hours),
        };
    }

    public static function validationUrl(string $code): string
    {
        return route('certificates.verify', ['codigo' => $code]);
    }

    public static function qrDataUri(string $url): string
    {
        $options = new QROptions([
            'outputType' => QROutputInterface::GDIMAGE_PNG,
            'scale' => 4,
            'outputBase64' => true,
        ]);

        return (new QRCode($options))->render($url);
    }

    private static function hoursText(mixed $hours): string
    {
        if ($hours === null || $hours === '') {
            return '-';
        }

        return (string) $hours;
    }

    private static function clock(mixed $time): ?string
    {
        $raw = trim((string) ($time ?? ''));
        if ($raw === '') {
            return null;
        }

        return substr($raw, 0, 5);
    }
}
