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

    // Retorna os usuários participantes do evento. || (N:N)
    // Aqui não existe uma chave estrangeira direta nas tabelas users e events que resolva essa relação.
    // Então é necessário criar uma tabela intermediária (chamada de tabela pivô).
    // Por convenção do Laravel, o nome da tabela pivô será event_user, seguindo ordem alfabética das tabelas relacionadas.
    public function users()
    {
        return $this->belongsToMany('App\Models\User');
    }

    // REGRAS DE NEGÓCIO
    public function registrationClosed()
    {
        return Carbon::now()->greaterThan(Carbon::parse($this->datetime_registration));
    }

    public function isFull()
    {
        return $this->users()->count() >= $this->capacity;
    }
}
