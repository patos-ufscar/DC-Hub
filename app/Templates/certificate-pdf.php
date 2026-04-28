<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Certificado de Participação</title>
    <style>
        @page { margin: 0; }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Helvetica', 'Arial', sans-serif;
            background: #E9D8A6;
            color: #001219;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
        }
        .certificate {
            width: 90%;
            max-width: 900px;
            margin: 30px auto;
            padding: 50px 60px;
            background: #fff;
            border: 4px solid #005F73;
            border-radius: 12px;
            position: relative;
        }
        .certificate::before {
            content: '';
            position: absolute;
            top: 8px; left: 8px; right: 8px; bottom: 8px;
            border: 2px solid #0A9396;
            border-radius: 8px;
            pointer-events: none;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
        }
        .header h1 {
            font-size: 28px;
            color: #005F73;
            letter-spacing: 4px;
            text-transform: uppercase;
            margin-bottom: 5px;
        }
        .header h2 {
            font-size: 16px;
            color: #0A9396;
            font-weight: normal;
        }
        .body-text {
            text-align: center;
            font-size: 14px;
            line-height: 1.8;
            margin-bottom: 25px;
        }
        .body-text .name {
            font-size: 24px;
            font-weight: bold;
            color: #005F73;
            display: block;
            margin: 10px 0;
        }
        .body-text .event-name {
            font-size: 18px;
            font-weight: bold;
            color: #0A9396;
        }
        .activities-table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
            font-size: 12px;
        }
        .activities-table th {
            background: #005F73;
            color: #fff;
            padding: 8px 12px;
            text-align: left;
        }
        .activities-table td {
            padding: 6px 12px;
            border-bottom: 1px solid #94D2BD;
        }
        .activities-table tr:nth-child(even) td {
            background: #f8f8f8;
        }
        .total-hours {
            text-align: right;
            font-size: 16px;
            font-weight: bold;
            color: #005F73;
            margin: 15px 0;
        }
        .footer {
            text-align: center;
            margin-top: 30px;
            font-size: 12px;
            color: #666;
        }
        .footer .date {
            font-size: 14px;
            color: #001219;
            margin-bottom: 10px;
        }
        @media print {
            body { background: #fff; }
            .certificate { border: none; box-shadow: none; }
        }
    </style>
</head>
<body>
    <div class="certificate">
        <div class="header">
            <h1>Certificado de Participação</h1>
            <h2><?= htmlspecialchars($evento['grupo_nome'] ?? '', ENT_QUOTES, 'UTF-8') ?></h2>
        </div>

        <div class="body-text">
            <p>Certificamos que</p>
            <span class="name"><?= htmlspecialchars($nomeCompleto, ENT_QUOTES, 'UTF-8') ?></span>
            <p>participou do evento</p>
            <span class="event-name"><?= htmlspecialchars($evento['titulo'], ENT_QUOTES, 'UTF-8') ?></span>
            <p>conforme detalhado abaixo:</p>
        </div>

        <table class="activities-table">
            <thead>
                <tr>
                    <th>Atividade</th>
                    <th>Data</th>
                    <th>Horário</th>
                    <th>Carga Horária</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($activities as $act): ?>
                <tr>
                    <td><?= htmlspecialchars($act['titulo'], ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= date('d/m/Y', strtotime($act['data'])) ?></td>
                    <td><?= substr($act['hora_inicio'], 0, 5) ?> - <?= substr($act['hora_fim'], 0, 5) ?></td>
                    <td><?= round((int)$act['carga_minutos'] / 60, 1) ?>h</td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <div class="total-hours">
            Carga Horária Total: <?= $totalHoras ?>h
        </div>

        <div class="footer">
            <p class="date">Emitido em <?= $dataEmissao ?></p>
            <p>DC Hub — Departamento de Computação</p>
        </div>
    </div>
</body>
</html>
