<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Alteração Orçamentária</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; font-size: 10px; color: #1a1a1a; background: #fff; }

        .header { text-align: center; border-bottom: 2px solid #1e3a5f; padding-bottom: 10px; margin-bottom: 16px; }
        .header .municipio { font-size: 13px; font-weight: bold; color: #1e3a5f; text-transform: uppercase; }
        .header .titulo { font-size: 16px; font-weight: bold; color: #1e3a5f; margin-top: 4px; }
        .header .subtitulo { font-size: 10px; color: #555; margin-top: 2px; }

        .section { margin-bottom: 12px; }
        .section-title { font-size: 10px; font-weight: bold; text-transform: uppercase; color: #1e3a5f;
                         background: #e8f0fe; padding: 4px 8px; border-left: 3px solid #1e3a5f; margin-bottom: 8px; }

        .grid-2 { display: table; width: 100%; border-collapse: collapse; }
        .grid-2 .col { display: table-cell; width: 50%; padding: 3px 8px; vertical-align: top; }
        .grid-3 { display: table; width: 100%; border-collapse: collapse; }
        .grid-3 .col { display: table-cell; width: 33.33%; padding: 3px 8px; vertical-align: top; }
        .grid-4 { display: table; width: 100%; border-collapse: collapse; }
        .grid-4 .col { display: table-cell; width: 25%; padding: 3px 8px; vertical-align: top; }

        .field-label { font-size: 8px; color: #777; text-transform: uppercase; font-weight: bold; }
        .field-value { font-size: 10px; color: #1a1a1a; margin-top: 1px; }

        table.dotacoes { width: 100%; border-collapse: collapse; margin-top: 4px; }
        table.dotacoes th { background: #1e3a5f; color: #fff; padding: 5px 6px; font-size: 9px; text-align: left; }
        table.dotacoes td { padding: 4px 6px; font-size: 9px; border-bottom: 1px solid #e5e7eb; }
        table.dotacoes tr:nth-child(even) td { background: #f8faff; }
        table.dotacoes .text-right { text-align: right; }
        table.dotacoes .val-neg { color: #b91c1c; }
        table.dotacoes .val-pos { color: #15803d; }

        .totals-row td { font-weight: bold; background: #e8f0fe !important; border-top: 2px solid #1e3a5f; }

        .summary-box { display: table; width: 100%; border-collapse: collapse; margin-top: 12px; }
        .summary-box .box { display: table-cell; width: 33.33%; padding: 8px 12px;
                            border: 1px solid #d1d5db; text-align: center; }
        .summary-box .box-label { font-size: 8px; text-transform: uppercase; color: #777; font-weight: bold; }
        .summary-box .box-value { font-size: 13px; font-weight: bold; margin-top: 2px; }
        .box-red { color: #b91c1c; }
        .box-green { color: #15803d; }
        .box-blue { color: #1e3a5f; }

        .footer { margin-top: 20px; border-top: 1px solid #d1d5db; padding-top: 8px;
                  font-size: 8px; color: #9ca3af; text-align: right; }
    </style>
</head>
<body>

    <div class="header">
        <div class="municipio">Prefeitura Municipal de São José dos Pinhais</div>
        <div class="titulo">Alteração Orçamentária</div>
        <div class="subtitulo">{{ $tipoAtoLabel }} — {{ $tipoCreditoLabel }} — Recurso: {{ $tipoRecursoLabel }}</div>
    </div>

    {{-- Dados da Lei/Ato --}}
    <div class="section">
        <div class="section-title">Dados da Lei / Ato Autorizador</div>
        <div class="grid-4">
            <div class="col">
                <div class="field-label">Lei / Ato</div>
                <div class="field-value">{{ $alteracao->leiAto->numero }}</div>
            </div>
            <div class="col">
                <div class="field-label">Tipo</div>
                <div class="field-value">{{ ucfirst($alteracao->leiAto->tipo) }}</div>
            </div>
            <div class="col">
                <div class="field-label">Data do Ato</div>
                <div class="field-value">{{ $alteracao->leiAto->data_ato->format('d/m/Y') }}</div>
            </div>
            <div class="col">
                <div class="field-label">Data da Publicação</div>
                <div class="field-value">{{ $alteracao->leiAto->data_publicacao->format('d/m/Y') }}</div>
            </div>
        </div>
    </div>

    {{-- Dados da Alteração --}}
    <div class="section">
        <div class="section-title">Dados da Alteração Orçamentária</div>
        <div class="grid-4">
            <div class="col">
                <div class="field-label">Decreto Autorizador</div>
                <div class="field-value">{{ $alteracao->decreto_autorizador }}</div>
            </div>
            <div class="col">
                <div class="field-label">Tipo de Ato</div>
                <div class="field-value">{{ $tipoAtoLabel }}</div>
            </div>
            <div class="col">
                <div class="field-label">Data do Ato</div>
                <div class="field-value">{{ $alteracao->data_ato->format('d/m/Y') }}</div>
            </div>
            <div class="col">
                <div class="field-label">Data da Publicação</div>
                <div class="field-value">{{ $alteracao->data_publicacao->format('d/m/Y') }}</div>
            </div>
        </div>
        <div class="grid-3" style="margin-top: 8px;">
            <div class="col">
                <div class="field-label">Tipo de Crédito</div>
                <div class="field-value">{{ $tipoCreditoLabel }}</div>
            </div>
            <div class="col">
                <div class="field-label">Tipo de Recurso</div>
                <div class="field-value">{{ $tipoRecursoLabel }}</div>
            </div>
            <div class="col">
                <div class="field-label">Valor do Crédito</div>
                <div class="field-value" style="font-weight:bold;">R$ {{ number_format($alteracao->valor_credito, 2, ',', '.') }}</div>
            </div>
        </div>
    </div>

    {{-- Dotações --}}
    <div class="section">
        <div class="section-title">Dotações Orçamentárias</div>
        @if($alteracao->dotacoes->count() > 0)
        <table class="dotacoes">
            <thead>
                <tr>
                    <th style="width:30%">Dotação Orçamentária</th>
                    <th style="width:20%">Conta Receita</th>
                    <th class="text-right" style="width:12%">Saldo Atual</th>
                    <th class="text-right" style="width:12%">Valor Suprimido</th>
                    <th class="text-right" style="width:12%">Valor Suplementado</th>
                    <th class="text-right" style="width:12%">Novo Saldo</th>
                </tr>
            </thead>
            <tbody>
                @foreach($alteracao->dotacoes as $dot)
                <tr>
                    <td>{{ $dot->dotacao_orcamentaria }}</td>
                    <td>{{ $dot->conta_receita ?? '-' }}</td>
                    <td class="text-right">R$ {{ number_format($dot->saldo_atual, 2, ',', '.') }}</td>
                    <td class="text-right val-neg">R$ {{ number_format($dot->valor_suprimido, 2, ',', '.') }}</td>
                    <td class="text-right val-pos">R$ {{ number_format($dot->valor_suplementado, 2, ',', '.') }}</td>
                    <td class="text-right" style="font-weight:bold;">R$ {{ number_format($dot->novo_saldo, 2, ',', '.') }}</td>
                </tr>
                @endforeach
                <tr class="totals-row">
                    <td colspan="3">TOTAIS</td>
                    <td class="text-right val-neg">R$ {{ number_format($totalSuprimido, 2, ',', '.') }}</td>
                    <td class="text-right val-pos">R$ {{ number_format($totalSuplementado, 2, ',', '.') }}</td>
                    <td class="text-right">R$ {{ number_format($totalSuplementado - $totalSuprimido, 2, ',', '.') }}</td>
                </tr>
            </tbody>
        </table>
        @else
        <p style="color:#9ca3af; font-style:italic; padding: 8px;">Nenhuma dotação cadastrada para esta alteração.</p>
        @endif
    </div>

    {{-- Resumo --}}
    <div class="summary-box">
        <div class="box">
            <div class="box-label">Total Suprimido</div>
            <div class="box-value box-red">R$ {{ number_format($totalSuprimido, 2, ',', '.') }}</div>
        </div>
        <div class="box">
            <div class="box-label">Total Suplementado</div>
            <div class="box-value box-green">R$ {{ number_format($totalSuplementado, 2, ',', '.') }}</div>
        </div>
        <div class="box">
            <div class="box-label">Diferença</div>
            @php $diff = $totalSuplementado - $totalSuprimido; @endphp
            <div class="box-value {{ $diff >= 0 ? 'box-green' : 'box-red' }}">R$ {{ number_format($diff, 2, ',', '.') }}</div>
        </div>
    </div>

    <div class="footer">
        Gerado em {{ $dataGeracao }} — Prefeitura Municipal de São José dos Pinhais — Sistema Integrado PMSJP
    </div>

</body>
</html>
