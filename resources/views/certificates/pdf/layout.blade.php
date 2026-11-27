<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <style>
        @page { margin: 0; size: 297mm 210mm; }
        html, body { margin: 0; padding: 0; }
        body { font-family: DejaVu Sans, sans-serif; color: #10233f; }
        .bg {
            position: fixed;
            top: 0;
            left: 0;
            width: 297mm;
            height: 210mm;
        }
        .content {
            position: absolute;
            top: 78mm;
            left: 42mm;
            width: 190mm;
        }
        .kind {
            margin: 0 0 3mm;
            text-align: center;
            font-size: 10.5pt;
            font-weight: bold;
            letter-spacing: 1.2pt;
            color: #17853a;
        }
        .body {
            margin: 0 0 3mm;
            text-align: center;
            font-size: 11pt;
            line-height: 1.4;
        }
        .body strong { color: #0c2340; }
        .meta { width: 100%; border-collapse: collapse; }
        .facts { font-size: 8pt; line-height: 1.35; color: #334155; vertical-align: top; }
        .facts-lead { width: 74%; }
        .facts-half { width: 37%; }
        .facts strong { color: #10233f; }
        .qr { width: 26%; text-align: center; vertical-align: middle; }
        .qr img { width: 18mm; height: 18mm; }
        .qr-caption { font-size: 7pt; color: #334155; margin-top: 0.5mm; }
        .sign-block {
            position: absolute;
            top: 166mm;
            left: 42mm;
            width: 190mm;
        }
        .sigs { width: 100%; border-collapse: collapse; }
        .sigs td { text-align: center; vertical-align: bottom; padding: 0 2mm; }
        .sigs img { max-height: 9mm; max-width: 36mm; margin-bottom: 0.5mm; }
        .nome { font-size: 8pt; font-weight: bold; color: #10233f; line-height: 1.2; }
        .cargo { font-size: 7pt; color: #3d4f66; line-height: 1.2; }
    </style>
</head>
<body>
@php
    $kind = preg_replace('/^\s*certificado\s+de\s+/iu', '', (string) ($title ?? ''));
    $kind = trim((string) $kind);
    if ($kind === '') {
        $kind = trim((string) ($title ?? 'Certificado'));
    }
    $kind = mb_strtoupper($kind, 'UTF-8');
@endphp
<img class="bg" src="{{ \App\Services\CertificateDocument::templateDataUri() }}" alt="">
<div class="content">
    <div class="kind">{{ $kind }}</div>
    <div class="body">
        {!! $bodyHtml !!}
    </div>
    <table class="meta">
        <tr>
            <td class="facts facts-lead" colspan="2">
                <div><strong>Período:</strong> {{ $period }}</div>
                @if(!empty($activityPeriod))
                    <div><strong>Período da atividade:</strong> {{ $activityPeriod }}</div>
                @endif
                <div><strong>Local:</strong> {{ $place }}</div>
            </td>
            <td class="qr" rowspan="2">
                @if(!empty($qrDataUri))
                    <img src="{{ $qrDataUri }}" alt="QR de validação">
                    <div class="qr-caption">Validar</div>
                @endif
            </td>
        </tr>
        <tr>
            <td class="facts facts-half">
                <div><strong>Modalidade:</strong> {{ $modality }}</div>
                <div><strong>Presença:</strong> {{ $presence }}</div>
                <div><strong>Código de validação:</strong> {{ $validationCode }}</div>
            </td>
            <td class="facts facts-half">
                @if(!empty($matricula))
                    <div><strong>Matrícula:</strong> {{ $matricula }}</div>
                @endif
                <div><strong>Emitido em:</strong> {{ $issuedDate }}</div>
                <div><strong>{{ $institution }}</strong></div>
            </td>
        </tr>
    </table>
</div>
@if(count($signatures) > 0)
    <div class="sign-block">
        <table class="sigs">
            <tr>
                @foreach($signatures as $sig)
                    <td style="width: {{ round(100 / count($signatures), 2) }}%;">
                        @if(!empty($sig['src']))
                            <div><img src="{{ $sig['src'] }}" alt=""></div>
                        @endif
                        <div class="nome">{{ $sig['nome'] }}</div>
                        <div class="cargo">{{ $sig['cargo'] }}</div>
                    </td>
                @endforeach
            </tr>
        </table>
    </div>
@endif
</body>
</html>
