<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\User;
use App\Models\Work;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class RepeatedWorkSubmissionTest extends TestCase
{
    use RefreshDatabase;

    public function test_participant_can_submit_the_same_type_and_reuse_a_coauthor(): void
    {
        config([
            'services.supabase.url' => 'https://supabase.test',
            'services.supabase.service_role' => 'test-key',
            'services.supabase.bucket_works' => 'works-test',
        ]);
        Http::fake(['*' => Http::response('', 200)]);

        $author = User::factory()->create([
            'role' => User::ROLE_PARTICIPANT,
            'email' => 'autor@example.com',
        ]);
        $coauthor = User::factory()->create([
            'role' => User::ROLE_PARTICIPANT,
            'name' => 'Coautor Real',
            'email' => 'coautor@example.com',
        ]);
        $event = $this->eventOpenForSubmissions();
        $event->users()->attach($author->id);
        DB::table('event_work_types')->insert([
            [
                'event_id' => $event->id,
                'work_type' => Work::TYPE_RESUMO_SIMPLES,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'event_id' => $event->id,
                'work_type' => Work::TYPE_ARTIGO_COMPLETO,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        $payload = [
            'work_type' => Work::TYPE_RESUMO_SIMPLES,
            'file' => UploadedFile::fake()->create('trabalho.pdf', 20, 'application/pdf'),
            'coauthors_manual' => [
                ['author_email' => 'coautor@example.com'],
            ],
        ];

        $this->actingAs($author)
            ->post(route('events.works.store', $event), $payload)
            ->assertRedirect(route('works.my'));

        $this->actingAs($author)
            ->post(route('events.works.store', $event), $payload)
            ->assertRedirect(route('works.my'));

        $this->actingAs($author)
            ->post(route('events.works.store', $event), [
                'work_type' => Work::TYPE_ARTIGO_COMPLETO,
                'file' => UploadedFile::fake()->create('artigo.pdf', 20, 'application/pdf'),
                'coauthors_manual' => [
                    ['author_email' => 'coautor@example.com'],
                ],
            ])
            ->assertRedirect(route('works.my'));

        $works = Work::query()->where('submitter_user_id', $author->id)->orderBy('id')->get();
        $this->assertCount(3, $works);
        $this->assertSame(
            [Work::TYPE_RESUMO_SIMPLES, Work::TYPE_RESUMO_SIMPLES, Work::TYPE_ARTIGO_COMPLETO],
            $works->pluck('work_type')->all()
        );
        $this->assertCount(3, $works->pluck('file_path')->unique());
        $this->assertSame(
            [1, 1, 1],
            $works->map(fn (Work $work) => $work->authors()->where('user_id', $coauthor->id)->count())->all()
        );

        $this->actingAs($author)
            ->get(route('events.works.create', $event))
            ->assertOk()
            ->assertSee('Resumo Simples')
            ->assertSee('Artigo Completo');
    }

    private function eventOpenForSubmissions(): Event
    {
        return Event::create([
            'user_id' => User::factory()->create()->id,
            'title' => 'Evento',
            'category' => 'Tecnologia',
            'event_type' => 'Congresso',
            'modality' => 'Presencial',
            'capacity' => 20,
            'internal_capacity' => 10,
            'external_capacity' => 10,
            'description' => 'Descricao',
            'start_date' => now()->toDateString(),
            'end_date' => now()->addDay()->toDateString(),
            'campus' => Event::FIXED_CAMPUS,
            'building' => Event::FIXED_BUILDING,
            'venue' => Event::FIXED_VENUE,
            'address' => Event::FIXED_ADDRESS,
            'image' => 'teste.png',
            'coordinator_name' => 'Coordenacao',
            'coordinator_email' => 'coord@example.com',
            'accepts_submissions' => true,
            'submission_deadline_at' => now()->addDay(),
        ]);
    }
}
