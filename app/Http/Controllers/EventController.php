<?php

namespace App\Http\Controllers;

use App\Models\Activity;
use App\Models\Event;
use App\Models\EventDocument;
use App\Models\EventGuest;
use App\Models\EventNews;
use App\Models\EventSponsor;
// Acesso ao Model de eventos
use App\Models\User;
use App\Models\Work;
use App\Models\WorkPresentation;
use App\Notifications\EventRegistrationConfirmed;
use App\Services\EventMailer;
use App\Services\SupabaseObjectClient;
// Acesso ao Model de novidades
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
// Acesso ao Model de usuários
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Mews\Purifier\Facades\Purifier;

class EventController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->search;

        $query = Event::orderBy('start_date', 'asc');

        if ($search) {
            $query->where('title', 'like', '%'.$search.'%');
        }

        // só 6 eventos iniciais
        $events = $query->take(6)->get();

        return view('newWelcome', ['events' => $events, 'search' => $search]);
    }

    public function loadMore(Request $request)
    {
        $page = $request->get('page', 1);
        $perPage = 6;

        $query = Event::orderBy('start_date', 'asc');

        if ($request->search) {
            $query->where('title', 'like', '%'.$request->search.'%');
        }

        $events = $query->skip(($page - 1) * $perPage)
            ->take($perPage)
            ->get();

        return view('events.cards', compact('events'))->render();
    }

    public function create()
    {
        return view('events.newCreate', [
            'eventTypeOptions' => Event::EVENT_TYPE_OPTIONS,
            'workTypes' => Work::workTypeOptions(),
            'workTypeLabels' => Work::workTypeLabels(),
            'activityTypeOptions' => Activity::typeOptions(),
            'activityTypeLabels' => Activity::typeLabels(),
            'guestRoleTypeOptions' => EventGuest::roleTypeOptions(),
            'guestRoleTypeLabels' => EventGuest::roleTypeLabels(),
            'documentTypeLabels' => EventDocument::typeLabels(),
        ]);
    }

    /**
     * @return array<int, mixed>
     */
    private function internalCapacityRules(?Event $event = null): array
    {
        $counts = $event?->audienceRegistrationCounts();

        return [
            'bail',
            'required',
            'integer',
            'min:0',
            function ($attribute, $value, $fail) use ($counts) {
                if ($counts === null) {
                    return;
                }

                if ((int) $value < $counts['internal']) {
                    $fail('Já existem '.$counts['internal'].' participantes internos inscritos. O limite de vagas internas não pode ser menor que '.$counts['internal'].'. Remova inscritos antes de reduzir este limite.');
                }
            },
        ];
    }

    /**
     * @return array<int, mixed>
     */
    private function externalCapacityRules(Request $request, ?Event $event = null): array
    {
        $counts = $event?->audienceRegistrationCounts();

        return [
            'bail',
            'required',
            'integer',
            'min:0',
            function ($attribute, $value, $fail) use ($request, $counts) {
                if ($counts !== null && (int) $value < $counts['external']) {
                    $fail('Já existem '.$counts['external'].' participantes externos inscritos. O limite de vagas externas não pode ser menor que '.$counts['external'].'. Remova inscritos antes de reduzir este limite.');
                }

                if (! is_numeric($request->input('capacity')) || ! is_numeric($request->input('internal_capacity')) || ! is_numeric($value)) {
                    return;
                }

                $capacity = (int) $request->input('capacity');
                $internal = (int) $request->input('internal_capacity');
                $external = (int) $value;
                if ($internal + $external !== $capacity) {
                    $fail('A soma das vagas internas ('.$internal.') e externas ('.$external.') deve ser igual à capacidade total ('.$capacity.').');
                }
            },
        ];
    }

    private function validationRules(Request $request): array
    {
        return [
            'title' => 'required|string|max:255',
            'category' => ['required', 'string', 'max:255', Rule::in(array_merge(Event::CATEGORY_OPTIONS, [Event::SELECT_OTHER_VALUE]))],
            'category_custom' => ['nullable', 'string', 'max:255', Rule::requiredIf(fn () => $request->input('category') === Event::SELECT_OTHER_VALUE)],
            'modality' => 'required|string|max:255',
            'event_type' => ['required', 'string', 'max:255', Rule::in(array_merge(Event::EVENT_TYPE_OPTIONS, [Event::SELECT_OTHER_VALUE]))],
            'event_type_custom' => ['nullable', 'string', 'max:255', Rule::requiredIf(fn () => $request->input('event_type') === Event::SELECT_OTHER_VALUE)],
            'natureza' => ['required', 'string', Rule::in(array_keys(Event::NATURE_OPTIONS))],
            'abrangencia' => ['required', 'string', Rule::in(array_keys(Event::SCOPE_OPTIONS))],
            'periodicidade' => ['required', 'string', Rule::in(array_keys(Event::PERIODICITY_OPTIONS))],
            'edicao' => [
                Rule::requiredIf(fn () => $request->input('periodicidade') !== Event::PERIODICITY_UNIQUE),
                'nullable',
                'integer',
                'min:1',
                'max:999',
            ],
            'capacity' => 'required|integer|min:1',
            'internal_capacity' => $this->internalCapacityRules(),
            'external_capacity' => $this->externalCapacityRules($request),

            'ead_link' => [
                Rule::requiredIf(in_array($request->modality, ['Online', 'Híbrido'])),
                'nullable',
                'url',
            ],

            'description' => [
                'required',
                'string',
                function ($attribute, $value, $fail) {
                    if (trim(strip_tags($value)) === '') {
                        $fail('A descrição do evento é obrigatória.');
                    }
                },
            ],

            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'start_time' => 'nullable|date_format:H:i',
            'end_time' => [
                'nullable',
                'date_format:H:i',
                function ($attribute, $value, $fail) use ($request) {
                    $this->failIfEventEndBeforeStart($request, $fail);
                },
            ],

            'campus' => ['required', Rule::in([Event::FIXED_CAMPUS])],
            'building' => ['required', Rule::in([Event::FIXED_BUILDING])],
            'venue' => ['required', Rule::in([Event::FIXED_VENUE])],
            'address' => ['required', Rule::in([Event::FIXED_ADDRESS])],
            'location_details' => 'nullable|string|max:255',

            'image' => 'required|image|mimes:jpg,jpeg,png|max:5120',

            'coordinator_name' => 'required|string|max:255',
            'coordinator_email' => 'required|email|max:255',
            'coordinator_phone' => [
                'nullable',
                'string',
                'max:20',
                'regex:/^\(\d{2}\)\s\d{4,5}-\d{4}$/',
            ],

            'datetime_registration' => [
                'required',
                'date',
                function ($attribute, $value, $fail) use ($request) {
                    $this->failIfDeadlineAfterEventStart(
                        $request,
                        $value,
                        $fail,
                        'O prazo de inscrição não pode ser posterior à data e hora de início do evento.',
                        'Enquanto o horário de início não estiver definido, o prazo de inscrição não pode passar do fim do dia da data de início.'
                    );
                },
            ],
            'submission_deadline_at' => [
                'required_if:accepts_submissions,1',
                'nullable',
                'date',
                function ($attribute, $value, $fail) use ($request) {
                    if (! $request->boolean('accepts_submissions') || empty($value)) {
                        return;
                    }
                    $this->failIfDeadlineAfterEventStart(
                        $request,
                        $value,
                        $fail,
                        'O prazo de submissão não pode ser posterior à data e hora de início do evento.',
                        'Enquanto o horário de início não estiver definido, o prazo de submissão não pode passar do fim do dia da data de início.'
                    );
                },
            ],
            'review_deadline_at' => $this->reviewDeadlineRules($request),
            'accepts_submissions' => 'nullable|boolean',
            'accepted_work_types' => 'exclude_unless:accepts_submissions,1|required|array|min:1',
            'accepted_work_types.*' => 'string|max:255',
            'accepted_work_types_custom' => [
                'exclude_unless:accepts_submissions,1',
                'nullable',
                'array',
                function ($attribute, $value, $fail) use ($request) {
                    $selectedTypes = collect($request->input('accepted_work_types', []));
                    if (! $selectedTypes->contains('__other__')) {
                        return;
                    }

                    $customTypes = collect($request->input('accepted_work_types_custom', []))
                        ->map(fn ($type) => trim((string) $type))
                        ->filter();

                    if ($customTypes->isEmpty()) {
                        $fail('Informe ao menos um tipo personalizado ao selecionar a opção "Outro".');
                    }
                },
            ],
            'accepted_work_types_custom.*' => 'nullable|string|max:255',
            'reviewers_min_per_work' => 'exclude_unless:accepts_submissions,1|required|integer|min:1|max:10',
            'reviewers_max_per_work' => 'exclude_unless:accepts_submissions,1|nullable|integer|min:1|max:10|gte:reviewers_min_per_work',

            'activities_new' => [
                'nullable',
                'array',
                function ($attribute, $value, $fail) use ($request) {
                    $typeOptions = Activity::typeOptions();
                    foreach (($value ?? []) as $index => $row) {
                        $title = trim((string) ($row['title'] ?? ''));
                        $type = trim((string) ($row['type'] ?? ''));
                        $activityDate = trim((string) ($row['activity_date'] ?? ''));
                        $startTime = trim((string) ($row['start_time'] ?? ''));
                        $endTime = trim((string) ($row['end_time'] ?? ''));
                        $location = trim((string) ($row['location'] ?? ''));
                        $guestRefs = $this->guestRefStringsFromActivityRow($row);
                        $anyFilled = $title !== '' || $type !== '' || $activityDate !== '' || $startTime !== '' || $endTime !== '' || $location !== '' || count($guestRefs) > 0;
                        if (! $anyFilled) {
                            continue;
                        }
                        if ($title === '' || $type === '' || $activityDate === '' || $startTime === '' || $endTime === '' || $location === '' || count($guestRefs) < 1) {
                            $fail('Preencha todos os campos da nova atividade na linha '.($index + 1).' (título, tipo, data, horários, local e ao menos um convidado).');

                            return;
                        }
                        if (! in_array($type, $typeOptions, true)) {
                            $fail('Selecione um tipo válido para a nova atividade na linha '.($index + 1).'.');

                            return;
                        }
                        try {
                            $startAt = $this->carbonFromActivityWizardDateAndTime($activityDate, $startTime);
                            $endAt = $this->carbonFromActivityWizardDateAndTime($activityDate, $endTime);
                            if ($endAt->lessThanOrEqualTo($startAt)) {
                                $fail('A hora de fim deve ser posterior à hora de início na nova atividade da linha '.($index + 1).'.');

                                return;
                            }
                            if ($this->activityOutsideEventRequestWindow($request, $startAt, $endAt)) {
                                $fail('A nova atividade na linha '.($index + 1).' deve ocorrer inteiramente entre a data e hora de início e a data e hora de término do evento (é permitido coincidir com o início ou o fim do evento, mas não ultrapassar esse intervalo).');

                                return;
                            }
                        } catch (\Throwable $e) {
                            $fail('Data ou horário inválido na nova atividade da linha '.($index + 1).'.');

                            return;
                        }
                    }
                },
            ],
            'activities_new.*.location' => 'nullable|string|max:255',

            'guests_new' => [
                'nullable',
                'array',
                function ($attribute, $value, $fail) {
                    $roleOptions = EventGuest::roleTypeOptions();
                    foreach (($value ?? []) as $index => $guest) {
                        $name = trim((string) ($guest['name'] ?? ''));
                        $roleType = trim((string) ($guest['role_type'] ?? ''));
                        $role = trim((string) ($guest['role'] ?? ''));
                        if ($name === '' && $roleType === '' && $role === '') {
                            continue;
                        }
                        if ($name === '') {
                            $fail('Informe o nome do convidado na linha '.($index + 1).'.');

                            return;
                        }
                        if ($roleType === '' || ! in_array($roleType, $roleOptions, true)) {
                            $fail('Selecione a função do convidado na linha '.($index + 1).'.');

                            return;
                        }
                    }
                },
            ],
            'guests_new.*.name' => 'nullable|string|max:255',
            'guests_new.*.role_type' => ['nullable', Rule::in(EventGuest::roleTypeOptions())],
            'guests_new.*.role' => 'nullable|string|max:2000',
            'guests_existing' => 'nullable|array',
            'guests_existing.*.id' => 'nullable|integer',
            'guests_existing.*.name' => 'nullable|string|max:255',
            'guests_existing.*.role_type' => ['nullable', Rule::in(EventGuest::roleTypeOptions())],
            'guests_existing.*.role' => 'nullable|string|max:2000',

            'activities_existing' => 'nullable|array',
            'activities_existing.*.title' => 'nullable|string|max:255',
            'activities_existing.*.type' => ['nullable', Rule::in(Activity::typeOptions())],
            'activities_existing.*.activity_date' => 'nullable|date',
            'activities_existing.*.start_time' => 'nullable|date_format:H:i',
            'activities_existing.*.end_time' => 'nullable|date_format:H:i',
            'activities_existing.*.guest_refs' => 'nullable|array',
            'activities_existing.*.guest_refs.*' => 'nullable|string|max:50',
            'activities_existing.*.location' => 'nullable|string|max:255',

            'documents_new' => 'nullable|array',
            'documents_new.*.title' => 'nullable|string|max:255',
            'documents_new.*.file' => 'nullable|file|mimes:pdf,doc,docx,ppt,pptx,xls,xlsx,zip,rar,jpg,jpeg,png|max:15360',
        ];
    }

    private function validationMessages(): array
    {
        return [
            'title.required' => 'O título do evento é obrigatório.',
            'title.string' => 'O título deve ser um texto válido.',
            'title.max' => 'O título não pode ter mais de 255 caracteres.',

            'category.required' => 'A categoria é obrigatória.',
            'category.string' => 'A categoria deve ser um texto válido.',
            'category.max' => 'A categoria não pode ter mais de 255 caracteres.',
            'category.in' => 'Selecione uma categoria válida.',
            'category_custom.required' => 'Informe a categoria ao selecionar "Outro".',
            'category_custom.max' => 'A categoria não pode ter mais de 255 caracteres.',

            'modality.required' => 'A modalidade é obrigatória.',
            'modality.string' => 'A modalidade deve ser um texto válido.',
            'modality.max' => 'A modalidade não pode ter mais de 255 caracteres.',
            'event_type.required' => 'O tipo de evento científico é obrigatório.',
            'event_type.string' => 'O tipo de evento deve ser um texto válido.',
            'event_type.in' => 'Selecione um tipo de evento válido.',
            'event_type_custom.required' => 'Informe o tipo de evento ao selecionar "Outro".',
            'event_type_custom.max' => 'O tipo de evento não pode ter mais de 255 caracteres.',
            'natureza.required' => 'A natureza do evento é obrigatória.',
            'natureza.in' => 'Selecione uma natureza válida.',
            'abrangencia.required' => 'A abrangência do evento é obrigatória.',
            'abrangencia.in' => 'Selecione uma abrangência válida.',
            'periodicidade.required' => 'A periodicidade do evento é obrigatória.',
            'periodicidade.in' => 'Selecione uma periodicidade válida.',
            'edicao.required' => 'Informe o número da edição.',
            'edicao.integer' => 'O número da edição deve ser um inteiro.',
            'edicao.min' => 'O número da edição começa em 1.',
            'edicao.max' => 'O número da edição não pode passar de 999.',

            'capacity.required' => 'A capacidade é obrigatória.',
            'capacity.integer' => 'A capacidade deve ser um número inteiro.',
            'capacity.min' => 'A capacidade deve ser no mínimo 1 participante.',
            'internal_capacity.required' => 'Informe quantas vagas são para participantes internos.',
            'internal_capacity.integer' => 'As vagas internas devem ser um número inteiro.',
            'internal_capacity.min' => 'As vagas internas não podem ser negativas.',
            'external_capacity.required' => 'Informe quantas vagas são para participantes externos.',
            'external_capacity.integer' => 'As vagas externas devem ser um número inteiro.',
            'external_capacity.min' => 'As vagas externas não podem ser negativas.',

            'ead_link.url' => 'O link do EAD deve ser uma URL válida.',

            'description.required' => 'A descrição do evento é obrigatória.',
            'description.string' => 'A descrição deve ser um texto válido.',

            'start_date.required' => 'A data de início é obrigatória.',
            'start_date.date' => 'A data de início deve ser uma data válida.',
            'end_date.required' => 'A data de término é obrigatória.',
            'end_date.date' => 'A data de término deve ser uma data válida.',
            'end_date.after_or_equal' => 'A data de término deve ser igual ou posterior à data de início.',
            'start_time.date_format' => 'O horário de início deve estar no formato HH:MM.',
            'end_time.date_format' => 'O horário de término deve estar no formato HH:MM.',

            'campus.required' => 'O campus é obrigatório.',
            'campus.in' => 'O campus selecionado não é válido.',
            'building.required' => 'O bloco/prédio é obrigatório.',
            'venue.required' => 'O local/sala é obrigatório.',

            'image.required' => 'Você precisa enviar uma imagem para o evento.',
            'image.image' => 'O arquivo enviado deve ser uma imagem (JPEG, PNG, JPG ou GIF).',
            'image.mimes' => 'A imagem deve estar em formato JPEG, PNG, JPG ou GIF.',
            'image.max' => 'A imagem não pode ter mais de 5MB.',

            'coordinator_name.required' => 'O nome do coordenador é obrigatório.',
            'coordinator_name.string' => 'O nome do coordenador deve ser um texto válido.',
            'coordinator_name.max' => 'O nome do coordenador não pode ter mais de 255 caracteres.',

            'coordinator_email.required' => 'O e-mail da coordenação é obrigatório.',
            'coordinator_email.email' => 'Informe um e-mail válido para a coordenação.',
            'coordinator_email.max' => 'O e-mail da coordenação não pode ter mais de 255 caracteres.',

            'coordinator_phone.required' => 'O telefone da coordenação é obrigatório.',
            'coordinator_phone.regex' => 'O telefone deve estar no formato (11) 99999-9999.',

            'datetime_registration.required' => 'O prazo de inscrição é obrigatório.',
            'datetime_registration.before_or_equal' => 'O prazo de inscrição não pode ser posterior à data e hora de início do evento.',
            'submission_deadline_at.required_if' => 'O prazo de submissão é obrigatório quando o evento aceita submissões.',
            'submission_deadline_at.date' => 'O prazo de submissão deve ser uma data válida.',
            'review_deadline_at.required_if' => 'O prazo de avaliação é obrigatório quando o evento aceita submissões.',
            'review_deadline_at.date' => 'O prazo de avaliação deve ser uma data válida.',
            'accepts_submissions.boolean' => 'O campo de submissão precisa ser verdadeiro ou falso.',
            'accepted_work_types.required' => 'Selecione ao menos um tipo de trabalho quando o evento aceitar submissões.',
            'accepted_work_types.required_if' => 'Selecione ao menos um tipo de trabalho quando o evento aceitar submissões.',
            'accepted_work_types.array' => 'Os tipos de trabalho devem ser informados em lista.',
            'accepted_work_types.min' => 'Selecione ao menos um tipo de trabalho.',
            'accepted_work_types.*.max' => 'Cada tipo de trabalho deve ter no máximo 255 caracteres.',
            'accepted_work_types_custom.*.max' => 'Cada tipo personalizado deve ter no máximo 255 caracteres.',
            'reviewers_min_per_work.required' => 'Informe a quantidade mínima de avaliadores por trabalho.',
            'reviewers_min_per_work.required_if' => 'Informe a quantidade mínima de avaliadores por trabalho.',
            'reviewers_min_per_work.integer' => 'A quantidade mínima de avaliadores deve ser um número inteiro.',
            'reviewers_min_per_work.min' => 'A quantidade mínima de avaliadores por trabalho é 1.',
            'reviewers_min_per_work.max' => 'A quantidade mínima de avaliadores por trabalho é 10.',
            'reviewers_max_per_work.integer' => 'A quantidade máxima de avaliadores deve ser um número inteiro.',
            'reviewers_max_per_work.min' => 'A quantidade máxima de avaliadores por trabalho é 1.',
            'reviewers_max_per_work.max' => 'A quantidade máxima de avaliadores por trabalho é 10.',
            'reviewers_max_per_work.gte' => 'A quantidade máxima de avaliadores deve ser maior ou igual à mínima.',

            'documents_new.*.file.file' => 'Envie um arquivo válido no documento anexado.',
            'documents_new.*.file.mimes' => 'Formato não suportado neste documento. Use PDF, Office (DOC, DOCX, PPT, PPTX…), Excel (XLS, XLSX), ZIP/RAR ou imagem JPG/JPEG/PNG.',
            'documents_new.*.file.max' => 'O documento anexado é muito grande. O máximo permitido é 15 MB.',
        ];
    }

    private function stepFields(): array
    {
        return [
            // STEP 0: Informações Básicas
            0 => [
                'title',
                'category',
                'category_custom',
                'modality',
                'event_type',
                'event_type_custom',
                'natureza',
                'abrangencia',
                'periodicidade',
                'edicao',
                'capacity',
                'internal_capacity',
                'external_capacity',
                'ead_link',
                'description',
            ],

            // STEP 1: Datas + Localização + Imagem
            1 => [
                'start_date',
                'end_date',
                'start_time',
                'end_time',
                'campus',
                'building',
                'venue',
                'address',
                'location_details',
                'image',
            ],

            // STEP 2: Coordenação + Configurações finais
            2 => [
                'coordinator_name',
                'coordinator_email',
                'coordinator_phone',
                'datetime_registration',
                'submission_deadline_at',
                'review_deadline_at',
                'accepts_submissions',
                'accepted_work_types',
                'accepted_work_types_custom',
                'reviewers_min_per_work',
                'reviewers_max_per_work',
            ],
            3 => [
                'guests_new',
            ],
            4 => [
                'activities_new',
                'documents_new',
            ],
        ];
    }

    /**
     * @return list<mixed>
     */
    private function reviewDeadlineRules(Request $request): array
    {
        return [
            'required_if:accepts_submissions,1',
            'nullable',
            'date',
            function ($attribute, $value, $fail) use ($request) {
                if (! $request->boolean('accepts_submissions') || empty($value)) {
                    return;
                }

                $submission = $request->input('submission_deadline_at');
                if (filled($submission)) {
                    try {
                        $reviewAt = Carbon::parse($value, config('app.timezone'));
                        $submissionAt = Carbon::parse($submission, config('app.timezone'));
                        if ($reviewAt->lessThanOrEqualTo($submissionAt)) {
                            $fail('O prazo de avaliação deve ser posterior ao prazo de submissão.');

                            return;
                        }
                    } catch (\Throwable) {
                        return;
                    }
                }

                $startDate = trim((string) $request->input('start_date', ''));
                if ($startDate === '') {
                    return;
                }

                try {
                    $limit = Event::deadlineLimitFromSchedule($startDate, $request->input('start_time'));
                    $reviewAt = Carbon::parse($value, config('app.timezone'));
                } catch (\Throwable) {
                    return;
                }

                if ($reviewAt->greaterThanOrEqualTo($limit)) {
                    $fail('O prazo de avaliação precisa terminar antes do início do evento.');
                }
            },
        ];
    }

    /**
     * Falha se o prazo (inscrição/submissão) passar do início do evento.
     * Com hora de início, o limite é esse instante. Sem hora, é o fim do dia da data de início.
     * Sem start_date no request, não compara (validação por etapa); o submit completo revalida.
     */
    private function failIfDeadlineAfterEventStart(
        Request $request,
        mixed $value,
        \Closure $fail,
        string $withTimeMessage,
        string $withoutTimeMessage
    ): void {
        $startDate = trim((string) $request->input('start_date', ''));
        if ($startDate === '' || $value === null || $value === '') {
            return;
        }

        try {
            $limit = Event::deadlineLimitFromSchedule($startDate, $request->input('start_time'));
            $deadlineAt = Carbon::parse($value, config('app.timezone'));
        } catch (\Throwable) {
            return;
        }

        if ($deadlineAt->greaterThan($limit)) {
            $startTime = trim((string) $request->input('start_time', ''));
            $fail($startTime === '' ? $withoutTimeMessage : $withTimeMessage);
        }
    }

    /** Falha quando os dois horários existem e o fim (data + hora) é anterior ao início. */
    private function failIfEventEndBeforeStart(Request $request, \Closure $fail): void
    {
        $startDate = trim((string) $request->input('start_date', ''));
        $endRaw = $request->input('end_date');
        $endDate = ($endRaw !== null && trim((string) $endRaw) !== '') ? trim((string) $endRaw) : null;

        if (Event::eventEndIsBeforeStart($startDate, $request->input('start_time'), $endDate, $request->input('end_time'))) {
            $fail('O término do evento deve ser igual ou posterior ao início, considerando data e horário.');
        }
    }

    private function requestScheduleIsComplete(Request $request): bool
    {
        return trim((string) $request->input('start_date', '')) !== ''
            && trim((string) $request->input('start_time', '')) !== ''
            && trim((string) $request->input('end_time', '')) !== '';
    }

    private function resolvedCategoryFromRequest(Request $request): string
    {
        if ($request->input('category') === Event::SELECT_OTHER_VALUE) {
            return trim((string) $request->input('category_custom', ''));
        }

        return trim((string) $request->input('category', ''));
    }

    private function resolvedEventTypeFromRequest(Request $request): string
    {
        if ($request->input('event_type') === Event::SELECT_OTHER_VALUE) {
            return trim((string) $request->input('event_type_custom', ''));
        }

        return trim((string) $request->input('event_type', ''));
    }

    private function applyAbntClassification(Event $event, Request $request): void
    {
        $event->natureza = $request->input('natureza');
        $event->abrangencia = $request->input('abrangencia');
        $event->periodicidade = $request->input('periodicidade');
        $event->edicao = $request->input('periodicidade') === Event::PERIODICITY_UNIQUE
            ? null
            : ($request->filled('edicao') ? (int) $request->input('edicao') : null);
        $event->acesso = Event::ACCESS_FREE;
    }

    /**
     * Verdadeiro se a atividade estiver fora do período do evento conforme datas/horários do request.
     * Sem data de início ou sem os dois horários, não restringe: a atividade é conferida quando o período oficial for gravado.
     */
    private function activityOutsideEventRequestWindow(Request $request, Carbon $activityStart, Carbon $activityEnd): bool
    {
        if (! $this->requestScheduleIsComplete($request)) {
            return false;
        }

        $startDate = trim((string) $request->input('start_date', ''));

        try {
            $endRaw = $request->input('end_date');
            $endDate = ($endRaw !== null && trim((string) $endRaw) !== '') ? trim((string) $endRaw) : $startDate;
            [$eventStart, $eventEnd] = Event::calendarBoundsFromSchedule(
                $startDate,
                $request->input('start_time'),
                $endDate,
                $request->input('end_time'),
            );
        } catch (\Throwable $e) {
            return false;
        }

        return ! Event::activityFitsEventWindow($activityStart, $activityEnd, $eventStart, $eventEnd);
    }

    /**
     * Monta Carbon a partir da data da atividade (Y-m-d) e do campo HTML time (HH:MM ou HH:MM:SS).
     */
    private function carbonFromActivityWizardDateAndTime(string $activityDate, string $timeRaw): Carbon
    {
        $normalized = Event::normalizeTimeForSchedule(trim($timeRaw), '00:00:00');

        return Carbon::parse(trim($activityDate).' '.$normalized, config('app.timezone'));
    }

    public function validateStep(Request $request)
    {
        $step = (int) $request->step;

        $allRules = $this->validationRules($request);
        $messages = $this->validationMessages();
        $fields = $this->stepFields()[$step] ?? [];

        $rules = array_intersect_key($allRules, array_flip($fields));

        $request->validate($rules, $messages);

        return response()->json(['ok' => true]);
    }

    public function store(Request $request)
    {
        if (! $request->boolean('accepts_submissions')) {
            $request->merge([
                'submission_deadline_at' => null,
                'review_deadline_at' => null,
            ]);
        }

        $request->validate(
            $this->validationRules($request),
            $this->validationMessages()
        );

        $event = new Event;
        $event->title = $request->title;
        $event->category = $this->resolvedCategoryFromRequest($request);
        $event->modality = $request->modality;
        $event->event_type = $this->resolvedEventTypeFromRequest($request);
        $event->modality_type = $this->normalizeModalityType($request->modality);
        $this->applyAbntClassification($event, $request);
        $event->capacity = $request->capacity;
        $event->internal_capacity = (int) $request->input('internal_capacity');
        $event->external_capacity = (int) $request->input('external_capacity');
        $event->ead_link = $request->ead_link ?? null;
        $event->description = Purifier::clean($request->description ?? '');

        $event->target_audience = [];
        $event->prerequisites = [];

        $event->start_date = $request->start_date;
        $event->end_date = $request->end_date ?? null;
        $event->start_time = $request->filled('start_time') ? $request->input('start_time') : null;
        $event->end_time = $request->filled('end_time') ? $request->input('end_time') : null;

        $event->campus = Event::FIXED_CAMPUS;
        $event->building = Event::FIXED_BUILDING;
        $event->venue = Event::FIXED_VENUE;
        $event->address = Event::FIXED_ADDRESS;
        $event->location_details = Event::FIXED_LOCATION_DETAILS;

        $event->coordinator_name = $request->coordinator_name;
        $event->coordinator_email = $request->coordinator_email;
        $event->coordinator_phone = $request->filled('coordinator_phone') ? trim($request->coordinator_phone) : null;

        $event->datetime_registration = $request->datetime_registration ?? null;
        $event->submission_deadline_at = $request->submission_deadline_at ?? null;
        $event->review_deadline_at = $request->review_deadline_at ?? null;
        $event->accepts_submissions = $request->boolean('accepts_submissions');
        $event->reviewers_min_per_work = $request->boolean('accepts_submissions')
            ? (int) $request->input('reviewers_min_per_work', 1)
            : 1;
        $event->reviewers_max_per_work = $request->boolean('accepts_submissions')
            ? (int) $request->input('reviewers_max_per_work', $event->reviewers_min_per_work)
            : 1;

        // =========================
        // Upload da imagem
        // =========================

        if ($request->hasFile('image') && $request->file('image')->isValid()) {

            $requestImage = $request->file('image');
            $extension = $requestImage->extension();
            $imageName = md5(
                $requestImage->getClientOriginalName().now()->timestamp
            ).'.'.$extension;

            $bucket = config('services.supabase.bucket_events');
            $path = "events/$imageName";

            // SALVA COMO UM ARQUIVO REAL NO BUCKET
            $response = Http::withHeaders([
                'Authorization' => 'Bearer '.config('services.supabase.service_role'),
                'apikey' => config('services.supabase.service_role'),
                'Content-Type' => $requestImage->getMimeType(),
            ])->withBody(
                file_get_contents($requestImage->getRealPath()),
                $requestImage->getMimeType()
            )->post(
                config('services.supabase.url').
                "/storage/v1/object/$bucket/$path"
            );

            if (! $response->successful()) {
                abort(500, 'Erro ao enviar imagem para o Supabase');
            }

            // continua salvando só o nome (igual antes)
            $event->image = $imageName;
        }

        $event->user_id = auth()->id();
        $event->save();
        $this->syncScientificConfiguration($event, $request);
        $guestRefMap = $this->syncEventGuestsFromRequest($event, $request, false);
        $this->storeExtraActivitiesFromRequest($event, $request, $guestRefMap);
        $this->storeExtraDocumentsFromRequest($event, $request);

        return redirect('/')
            ->with('msg', 'Evento criado com sucesso!');
    }

    public function show($id)
    {
        $event = Event::with(['eventNews', 'sponsors', 'documents', 'activities.parentActivity', 'activities.guest', 'activities.eventGuests', 'guests'])->findOrFail($id);

        // Retorna o usuário dono do evento usando o método user() do Model Event.
        $eventOwner = $event->user()->first();

        // echo "<pre>"; print_r($event); echo "</pre>"; exit;
        // echo "<pre>"; print_r($eventOwner); echo "</pre>"; exit;

        $acceptedWorkTypes = DB::table('event_work_types')->where('event_id', $event->id)->pluck('work_type');

        return view('events.show', [
            'event' => $event,
            'eventOwner' => $eventOwner,
            'acceptedWorkTypes' => $acceptedWorkTypes,
            'documentTypeLabels' => EventDocument::typeLabels(),
            'activityTypeLabels' => Activity::typeLabels(),
            'activityTypeOptions' => Activity::typeOptions(),
            'guestRoleTypeLabels' => EventGuest::roleTypeLabels(),
            'parentActivities' => $event->activities
                ->whereNull('parent_activity_id')
                ->values(),
        ]);
    }

    public function newShow($id)
    {

        $event = Event::with(['activities', 'documents', 'guests'])->findOrFail($id);

        // Retorna o usuário dono do evento usando o método user() do Model Event.
        $eventOwner = $event->user()->first();

        // echo "<pre>"; print_r($event); echo "</pre>"; exit;
        // echo "<pre>"; print_r($eventOwner); echo "</pre>"; exit;

        $acceptedWorkTypes = DB::table('event_work_types')->where('event_id', $event->id)->pluck('work_type');

        return view('events.newShow', [
            'event' => $event,
            'eventOwner' => $eventOwner,
            'acceptedWorkTypes' => $acceptedWorkTypes,
        ]);
    }

    public function dashboard()
    {

        $user = auth()->user();

        // Retorna os eventos que o usuário possui usando o método events() do Model User.
        // Usa get() para executar a consulta e obter os eventos como Collection
        // $events = $user->events()->get()->toArray();

        $events = $user->events()
            ->withCount([
                'users as registered_count',
                'users as external_registered_count' => function ($query) {
                    $query->where('users.is_external_participant', true);
                },
            ])
            ->get();

        $eventsAsParticipant = $user->eventsAsParticipant()
            ->withCount([
                'users as registered_count',
            ])
            ->get();

        // echo "<pre>"; print_r($events); echo "</pre>"; exit;

        $satisfactionAnsweredEventIds = \App\Models\EventSatisfactionResponse::query()
            ->where('user_id', $user->id)
            ->pluck('event_id')
            ->all();

        return view('events.dashboard', [
            'events' => $events,
            'eventsAsParticipant' => $eventsAsParticipant,
            'satisfactionAnsweredEventIds' => $satisfactionAnsweredEventIds,
        ]);
    }

    public function destroy($id)
    {
        $event = Event::findOrFail($id);

        if (auth()->id() != $event->user_id) {
            return redirect('/dashboard')->with('msg', 'Acesso negado.');
        }

        // Remove todos os participantes relacionados
        $event->users()->detach();

        // Deleta o evento
        $event->delete();

        return redirect('/dashboard')->with('msg', 'Evento excluído com sucesso!');
    }

    public function edit($id)
    {

        $user = auth()->user();

        $event = Event::with(['guests', 'activities.eventGuests', 'activities.guest'])->findOrFail($id);

        // NÃO PERMITE EDIÇÃO DE UM USUÁRIO QUE NÃO SEJA DONO DO EVENTO.
        if ($user->id != $event->user_id) {
            return redirect('/dashboard');
        }

        if ($event->isFinalized()) {
            return redirect('/dashboard')->with('msg', 'Este evento foi finalizado e não pode mais ser editado.');
        }

        $acceptedWorkTypes = DB::table('event_work_types')
            ->where('event_id', $event->id)
            ->pluck('work_type')
            ->toArray();

        return view('events.newEdit', [
            'event' => $event,
            'eventTypeOptions' => Event::EVENT_TYPE_OPTIONS,
            'workTypes' => Work::workTypeOptions(),
            'workTypeLabels' => Work::workTypeLabels(),
            'acceptedWorkTypes' => $acceptedWorkTypes,
            'activityTypeOptions' => Activity::typeOptions(),
            'activityTypeLabels' => Activity::typeLabels(),
            'guestRoleTypeOptions' => EventGuest::roleTypeOptions(),
            'guestRoleTypeLabels' => EventGuest::roleTypeLabels(),
            'documentTypeLabels' => EventDocument::typeLabels(),
        ]);
    }

    public function update(Request $request, $id)
    {
        $event = Event::findOrFail($id);

        if (auth()->id() != $event->user_id) {
            return redirect('/dashboard');
        }

        if ($event->isFinalized()) {
            return redirect('/dashboard')->with('msg', 'Este evento foi finalizado e não pode mais ser alterado.');
        }

        // Sem trabalhos/submissões: não persiste data de encerramento de submissões.
        if (! $request->boolean('accepts_submissions')) {
            $request->merge([
                'submission_deadline_at' => null,
                'review_deadline_at' => null,
            ]);
        }

        // Validação
        $request->validate([
            'title' => 'required|string|max:255',
            'category' => ['required', 'string', 'max:255', Rule::in(array_merge(Event::CATEGORY_OPTIONS, [Event::SELECT_OTHER_VALUE]))],
            'category_custom' => ['nullable', 'string', 'max:255', Rule::requiredIf(fn () => $request->input('category') === Event::SELECT_OTHER_VALUE)],
            'modality' => 'required|string|max:255',
            'event_type' => ['required', 'string', 'max:255', Rule::in(array_merge(Event::EVENT_TYPE_OPTIONS, [Event::SELECT_OTHER_VALUE]))],
            'event_type_custom' => ['nullable', 'string', 'max:255', Rule::requiredIf(fn () => $request->input('event_type') === Event::SELECT_OTHER_VALUE)],
            'natureza' => ['required', 'string', Rule::in(array_keys(Event::NATURE_OPTIONS))],
            'abrangencia' => ['required', 'string', Rule::in(array_keys(Event::SCOPE_OPTIONS))],
            'periodicidade' => ['required', 'string', Rule::in(array_keys(Event::PERIODICITY_OPTIONS))],
            'edicao' => [
                Rule::requiredIf(fn () => $request->input('periodicidade') !== Event::PERIODICITY_UNIQUE),
                'nullable',
                'integer',
                'min:1',
                'max:999',
            ],
            'capacity' => 'required|integer|min:1',
            'internal_capacity' => $this->internalCapacityRules($event),
            'external_capacity' => $this->externalCapacityRules($request, $event),
            // EAD link: obrigatório apenas se modalidade for Online ou Híbrido
            'ead_link' => Rule::requiredIf(in_array($request->modality, ['Online', 'Híbrido'])).'|nullable|url',

            'description' => [
                'required',
                'string',
                function ($attribute, $value, $fail) {
                    if (trim(strip_tags($value)) === '') {
                        $fail('A descrição do evento é obrigatória.');
                    }
                },
            ],

            'start_date' => 'required|date',
            'end_date' => [
                'required',
                'date',
                'after_or_equal:start_date',
                function ($attribute, $value, $fail) use ($request, $event) {
                    $this->failIfScheduleChangeBreaksDependents($request, $event, $fail);
                },
            ],
            'start_time' => 'nullable|date_format:H:i',
            'end_time' => [
                'nullable',
                'date_format:H:i',
                function ($attribute, $value, $fail) use ($request) {
                    $this->failIfEventEndBeforeStart($request, $fail);
                },
            ],

            'campus' => ['required', Rule::in([Event::FIXED_CAMPUS])],
            'building' => ['required', Rule::in([Event::FIXED_BUILDING])],
            'venue' => ['required', Rule::in([Event::FIXED_VENUE])],
            'address' => ['required', Rule::in([Event::FIXED_ADDRESS])],
            'location_details' => 'nullable|string|max:255',

            'image' => 'nullable|image|mimes:jpg,jpeg,png|max:5120',

            'coordinator_name' => 'required|string|max:255',
            'coordinator_email' => 'required|email|max:255',
            'coordinator_phone' => [
                'nullable',
                'string',
                'max:20',
                'regex:/^\(\d{2}\)\s\d{4,5}-\d{4}$/',
            ],

            'datetime_registration' => [
                'required',
                'date',
                function ($attribute, $value, $fail) use ($request) {
                    $this->failIfDeadlineAfterEventStart(
                        $request,
                        $value,
                        $fail,
                        'O prazo de inscrição não pode ser posterior à data e hora de início do evento.',
                        'Enquanto o horário de início não estiver definido, o prazo de inscrição não pode passar do fim do dia da data de início.'
                    );
                },
            ],
            'submission_deadline_at' => [
                'required_if:accepts_submissions,1',
                'nullable',
                'date',
                function ($attribute, $value, $fail) use ($request) {
                    if (! $request->boolean('accepts_submissions') || empty($value)) {
                        return;
                    }
                    $this->failIfDeadlineAfterEventStart(
                        $request,
                        $value,
                        $fail,
                        'O prazo de submissão não pode ser posterior à data e hora de início do evento.',
                        'Enquanto o horário de início não estiver definido, o prazo de submissão não pode passar do fim do dia da data de início.'
                    );
                },
            ],
            'review_deadline_at' => $this->reviewDeadlineRules($request),
            'accepts_submissions' => 'nullable|boolean',
            'accepted_work_types' => 'exclude_unless:accepts_submissions,1|required|array|min:1',
            'accepted_work_types.*' => 'string|max:255',
            'accepted_work_types_custom' => [
                'exclude_unless:accepts_submissions,1',
                'nullable',
                'array',
                function ($attribute, $value, $fail) use ($request) {
                    $selectedTypes = collect($request->input('accepted_work_types', []));
                    if (! $selectedTypes->contains('__other__')) {
                        return;
                    }

                    $customTypes = collect($request->input('accepted_work_types_custom', []))
                        ->map(fn ($type) => trim((string) $type))
                        ->filter();

                    if ($customTypes->isEmpty()) {
                        $fail('Informe ao menos um tipo personalizado ao selecionar a opção "Outro".');
                    }
                },
            ],
            'accepted_work_types_custom.*' => 'nullable|string|max:255',
            'reviewers_min_per_work' => 'exclude_unless:accepts_submissions,1|required|integer|min:1|max:10',
            'reviewers_max_per_work' => 'exclude_unless:accepts_submissions,1|nullable|integer|min:1|max:10|gte:reviewers_min_per_work',

            'activities_new' => [
                'nullable',
                'array',
                function ($attribute, $value, $fail) use ($request) {
                    $typeOptions = Activity::typeOptions();
                    foreach (($value ?? []) as $index => $row) {
                        $title = trim((string) ($row['title'] ?? ''));
                        $type = trim((string) ($row['type'] ?? ''));
                        $activityDate = trim((string) ($row['activity_date'] ?? ''));
                        $startTime = trim((string) ($row['start_time'] ?? ''));
                        $endTime = trim((string) ($row['end_time'] ?? ''));
                        $location = trim((string) ($row['location'] ?? ''));
                        $guestRefs = $this->guestRefStringsFromActivityRow($row);
                        $anyFilled = $title !== '' || $type !== '' || $activityDate !== '' || $startTime !== '' || $endTime !== '' || $location !== '' || count($guestRefs) > 0;
                        if (! $anyFilled) {
                            continue;
                        }
                        if ($title === '' || $type === '' || $activityDate === '' || $startTime === '' || $endTime === '' || $location === '' || count($guestRefs) < 1) {
                            $fail('Preencha todos os campos da nova atividade na linha '.($index + 1).' (título, tipo, data, horários, local e ao menos um convidado).');

                            return;
                        }
                        if (! in_array($type, $typeOptions, true)) {
                            $fail('Selecione um tipo válido para a nova atividade na linha '.($index + 1).'.');

                            return;
                        }
                        try {
                            $startAt = $this->carbonFromActivityWizardDateAndTime($activityDate, $startTime);
                            $endAt = $this->carbonFromActivityWizardDateAndTime($activityDate, $endTime);
                            if ($endAt->lessThanOrEqualTo($startAt)) {
                                $fail('A hora de fim deve ser posterior à hora de início na nova atividade da linha '.($index + 1).'.');

                                return;
                            }
                            if ($this->activityOutsideEventRequestWindow($request, $startAt, $endAt)) {
                                $fail('A nova atividade na linha '.($index + 1).' deve ocorrer inteiramente entre a data e hora de início e a data e hora de término do evento (é permitido coincidir com o início ou o fim do evento, mas não ultrapassar esse intervalo).');

                                return;
                            }
                        } catch (\Throwable $e) {
                            $fail('Data ou horário inválido na nova atividade da linha '.($index + 1).'.');

                            return;
                        }
                    }
                },
            ],
            'activities_new.*.location' => 'nullable|string|max:255',

            'guests_new' => [
                'nullable',
                'array',
                function ($attribute, $value, $fail) {
                    $roleOptions = EventGuest::roleTypeOptions();
                    foreach (($value ?? []) as $index => $guest) {
                        $name = trim((string) ($guest['name'] ?? ''));
                        $roleType = trim((string) ($guest['role_type'] ?? ''));
                        $role = trim((string) ($guest['role'] ?? ''));
                        if ($name === '' && $roleType === '' && $role === '') {
                            continue;
                        }
                        if ($name === '') {
                            $fail('Informe o nome do convidado na linha '.($index + 1).'.');

                            return;
                        }
                        if ($roleType === '' || ! in_array($roleType, $roleOptions, true)) {
                            $fail('Selecione a função do convidado na linha '.($index + 1).'.');

                            return;
                        }
                    }
                },
            ],
            'guests_new.*.name' => 'nullable|string|max:255',
            'guests_new.*.role_type' => ['nullable', Rule::in(EventGuest::roleTypeOptions())],
            'guests_new.*.role' => 'nullable|string|max:2000',
            'guests_existing' => [
                'nullable',
                'array',
                function ($attribute, $value, $fail) use ($request) {
                    $roleOptions = EventGuest::roleTypeOptions();
                    $removed = collect($request->input('guests_remove', []))
                        ->map(fn ($id) => (int) $id)
                        ->filter(fn ($id) => $id > 0)
                        ->all();
                    foreach ((array) $value as $row) {
                        $id = (int) ($row['id'] ?? 0);
                        if ($id > 0 && in_array($id, $removed, true)) {
                            continue;
                        }
                        $roleType = trim((string) ($row['role_type'] ?? ''));
                        if ($roleType === '' || ! in_array($roleType, $roleOptions, true)) {
                            $fail('Selecione a função de cada convidado cadastrado (campo obrigatório).');

                            return;
                        }
                    }
                },
            ],
            'guests_existing.*.id' => 'nullable|integer',
            'guests_existing.*.name' => 'nullable|string|max:255',
            'guests_existing.*.role_type' => ['nullable', Rule::in(EventGuest::roleTypeOptions())],
            'guests_existing.*.role' => 'nullable|string|max:2000',
            'guests_remove' => 'nullable|array',
            'guests_remove.*' => 'nullable|integer',
            'activities_remove' => 'nullable|array',
            'activities_remove.*' => 'nullable|integer',
            'activities_existing' => [
                'nullable',
                'array',
                function ($attribute, $value, $fail) use ($request) {
                    $typeOptions = Activity::typeOptions();
                    $removed = collect($request->input('activities_remove', []))
                        ->map(fn ($id) => (int) $id)
                        ->filter(fn ($id) => $id > 0)
                        ->all();
                    foreach ((array) $value as $activityId => $row) {
                        $id = (int) $activityId;
                        if (in_array($id, $removed, true)) {
                            continue;
                        }
                        $title = trim((string) ($row['title'] ?? ''));
                        $type = trim((string) ($row['type'] ?? ''));
                        $activityDate = trim((string) ($row['activity_date'] ?? ''));
                        $startTime = trim((string) ($row['start_time'] ?? ''));
                        $endTime = trim((string) ($row['end_time'] ?? ''));
                        $location = trim((string) ($row['location'] ?? ''));
                        $guestRefs = $this->guestRefStringsFromActivityRow($row);
                        if ($title === '' || $type === '' || $activityDate === '' || $startTime === '' || $endTime === '' || $location === '' || count($guestRefs) < 1) {
                            $fail('Preencha todos os campos das atividades cadastradas (título, tipo, data, horários, local e ao menos um convidado).');

                            return;
                        }
                        if (! in_array($type, $typeOptions, true)) {
                            $fail('Selecione um tipo válido para cada atividade cadastrada.');

                            return;
                        }
                        try {
                            $startAt = $this->carbonFromActivityWizardDateAndTime($activityDate, $startTime);
                            $endAt = $this->carbonFromActivityWizardDateAndTime($activityDate, $endTime);
                            if ($endAt->lessThanOrEqualTo($startAt)) {
                                $fail('A hora de fim deve ser posterior à hora de início em cada atividade.');

                                return;
                            }
                        } catch (\Throwable $e) {
                            $fail('Data ou horário inválido em uma das atividades cadastradas.');

                            return;
                        }
                    }
                },
            ],
            'activities_existing.*.guest_refs' => 'nullable|array',
            'activities_existing.*.guest_refs.*' => 'nullable|string|max:50',
            'activities_existing.*.location' => 'nullable|string|max:255',

            'documents_new' => 'nullable|array',
            'documents_new.*.title' => 'nullable|string|max:255',
            'documents_new.*.file' => 'nullable|file|mimes:pdf,doc,docx,ppt,pptx,xls,xlsx,zip,rar,jpg,jpeg,png|max:15360',

            'documents_existing' => 'nullable|array',
            'documents_existing.*.title' => 'nullable|string|max:255',
            'documents_existing.*.file' => 'nullable|file|mimes:pdf,doc,docx,ppt,pptx,xls,xlsx,zip,rar,jpg,jpeg,png|max:15360',
        ], [
            // Mensagens de erro personalizadas

            // Título
            'title.required' => 'O título do evento é obrigatório.',
            'title.string' => 'O título deve ser um texto válido.',
            'title.max' => 'O título não pode ter mais de 255 caracteres.',

            // Categoria
            'category.required' => 'A categoria é obrigatória.',
            'category.string' => 'A categoria deve ser um texto válido.',
            'category.max' => 'A categoria não pode ter mais de 255 caracteres.',
            'category.in' => 'Selecione uma categoria válida.',
            'category_custom.required' => 'Informe a categoria ao selecionar "Outro".',
            'category_custom.max' => 'A categoria não pode ter mais de 255 caracteres.',

            // Modalidade
            'modality.required' => 'A modalidade é obrigatória.',
            'modality.string' => 'A modalidade deve ser um texto válido.',
            'modality.max' => 'A modalidade não pode ter mais de 255 caracteres.',
            'event_type.required' => 'O tipo de evento científico é obrigatório.',
            'event_type.string' => 'O tipo de evento deve ser um texto válido.',
            'event_type.in' => 'Selecione um tipo de evento válido.',
            'event_type_custom.required' => 'Informe o tipo de evento ao selecionar "Outro".',
            'event_type_custom.max' => 'O tipo de evento não pode ter mais de 255 caracteres.',
            'natureza.required' => 'A natureza do evento é obrigatória.',
            'natureza.in' => 'Selecione uma natureza válida.',
            'abrangencia.required' => 'A abrangência do evento é obrigatória.',
            'abrangencia.in' => 'Selecione uma abrangência válida.',
            'periodicidade.required' => 'A periodicidade do evento é obrigatória.',
            'periodicidade.in' => 'Selecione uma periodicidade válida.',
            'edicao.required' => 'Informe o número da edição.',
            'edicao.integer' => 'O número da edição deve ser um inteiro.',
            'edicao.min' => 'O número da edição começa em 1.',
            'edicao.max' => 'O número da edição não pode passar de 999.',

            // Capacidade
            'capacity.required' => 'A capacidade é obrigatória.',
            'capacity.integer' => 'A capacidade deve ser um número inteiro.',
            'capacity.min' => 'A capacidade deve ser no mínimo 1 participante.',
            'internal_capacity.required' => 'Informe quantas vagas são para participantes internos.',
            'internal_capacity.integer' => 'As vagas internas devem ser um número inteiro.',
            'internal_capacity.min' => 'As vagas internas não podem ser negativas.',
            'external_capacity.required' => 'Informe quantas vagas são para participantes externos.',
            'external_capacity.integer' => 'As vagas externas devem ser um número inteiro.',
            'external_capacity.min' => 'As vagas externas não podem ser negativas.',

            // EAD
            'ead_link.url' => 'O link do EAD deve ser uma URL válida.',

            // Descrição
            'description.required' => 'A descrição do evento é obrigatória.',
            'description.string' => 'A descrição deve ser um texto válido.',

            // Datas e horários
            'start_date.required' => 'A data de início é obrigatória.',
            'start_date.date' => 'A data de início deve ser uma data válida.',
            'end_date.required' => 'A data de término é obrigatória.',
            'end_date.date' => 'A data de término deve ser uma data válida.',
            'end_date.after_or_equal' => 'A data de término deve ser igual ou posterior à data de início.',
            'start_time.date_format' => 'O horário de início deve estar no formato HH:MM.',
            'end_time.date_format' => 'O horário de término deve estar no formato HH:MM.',

            // Localização
            'campus.required' => 'O campus é obrigatório.',
            'campus.in' => 'O campus selecionado não é válido.',
            'building.required' => 'O bloco/prédio é obrigatório.',
            'venue.required' => 'O local/sala é obrigatório.',

            // Imagem
            'image.required' => 'Você precisa enviar uma imagem para o evento.',
            'image.image' => 'O arquivo enviado deve ser uma imagem (JPEG, PNG, JPG ou GIF).',
            'image.mimes' => 'A imagem deve estar em formato JPEG, PNG, JPG ou GIF.',
            'image.max' => 'A imagem não pode ter mais de 5MB.',

            // Coordenador
            'coordinator_name.required' => 'O nome do coordenador é obrigatório.',
            'coordinator_name.string' => 'O nome do coordenador deve ser um texto válido.',
            'coordinator_name.max' => 'O nome do coordenador não pode ter mais de 255 caracteres.',

            'coordinator_email.required' => 'O e-mail da coordenação é obrigatório.',
            'coordinator_email.email' => 'Informe um e-mail válido para a coordenação.',
            'coordinator_email.max' => 'O e-mail da coordenação não pode ter mais de 255 caracteres.',

            'coordinator_phone.required' => 'O telefone da coordenação é obrigatório.',
            'coordinator_phone.string' => 'O telefone da coordenação deve ser um texto válido.',
            'coordinator_phone.max' => 'O telefone da coordenação não pode ter mais de 20 caracteres.',
            'coordinator_phone.regex' => 'O telefone deve estar no formato (11) 99999-9999.',

            // Prazo de Inscrição
            'datetime_registration.required' => 'O prazo de inscrição é obrigatório.',
            'datetime_registration.before_or_equal' => 'O prazo de inscrição não pode ser posterior à data e hora de início do evento.',
            'submission_deadline_at.required_if' => 'O prazo de submissão é obrigatório quando o evento aceita submissões.',
            'submission_deadline_at.date' => 'O prazo de submissão deve ser uma data válida.',
            'review_deadline_at.required_if' => 'O prazo de avaliação é obrigatório quando o evento aceita submissões.',
            'review_deadline_at.date' => 'O prazo de avaliação deve ser uma data válida.',
            'accepts_submissions.boolean' => 'O campo de submissão precisa ser verdadeiro ou falso.',
            'accepted_work_types.required' => 'Selecione ao menos um tipo de trabalho quando o evento aceitar submissões.',
            'accepted_work_types.required_if' => 'Selecione ao menos um tipo de trabalho quando o evento aceitar submissões.',
            'accepted_work_types.min' => 'Selecione ao menos um tipo de trabalho.',
            'accepted_work_types.*.max' => 'Cada tipo de trabalho deve ter no máximo 255 caracteres.',
            'accepted_work_types_custom.*.max' => 'Cada tipo personalizado deve ter no máximo 255 caracteres.',
            'reviewers_min_per_work.required' => 'Informe a quantidade mínima de avaliadores por trabalho.',
            'reviewers_min_per_work.required_if' => 'Informe a quantidade mínima de avaliadores por trabalho.',
            'reviewers_min_per_work.integer' => 'A quantidade mínima de avaliadores deve ser um número inteiro.',
            'reviewers_min_per_work.min' => 'A quantidade mínima de avaliadores por trabalho é 1.',
            'reviewers_min_per_work.max' => 'A quantidade mínima de avaliadores por trabalho é 10.',
            'reviewers_max_per_work.integer' => 'A quantidade máxima de avaliadores deve ser um número inteiro.',
            'reviewers_max_per_work.min' => 'A quantidade máxima de avaliadores por trabalho é 1.',
            'reviewers_max_per_work.max' => 'A quantidade máxima de avaliadores por trabalho é 10.',
            'reviewers_max_per_work.gte' => 'A quantidade máxima de avaliadores deve ser maior ou igual à mínima.',

            'documents_existing.*.file.mimes' => 'Formato não suportado no documento. Envie PDF, Office, ZIP/RAR ou imagem.',
            'documents_existing.*.file.max' => 'Cada documento deve ter no máximo 15MB.',

        ]);

        // Atualiza campos comuns
        $event->title = $request->title;
        $event->category = $this->resolvedCategoryFromRequest($request);
        $event->modality = $request->modality;
        $event->event_type = $this->resolvedEventTypeFromRequest($request);
        $event->modality_type = $this->normalizeModalityType($request->modality);
        $this->applyAbntClassification($event, $request);
        $event->capacity = $request->capacity;
        $event->internal_capacity = (int) $request->input('internal_capacity');
        $event->external_capacity = (int) $request->input('external_capacity');
        $event->ead_link = $request->ead_link ?? null;
        $event->description = Purifier::clean($request->description ?? '');
        $event->start_date = $request->start_date;
        $event->end_date = $request->end_date ?? null;
        $event->start_time = $request->filled('start_time') ? $request->input('start_time') : null;
        $event->end_time = $request->filled('end_time') ? $request->input('end_time') : null;
        $event->campus = Event::FIXED_CAMPUS;
        $event->building = Event::FIXED_BUILDING;
        $event->venue = Event::FIXED_VENUE;
        $event->address = Event::FIXED_ADDRESS;
        $event->location_details = Event::FIXED_LOCATION_DETAILS;
        $event->coordinator_name = $request->coordinator_name;
        $event->coordinator_email = $request->coordinator_email;
        $event->coordinator_phone = $request->filled('coordinator_phone') ? trim($request->coordinator_phone) : null;
        $event->datetime_registration = $request->datetime_registration ?? null;
        $event->submission_deadline_at = $request->submission_deadline_at ?? null;
        $event->review_deadline_at = $request->review_deadline_at ?? null;
        $event->accepts_submissions = $request->boolean('accepts_submissions');
        $event->reviewers_min_per_work = $request->boolean('accepts_submissions')
            ? (int) $request->input('reviewers_min_per_work', 1)
            : 1;
        $event->reviewers_max_per_work = $request->boolean('accepts_submissions')
            ? (int) $request->input('reviewers_max_per_work', $event->reviewers_min_per_work)
            : 1;

        // Atualiza imagem apenas se houver upload
        if ($request->hasFile('image') && $request->file('image')->isValid()) {

            $bucket = config('services.supabase.bucket_events');

            // 1️⃣ Apaga imagem antiga no Supabase
            if ($event->image) {
                $oldPath = "events/{$event->image}";

                Http::withHeaders([
                    'Authorization' => 'Bearer '.config('services.supabase.service_role'),
                    'apikey' => config('services.supabase.service_role'),
                ])->delete(
                    config('services.supabase.url').
                    "/storage/v1/object/$bucket/$oldPath"
                );
            }

            // 2️⃣ Upload da nova imagem
            $requestImage = $request->file('image');
            $extension = $requestImage->extension();
            $imageName = md5(
                $requestImage->getClientOriginalName().now()->timestamp
            ).'.'.$extension;

            $path = "events/$imageName";

            // SALVA COMO UM ARQUIVO REAL NO BUCKET
            $response = Http::withHeaders([
                'Authorization' => 'Bearer '.config('services.supabase.service_role'),
                'apikey' => config('services.supabase.service_role'),
                'Content-Type' => $requestImage->getMimeType(),
            ])->withBody(
                file_get_contents($requestImage->getRealPath()),
                $requestImage->getMimeType()
            )->post(
                config('services.supabase.url').
                "/storage/v1/object/$bucket/$path"
            );

            if (! $response->successful()) {
                abort(500, 'Erro ao enviar imagem para o Supabase');
            }

            $event->image = $imageName;
        }

        $event->save();
        $this->syncScientificConfiguration($event, $request);
        $this->syncExistingGuestsFromRequest($event, $request);
        $guestRefMap = $this->syncEventGuestsFromRequest($event, $request, true);
        $this->removeActivitiesFromRequest($event, $request);
        $this->syncExistingActivitiesFromRequest($event, $request, $guestRefMap);
        $this->storeExtraActivitiesFromRequest($event, $request, $guestRefMap);
        $this->syncExistingDocumentsFromRequest($event, $request);
        $this->storeExtraDocumentsFromRequest($event, $request);

        return redirect('/dashboard')->with('msg', 'Evento editado com sucesso!');
    }

    /**
     * Recusa a edição quando o novo período deixa atividade ou apresentação de fora,
     * ou quando o horário some e ainda há apresentação agendada.
     */
    private function failIfScheduleChangeBreaksDependents(Request $request, Event $event, \Closure $fail): void
    {
        $message = $this->scheduleChangeConflictMessage($request, $event);
        if ($message !== null) {
            $fail($message);
        }
    }

    private function scheduleChangeConflictMessage(Request $request, Event $event): ?string
    {
        $removedIds = collect($request->input('activities_remove', []))
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $id > 0)
            ->all();

        $presentations = WorkPresentation::query()
            ->whereNotNull('scheduled_start')
            ->whereNotNull('scheduled_end')
            ->whereHas('work', fn ($query) => $query->where('event_id', $event->id))
            ->with('work:id,event_id,title')
            ->get();

        if (! $this->requestScheduleIsComplete($request)) {
            if ($presentations->isEmpty()) {
                return null;
            }

            $titles = $presentations
                ->map(fn (WorkPresentation $presentation) => $presentation->work?->title ?? ('Trabalho #'.$presentation->work_id))
                ->unique()
                ->values()
                ->all();

            return 'Defina o horário de início e de fim do evento. Estas apresentações já estão agendadas e precisam do período oficial: '.implode('; ', $titles).'.';
        }

        try {
            $endRaw = $request->input('end_date');
            $endDate = ($endRaw !== null && trim((string) $endRaw) !== '')
                ? trim((string) $endRaw)
                : trim((string) $request->input('start_date'));
            [$eventStart, $eventEnd] = Event::calendarBoundsFromSchedule(
                trim((string) $request->input('start_date')),
                $request->input('start_time'),
                $endDate,
                $request->input('end_time'),
            );
        } catch (\Throwable) {
            return null;
        }

        $submitted = (array) $request->input('activities_existing', []);
        $activityTitles = [];
        $activities = Activity::query()->where('event_id', $event->id)->get();
        foreach ($activities as $activity) {
            if (in_array((int) $activity->id, $removedIds, true)) {
                continue;
            }

            $row = $submitted[$activity->id] ?? $submitted[(string) $activity->id] ?? null;
            if (is_array($row)) {
                $activityDate = trim((string) ($row['activity_date'] ?? ''));
                $startTime = trim((string) ($row['start_time'] ?? ''));
                $endTime = trim((string) ($row['end_time'] ?? ''));
                $title = trim((string) ($row['title'] ?? '')) ?: (string) $activity->title;
                if ($activityDate === '' || $startTime === '' || $endTime === '') {
                    continue;
                }
                try {
                    $startAt = $this->carbonFromActivityWizardDateAndTime($activityDate, $startTime);
                    $endAt = $this->carbonFromActivityWizardDateAndTime($activityDate, $endTime);
                } catch (\Throwable) {
                    continue;
                }
            } else {
                $title = (string) $activity->title;
                $startAt = Carbon::parse($activity->start_at);
                $endAt = Carbon::parse($activity->end_at ?? $activity->start_at);
            }

            if (! Event::activityFitsEventWindow($startAt, $endAt, $eventStart, $eventEnd)) {
                $activityTitles[] = $title;
            }
        }

        $presentationTitles = [];
        foreach ($presentations as $presentation) {
            $slotStart = Carbon::parse($presentation->scheduled_start);
            $slotEnd = Carbon::parse($presentation->scheduled_end);
            if ($slotStart->lt($eventStart) || $slotEnd->gt($eventEnd)) {
                $presentationTitles[] = $presentation->work?->title ?? ('Trabalho #'.$presentation->work_id);
            }
        }

        $parts = [];
        if ($activityTitles !== []) {
            $parts[] = 'Atividades fora do novo período: '.implode('; ', $activityTitles).'.';
        }
        if ($presentationTitles !== []) {
            $parts[] = 'Apresentações fora do novo período: '.implode('; ', $presentationTitles).'.';
        }
        if ($parts === []) {
            return null;
        }

        return 'A alteração não foi salva porque algum item fica fora do novo período. Ajuste as datas e horários do evento ou corrija estes itens antes. '.implode(' ', $parts);
    }

    private function syncScientificConfiguration(Event $event, Request $request): void
    {
        if (! $event->acceptsSubmissions()) {
            DB::table('event_work_types')->where('event_id', $event->id)->delete();

            return;
        }

        $acceptedWorkTypes = $this->collectAcceptedWorkTypes($request);

        DB::table('event_work_types')->where('event_id', $event->id)->delete();
        foreach ($acceptedWorkTypes as $workType) {
            DB::table('event_work_types')->insert([
                'event_id' => $event->id,
                'work_type' => $workType,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

    }

    private function collectAcceptedWorkTypes(Request $request): array
    {
        $selected = collect($request->input('accepted_work_types', []))
            ->filter(fn ($type) => filled($type) && $type !== '__other__')
            ->map(fn ($type) => trim((string) $type))
            ->filter()
            ->values();

        $custom = collect($request->input('accepted_work_types_custom', []))
            ->map(fn ($type) => trim((string) $type))
            ->filter()
            ->values();

        return $selected
            ->merge($custom)
            ->unique()
            ->values()
            ->all();
    }

    private function storeExtraActivitiesFromRequest(Event $event, Request $request, array $guestRefMap): void
    {
        $rows = collect($request->input('activities_new', []));

        foreach ($rows as $row) {
            $title = trim((string) ($row['title'] ?? ''));
            $type = $row['type'] ?? null;
            $activityDate = $row['activity_date'] ?? null;
            $startTime = $row['start_time'] ?? null;
            $endTime = $row['end_time'] ?? null;
            $guestRefs = $this->guestRefStringsFromActivityRow($row);
            $guestIds = $this->resolveGuestIdsFromGuestRefsArray($guestRefs, $guestRefMap);
            $location = trim((string) ($row['location'] ?? ''));
            $location = $location !== '' ? $location : null;

            if ($title === '' || empty($type) || empty($activityDate) || empty($startTime) || empty($endTime) || $guestIds === []) {
                continue;
            }

            $startAt = $this->carbonFromActivityWizardDateAndTime((string) $activityDate, (string) $startTime);
            $endAt = $this->carbonFromActivityWizardDateAndTime((string) $activityDate, (string) $endTime);

            if ($endAt->lessThanOrEqualTo($startAt)) {
                continue;
            }

            $activity = Activity::create([
                'event_id' => $event->id,
                'parent_activity_id' => null,
                'title' => $title,
                'type' => $type,
                'start_at' => $startAt,
                'end_at' => $endAt,
                'location' => $location,
                'guest_id' => null,
                'speakers' => [],
                'is_extension' => filter_var($row['is_extension'] ?? false, FILTER_VALIDATE_BOOLEAN),
            ]);
            $this->syncActivityEventGuests($activity, $guestIds);
        }
    }

    private function syncExistingDocumentsFromRequest(Event $event, Request $request): void
    {
        if ($event->isFinalized()) {
            return;
        }

        foreach ((array) $request->input('documents_existing', []) as $docId => $row) {
            $id = (int) $docId;
            if ($id <= 0) {
                continue;
            }

            $document = EventDocument::where('event_id', $event->id)->where('id', $id)->first();
            if (! $document) {
                continue;
            }

            $title = trim((string) ($row['title'] ?? ''));
            if ($title !== '') {
                $document->title = $title;
            }

            $file = $request->file('documents_existing.'.$id.'.file');
            if ($file && $file->isValid()) {
                $storedPath = $this->uploadEventDocumentToStorage($event, $file);
                $oldPath = $document->storage_path;
                $document->storage_path = $storedPath;
                $document->file_name = $file->getClientOriginalName();
                $document->save();
                $this->deleteEventDocumentFromStorage($oldPath);
            } else {
                $document->save();
            }
        }
    }

    private function storeExtraDocumentsFromRequest(Event $event, Request $request): void
    {
        $rows = collect($request->input('documents_new', []));
        $files = $request->file('documents_new', []);

        foreach ($rows as $index => $row) {
            $file = $files[$index]['file'] ?? null;
            if (! $file) {
                continue;
            }

            $storedPath = $this->uploadEventDocumentToStorage($event, $file);
            $title = trim((string) ($row['title'] ?? ''));

            EventDocument::create([
                'event_id' => $event->id,
                'title' => $title !== '' ? $title : $file->getClientOriginalName(),
                'document_type' => EventDocument::TYPE_ATTACHMENT,
                'file_name' => $file->getClientOriginalName(),
                'storage_path' => $storedPath,
                'display_order' => 0,
                'uploaded_by_user_id' => auth()->id(),
            ]);
        }
    }

    public function joinEvent($id)
    {

        $user = auth()->user();

        if ($user->isCoordinator()) {
            return redirect()->back()->with(
                'msg',
                'Contas de coordenação não podem se inscrever em eventos.'
            );
        }
        if ($user->isReviewer()) {
            return redirect()->back()->with(
                'msg',
                'Contas de avaliador não podem se inscrever em eventos.'
            );
        }

        $result = DB::transaction(function () use ($id, $user) {
            $event = Event::query()->lockForUpdate()->findOrFail($id);

            if ($event->users()->where('users.id', $user->id)->exists()) {
                return ['status' => 'already', 'event' => $event];
            }

            $reason = $event->registrationsBlockedReasonFor($user);
            if ($reason !== null) {
                return ['status' => 'blocked', 'reason' => $reason, 'event' => $event];
            }

            $event->users()->attach($user->id);

            return ['status' => 'ok', 'event' => $event];
        });

        if ($result['status'] === 'already') {
            return redirect('/dashboard')->with('msg', 'Você já confirmou presença neste evento!');
        }

        if ($result['status'] === 'blocked') {
            $messages = [
                'finalized' => 'Este evento foi finalizado. As inscrições não estão mais disponíveis.',
                'ended' => 'O período deste evento foi encerrado.',
                'started' => 'Este evento já está em andamento. As inscrições estão encerradas.',
                'deadline' => 'O prazo de inscrições encerrou.',
                'full' => 'Não há mais vagas para este evento.',
                'internal_full' => 'Não há mais vagas para participantes internos neste evento.',
                'external_full' => 'Não há mais vagas para participantes externos neste evento.',
            ];

            return redirect()->back()->with('msg', $messages[$result['reason']] ?? 'Não é possível se inscrever neste evento.');
        }

        EventMailer::notify($user, new EventRegistrationConfirmed($result['event']));

        return redirect('/dashboard')->with('msg', 'Sua presença está confirmada no evento: '.$result['event']->title);

    }

    public function finalizeEvent($id)
    {
        $event = Event::findOrFail($id);

        if (auth()->id() != $event->user_id) {
            abort(403, 'Acesso negado.');
        }

        if ($event->isFinalized()) {
            return redirect()->back()->with('msg', 'Este evento já foi finalizado.');
        }

        if (! $event->scheduleIsComplete()) {
            return redirect()->back()->with(
                'msg',
                'Defina o horário de início e de fim do evento antes de finalizá-lo.'
            );
        }

        $allowEarly = (bool) config('app.allow_early_event_finalize', false);

        if (! $event->calendarEnded() && ! $allowEarly) {
            return redirect()->back()->with(
                'msg',
                'Só é possível finalizar o evento após o término da data e horário de fim informados.'
            );
        }

        if (! $event->calendarEnded() && $allowEarly) {
            Log::info('Evento finalizado antes do fim do período (EVENT_ALLOW_EARLY_FINALIZE).', [
                'event_id' => $event->id,
                'user_id' => auth()->id(),
            ]);
        }

        $event->finalized_at = now();
        $event->save();

        return redirect()->back()->with(
            'msg',
            'Evento finalizado. Não será mais possível editar, gerenciar inscritos, novidades ou patrocinadores; você ainda pode excluir o evento se precisar.'
        );
    }

    public function leaveEvent($id)
    {

        $user = auth()->user();

        $user->eventsAsParticipant()->detach($id);

        $event = Event::findOrFail($id);

        return redirect('/dashboard')->with('msg', 'Você saiu com sucesso do evento: '.$event->title);

    }

    public function registered($id)
    {
        $event = Event::findOrFail($id);

        if (auth()->id() != $event->user_id) {
            return redirect('/dashboard');
        }

        $users = $event->users()
            ->orderBy('users.is_external_participant')
            ->orderBy('users.name')
            ->get();

        return view('events.registered', [
            'event' => $event,
            'users' => $users,
            'audienceCounts' => $event->audienceRegistrationCounts(),
        ]);
    }

    public function exportCsv($id)
    {

        $event = Event::findOrFail($id);

        if (auth()->id() != $event->user_id) {
            return redirect('/dashboard');
        }

        $users = $event->users;

        $fileName = 'inscritos_'.$event->title.'.csv';

        // Cabeçalhos para download
        $headers = [
            'Content-type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"$fileName\"",
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        $callback = function () use ($users) {
            $file = fopen('php://output', 'w');

            // Adicionar BOM UTF-8 para evitar acentos bugados
            echo "\xEF\xBB\xBF";

            // Cabeçalho
            fputcsv($file, ['Nome', 'E-mail', 'Categoria', 'Perfil', 'Matrícula', 'Instituição', 'Curso / área / setor']);

            // Dados
            foreach ($users as $user) {
                fputcsv($file, [
                    $user->name,
                    $user->email,
                    $user->audienceCategoryLabel(),
                    $user->participantProfileLabel(),
                    $user->matricula,
                    $user->is_external_participant ? ($user->institution ?: '') : 'IFPA - Belém',
                    $user->curso,
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function removeParticipant($eventId, $userId)
    {

        $event = Event::findOrFail($eventId);

        if (auth()->id() != $event->user_id) {
            return redirect('/dashboard')->with('msg', 'Acesso negado.');
        }

        if ($event->isFinalized()) {
            return back()->with('msg', 'Este evento foi finalizado. Não é possível alterar a lista de inscritos.');
        }

        $participant = $event->users()->where('users.id', $userId)->first();
        if ($participant) {
            $category = $participant->is_external_participant ? 'externa' : 'interna';
            $event->users()->detach($userId);

            return back()->with('msg', 'Participante removido. A vaga '.$category.' foi liberada.');
        }

        return back()->with('msg', 'Este participante não estava inscrito neste evento.');
    }

    public function novidades($id)
    {
        $event = Event::findOrFail($id);

        if (auth()->id() != $event->user_id) {
            return redirect('/dashboard');
        }

        if ($event->isFinalized()) {
            return redirect('/dashboard')->with('msg', 'Este evento foi finalizado. Novidades não podem mais ser alteradas.');
        }

        $novidades = $event->eventNews()->orderBy('created_at', 'desc')->get();

        return view('events.novidades', ['event' => $event, 'novidades' => $novidades]);
    }

    public function storeNovidade(Request $request, $id)
    {
        $event = Event::findOrFail($id);

        if (auth()->id() != $event->user_id) {
            return redirect('/dashboard');
        }

        if ($event->isFinalized()) {
            return redirect('/dashboard')->with('msg', 'Este evento foi finalizado. Não é possível adicionar novidades.');
        }

        $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string|max:2000',
        ], [
            'title.required' => 'O título da novidade é obrigatório.',
            'content.required' => 'O conteúdo da novidade é obrigatório.',
        ]);

        $news = EventNews::create([
            'event_id' => $event->id,
            'title' => $request->title,
            'content' => $request->content,
        ]);

        $participants = $event->participantUsers()->get();
        foreach ($participants as $participant) {
            $participant->notify(new \App\Notifications\EventNewsPublished($news));
        }

        $message = $participants->isEmpty()
            ? 'Novidade adicionada. Não há inscritos para avisar.'
            : 'Novidade adicionada. O aviso aos '.$participants->count().' inscrito(s) entra na fila do Gmail: no máximo 8 por minuto e 400 por dia.';

        return redirect("/events/{$event->id}/novidades")->with('msg', $message);
    }

    public function destroyNovidade($eventId, $novidadeId)
    {
        $event = Event::findOrFail($eventId);

        if (auth()->id() != $event->user_id) {
            return redirect('/dashboard');
        }

        if ($event->isFinalized()) {
            return redirect('/dashboard')->with('msg', 'Este evento foi finalizado. Não é possível remover novidades.');
        }

        $novidade = EventNews::where('event_id', $eventId)->where('id', $novidadeId)->firstOrFail();
        $novidade->delete();

        return redirect("/events/{$event->id}/novidades")->with('msg', 'Novidade removida com sucesso!');
    }

    public function patrocinadores($id)
    {
        $event = Event::findOrFail($id);

        if (auth()->id() != $event->user_id) {
            return redirect('/dashboard');
        }

        if ($event->isFinalized()) {
            return redirect('/dashboard')->with('msg', 'Este evento foi finalizado. Patrocinadores não podem mais ser alterados.');
        }

        $sponsors = $event->sponsors()->get();

        return view('events.patrocinadores', ['event' => $event, 'sponsors' => $sponsors]);
    }

    public function storePatrocinador(Request $request, $id)
    {
        $event = Event::findOrFail($id);

        if (auth()->id() != $event->user_id) {
            return redirect('/dashboard');
        }

        if ($event->isFinalized()) {
            return redirect('/dashboard')->with('msg', 'Este evento foi finalizado. Não é possível adicionar patrocinadores.');
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'website' => ['nullable', 'string', 'max:255', 'regex:/^https?:\/\/\S+$/i'],
        ], [
            'name.required' => 'O nome do patrocinador é obrigatório.',
            'website.regex' => 'O site precisa começar com http:// ou https://.',
        ]);

        EventSponsor::create([
            'event_id' => $event->id,
            'name' => $request->name,
            'website' => $request->website,
        ]);

        return redirect("/events/{$event->id}/patrocinadores")->with('msg', 'Patrocinador adicionado.');
    }

    public function destroyPatrocinador($eventId, $sponsorId)
    {
        $event = Event::findOrFail($eventId);

        if (auth()->id() != $event->user_id) {
            return redirect('/dashboard');
        }

        if ($event->isFinalized()) {
            return redirect('/dashboard')->with('msg', 'Este evento foi finalizado. Não é possível remover patrocinadores.');
        }

        $sponsor = EventSponsor::where('event_id', $eventId)->where('id', $sponsorId)->firstOrFail();
        $sponsor->delete();

        return redirect("/events/{$event->id}/patrocinadores")->with('msg', 'Patrocinador removido.');
    }

    public function storeDocument(Request $request, $eventId)
    {
        $event = Event::findOrFail($eventId);

        if (auth()->id() != $event->user_id) {
            return redirect('/dashboard');
        }

        if ($event->isFinalized()) {
            return back()->with('msg', 'Este evento foi finalizado. Não é possível adicionar documentos.');
        }

        $data = $request->validate([
            'title' => 'required|string|max:255',
            'document_type' => ['nullable', Rule::in(EventDocument::typeOptions())],
            'display_order' => 'nullable|integer|min:0|max:9999',
            'file' => 'required|file|mimes:pdf,doc,docx,ppt,pptx,xls,xlsx,zip,rar,jpg,jpeg,png|max:15360',
        ], [
            'title.required' => 'Informe o título do documento.',
            'file.required' => 'Envie o arquivo do documento.',
            'file.mimes' => 'Formato não suportado. Envie PDF, Office, ZIP/RAR ou imagem.',
            'file.max' => 'O documento deve ter no máximo 15MB.',
        ]);

        $uploadedFile = $request->file('file');
        $storedPath = $this->uploadEventDocumentToStorage($event, $uploadedFile);

        EventDocument::create([
            'event_id' => $event->id,
            'title' => $data['title'],
            'document_type' => $data['document_type'] ?? EventDocument::TYPE_ATTACHMENT,
            'file_name' => $uploadedFile->getClientOriginalName(),
            'storage_path' => $storedPath,
            'display_order' => (int) ($data['display_order'] ?? 0),
            'uploaded_by_user_id' => auth()->id(),
        ]);

        return back()->with('msg', 'Documento adicionado com sucesso.');
    }

    public function destroyDocument($eventId, $documentId)
    {
        $event = Event::findOrFail($eventId);

        if (auth()->id() != $event->user_id) {
            return redirect('/dashboard');
        }

        if ($event->isFinalized()) {
            return back()->with('msg', 'Este evento foi finalizado. Não é possível remover documentos.');
        }

        $document = EventDocument::where('event_id', $event->id)
            ->where('id', $documentId)
            ->firstOrFail();

        $this->deleteEventDocumentFromStorage($document->storage_path);
        $document->delete();

        return back()->with('msg', 'Documento removido com sucesso.');
    }

    public function downloadDocument($eventId, $documentId)
    {
        $event = Event::findOrFail($eventId);
        $document = EventDocument::where('event_id', $event->id)
            ->where('id', $documentId)
            ->firstOrFail();

        $bucket = config('services.supabase.bucket_attachments');
        $response = Http::withHeaders([
            'Authorization' => 'Bearer '.config('services.supabase.service_role'),
            'apikey' => config('services.supabase.service_role'),
        ])->get(
            config('services.supabase.url')."/storage/v1/object/{$bucket}/{$document->storage_path}"
        );

        if (! $response->successful()) {
            abort(404, 'Não foi possível baixar o documento solicitado.');
        }

        $fileName = str_replace('"', '', $document->file_name);

        return response($response->body(), 200, [
            'Content-Type' => $response->header('Content-Type', 'application/octet-stream'),
            'Content-Disposition' => 'attachment; filename="'.$fileName.'"',
        ]);
    }

    public function storeActivity(Request $request, $eventId)
    {
        $event = Event::with('activities')->findOrFail($eventId);

        if (auth()->id() != $event->user_id) {
            return redirect('/dashboard');
        }

        if ($event->isFinalized()) {
            return back()->with('msg', 'Este evento foi finalizado. Não é possível adicionar atividades.');
        }

        $data = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:5000',
            'type' => ['required', Rule::in(Activity::typeOptions())],
            'start_at' => 'required|date',
            'end_at' => 'nullable|date|after_or_equal:start_at',
            'location' => 'required|string|max:255',
            'capacity' => 'nullable|integer|min:1',
            'speakers_raw' => 'nullable|string|max:5000',
            'parent_activity_id' => [
                'nullable',
                Rule::exists('activities', 'id')->where('event_id', $event->id),
            ],
        ], [
            'title.required' => 'Informe o título da atividade.',
            'type.required' => 'Selecione o tipo da atividade.',
            'start_at.required' => 'Informe a data/hora de início da atividade.',
            'location.required' => 'Informe o local da atividade.',
            'end_at.after_or_equal' => 'A data/hora de fim deve ser igual ou posterior ao início.',
        ]);

        $actStart = Carbon::parse($data['start_at']);
        $actEnd = ! empty($data['end_at']) ? Carbon::parse($data['end_at']) : $actStart;
        if ($event->scheduleIsComplete() && ! Event::activityFitsEventWindow($actStart, $actEnd, $event->calendarStartAt(), $event->calendarEndAt())) {
            throw ValidationException::withMessages([
                'start_at' => 'A atividade deve ocorrer inteiramente entre o início e o fim do evento (pode coincidir com esses instantes, mas não ultrapassar).',
            ]);
        }

        Activity::create([
            'event_id' => $event->id,
            'parent_activity_id' => $data['parent_activity_id'] ?? null,
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'type' => $data['type'],
            'start_at' => $data['start_at'],
            'end_at' => $data['end_at'] ?? null,
            'location' => $data['location'],
            'capacity' => $data['capacity'] ?? null,
            'speakers' => $this->parseSpeakersRaw($data['speakers_raw'] ?? null),
        ]);

        return back()->with('msg', 'Atividade adicionada com sucesso.');
    }

    public function destroyActivity($eventId, $activityId)
    {
        $event = Event::findOrFail($eventId);

        if (auth()->id() != $event->user_id) {
            return redirect('/dashboard');
        }

        if ($event->isFinalized()) {
            return back()->with('msg', 'Este evento foi finalizado. Não é possível remover atividades.');
        }

        $activity = Activity::where('event_id', $event->id)->where('id', $activityId)->firstOrFail();
        $activity->delete();

        return back()->with('msg', 'Atividade removida com sucesso.');
    }

    private function normalizeModalityType(?string $modality): ?string
    {
        return match ($modality) {
            'Presencial' => 'presencial',
            'Online' => 'online',
            'Híbrido' => 'hibrido',
            default => null,
        };
    }

    private function syncEventGuestsFromRequest(Event $event, Request $request, bool $allowRemoveExisting): array
    {
        $rows = collect($request->input('guests_new', []));
        $map = [];

        if ($allowRemoveExisting) {
            $removeIds = collect($request->input('guests_remove', []))
                ->filter()
                ->map(fn ($id) => (int) $id)
                ->filter(fn ($id) => $id > 0)
                ->values()
                ->all();

            if (! empty($removeIds)) {
                DB::table('activity_event_guest')->whereIn('event_guest_id', $removeIds)->delete();

                Activity::where('event_id', $event->id)
                    ->whereIn('guest_id', $removeIds)
                    ->update(['guest_id' => null]);

                EventGuest::where('event_id', $event->id)
                    ->whereIn('id', $removeIds)
                    ->delete();

                $this->reconcileActivityPrimaryGuestsForEvent($event->id);
            }
        }

        foreach ($rows as $index => $row) {
            $name = trim((string) ($row['name'] ?? ''));
            $roleType = trim((string) ($row['role_type'] ?? ''));
            $role = trim((string) ($row['role'] ?? ''));

            if ($name === '' && $roleType === '' && $role === '') {
                continue;
            }

            if ($name === '') {
                continue;
            }

            $guest = EventGuest::create([
                'event_id' => $event->id,
                'name' => $name,
                'role_type' => $roleType !== '' ? $roleType : null,
                'role' => $role !== '' ? $role : null,
            ]);

            $map['new:'.$index] = $guest->id;
        }

        // Depois de criar convidados novos: mapear id:* para todos os convidados do evento
        // (evita falha ao resolver atividades que enviam id: do convidado recém-criado no mesmo POST).
        foreach ($event->guests()->get() as $guest) {
            $map['id:'.$guest->id] = $guest->id;
        }

        return $map;
    }

    private function syncExistingGuestsFromRequest(Event $event, Request $request): void
    {
        foreach ((array) $request->input('guests_existing', []) as $row) {
            $id = (int) ($row['id'] ?? 0);
            if ($id <= 0) {
                continue;
            }

            $guest = EventGuest::where('event_id', $event->id)->where('id', $id)->first();
            if (! $guest) {
                continue;
            }

            $name = trim((string) ($row['name'] ?? ''));
            if ($name === '') {
                continue;
            }

            $role = trim((string) ($row['role'] ?? ''));
            $roleType = trim((string) ($row['role_type'] ?? ''));
            $guest->name = $name;
            $guest->role_type = $roleType !== '' ? $roleType : null;
            $guest->role = $role !== '' ? $role : null;
            $guest->save();
        }
    }

    private function removeActivitiesFromRequest(Event $event, Request $request): void
    {
        $removeIds = collect($request->input('activities_remove', []))
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $id > 0)
            ->values()
            ->all();

        if (empty($removeIds)) {
            return;
        }

        Activity::where('event_id', $event->id)
            ->whereIn('id', $removeIds)
            ->delete();
    }

    private function syncExistingActivitiesFromRequest(Event $event, Request $request, array $guestRefMap): void
    {
        foreach ((array) $request->input('activities_existing', []) as $activityId => $row) {
            $id = (int) $activityId;
            if ($id <= 0) {
                continue;
            }

            $activity = Activity::where('event_id', $event->id)->where('id', $id)->first();
            if (! $activity) {
                continue;
            }

            $title = trim((string) ($row['title'] ?? ''));
            $type = $row['type'] ?? null;
            $activityDate = $row['activity_date'] ?? null;
            $startTime = $row['start_time'] ?? null;
            $endTime = $row['end_time'] ?? null;
            $guestRefs = $this->guestRefStringsFromActivityRow($row);
            $guestIds = $this->resolveGuestIdsFromGuestRefsArray($guestRefs, $guestRefMap);
            $location = trim((string) ($row['location'] ?? ''));
            $location = $location !== '' ? $location : null;

            if ($title === '' || empty($type) || empty($activityDate) || empty($startTime) || empty($endTime) || $guestIds === []) {
                continue;
            }

            $startAt = $this->carbonFromActivityWizardDateAndTime((string) $activityDate, (string) $startTime);
            $endAt = $this->carbonFromActivityWizardDateAndTime((string) $activityDate, (string) $endTime);
            if ($endAt->lessThanOrEqualTo($startAt)) {
                continue;
            }

            $activity->title = $title;
            $activity->type = $type;
            $activity->start_at = $startAt;
            $activity->end_at = $endAt;
            $activity->location = $location;
            $activity->is_extension = filter_var($row['is_extension'] ?? false, FILTER_VALIDATE_BOOLEAN);
            $activity->save();
            $this->syncActivityEventGuests($activity, $guestIds);
        }
    }

    private function guestRefStringsFromActivityRow(array $row): array
    {
        $refs = $row['guest_refs'] ?? [];
        if (! is_array($refs)) {
            $s = trim((string) $refs);
            $refs = $s !== '' ? [$s] : [];
        }
        $out = collect($refs)
            ->map(fn ($r) => trim((string) $r))
            ->filter()
            ->values()
            ->all();
        $legacy = trim((string) ($row['guest_ref'] ?? ''));
        if ($legacy !== '') {
            $out[] = $legacy;
        }

        return array_values(array_unique($out));
    }

    private function resolveGuestIdsFromGuestRefsArray(array $refs, array $guestRefMap): array
    {
        $ids = [];
        foreach ($refs as $ref) {
            $id = $this->resolveGuestIdFromRef($ref, $guestRefMap);
            if ($id !== null) {
                $ids[] = $id;
            }
        }

        return array_values(array_unique($ids));
    }

    private function syncActivityEventGuests(Activity $activity, array $guestIdsOrdered): void
    {
        $guestIdsOrdered = array_values(array_unique(array_filter($guestIdsOrdered)));
        $sync = [];
        foreach ($guestIdsOrdered as $order => $gid) {
            $sync[(int) $gid] = ['sort_order' => $order];
        }
        $activity->eventGuests()->sync($sync);
        $first = $guestIdsOrdered[0] ?? null;
        if ((int) $activity->guest_id !== (int) ($first ?? 0)) {
            $activity->guest_id = $first;
            $activity->saveQuietly();
        }
    }

    private function reconcileActivityPrimaryGuestsForEvent(int $eventId): void
    {
        $activities = Activity::where('event_id', $eventId)->get();
        foreach ($activities as $activity) {
            $firstId = $activity->eventGuests()->orderByPivot('sort_order', 'asc')->first()?->id;
            if ((int) $activity->guest_id !== (int) ($firstId ?? 0)) {
                $activity->guest_id = $firstId;
                $activity->saveQuietly();
            }
        }
    }

    private function resolveGuestIdFromRef(string $guestRef, array $guestRefMap): ?int
    {
        if ($guestRef === '' || ! isset($guestRefMap[$guestRef])) {
            return null;
        }

        return (int) $guestRefMap[$guestRef];
    }

    private function parseSpeakersRaw(?string $speakersRaw): array
    {
        if ($speakersRaw === null || trim($speakersRaw) === '') {
            return [];
        }

        $lines = preg_split('/\r\n|\r|\n/', $speakersRaw) ?: [];
        $parsed = [];

        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '') {
                continue;
            }

            [$name, $role] = array_pad(explode('|', $line, 2), 2, '');
            $name = trim($name);
            $role = trim($role);

            if ($name === '') {
                continue;
            }

            $parsed[] = [
                'name' => $name,
                'role' => $role !== '' ? $role : null,
            ];
        }

        return $parsed;
    }

    private function uploadEventDocumentToStorage(Event $event, $file): string
    {
        $extension = $file->extension();
        $fileName = md5($file->getClientOriginalName().now()->timestamp.rand()).($extension ? ".{$extension}" : '');
        $storagePath = "events/documents/{$event->id}/{$fileName}";
        $bucket = config('services.supabase.bucket_attachments');

        $response = Http::withHeaders([
            'Authorization' => 'Bearer '.config('services.supabase.service_role'),
            'apikey' => config('services.supabase.service_role'),
            'Content-Type' => $file->getMimeType(),
        ])->withBody(
            file_get_contents($file->getRealPath()),
            $file->getMimeType()
        )->post(
            config('services.supabase.url')."/storage/v1/object/{$bucket}/{$storagePath}"
        );

        if (! $response->successful()) {
            abort(500, 'Erro ao enviar documento para o armazenamento.');
        }

        return $storagePath;
    }

    private function deleteEventDocumentFromStorage(string $storagePath): void
    {
        $bucket = config('services.supabase.bucket_attachments');
        Http::withHeaders([
            'Authorization' => 'Bearer '.config('services.supabase.service_role'),
            'apikey' => config('services.supabase.service_role'),
        ])->delete(
            config('services.supabase.url')."/storage/v1/object/{$bucket}/{$storagePath}"
        );
    }

    public function annalsPublic($id)
    {
        $event = Event::findOrFail($id);

        return view('events.annals-public', compact('event'));
    }

    public function downloadAnnals($id)
    {
        $event = Event::findOrFail($id);

        if (! $event->hasAnnalsPublication()) {
            abort(404, 'Ainda não há anais ou e-book publicado para este evento.');
        }

        $client = app(SupabaseObjectClient::class);
        $response = $client->fetch(
            (string) config('services.supabase.bucket_annals'),
            $event->annals_storage_path
        );

        if ($response === null || ! $response->successful()) {
            abort(404, 'Não foi possível baixar o arquivo.');
        }

        $fileName = str_replace(['"', "\r", "\n"], '', (string) $event->annals_file_name);
        if ($fileName === '') {
            $fileName = 'publicacao.pdf';
        }

        return response($response->body(), 200, [
            'Content-Type' => $response->header('Content-Type', 'application/pdf'),
            'Content-Disposition' => 'attachment; filename="'.$fileName.'"',
        ]);
    }

    public function storeAnnals(Request $request, $id)
    {
        $event = Event::findOrFail($id);
        $this->assertCoordinatorOwner($event);

        if (! $event->isFinalized()) {
            return back()->with('msg', 'Publique os anais ou o e-book somente depois de finalizar o evento.');
        }

        $data = $request->validate([
            'annals_kind' => ['required', Rule::in(array_keys(Event::annalsKindOptions()))],
            'file' => 'required|file|mimes:pdf|mimetypes:application/pdf|max:40960',
        ], [
            'annals_kind.required' => 'Informe se o arquivo é de anais ou de e-book.',
            'annals_kind.in' => 'Escolha Anais ou E-book.',
            'file.required' => 'Envie o PDF.',
            'file.mimes' => 'O arquivo precisa ser um PDF.',
            'file.mimetypes' => 'O arquivo precisa ser um PDF.',
            'file.max' => 'O PDF deve ter no máximo 40 MB.',
        ]);

        /** @var UploadedFile $uploadedFile */
        $uploadedFile = $request->file('file');
        $previousPath = $event->annals_storage_path;
        $storagePath = $this->uploadAnnalsToStorage($event, $uploadedFile);

        $event->annals_kind = $data['annals_kind'];
        $event->annals_file_name = $uploadedFile->getClientOriginalName();
        $event->annals_storage_path = $storagePath;
        $event->annals_uploaded_at = now();
        $event->save();

        if ($previousPath && $previousPath !== $storagePath) {
            $this->deleteAnnalsFromStorage($previousPath);
        }

        $label = $event->annalsKindLabel();

        return back()->with('msg', $label.' publicado. Qualquer pessoa pode baixar o PDF na página do evento.');
    }

    public function destroyAnnals($id)
    {
        $event = Event::findOrFail($id);
        $this->assertCoordinatorOwner($event);

        if (! $event->isFinalized()) {
            return back()->with('msg', 'Só é possível remover esta publicação depois que o evento estiver finalizado.');
        }

        if ($event->annals_storage_path) {
            $this->deleteAnnalsFromStorage($event->annals_storage_path);
        }

        $event->annals_kind = null;
        $event->annals_file_name = null;
        $event->annals_storage_path = null;
        $event->annals_uploaded_at = null;
        $event->save();

        return back()->with('msg', 'O PDF dos anais ou do e-book foi removido.');
    }

    private function assertCoordinatorOwner(Event $event): void
    {
        abort_unless(
            auth()->check()
            && auth()->user()->isCoordinator()
            && (int) auth()->id() === (int) $event->user_id,
            403
        );
    }

    private function uploadAnnalsToStorage(Event $event, UploadedFile $file): string
    {
        $fileName = md5($file->getClientOriginalName().now()->timestamp.random_int(1, 99999)).'.pdf';
        $storagePath = "events/{$event->id}/{$fileName}";
        $client = app(SupabaseObjectClient::class);

        try {
            $client->upload(
                (string) config('services.supabase.bucket_annals'),
                $storagePath,
                (string) file_get_contents($file->getRealPath()),
                'application/pdf',
                true
            );
        } catch (\RuntimeException $exception) {
            abort(500, 'Erro ao enviar o PDF para o armazenamento.');
        }

        return $storagePath;
    }

    private function deleteAnnalsFromStorage(string $storagePath): void
    {
        app(SupabaseObjectClient::class)->deleteObject(
            (string) config('services.supabase.bucket_annals'),
            $storagePath
        );
    }
}
