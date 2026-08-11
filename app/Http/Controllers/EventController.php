<?php

namespace App\Http\Controllers;

use App\Models\Activity;
use App\Models\Event;
use App\Models\EventDocument;
use App\Models\EventGuest;
// Acesso ao Model de eventos
use App\Models\EventNews;
use App\Models\User;
use App\Models\Work;
// Acesso ao Model de novidades
use Carbon\Carbon;
use Illuminate\Http\Request;
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

    private function validationRules(Request $request): array
    {
        return [
            'title' => 'required|string|max:255',
            'category' => ['required', 'string', 'max:255', Rule::in(array_merge(Event::CATEGORY_OPTIONS, [Event::SELECT_OTHER_VALUE]))],
            'category_custom' => ['nullable', 'string', 'max:255', Rule::requiredIf(fn () => $request->input('category') === Event::SELECT_OTHER_VALUE)],
            'modality' => 'required|string|max:255',
            'event_type' => ['required', 'string', 'max:255', Rule::in(array_merge(Event::EVENT_TYPE_OPTIONS, [Event::SELECT_OTHER_VALUE]))],
            'event_type_custom' => ['nullable', 'string', 'max:255', Rule::requiredIf(fn () => $request->input('event_type') === Event::SELECT_OTHER_VALUE)],
            'capacity' => 'required|integer|min:1',

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
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',

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
                        'O prazo de inscrição não pode ser posterior à data e hora de início do evento.'
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
                        'O prazo de submissão não pode ser posterior à data e hora de início do evento.'
                    );
                },
            ],
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

            'capacity.required' => 'A capacidade é obrigatória.',
            'capacity.integer' => 'A capacidade deve ser um número inteiro.',
            'capacity.min' => 'A capacidade deve ser no mínimo 1 aluno.',

            'ead_link.url' => 'O link do EAD deve ser uma URL válida.',

            'description.required' => 'A descrição do evento é obrigatória.',
            'description.string' => 'A descrição deve ser um texto válido.',

            'start_date.required' => 'A data de início é obrigatória.',
            'start_date.date' => 'A data de início deve ser uma data válida.',
            'end_date.required' => 'A data de término é obrigatória.',
            'end_date.date' => 'A data de término deve ser uma data válida.',
            'end_date.after_or_equal' => 'A data de término deve ser igual ou posterior à data de início.',
            'start_time.required' => 'O horário de início é obrigatório.',
            'start_time.date_format' => 'O horário de início deve estar no formato HH:MM.',
            'end_time.required' => 'O horário de término é obrigatório.',
            'end_time.date_format' => 'O horário de término deve estar no formato HH:MM.',
            'end_time.after' => 'O horário de término deve ser posterior ao horário de início.',

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
            // STEP 0 — Informações Básicas
            0 => [
                'title',
                'category',
                'category_custom',
                'modality',
                'event_type',
                'event_type_custom',
                'capacity',
                'ead_link',
                'description',
            ],

            // STEP 1 — Datas + Localização + Imagem
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

            // STEP 2 — Coordenação + Configurações finais
            2 => [
                'coordinator_name',
                'coordinator_email',
                'coordinator_phone',
                'datetime_registration',
                'submission_deadline_at',
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
     * Falha se o prazo (inscrição/submissão) for estritamente após o início do evento (data + hora).
     * Sem start_date ou start_time no request, não compara (validação por etapa); o submit completo revalida.
     */
    private function failIfDeadlineAfterEventStart(Request $request, mixed $value, \Closure $fail, string $message): void
    {
        $startDate = trim((string) $request->input('start_date', ''));
        $startTime = trim((string) $request->input('start_time', ''));
        if ($startDate === '' || $startTime === '') {
            return;
        }

        $startTimeNorm = strlen($startTime) === 5 ? $startTime.':00' : $startTime;
        $tz = config('app.timezone');

        try {
            $startAt = Carbon::parse($startDate.' '.$startTimeNorm, $tz);
            $deadlineAt = Carbon::parse($value, $tz);
        } catch (\Throwable) {
            return;
        }

        if ($deadlineAt->greaterThan($startAt)) {
            $fail($message);
        }
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

    /**
     * Verdadeiro se a atividade estiver fora do período do evento conforme datas/horários do request.
     * Se não houver start_date no request, não restringe (outras regras tratam o formulário completo).
     */
    private function activityOutsideEventRequestWindow(Request $request, Carbon $activityStart, Carbon $activityEnd): bool
    {
        $startDate = trim((string) $request->input('start_date', ''));
        if ($startDate === '') {
            return false;
        }

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
            $request->merge(['submission_deadline_at' => null]);
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
        $event->capacity = $request->capacity;
        $event->ead_link = $request->ead_link ?? null;
        $event->description = Purifier::clean($request->description ?? '');

        $event->target_audience = [];
        $event->prerequisites = [];

        $event->start_date = $request->start_date;
        $event->end_date = $request->end_date ?? null;
        $event->start_time = $request->start_time ?? null;
        $event->end_time = $request->end_time ?? null;

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
        $event = Event::with(['eventNews', 'documents', 'activities.parentActivity', 'activities.guest', 'activities.eventGuests', 'guests'])->findOrFail($id);

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

        $events = $user->events;

        $eventsAsParticipant = $user->eventsAsParticipant;

        // echo "<pre>"; print_r($events); echo "</pre>"; exit;

        return view('events.dashboard', ['events' => $events, 'eventsAsParticipant' => $eventsAsParticipant]);
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

        // Prazos/agenda já definidos são fixados antes da validação; prazo de submissão só se já existir valor salvo (senão permite preencher).
        $this->mergeLockedEventScheduleFromModelIntoRequest($request, $event);

        // Sem trabalhos/submissões: não persiste data de encerramento de submissões.
        if (! $request->boolean('accepts_submissions')) {
            $request->merge(['submission_deadline_at' => null]);
        }

        // Validação
        $request->validate([
            'title' => 'required|string|max:255',
            'category' => ['required', 'string', 'max:255', Rule::in(array_merge(Event::CATEGORY_OPTIONS, [Event::SELECT_OTHER_VALUE]))],
            'category_custom' => ['nullable', 'string', 'max:255', Rule::requiredIf(fn () => $request->input('category') === Event::SELECT_OTHER_VALUE)],
            'modality' => 'required|string|max:255',
            'event_type' => ['required', 'string', 'max:255', Rule::in(array_merge(Event::EVENT_TYPE_OPTIONS, [Event::SELECT_OTHER_VALUE]))],
            'event_type_custom' => ['nullable', 'string', 'max:255', Rule::requiredIf(fn () => $request->input('event_type') === Event::SELECT_OTHER_VALUE)],
            'capacity' => 'required|integer|min:1',
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
            'end_date' => 'required|date|after_or_equal:start_date',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',

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
                        'O prazo de inscrição não pode ser posterior à data e hora de início do evento.'
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
                        'O prazo de submissão não pode ser posterior à data e hora de início do evento.'
                    );
                },
            ],
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
                            if ($this->activityOutsideEventRequestWindow($request, $startAt, $endAt)) {
                                $fail('Cada atividade cadastrada deve ocorrer inteiramente entre a data e hora de início e a data e hora de término do evento (é permitido coincidir com o início ou o fim do evento, mas não ultrapassar esse intervalo).');

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

            // Capacidade
            'capacity.required' => 'A capacidade é obrigatória.',
            'capacity.integer' => 'A capacidade deve ser um número inteiro.',
            'capacity.min' => 'A capacidade deve ser no mínimo 1 aluno.',

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
            'start_time.required' => 'O horário de início é obrigatório.',
            'start_time.date_format' => 'O horário de início deve estar no formato HH:MM.',
            'end_time.required' => 'O horário de término é obrigatório.',
            'end_time.date_format' => 'O horário de término deve estar no formato HH:MM.',
            'end_time.after' => 'O horário de término deve ser posterior ao horário de início.',

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
        $event->capacity = $request->capacity;
        $event->ead_link = $request->ead_link ?? null;
        $event->description = Purifier::clean($request->description ?? '');
        $event->start_date = $request->start_date;
        $event->end_date = $request->end_date ?? null;
        $event->start_time = $request->start_time ?? null;
        $event->end_time = $request->end_time ?? null;
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
     * Substitui no request datas, horários e prazo de inscrição pelos valores já persistidos
     * (bloqueados na edição). O prazo de submissão de trabalhos só é bloqueado se já foi salvo antes.
     */
    private function mergeLockedEventScheduleFromModelIntoRequest(Request $request, Event $event): void
    {
        $payload = [
            'start_date' => $event->start_date?->format('Y-m-d'),
            'end_date' => $event->end_date?->format('Y-m-d'),
            'start_time' => $event->start_time
                ? Carbon::parse($event->start_time)->format('H:i')
                : null,
            'end_time' => $event->end_time
                ? Carbon::parse($event->end_time)->format('H:i')
                : null,
            'datetime_registration' => $event->datetime_registration
                ? $event->datetime_registration->format('Y-m-d\TH:i')
                : null,
        ];

        // Só impedir alteração do prazo de submissões se já tiver sido definido antes (como datas de início/fim e inscrição).
        if ($event->submission_deadline_at !== null) {
            $payload['submission_deadline_at'] = $event->submission_deadline_at->format('Y-m-d\TH:i');
        }

        $request->merge($payload);
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

        $event = Event::findOrFail($id);

        // LÓGICA QUE NÃO PERMITE O USUÁRIO CONFIRMAR PRESENÇA MAIS DE UMA VEZ NO MESMO EVENTO.
        $userEvents = $user->eventsAsParticipant;

        foreach ($userEvents as $userEvent) {
            if ($userEvent->id == $id) {
                return redirect('/dashboard')->with('msg', 'Você já confirmou presença neste evento!');
            }
        }

        $reason = $event->registrationsBlockedReason();
        if ($reason !== null) {
            $messages = [
                'finalized' => 'Este evento foi finalizado. As inscrições não estão mais disponíveis.',
                'ended' => 'O período deste evento foi encerrado.',
                'started' => 'Este evento já está em andamento. As inscrições estão encerradas.',
                'deadline' => 'O prazo de inscrições encerrou.',
                'full' => 'Não há mais vagas para este evento.',
            ];

            return redirect()->back()->with('msg', $messages[$reason] ?? 'Não é possível se inscrever neste evento.');
        }

        $user->eventsAsParticipant()->attach($id);

        return redirect('/dashboard')->with('msg', 'Sua presença está confirmada no evento: '.$event->title);

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
            'Evento finalizado. Não será mais possível editar, gerenciar inscritos ou novidades; você ainda pode excluir o evento se precisar.'
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

        if ($event->isFinalized()) {
            return redirect('/dashboard')->with('msg', 'Este evento foi finalizado. A gestão de inscritos não está mais disponível.');
        }

        $users = $event->users;

        return view('events.registered', ['event' => $event, 'users' => $users]);
    }

    public function exportCsv($id)
    {

        $event = Event::findOrFail($id);

        if (auth()->id() != $event->user_id) {
            return redirect('/dashboard');
        }

        if ($event->isFinalized()) {
            return redirect('/dashboard')->with('msg', 'Este evento foi finalizado. A exportação não está mais disponível.');
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
            fputcsv($file, ['Nome', 'Email']);

            // Dados
            foreach ($users as $user) {
                fputcsv($file, [
                    $user->name,
                    $user->email,
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

        // garante que o user está inscrito
        if ($event->users()->where('users.id', $userId)->exists()) {
            $event->users()->detach($userId);

            return back()->with('msg', 'Aluno removido com sucesso!');
        }

        return back()->with('msg', 'O aluno não estava inscrito neste evento.');
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

        EventNews::create([
            'event_id' => $event->id,
            'title' => $request->title,
            'content' => $request->content,
        ]);

        return redirect("/events/{$event->id}/novidades")->with('msg', 'Novidade adicionada com sucesso!');
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
        if (! Event::activityFitsEventWindow($actStart, $actEnd, $event->calendarStartAt(), $event->calendarEndAt())) {
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
        $works = Work::with(['authors'])
            ->where('event_id', $event->id)
            ->where('status', Work::STATUS_PUBLISHED_ANNALS)
            ->whereNotNull('published_in_annals_at')
            ->orderBy('title')
            ->get();

        return view('events.annals-public', compact('event', 'works'));
    }

    /**
     * Painel do coordenador: registrar ou atualizar publicação nos anais (apenas trabalhos já apresentados).
     */
    public function annalsManage(Event $event)
    {
        abort_unless(
            $event->user_id === (int) auth()->id() && auth()->user()->isCoordinator(),
            403
        );

        if (! $event->acceptsSubmissions()) {
            return redirect()->route('dashboard')
                ->with('msg', 'Este evento não possui fluxo de submissão de trabalhos.');
        }

        $works = Work::with(['submitter', 'authors'])
            ->where('event_id', $event->id)
            ->whereIn('status', [Work::STATUS_PRESENTED, Work::STATUS_PUBLISHED_ANNALS])
            ->orderByRaw("CASE WHEN title IS NOT NULL AND title <> '' THEN 0 ELSE 1 END")
            ->orderBy('title')
            ->get();

        return view('events.annals-manage', compact('event', 'works'));
    }
}
