<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Http;

class Event extends Model
{
    // Se não especificar o nome da tabela no Model, ele procura a tabela com o nome do Model no plural.
    protected $table = 'events';

    // Sempre que acessarmos $event->items, o Laravel vai devolver um array (não uma string JSON),
    // graças a esse cast. Isso facilita o uso e evita ter que usar json_decode() manualmente.
    // 
    // Já o atributo 'date' será automaticamente convertido para um objeto Carbon (classe de datas do Laravel),
    // permitindo usar métodos como format(), addDays(), diffForHumans(), etc., de forma simples.
    protected $casts = [
        'target_audience' => 'array',
        'prerequisites' => 'array',
        'modules' => 'array',
        'start_date' => 'date',
        'end_date' => 'date',
        'datetime_registration' => 'datetime',
        'finalized_at' => 'datetime',
    ];

    // Tudo que foi enviado pelo POST pode ser atualizado, sem restrição.
    protected $guarded = [];

    // DELETA A IMAGEM DO EVENTO DO BUCKET LOCAL OU PRODUÇÃO QUANDO O EVENTO FOR DELETADO ($event->delete();)
    protected static function booted()
    {
        static::deleting(function ($event) {

            if (!$event->image) {
                return;
            }

            $bucket = config('services.supabase.bucket');
            $path = "events/{$event->image}";

            Http::withHeaders([
                'Authorization' => 'Bearer ' . config('services.supabase.service_role'),
                'apikey'        => config('services.supabase.service_role'),
            ])->delete(
                config('services.supabase.url') .
                "/storage/v1/object/$bucket/$path"
            );
        });
    }


    // RELACIONAMENTOS

    // Retorna o dono do evento. || (1:N)
    public function user() 
    {
        return $this->belongsTo('App\Models\User');
    }

    // Retorna as novidades do evento (mais recentes primeiro).
    public function eventNews()
    {
        return $this->hasMany(EventNews::class)->orderBy('created_at', 'desc');
    }

    // Retorna os usuários participantes do evento. || (N:N)
    // Aqui não existe uma chave estrangeira direta nas tabelas users e events que resolva essa relação.
    // Então é necessário criar uma tabela intermediária (chamada de tabela pivô).
    // Por convenção do Laravel, o nome da tabela pivô será event_user, seguindo ordem alfabética das tabelas relacionadas.
    public function users()
    {
        return $this->belongsToMany('App\Models\User');
    }

    // REGRAS DE NEGÓCIO
    public function isFinalized(): bool
    {
        return $this->finalized_at !== null;
    }

    /** Apenas prazo de inscrição (campo datetime_registration), ignorando calendário do evento. */
    public function registrationDeadlinePassed(): bool
    {
        if ($this->datetime_registration === null) {
            return false;
        }

        return Carbon::now()->greaterThan(Carbon::parse($this->datetime_registration));
    }

    /** Alias histórico: “prazo de inscrição encerrado”. */
    public function registrationClosed(): bool
    {
        return $this->registrationDeadlinePassed();
    }

    public function isFull(): bool
    {
        return $this->users()->count() >= $this->capacity;
    }

    public function calendarStartAt(): Carbon
    {
        $d = $this->start_date->format('Y-m-d');
        $t = $this->normalizeTimeString($this->start_time, '00:00:00');

        return Carbon::parse($d.' '.$t);
    }

    public function calendarEndAt(): Carbon
    {
        $day = $this->end_date ?? $this->start_date;
        $d = $day->format('Y-m-d');
        $t = $this->normalizeTimeString($this->end_time, '23:59:59');

        return Carbon::parse($d.' '.$t);
    }

    /** Após data/hora de início do evento (inscrições fecham e evento aparece como iniciado). */
    public function calendarStarted(): bool
    {
        return Carbon::now()->greaterThanOrEqualTo($this->calendarStartAt());
    }

    /** Após data/hora de término do evento (aparece encerrado na tela). */
    public function calendarEnded(): bool
    {
        return Carbon::now()->greaterThanOrEqualTo($this->calendarEndAt());
    }

    /**
     * Ordem: finalizado → período encerrado → em andamento (inscrições fechadas) → prazo de inscrição → lotação.
     */
    public function registrationsBlockedReason(): ?string
    {
        if ($this->isFinalized()) {
            return 'finalized';
        }
        if ($this->calendarEnded()) {
            return 'ended';
        }
        if ($this->calendarStarted()) {
            return 'started';
        }
        if ($this->registrationDeadlinePassed()) {
            return 'deadline';
        }
        if ($this->isFull()) {
            return 'full';
        }

        return null;
    }

    public function acceptsNewRegistrations(): bool
    {
        return $this->registrationsBlockedReason() === null;
    }

    private function normalizeTimeString(mixed $time, string $default): string
    {
        if ($time === null || $time === '') {
            return $default;
        }
        if ($time instanceof \DateTimeInterface) {
            return $time->format('H:i:s');
        }
        $s = (string) $time;
        if (strlen($s) === 5) {
            return $s.':00';
        }
        if (strlen($s) >= 8) {
            return substr($s, 0, 8);
        }

        return $default;
    }
}
