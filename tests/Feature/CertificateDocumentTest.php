<?php

namespace Tests\Feature;

use App\Models\Activity;
use App\Models\Certificate;
use App\Models\Event;
use App\Models\User;
use App\Services\CertificateDocument;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CertificateDocumentTest extends TestCase
{
    use RefreshDatabase;

    public function test_place_names_the_city_and_online_events_stay_online(): void
    {
        $presencial = $this->makeEvent(['modality' => 'Presencial']);
        $this->assertStringContainsString('Belém', CertificateDocument::place($presencial));

        $online = $this->makeEvent(['modality' => 'Online']);
        $this->assertSame('Online', CertificateDocument::place($online));

        $hybrid = $this->makeEvent(['modality' => 'Híbrido']);
        $this->assertStringContainsString('participação online', CertificateDocument::place($hybrid));
        $this->assertStringContainsString('Belém', CertificateDocument::place($hybrid));
    }

    public function test_pdf_and_public_page_repeat_period_place_modality_matricula_presence_and_qr(): void
    {
        $event = $this->makeEvent([
            'modality' => 'Presencial',
            'certificate_total_hours' => 8,
            'start_date' => '2026-05-05',
            'end_date' => '2026-05-08',
            'start_time' => '08:00:00',
            'end_time' => '21:00:00',
        ]);
        $student = User::factory()->create([
            'role' => User::ROLE_PARTICIPANT,
            'participant_profile' => User::PROFILE_DISCENTE,
            'is_external_participant' => false,
            'matricula' => '123456789012',
            'name' => 'Ana Discente',
        ]);
        $external = User::factory()->create([
            'role' => User::ROLE_PARTICIPANT,
            'participant_profile' => User::PROFILE_COMUNIDADE_GERAL,
            'is_external_participant' => true,
            'matricula' => null,
            'name' => 'Bruno Externo',
        ]);

        $studentCertificate = $this->makeCertificate($student, $event, 'CERTALUNO123456');
        $externalCertificate = $this->makeCertificate($external, $event, 'CERTEXTERNO1234');

        $html = view('certificates.pdf.participacao', array_merge([
            'recipientName' => $student->name,
            'eventTitle' => $event->title,
            'organizer' => 'Coordenação',
            'institution' => 'IFPA',
            'hours' => '8',
            'issuedDate' => '03/11/2026',
            'validationCode' => $studentCertificate->codigo_validacao,
            'signatures' => [],
        ], CertificateDocument::pdfFields($student, $event, $studentCertificate->codigo_validacao, Certificate::TYPE_PARTICIPATION)))->render();

        $this->assertStringContainsString('05/05/2026 08:00 até 08/05/2026 21:00', $html);
        $this->assertStringContainsString('Belém', $html);
        $this->assertStringContainsString('Presencial', $html);
        $this->assertStringContainsString('123456789012', $html);
        $this->assertStringContainsString('Presença no evento registrada', $html);
        $this->assertStringContainsString('PARTICIPAÇÃO', $html);
        $this->assertStringContainsString('data:image/png;base64', $html);
        $this->assertStringContainsString('data:image/jpeg;base64', $html);
        $this->assertFileExists(resource_path('certificates/template.jpg'));
        $this->assertFileDoesNotExist(public_path('certificates/template.jpg'));

        $this->get(route('certificates.verify', ['codigo' => $studentCertificate->codigo_validacao]))
            ->assertOk()
            ->assertSee('Ana Discente')
            ->assertSee('05/05/2026 08:00 até 08/05/2026 21:00')
            ->assertSee('Belém')
            ->assertSee('Presencial')
            ->assertSee('123456789012')
            ->assertSee('Presença no evento registrada')
            ->assertSee('8.00 hora(s)', false)
            ->assertSee('data:image/png;base64', false);

        $this->get(route('certificates.verify', ['codigo' => $externalCertificate->codigo_validacao]))
            ->assertOk()
            ->assertSee('Bruno Externo')
            ->assertDontSee('Matrícula:');
    }

    public function test_all_certificate_views_print_on_the_same_template(): void
    {
        $shared = [
            'period' => '05/05/2026',
            'activityPeriod' => '06/05/2026, 10:00 às 12:00',
            'place' => 'Belém/PA',
            'modality' => 'Presencial',
            'matricula' => '123456789012',
            'presence' => 'Presença no evento registrada',
            'validationUrl' => 'https://example.test/certificado/ABC',
            'qrDataUri' => '',
            'validationCode' => 'ABC123',
            'issuedDate' => '26/11/2026',
            'institution' => 'IFPA Campus Belém',
            'signatures' => [
                ['nome' => 'Maria Souza', 'cargo' => 'Coordenadora', 'src' => null],
            ],
            'hours' => '8',
            'recipientName' => 'Ana Discente',
            'eventTitle' => 'Semana de Tecnologia',
            'organizer' => 'Coordenação',
            'activityTitle' => 'Oficina de PHP',
            'activityTypeLabel' => 'oficina',
            'workTitle' => 'Um título de trabalho',
            'publicationKind' => 'anais',
            'title' => 'Certificado de convidado',
            'bodyHtml' => 'Certificamos que <strong>Ana Discente</strong> participou como convidada.',
        ];

        foreach ([
            'certificates.pdf.participacao',
            'certificates.pdf.atividade',
            'certificates.pdf.apresentacao',
            'certificates.pdf.publicacao',
            'certificates.pdf.opcional',
        ] as $view) {
            $html = view($view, $shared)->render();
            $this->assertStringContainsString('data:image/jpeg;base64', $html, $view);
            $this->assertStringContainsString('Maria Souza', $html, $view);
        }
    }

    public function test_activity_sheet_includes_the_activity_period_and_room(): void
    {
        $event = $this->makeEvent(['modality' => 'Presencial']);
        $activity = Activity::create([
            'event_id' => $event->id,
            'title' => 'Oficina de PHP',
            'type' => Activity::TYPE_WORKSHOP,
            'start_at' => '2026-05-06 10:00:00',
            'end_at' => '2026-05-06 12:00:00',
            'location' => 'Laboratório 2',
            'workload_hours' => 2,
        ]);
        $student = User::factory()->create([
            'role' => User::ROLE_PARTICIPANT,
            'participant_profile' => User::PROFILE_DISCENTE,
            'matricula' => '123456789012',
        ]);
        $certificate = Certificate::create([
            'user_id' => $student->id,
            'tipo' => Certificate::TYPE_ACTIVITY,
            'event_id' => $event->id,
            'activity_id' => $activity->id,
            'arquivo_pdf' => 'certificates/1/atividade.pdf',
            'codigo_validacao' => 'CERTATIVIDADE01',
            'data_emissao' => now(),
        ]);

        $sheet = CertificateDocument::forCertificate($certificate);

        $this->assertSame('06/05/2026, 10:00 às 12:00', $sheet['activityPeriod']);
        $this->assertStringContainsString('Laboratório 2', $sheet['place']);
        $this->assertSame('Presença na atividade registrada', $sheet['presence']);

        $this->get(route('certificates.verify', ['codigo' => $certificate->codigo_validacao]))
            ->assertOk()
            ->assertSee('Oficina de PHP')
            ->assertSee('06/05/2026, 10:00 às 12:00')
            ->assertSee('Laboratório 2');
    }

    private function makeEvent(array $overrides = []): Event
    {
        $coordinatorId = $overrides['user_id'] ?? User::factory()->create()->id;
        $start = now()->addMonth()->startOfDay();

        return Event::create(array_merge([
            'user_id' => $coordinatorId,
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
        ], $overrides));
    }

    private function makeCertificate(User $user, Event $event, string $code): Certificate
    {
        return Certificate::create([
            'user_id' => $user->id,
            'tipo' => Certificate::TYPE_PARTICIPATION,
            'event_id' => $event->id,
            'arquivo_pdf' => 'certificates/'.$event->id.'/'.$code.'.pdf',
            'codigo_validacao' => $code,
            'data_emissao' => now(),
        ]);
    }
}
