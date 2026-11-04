@include('certificates.pdf.layout', [
    'title' => $title,
    'bodyHtml' => $bodyHtml,
    'validationCode' => $validationCode,
    'issuedDate' => $issuedDate,
    'institution' => $institution,
    'signatures' => $signatures,
])
