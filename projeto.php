<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastro para concorrer ao sorteio</title>
    <link rel="stylesheet" type="text/css" href="style.css"/> 
    <script type="text/javascript" src="script.js"></script>
</head>
<body>

<?php
// Configurações do banco de dados
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "projeto";

// Criando conexão
$conn = new mysqli($servername, $username, $password, $dbname);

// Verificando conexão
if ($conn->connect_error) {
    die("Conexão falhou: " . $conn->connect_error);
}

// Função para limpar entradas do formulário
function limparEntrada($data) {
    return htmlspecialchars(stripslashes(trim($data)));
}

// Inicializar variáveis
$id = $Nome = $Email = $Telefone = "";

// Adicionar concorrente
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["adicionar"])) {
    $Nome = limparEntrada($_POST["nome"]);
    $Email = limparEntrada($_POST["email"]);
    $Telefone = limparEntrada($_POST["telefone"]);

    // Selecionar um número sem dono
    $sqlNumero = "SELECT numero FROM numeros WHERE id_pertencente IS NULL";
    $resultNumero = $conn->query($sqlNumero);
    
    if ($resultNumero->num_rows > 0) {
        // Escolher um número aleatoriamente
        $numerosSemDonos = $resultNumero->fetch_all(MYSQLI_ASSOC);
        $numeroEscolhido = $numerosSemDonos[array_rand($numerosSemDonos)]['numero'];

        // Inserir novo concorrente
        $sql = "INSERT INTO concorrentes (nome, celular, email) VALUES ('$Nome', '$Telefone', '$Email')";
        
        if ($conn->query($sql) === TRUE) {
            $idConcorrente = $conn->insert_id;

            // Atualizar o número escolhido
            $sqlAtualizarNumero = "UPDATE numeros SET id_pertencente = $idConcorrente WHERE numero = $numeroEscolhido";
            $conn->query($sqlAtualizarNumero);

            echo "<p>Participante cadastrado com sucesso! Número $numeroEscolhido vinculado.</p>";
        } else {
            echo "Erro: " . $sql . "<br>" . $conn->error;
        }
    } else {
        echo "<p>Não há números disponíveis para vincular.</p>";
    }
}

// Atualizar concorrente
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["atualizar"])) {
    $id = limparEntrada($_POST["id"]);
    $Nome = limparEntrada($_POST["nome"]);
    $Email = limparEntrada($_POST["email"]);
    $Telefone = limparEntrada($_POST["telefone"]);

    $sql = "UPDATE concorrentes SET nome='$Nome', celular='$Telefone', email='$Email' WHERE id=$id";

    if ($conn->query($sql) === TRUE) {
        echo "<p>Participante atualizado com sucesso!</p>";

        // Limpar campos após atualizar
        $id = $Nome = $Email = $Telefone = "";
    } else {
        echo "Erro: " . $sql . "<br>" . $conn->error;
    }
}

// Gerar novo número
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["gerar_novo_numero"])) {
    $id = limparEntrada($_POST["id"]);

    // Primeiro, verificar qual número está atualmente atribuído ao participante
    $sqlNumeroAtual = "SELECT n.numero FROM numeros n JOIN concorrentes c ON n.id_pertencente = c.id WHERE c.id = $id";
    $resultNumeroAtual = $conn->query($sqlNumeroAtual);
    
    if ($resultNumeroAtual->num_rows > 0) {
        $numeroAtual = $resultNumeroAtual->fetch_assoc()['numero'];

        // Liberar o número atual
        $sqlLiberarNumero = "UPDATE numeros SET id_pertencente = NULL WHERE numero = $numeroAtual";
        $conn->query($sqlLiberarNumero);

        // Selecionar um novo número sem dono
        $sqlNumero = "SELECT numero FROM numeros WHERE id_pertencente IS NULL";
        $resultNumero = $conn->query($sqlNumero);
        
        if ($resultNumero->num_rows > 0) {
            // Escolher um novo número aleatoriamente
            $numerosSemDonos = $resultNumero->fetch_all(MYSQLI_ASSOC);
            $novoNumeroEscolhido = $numerosSemDonos[array_rand($numerosSemDonos)]['numero'];

            // Atribuir o novo número ao participante
            $sqlAtualizarNumero = "UPDATE numeros SET id_pertencente = $id WHERE numero = $novoNumeroEscolhido";
            $conn->query($sqlAtualizarNumero);

            echo "<p>Número atualizado com sucesso! Novo número vinculado: $novoNumeroEscolhido.</p>";
        } else {
            echo "<p>Não há números disponíveis para vincular.</p>";
        }
    }
}

// Verificar se está editando um participante
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["editar"])) {
    $id = $_POST["id"];

    // Buscar o participante pelo ID
    $sql = "SELECT * FROM concorrentes WHERE id=$id";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        // Preencher os campos com os dados do participante
        $row = $result->fetch_assoc();
        $Nome = $row["nome"];
        $Email = $row["email"];
        $Telefone = $row["celular"];
    }
}

// Excluir participante
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["excluir"])) {
    $id = limparEntrada($_POST["id"]);

    // Primeiro, liberar o número do participante
    $sqlNumero = "SELECT n.numero FROM numeros n JOIN concorrentes c ON n.id_pertencente = c.id WHERE c.id = $id";
    $resultNumero = $conn->query($sqlNumero);
    
    if ($resultNumero->num_rows > 0) {
        $numeroVinculado = $resultNumero->fetch_assoc()['numero'];
        // Liberar o número
        $sqlLiberarNumero = "UPDATE numeros SET id_pertencente = NULL WHERE numero = $numeroVinculado";
        $conn->query($sqlLiberarNumero);
    }

    // Excluir o participante
    $sql = "DELETE FROM concorrentes WHERE id=$id";

    if ($conn->query($sql) === TRUE) {
        echo "<p>Participante excluído com sucesso!</p>";
    } else {
        echo "Erro: " . $sql . "<br>" . $conn->error;
    }
}
?>

<h1>Cadastro para concorrer ao sorteio</h1>

<!-- Formulário de cadastro/atualização -->
<form method="POST" action="">
    <input type="hidden" name="id" value="<?php echo $id; ?>">
    Nome: <input type="text" name="nome" value="<?php echo $Nome; ?>" required><br>
    E-mail: <input type="email" name="email" value="<?php echo $Email; ?>" required><br>
    Telefone: <input type="text" name="telefone" value="<?php echo $Telefone; ?>" required><br>
    <?php if ($id): ?>
        <button type="submit" name="atualizar">Atualizar Participante</button>
        <button type="submit" name="gerar_novo_numero">Gerar Novo Número</button> <!-- Botão para gerar novo número -->
    <?php else: ?>
        <button type="submit" name="adicionar">Adicionar Participante</button>
    <?php endif; ?>
</form>

<h2>Lista de Participantes</h2>
<table>
    <thead>
        <tr>
            <th>ID</th>
            <th>Nome</th>
            <th>E-mail</th>
            <th>Telefone</th>
            <th>Número Vinculado</th>
            <th>Ações</th>
        </tr>
    </thead>    
    <tbody>
        <?php
        // Listar concorrentes e seus números vinculados
        $sql = "SELECT c.id, c.nome, c.email, c.celular, n.numero 
                FROM concorrentes c
                LEFT JOIN numeros n ON c.id = n.id_pertencente";
        $result = $conn->query($sql);

        if ($result->num_rows > 0) {
            // Exibir os dados de cada linha
            while($row = $result->fetch_assoc()) {
                echo "<tr>
                <td>" . $row["id"] . "</td>
                <td>" . $row["nome"] . "</td>
                <td>" . $row["email"] . "</td>
                <td>" . $row["celular"] . "</td>
                <td>" . ($row["numero"] !== null ? $row["numero"] : "Nenhum") . "</td>
                <td>
                <form method='POST' action='' style='display:inline;'>
                <input type='hidden' name='id' value='" . $row["id"] . "'>
                <button type='submit' name='editar'>Editar</button> 
                </form>
                <form method='POST' action='' style='display:inline;'> 
                <input type='hidden' name='id' value='" . $row["id"] . "'>
                <button type='submit' name='excluir'>Excluir</button>
                </form>
                </td>
                </tr>";
            }
        } else {
            echo "<tr><td colspan='6'>Nenhum participante cadastrado.</td></tr>";
        }
        ?>
    </tbody>
</table>

<?php
// Fechar conexão
$conn->close();
?>
</body>
</html>
