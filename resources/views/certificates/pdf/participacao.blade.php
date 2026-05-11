@php
    $bodyHtml = 'Certificamos que <strong>'.e($recipientName).'</strong> participou do evento <strong>'.e($eventTitle).'</strong>, com carga horária total de <strong>'.e((string) $hours).' hora(s)</strong>, sob a organização de <strong>'.e($organizer).'</strong>, na instituição <strong>'.e($institution).'</strong>.';
@endphp
@include('certificates.pdf.layout', [
    'title' => 'Certificado de participação',
    'bodyHtml' => $bodyHtml,
    'validationCode' => $validationCode,
    'issuedDate' => $issuedDate,
    'institution' => $institution,
    'signatures' => $signatures,
])
