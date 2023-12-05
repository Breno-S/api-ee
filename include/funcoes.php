<?php

date_default_timezone_set('America/Sao_Paulo');

function login(mysqli $conn, string $username, string $password) {
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

function get_all_vagas_livres(mysqli $conn) {
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

function get_all_vagas_ocupadas(mysqli $conn) {
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

function registrar_entrada(mysqli $conn, int $idVaga, string $placa) {
    $idCarro = get_idCarro($conn, $placa);

    // String de consulta
    $sql = "INSERT INTO Locacao VALUES (DEFAULT, DEFAULT, DEFAULT, ?, ?)";

    // Preparação da consulta
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, 'ss', $idCarro, $idVaga);

    // Execução da consulta
    if (mysqli_stmt_execute($stmt)) {
        return true;
    }
    
    return false;    
}

// /*****************************************************************************/

function registrar_saida(mysqli $conn, int $idLocacao) {
    $sql = "UPDATE Locacao SET saida = CURRENT_TIMESTAMP WHERE idLocacao = ?";
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
        $sql = "INSERT INTO Carro VALUES (DEFAULT, '$placa')";

        // Preparação da consulta
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, 's', $placa);

        // Execução da consulta
        mysqli_stmt_execute($stmt);

        $idCarro = mysqli_insert_id($conn);
    }

    return $idCarro;
}