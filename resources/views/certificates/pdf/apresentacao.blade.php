@php
    $workTitleEscaped = nl2br(e((string) $workTitle));
    $bodyHtml = 'Certificamos que <strong>'.e($recipientName).'</strong> apresentou o trabalho intitulado <strong>'.$workTitleEscaped.'</strong>, com carga horária de <strong>'.e((string) $hours).' hora(s)</strong>, no âmbito do evento <strong>'.e($eventTitle).'</strong>, por <strong>'.e($institution).'</strong>.';
@endphp
@include('certificates.pdf.layout', [
    'title' => 'Certificado de apresentação de trabalho',
    'bodyHtml' => $bodyHtml,
    'validationCode' => $validationCode,
    'issuedDate' => $issuedDate,
    'institution' => $institution,
    'signatures' => $signatures,
])
