@php
    $hoursClause = ($hours !== null && $hours !== '' && $hours !== '-')
        ? ', com carga horária de <strong>'.e((string) $hours).' hora(s)</strong>'
        : '';
    $bodyHtml = 'Certificamos que <strong>'.e($recipientName).'</strong> é autor do trabalho intitulado <strong>'.nl2br(e((string) $workTitle)).'</strong>'
        .$hoursClause
        .', publicado nos '.$publicationKind.' do evento <strong>'.e($eventTitle).'</strong>, por <strong>'.e($institution).'</strong>.';
@endphp
@include('certificates.pdf.layout', [
    'title' => 'Certificado de publicação',
    'bodyHtml' => $bodyHtml,
    'validationCode' => $validationCode,
    'issuedDate' => $issuedDate,
    'institution' => $institution,
    'signatures' => $signatures,
])
