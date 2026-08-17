<?php

use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\CertificateDownloadController;
use App\Http\Controllers\CertificateVerificationController;
use App\Http\Controllers\EventCertificateController;
use App\Http\Controllers\EventController;
use App\Http\Controllers\EventPresentationScheduleController;
use App\Http\Controllers\MyCertificatesController;
use App\Http\Controllers\PresentationController;
use App\Http\Controllers\ReviewController;
use App\Http\Controllers\SignatureController;
use App\Http\Controllers\WorkController;
use Illuminate\Support\Facades\Route;

Route::get('/', [EventController::class, 'index']);
Route::get('/events/load-more', [EventController::class, 'loadMore'])
    ->name('events.loadMore');
Route::get('/events/{eventId}/documentos/{documentId}/download', [EventController::class, 'downloadDocument'])
    ->name('events.documents.download');
// ->middleware('auth') retorna por padrão para /login se não reconhecer usuário autenticado.
Route::get('/events/create', [EventController::class, 'create'])->middleware(['auth', 'isCoordinator']);
Route::get('/events/{id}/anais', [EventController::class, 'annalsPublic'])->name('events.annals');
Route::get('/events/{id}', [EventController::class, 'show'])->name('events.show');
Route::post('/events', [EventController::class, 'store'])->middleware(['auth', 'isCoordinator']);
Route::get('/dashboard', [EventController::class, 'dashboard'])->middleware('auth')->name('dashboard');
Route::delete('/events/{id}', [EventController::class, 'destroy'])->middleware(['auth', 'isCoordinator']);
Route::get('/events/edit/{id}', [EventController::class, 'edit'])->middleware(['auth', 'isCoordinator']);
Route::put('/events/update/{id}', [EventController::class, 'update'])->middleware(['auth', 'isCoordinator']);
Route::post('/events/{id}/finalize', [EventController::class, 'finalizeEvent'])->middleware(['auth', 'isCoordinator']);
Route::post('/events/join/{id}', [EventController::class, 'joinEvent'])->middleware('auth');
Route::delete('/events/leave/{id}', [EventController::class, 'leaveEvent'])->middleware('auth');

Route::middleware(['auth', 'isCoordinator'])->group(function () {
    Route::get('/register/coordinator', [RegisteredUserController::class, 'create'])
        ->name('register.coordinator');
    Route::get('/register/reviewer', [RegisteredUserController::class, 'create'])
        ->name('register.reviewer');

    Route::post('/register/coordinator', [RegisteredUserController::class, 'storeCoordinator']);
    Route::post('/register/reviewer', [RegisteredUserController::class, 'storeReviewer']);
});

Route::get('/events/registered/{id}', [EventController::class, 'registered'])->middleware(['auth', 'isCoordinator']);

Route::get('/events/{id}/export-csv', [EventController::class, 'exportCsv'])->middleware(['auth', 'isCoordinator']);

Route::delete('/events/{eventId}/remove/{userId}', [EventController::class, 'removeParticipant'])->middleware(['auth', 'isCoordinator']);

Route::get('/events/{id}/novidades', [EventController::class, 'novidades'])->middleware(['auth', 'isCoordinator']);
Route::post('/events/{id}/novidades', [EventController::class, 'storeNovidade'])->middleware(['auth', 'isCoordinator']);
Route::delete('/events/{eventId}/novidades/{novidadeId}', [EventController::class, 'destroyNovidade'])->middleware(['auth', 'isCoordinator']);
Route::post('/events/{eventId}/documentos', [EventController::class, 'storeDocument'])->middleware(['auth', 'isCoordinator'])->name('events.documents.store');
Route::delete('/events/{eventId}/documentos/{documentId}', [EventController::class, 'destroyDocument'])->middleware(['auth', 'isCoordinator'])->name('events.documents.destroy');
Route::post('/events/{eventId}/atividades', [EventController::class, 'storeActivity'])->middleware(['auth', 'isCoordinator'])->name('events.activities.store');
Route::delete('/events/{eventId}/atividades/{activityId}', [EventController::class, 'destroyActivity'])->middleware(['auth', 'isCoordinator'])->name('events.activities.destroy');
// Route::get('/event/{id}', [EventController::class, 'newShow']);

Route::post('/events/validate-step', [EventController::class, 'validateStep'])
    ->name('events.validate-step');

Route::middleware(['auth'])->group(function () {
    Route::get('/works/my-presentation', [\App\Http\Controllers\PostEvaluationController::class, 'myPresentationSchedule'])
        ->name('works.my-presentation');
    Route::post('/works/{work}/post-eval/attendance', [\App\Http\Controllers\PostEvaluationController::class, 'setAttendance'])
        ->middleware('isCoordinator')
        ->name('works.presentation.attendance');

    Route::get('/events/{event}/apresentacoes', [EventPresentationScheduleController::class, 'manage'])
        ->middleware('isCoordinator')
        ->name('events.presentations.manage');
    Route::get('/events/{event}/anais/coordenacao', [EventController::class, 'annalsManage'])
        ->middleware('isCoordinator')
        ->name('events.annals.manage');

    Route::get('/works/my', [WorkController::class, 'myWorks'])->name('works.my');
    Route::get('/works/{work}', [WorkController::class, 'show'])->name('works.show');
    Route::get('/works/{work}/download', [WorkController::class, 'download'])->name('works.download');
    Route::get('/works/{work}/reviews/{review}/refined-correction/download', [ReviewController::class, 'downloadReviewerRefinedCorrection'])
        ->name('reviews.refined-correction.download');
    Route::get('/works/{work}/coordinator-feedback/download', [WorkController::class, 'downloadCoordinatorFeedback'])
        ->name('works.coordinator-feedback.download');
    Route::get('/works/{work}/edit', [WorkController::class, 'edit'])->name('works.edit');
    Route::put('/works/{work}', [WorkController::class, 'update'])->name('works.update');
    Route::post('/works/{work}/submit-correction', [WorkController::class, 'submitCorrection'])->name('works.submit-correction');
    Route::delete('/works/{work}', [WorkController::class, 'destroy'])
        ->middleware('isCoordinator')
        ->name('works.destroy');

    Route::get('/events/{event}/works', [WorkController::class, 'indexByEvent'])
        ->middleware('isCoordinator')
        ->name('events.works.index');
    Route::post('/events/{event}/works/reviewers/distribute', [ReviewController::class, 'distributeReviewersForEvent'])
        ->middleware('isCoordinator')
        ->name('events.works.reviewers.distribute');

    Route::get('/events/{event}/works/create', [WorkController::class, 'create'])
        ->name('events.works.create');
    Route::post('/events/{event}/works', [WorkController::class, 'store'])
        ->name('events.works.store');

    Route::post('/works/{work}/reviewers', [ReviewController::class, 'assignReviewer'])
        ->middleware('isCoordinator')
        ->name('works.reviewers.assign');
    Route::delete('/works/{work}/reviewers/{reviewer}', [ReviewController::class, 'removeReviewer'])
        ->middleware('isCoordinator')
        ->name('works.reviewers.remove');
    Route::post('/works/{work}/decision', [ReviewController::class, 'decide'])
        ->middleware('isCoordinator')
        ->name('works.decision');
    Route::get('/works/{work}/reviews/{review}/feedback/download', [ReviewController::class, 'downloadEvaluatorFeedback'])
        ->middleware('isCoordinator')
        ->name('reviews.feedback.download');
    Route::post('/works/{work}/annals', [ReviewController::class, 'markAnnalsPublication'])
        ->middleware('isCoordinator')
        ->name('works.annals.publish');
    Route::delete('/works/{work}/annals', [ReviewController::class, 'clearAnnalsPublication'])
        ->middleware('isCoordinator')
        ->name('works.annals.clear');
    Route::post('/works/{work}/presentation', [PresentationController::class, 'upsert'])
        ->middleware('isCoordinator')
        ->name('works.presentation.upsert');
    Route::delete('/works/{work}/presentation', [PresentationController::class, 'destroy'])
        ->middleware('isCoordinator')
        ->name('works.presentation.destroy');
    Route::get('/events/{event}/reviews/export-csv', [ReviewController::class, 'exportEventReportCsv'])
        ->middleware('isCoordinator')
        ->name('events.reviews.export');
});

Route::middleware(['auth', 'isReviewer'])->group(function () {
    Route::get('/reviews/assigned', [ReviewController::class, 'assigned'])->name('reviews.assigned');
    Route::get('/works/{work}/review', [ReviewController::class, 'form'])->name('reviews.form');
    Route::post('/works/{work}/review', [ReviewController::class, 'submit'])->name('reviews.submit');
    Route::get('/works/{work}/review/my-feedback/download', [ReviewController::class, 'downloadMyEvaluatorFeedback'])
        ->name('reviews.my-feedback.download');
});

Route::get('/validar-certificado', [CertificateVerificationController::class, 'lookupForm'])
    ->name('certificates.lookup');
Route::post('/validar-certificado', [CertificateVerificationController::class, 'lookupSubmit'])
    ->name('certificates.lookup.submit');

Route::get('/certificado/{codigo}', [CertificateVerificationController::class, 'show'])
    ->name('certificates.verify');

Route::middleware('auth')->group(function () {
    Route::get('/meus-certificados', [MyCertificatesController::class, 'index'])->name('certificates.my');
    Route::get('/certificates/{certificate}/download', [CertificateDownloadController::class, 'download'])
        ->name('certificates.download');
});

Route::middleware(['auth', 'isCoordinator'])->group(function () {
    Route::get('/assinaturas', [SignatureController::class, 'index'])->name('signatures.index');
    Route::post('/assinaturas', [SignatureController::class, 'store'])->name('signatures.store');
    Route::delete('/assinaturas/{signature}', [SignatureController::class, 'destroy'])->name('signatures.destroy');
    Route::get('/assinaturas/{signature}/imagem', [SignatureController::class, 'showImage'])->name('signatures.image');

    Route::get('/events/{event}/certificates', [EventCertificateController::class, 'index'])->name('events.certificates.index');
    Route::post('/events/{event}/certificates/meta', [EventCertificateController::class, 'updateCertificateMeta'])->name('events.certificates.meta');
    Route::post('/events/{event}/certificates/presence', [EventCertificateController::class, 'updateEventPresence'])->name('events.certificates.presence');
    Route::post('/events/{event}/certificates/presentation-rows', [EventCertificateController::class, 'updatePresentationCertificateRows'])->name('events.certificates.presentation-rows');
    Route::post('/events/{event}/certificates/signatures', [EventCertificateController::class, 'syncSignatures'])->name('events.certificates.signatures');
    Route::post('/events/{event}/certificates/generate/participation', [EventCertificateController::class, 'generateParticipation'])->name('events.certificates.generate.participation');
    Route::post('/events/{event}/certificates/generate/presentations', [EventCertificateController::class, 'generatePresentations'])->name('events.certificates.generate.presentations');
    Route::post('/events/{event}/certificates/generate/activities-all', [EventCertificateController::class, 'generateActivitiesAll'])->name('events.certificates.generate.activities-all');
    Route::get('/events/{event}/certificates/issued', [EventCertificateController::class, 'issuedList'])->name('events.certificates.issued');

    Route::get('/events/{event}/certificates/activities/{activity}/presence', [EventCertificateController::class, 'activityAttendance'])->name('events.certificates.activity.presence');
    Route::post('/events/{event}/certificates/activities/{activity}/presence', [EventCertificateController::class, 'updateActivityPresence'])->name('events.certificates.activity.presence.update');
    Route::post('/events/{event}/certificates/activities/{activity}/workload', [EventCertificateController::class, 'updateActivityWorkload'])->name('events.certificates.activity.workload');
    Route::post('/events/{event}/certificates/activities/{activity}/generate', [EventCertificateController::class, 'generateActivity'])->name('events.certificates.activity.generate');
});
