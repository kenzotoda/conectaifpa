<?php

namespace Tests\Feature;

use App\Models\Certificate;
use App\Models\Event;
use App\Models\EventGuest;
use App\Models\Review;
use App\Models\User;
use App\Models\Work;
use App\Models\WorkAuthor;
use App\Services\CertificateIssuer;
use App\Services\SupabaseObjectClient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class OptionalCertificateTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
        $this->mock(SupabaseObjectClient::class, function ($mock) {
            $mock->shouldReceive('isConfigured')->andReturn(false);
        });
    }

    public function test_optional_types_stay_off_until_the_coordinator_enables_them(): void
    {
        [$event, $coordinator] = $this->readyEvent();
        $reviewer = User::factory()->create(['role' => User::ROLE_REVIEWER, 'name' => 'Eva Avaliadora']);
        $this->submittedReview($event, $reviewer);
        $event->refresh();

        $this->assertFalse($event->certificate_reviewer_enabled);
        $this->assertSame(0, app(CertificateIssuer::class)->batchReviewers($event));
        $this->assertSame(0, Certificate::query()->count());

        $this->actingAs($coordinator)
            ->post(route('events.certificates.optional', $event), [
                'certificate_reviewer_enabled' => '0',
                'certificate_guest_enabled' => '0',
                'certificate_organization_enabled' => '0',
                'certificate_coauthor_enabled' => '1',
            ])
            ->assertRedirect();

        $event->refresh();
        $this->assertTrue($event->certificate_coauthor_enabled);
        $this->assertFalse($event->certificate_reviewer_enabled);

        $this->actingAs($coordinator)
            ->post(route('events.certificates.generate.reviewers', $event))
            ->assertRedirect()
            ->assertSessionHas('msg');

        $this->assertSame(0, Certificate::query()->where('tipo', Certificate::TYPE_REVIEWER)->count());
    }

    public function test_reviewer_with_a_submitted_opinion_receives_a_certificate_on_their_account(): void
    {
        [$event] = $this->readyEvent();
        $event->update([
            'certificate_reviewer_enabled' => true,
            'certificate_reviewer_hours' => 12,
        ]);
        $finished = User::factory()->create(['role' => User::ROLE_REVIEWER, 'name' => 'Eva Avaliadora']);
        $pending = User::factory()->create(['role' => User::ROLE_REVIEWER, 'name' => 'Otto Pendente']);
        $this->submittedReview($event, $finished);
        $this->submittedReview($event, $pending, submitted: false);

        $this->assertSame(1, app(CertificateIssuer::class)->batchReviewers($event->fresh()));

        $certificate = Certificate::query()->where('tipo', Certificate::TYPE_REVIEWER)->first();
        $this->assertNotNull($certificate);
        $this->assertSame($finished->id, $certificate->user_id);
        $this->assertSame('Eva Avaliadora', $certificate->recipient_name);

        $this->actingAs($finished)
            ->get(route('certificates.my'))
            ->assertOk()
            ->assertSee('atuação como avaliador');

        $this->actingAs($pending)
            ->get(route('certificates.my'))
            ->assertOk()
            ->assertDontSee('Eva Avaliadora');

        $this->get(route('certificates.verify', ['codigo' => $certificate->codigo_validacao]))
            ->assertOk()
            ->assertSee('Eva Avaliadora')
            ->assertSee('Parecer concluído registrado')
            ->assertSee('12.00 hora(s)');
    }

    public function test_guest_and_organization_without_accounts_are_downloaded_by_the_coordinator(): void
    {
        [$event, $coordinator] = $this->readyEvent();
        $event->update([
            'certificate_guest_enabled' => true,
            'certificate_organization_enabled' => true,
        ]);
        EventGuest::create([
            'event_id' => $event->id,
            'name' => 'Lia Palestrante',
            'role_type' => 'palestrante',
            'role' => 'Abertura',
        ]);
        EventGuest::create([
            'event_id' => $event->id,
            'name' => 'Caio Monitor',
            'role_type' => 'monitor',
            'role' => 'Apoio',
        ]);

        $issuer = app(CertificateIssuer::class);
        $this->assertSame(1, $issuer->batchGuests($event->fresh()));
        $this->assertSame(1, $issuer->batchOrganization($event->fresh()));

        $guestCertificate = Certificate::query()->where('tipo', Certificate::TYPE_GUEST)->first();
        $this->assertNull($guestCertificate->user_id);
        $this->assertSame('Lia Palestrante', $guestCertificate->recipient_name);
        $this->assertSame('Palestrante', $guestCertificate->recipient_role);

        $this->actingAs($coordinator)
            ->get(route('events.certificates.issued', $event))
            ->assertOk()
            ->assertSee('Lia Palestrante')
            ->assertSee('Caio Monitor')
            ->assertSee('Sem conta');

        $this->get(route('certificates.verify', ['codigo' => $guestCertificate->codigo_validacao]))
            ->assertOk()
            ->assertSee('Lia Palestrante')
            ->assertSee('Palestrante');
    }

    public function test_coauthor_pdf_links_to_an_account_only_when_the_email_matches(): void
    {
        [$event] = $this->readyEvent(['accepts_submissions' => true]);
        $event->update(['certificate_coauthor_enabled' => true]);
        $author = User::factory()->create(['role' => User::ROLE_PARTICIPANT]);
        $known = User::factory()->create([
            'role' => User::ROLE_PARTICIPANT,
            'email' => 'coautor@example.com',
            'name' => 'Conta Coautor',
        ]);
        $work = Work::create([
            'event_id' => $event->id,
            'submitter_user_id' => $author->id,
            'title' => 'Trabalho principal',
            'abstract' => null,
            'work_type' => Work::TYPE_RESUMO_SIMPLES,
            'file_path' => 'arquivo.pdf',
            'status' => Work::STATUS_PRESENTED,
            'certificate_presentation_title' => 'Titulo no certificado',
            'certificate_presentation_hours' => 3,
        ]);
        WorkAuthor::create([
            'work_id' => $work->id,
            'user_id' => $author->id,
            'author_name' => 'Autor Principal',
            'is_main_author' => true,
            'author_order' => 1,
        ]);
        $linked = WorkAuthor::create([
            'work_id' => $work->id,
            'author_name' => 'Nome no trabalho',
            'author_email' => 'Coautor@example.com',
            'is_main_author' => false,
            'author_order' => 2,
        ]);
        $loose = WorkAuthor::create([
            'work_id' => $work->id,
            'author_name' => 'Sem Cadastro',
            'author_email' => 'ninguem@example.com',
            'is_main_author' => false,
            'author_order' => 3,
        ]);

        $this->assertSame(2, app(CertificateIssuer::class)->batchCoauthors($event->fresh()));

        $linkedCertificate = Certificate::query()->where('work_author_id', $linked->id)->first();
        $looseCertificate = Certificate::query()->where('work_author_id', $loose->id)->first();

        $this->assertSame($known->id, $linkedCertificate->user_id);
        $this->assertSame('Nome no trabalho', $linkedCertificate->recipient_name);
        $this->assertNull($looseCertificate->user_id);
        $this->assertSame(0, Certificate::query()->where('tipo', Certificate::TYPE_COAUTHOR)->where('recipient_name', 'Autor Principal')->count());

        $this->actingAs($known)
            ->get(route('certificates.my'))
            ->assertOk()
            ->assertSee('coautoria de trabalho');
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array{0: Event, 1: User}
     */
    private function readyEvent(array $overrides = []): array
    {
        $coordinator = User::factory()->create(['role' => User::ROLE_COORDINATOR]);
        $start = now()->addMonth()->startOfDay();

        $event = Event::create(array_merge([
            'user_id' => $coordinator->id,
            'title' => 'Semana de Tecnologia',
            'category' => 'Tecnologia',
            'event_type' => 'Congresso',
            'modality' => 'Presencial',
            'natureza' => 'cientifico',
            'abrangencia' => 'nacional',
            'periodicidade' => 'unico',
            'acesso' => Event::ACCESS_FREE,
            'capacity' => 20,
            'internal_capacity' => 10,
            'external_capacity' => 10,
            'description' => 'Descricao do evento de teste.',
            'start_date' => $start->toDateString(),
            'end_date' => $start->copy()->addDay()->toDateString(),
            'start_time' => '10:00:00',
            'end_time' => '18:00:00',
            'campus' => Event::FIXED_CAMPUS,
            'building' => Event::FIXED_BUILDING,
            'venue' => Event::FIXED_VENUE,
            'address' => Event::FIXED_ADDRESS,
            'image' => 'teste.png',
            'coordinator_name' => 'Coordenacao',
            'coordinator_email' => 'coord@example.com',
            'datetime_registration' => $start->copy()->setTime(9, 0),
            'accepts_submissions' => false,
            'certificate_organizer' => 'Coordenação do evento',
            'certificate_institution' => 'IFPA Campus Belém',
            'finalized_at' => now(),
        ], $overrides));

        return [$event, $coordinator];
    }

    private function submittedReview(Event $event, User $reviewer, bool $submitted = true): void
    {
        $author = User::factory()->create(['role' => User::ROLE_PARTICIPANT]);
        $work = Work::create([
            'event_id' => $event->id,
            'submitter_user_id' => $author->id,
            'title' => 'Trabalho avaliado',
            'abstract' => null,
            'work_type' => Work::TYPE_RESUMO_SIMPLES,
            'file_path' => 'arquivo.pdf',
            'status' => Work::STATUS_SUBMITTED,
        ]);

        Review::create([
            'work_id' => $work->id,
            'reviewer_user_id' => $reviewer->id,
            'recommendation' => Review::RECOMMENDATION_ACCEPT,
            'submitted_at' => $submitted ? now() : null,
        ]);
    }
}
