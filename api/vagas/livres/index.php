<?php
include_once '../../../include/conexao.php';
include_once '../../../include/funcoes.php';
require '../../../vendor/autoload.php';

use \Firebase\JWT\JWT;
use \Firebase\JWT\Key;

$allowedOrigin = getenv("ALLOWED_ORIGIN");

// Headers
// Verifique se o valor está presente e defina o cabeçalho Access-Control-Allow-Origin
if ($allowedOrigin) {
    header("Access-Control-Allow-Origin: " . $allowedOrigin);
} else {
    header("Access-Control-Allow-Origin: http://localhost:3000");
}

header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Content-Type: application/json; charset=UTF-8");

// Parâmetros permitidos pelo endpoint
$allowed_params = ["id"];

// Response (deve ser um array associativo)
$response = [];

// Verifique o método da requisição
$method = $_SERVER['REQUEST_METHOD'];

// Se a requisição for uma solicitação OPTIONS, retorne os cabeçalhos permitidos
if ($method === 'OPTIONS') {
    header("HTTP/1.1 200 OK");
    exit;
}

if ($method == 'GET') {

    // Pega todos os headers do request
    $headers = getallheaders();

    // Transformar as chaves do $headers em lowercase
    foreach ($headers as $key => $value) {
        // Remover a chave original
        unset($headers[$key]);
    
        // Adicionar a chave em minúsculas com o valor original
        $headers[strtolower($key)] = $value;
    }

    // Verifica a presença do cabeçalho de autorização
    if (isset($headers['authorization'])) {
        $authorizationHeader = $headers['authorization'];
    } else {
        http_response_code(400);
        echo json_encode(['status' => '400 Bad Request', 'message' => 'Cabeçalho de autorização ausente']);
        exit;
    }
    
    // Verifica se o cabeçalho de autorização está no formato "Bearer <token>"
    if (preg_match('/^Bearer [A-Za-z0-9\-._~+\/]+=*$/', $authorizationHeader)) {
        list(, $token) = explode(' ', $authorizationHeader);
    } else {
        http_response_code(401);
        echo json_encode(['status' => '401 Unauthorized', 'message' => 'Token de autorização ausente']);
        exit;
    }

    // Chave secreta usada para assinar e verificar o token
    $key = 'parking420';

    try {
        // Decodifica o token usando a chave secreta
        $decoded = JWT::decode($token, new Key($key, 'HS256'));
    } catch (Exception $e) {
        http_response_code(401);
        echo json_encode(['status' => '401 Unauthorized', 'message' => 'Acesso não autorizado: ' . $e->getMessage()]);
        exit;
    } 

    // Obtem os parâmetros de query string
    if (!empty($_GET)) {
        $query_params = array_keys($_GET);

        // Verifica se há chaves inválidas
        if (array_diff($query_params, $allowed_params)) {
            http_response_code(400);
            $response['status'] = "400 Bad Request";
            $response['message'] = "Parâmetros desconhecidos na requisição";
            echo json_encode($response);
            exit;
        }
    }
        
    // // Request com query -> Obter sala específica
    // if (isset($_GET['id'])) {

    //     // Verifica se o valor da chave id é numérico
    //     if (filter_var($_GET['id'], FILTER_VALIDATE_INT) === false ) {
    //         http_response_code(400);
    //         $response['status'] = "400 Bad Request";
    //         $response['message'] = "Argumento inválido";
    //         echo json_encode($response);
    //         exit;
    //     }

    //     $id = $_GET['id'];

    //     if ($sala = get_sala($conn, $id)) {
    //         $response['status'] = "200 OK";
    //         $response['message'] =   "Sala encontrada";
    //         $response['data'] = [
    //             "id" => $sala['ID_SALA'],
    //             "nome" => $sala['NOME_SALA'],
    //             "numero" => $sala['NUMERO_SALA'],
    //             "doorsense" => $sala['UNIQUE_ID'],
    //             "status" => $sala['STATUS_ARDUINO']
    //         ];
    //     } else {
    //         http_response_code(404);
    //         $response['status'] = "404 Not Found";
    //         $response['message'] = "Sala não encontrada";
    //     }
    // } else {
        // Request sem query -> Obter todas as salas
        if ($all_vagas = get_all_vagas_livres($conn)) {
            $response['status'] = "200 OK";
            $response['message'] = "Todas as vagas livres";

            

            $response['data'] = [
                "total" => count($all_vagas),
                "vagas" => []
            ];

            foreach ($all_vagas as $indice => $dados_sala) {
                array_push($response['data']['vagas'], $dados_sala);
            }
        } else {
            http_response_code(500);
            $response['status'] = "500 Internal Server";
            $response['message'] = "Erro ao obter todas as vagas livres";
        }
    // }
} else {
    http_response_code(405);
    $response['status'] = "400 Method Not Allowed";
    $response['message'] = "Método da requisição inválido";
}

// Resposta
echo json_encode($response);

exit;
?>