@php
    $bodyHtml = 'Certificamos que <strong>'.e($recipientName).'</strong> participou da '.e((string) ($activityTypeLabel ?? 'atividade')).' <strong>\''.e((string) ($activityTitle ?? '')).'\'</strong>, com carga horária de <strong>'.e((string) $hours).' hora(s)</strong>, no evento <strong>'.e($eventTitle).'</strong>, promovido por <strong>'.e($institution).'</strong>.';
@endphp
@include('certificates.pdf.layout', [
    'title' => 'Certificado de participação em atividade',
    'bodyHtml' => $bodyHtml,
    'validationCode' => $validationCode,
    'issuedDate' => $issuedDate,
    'institution' => $institution,
    'signatures' => $signatures,
])
