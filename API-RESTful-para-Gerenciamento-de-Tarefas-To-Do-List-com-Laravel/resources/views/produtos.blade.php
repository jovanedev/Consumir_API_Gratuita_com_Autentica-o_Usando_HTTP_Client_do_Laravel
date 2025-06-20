<!DOCTYPE html>
<html lang="pt">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lista de Produtos</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 14px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }

        th,
        td {
            border: 1px solid black;
            padding: 8px;
            text-align: left;
        }

        th {
            background-color: #f2f2f2;
        }
    </style>
</head>

<body>
    <h2>Lista de Produtos</h2>
    <table>
        <thead>
            <tr>
                <th>Nome</th>
                <th>Referência</th>
                <th>Preço de Venda</th>
                <th>Categoria</th>
                <th>Marca</th>
                <th>Fornecedor</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($produtos as $produto)
                <tr>
                    <td>{{ $produto->nome }}</td>
                    <td>{{ $produto->referencia }}</td>
                    <td>Kz {{ number_format($produto->preco_venda, 2, ',', '.') }}</td>
                    <td>{{ $produto->categoria->nome ?? 'N/A' }}</td>
                    <td>{{ $produto->marca->nome ?? 'N/A' }}</td>
                    <td>{{ $produto->fornecedor->nome ?? 'N/A' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>

</html>
