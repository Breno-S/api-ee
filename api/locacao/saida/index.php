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

header("Access-Control-Allow-Methods: PUT, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Content-Type: application/json; charset=UTF-8");

// Parâmetros permitidos pelo endpoint
$allowed_params = ["idLocacao"];

// Response (deve ser um array associativo)
$response = [];

// Verifique o método da requisição
$method = $_SERVER['REQUEST_METHOD'];

// Se a requisição for uma solicitação OPTIONS, retorne os cabeçalhos permitidos
if ($method === 'OPTIONS') {
    header("HTTP/1.1 200 OK");
    exit;
}

if ($method == 'PUT') {
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

    // Verifica se há um body na requisição
    if (!($json_data = file_get_contents('php://input'))) {
        http_response_code(400);
        $response['status'] = "400 Bad Request";
        $response['message'] = "Requisição sem body";
        echo json_encode($response);
        exit;
    }
    
    // Verifica se o JSON é válido
    if (!($data = json_decode($json_data, true))) {
        http_response_code(400);
        $response['status'] = "400 Bad Request";
        $response['message'] = "Body mal estruturado";
        echo json_encode($response);
        exit;
    }

    // Obtém todas as chaves do JSON do body
    $body_params = array_keys($data);

    // Verifica se há chaves inválidas na requisição
    if (array_diff($body_params, $allowed_params)) {
        http_response_code(400);
        $response['status'] = "400 Bad Request";
        $response['message'] = "Parâmetros desconhecidos na requisição";
        echo json_encode($response);
        exit;
    }
        
    // Verifica se tem os parâmetros obrigatórios
	if (isset($data['idLocacao'])) {

        // Validação dos dados
        if (filter_var($data['idLocacao'], FILTER_VALIDATE_INT) === false) {
            http_response_code(400);
            $response['status'] = "400 Bad Request";
            $response['message'] = "Argumento inválido";
            echo json_encode($response);
            exit;
        }

        if (registrar_saida($conn, $data['idLocacao'])) {
            $response['status'] = "200 OK";
            $response['message'] = "Saída registrada";
        } else {
            http_response_code(500);
            $response['status'] = "500 Internal Server";
            $response['message'] = "Erro ao registrar saída";
        }
	} else {
		http_response_code(400);
 	  	$response['status'] = "400 Bad Request";
   		$response['message'] = "Argumentos obrigatórios ausentes";
	}
} else {
    http_response_code(405);
    $response['status'] = "400 Method Not Allowed";
    $response['message'] = "Método da requisição inválido";
}

// Resposta
echo json_encode($response);

exit;
?>