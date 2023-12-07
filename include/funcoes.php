<?php

function login(mysqli $conn, string $username, string $password): bool {
    // String de consulta
    $sql = "SELECT * FROM admin WHERE email = ? AND senha = ?";
    
    // Preparação da consulta
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, 'ss', $username, $password);

    // Execução da consulta
    mysqli_stmt_execute($stmt);

    // Guardar TODO o result set da consulta
    mysqli_stmt_store_result($stmt);
    
    if (mysqli_stmt_num_rows($stmt) == 1) {
        return true;
    }

    return false;
}

/*****************************************************************************/

function get_all_vagas_livres(mysqli $conn): array | false {
    // Armazena o resultado da consulta
    $result_set = [];

    // String de consulta
    $sql = "SELECT * FROM Vaga WHERE ocupado = 0";

    // Execução da consulta
    if ($result = mysqli_query($conn, $sql)) {
            
        // Agrupar os resultados
        while ($row = mysqli_fetch_assoc($result)) {
            $result_set[] = $row;
        }

        return $result_set;
    }

    return false;
}

/*****************************************************************************/

function get_all_vagas_ocupadas(mysqli $conn): array | false {
    // Armazena o resultado da consulta
    $result_set = [];

    // String de consulta
    $sql = "SELECT * FROM Vaga
            INNER JOIN Locacao ON fk_vaga = idVaga
            INNER JOIN Carro ON fk_carro = idCarro
            WHERE ocupado = 1 AND saida IS NULL";

    // Execução da consulta
    if ($result = mysqli_query($conn, $sql)) {
            
        // Agrupar os resultados
        while ($row = mysqli_fetch_assoc($result)) {
            $result_set[] = $row;
        }


        return $result_set;
    }

    return false;
}

// /*****************************************************************************/

function registrar_entrada(mysqli $conn, int $idVaga, string $placa): bool {
    $idCarro = get_idCarro($conn, $placa);

    // String de consulta
    $sql = "INSERT INTO Locacao VALUES (DEFAULT, ?, ?, DEFAULT, DEFAULT, DEFAULT)";

    // Preparação da consulta
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, 'ii', $idVaga, $idCarro);

    // Execução da consulta
    if (mysqli_stmt_execute($stmt)) {
        $idLocacao = mysqli_insert_id($conn);

        $sql = "UPDATE Vaga 
                INNER JOIN Locacao ON fk_vaga = idVaga
                SET ocupado = 1 WHERE idLocacao = ?";

        // Preparação da consulta
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, 'i', $idLocacao);
        
        if (mysqli_stmt_execute($stmt)) {
            return true;
        }
    }
    
    return false;    
}

// /*****************************************************************************/

function registrar_saida(mysqli $conn, int $idLocacao): bool {
    $sql = "UPDATE Locacao SET saida = CURRENT_TIMESTAMP WHERE idLocacao = ?";

    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, 'i', $idLocacao);

    if (!mysqli_stmt_execute($stmt)) {
        return false;
    }

    $sql = "UPDATE Vaga 
            INNER JOIN Locacao ON fk_vaga = idVaga
            SET ocupado = 0 WHERE idLocacao = ?";

    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, 'i', $idLocacao);

    if (!mysqli_stmt_execute($stmt)) {
        return false;
    }

    $valor = get_valor_locacao($conn, $idLocacao);

    $sql = "UPDATE Locacao SET preco = ? WHERE idLocacao = ?";

    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, 'di', $valor, $idLocacao);

    if (!mysqli_stmt_execute($stmt)) {
        return false;
    }

    return true;
}

// /*****************************************************************************/

function get_idCarro(mysqli $conn, string $placa): int | false {
    $idCarro = null;

    // String de consulta
    $sql = "SELECT idCarro FROM Carro WHERE placa = ?";

    // Preparação da consulta
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, 's', $placa);

    // Execução da consulta
    mysqli_stmt_execute($stmt);

    // Obter o resultado da consulta
    $result = mysqli_stmt_get_result($stmt);

    if (mysqli_num_rows($result) == 1) {

        $row_carro = mysqli_fetch_assoc($result);
        $idCarro = $row_carro['idCarro'];

    } else {
        // String de consulta
        $sql = "INSERT INTO Carro VALUES (DEFAULT, ?)";

        // Preparação da consulta
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, 's', $placa);

        // Execução da consulta
        mysqli_stmt_execute($stmt);

        $idCarro = mysqli_insert_id($conn);
    }

    return $idCarro;
}

// /*****************************************************************************/

function get_valor_locacao(mysqli $conn, int $idLocacao): float {
    $sql = "SELECT entrada, saida FROM Locacao WHERE idLocacao = ?";

    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, 'i', $idLocacao);
    
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    $row = mysqli_fetch_assoc($result);

    $from_time = strtotime($row['entrada']);
    $to_time = strtotime($row['saida']);
    $tempo_no_estacionamento = round(abs($from_time - $to_time) / 60, 2); // tempo em minutos

    $multa_hora_adicional = 9.00;
    
    if ($tempo_no_estacionamento <= 15)
        $total_pagar = 0;
    elseif ($tempo_no_estacionamento > 15 && $tempo_no_estacionamento <= 60)
        $total_pagar = 27.00;
    elseif ($tempo_no_estacionamento > 60 && $tempo_no_estacionamento <= 120)
        $total_pagar = 32.00;
    else
        $total_pagar = 32.00 + ($multa_hora_adicional * ceil(($tempo_no_estacionamento - 120) / 60));

    return $total_pagar;
}

function valida_placa(string $placa): bool {
    $pattern = '/^[A-Z]{3}[0-9]{1}[A-Z0-9]{1}[0-9]{2}$/';

    if (preg_match_all($pattern, $placa)){
        return true;
    }
    
    return false;
}

function procura_copia(mysqli $conn, string $placa): bool {
    $sql = "SELECT COUNT(*) AS isParked FROM Locacao INNER JOIN Carro ON fk_carro = idCarro INNER JOIN Vaga ON fk_vaga = idVaga WHERE placa = ? AND ocupado = 1";
    
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, 's', $placa);
    
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    $row = mysqli_fetch_assoc($result);
    
    if($row['isParked']) {
        return true;
    }

    return false;
}