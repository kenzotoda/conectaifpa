<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 13px; color: #111827; margin: 0; padding: 36px 48px; }
        .border { border: 2px solid #059669; padding: 28px 36px; min-height: 420px; position: relative; }
        h1 { font-size: 18px; text-align: center; margin: 0 0 20px; color: #065f46; text-transform: uppercase; letter-spacing: 1px; }
        .body { text-align: justify; line-height: 1.65; margin-bottom: 28px; font-size: 14px; }
        .facts { width: 100%; margin-top: 28px; border-top: 1px solid #e5e7eb; padding-top: 12px; }
        .facts td { vertical-align: top; }
        .facts-text { font-size: 11px; color: #4b5563; line-height: 1.55; }
        .facts-qr { width: 110px; text-align: center; }
        .facts-qr img { width: 96px; height: 96px; }
        .qr-caption { font-size: 9px; color: #4b5563; margin-top: 4px; }
        .sig-row { margin-top: 32px; text-align: center; }
        .sig { display: inline-block; width: 42%; vertical-align: top; padding: 0 8px; }
        .sig img { max-height: 64px; margin-bottom: 6px; }
        .sig .nome { font-weight: bold; font-size: 12px; }
        .sig .cargo { font-size: 11px; color: #374151; }
    </style>
</head>
<body>
<div class="border">
    <h1>{{ $title }}</h1>
    <div class="body">
        {!! $bodyHtml !!}
    </div>
    <table class="facts">
        <tr>
            <td class="facts-text">
                <div><strong>Período:</strong> {{ $period }}</div>
                @if(!empty($activityPeriod))
                    <div><strong>Período da atividade:</strong> {{ $activityPeriod }}</div>
                @endif
                <div><strong>Local:</strong> {{ $place }}</div>
                <div><strong>Modalidade:</strong> {{ $modality }}</div>
                @if(!empty($matricula))
                    <div><strong>Matrícula:</strong> {{ $matricula }}</div>
                @endif
                <div><strong>Presença:</strong> {{ $presence }}</div>
                <div><strong>Código de validação:</strong> {{ $validationCode }}</div>
                <div><strong>Emitido em:</strong> {{ $issuedDate }}</div>
                <div><strong>{{ $institution }}</strong></div>
            </td>
            <td class="facts-qr">
                @if(!empty($qrDataUri))
                    <img src="{{ $qrDataUri }}" alt="QR de validação">
                    <div class="qr-caption">Validar</div>
                @endif
            </td>
        </tr>
    </table>
    @if(count($signatures) > 0)
        <div class="sig-row">
            @foreach($signatures as $sig)
                <div class="sig">
                    @if(!empty($sig['src']))
                        <div><img src="{{ $sig['src'] }}" alt=""></div>
                    @endif
                    <div class="nome">{{ $sig['nome'] }}</div>
                    <div class="cargo">{{ $sig['cargo'] }}</div>
                </div>
            @endforeach
        </div>
    @endif
</div>
</body>
</html>
