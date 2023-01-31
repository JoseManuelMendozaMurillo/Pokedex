<?php

// Obtenemos los datos que llegan desde el FrontEnd
$data = json_decode(file_get_contents("php://input"));

$pokemonName = empty($data->pokemonName) ? "" : strtolower($data->pokemonName);

// Realizamos la busqueda
$response = facade($pokemonName);

try {
    // Si el pokemon no se encuentra devolvera un 404
    if ($response["httpCode"] != 404) {
        http_response_code(202);
        echo $response["response"];
    } else {
        http_response_code(404);
    }

} catch (\Throwable$th) {
    // Si ocurre algun fallo devolvera un 503
    http_response_code(503);
}

/**
 * facade
 *
 * @param string $pokemonName
 * @return array
 */
function facade(String $pokemonName): array
{
    // Función que servira como fachada para conectarnos a la PokeApi

    // Consultamos los datos del pokemon
    $urlPokemonInfo = "https://pokeapi.co/api/v2/pokemon/" . $pokemonName;
    $responsePokemonInfo = getRequest($urlPokemonInfo);

    // Consultamos el rap del pokemon
    $responsePokemonRap = getPokemonRap($pokemonName);

    // Definimos el codigo http que se devolvera
    if ($responsePokemonInfo["httpCode"] != 404 && $responsePokemonRap["httpCode"] != 404) {
        $httpCode = 202;
    } else {
        $httpCode = 404;
    }

    // Retornamos el resultado
    return [
        "httpCode" => $httpCode,
        "response" => getPokemon($responsePokemonInfo["response"], $responsePokemonRap["response"]),
    ];
}

/**
 * getRequest
 *
 * @param string $url
 * @return array
 */
function getRequest(string $url): array
{
    // Método para hacer una petición get mediante CURL

    // Inicializamos y configuramos curl
    $ch = curl_init();

    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    // Ejecutamos la consulta a la poke api
    $response = curl_exec($ch);

    // Obtenemos el estado de la consulta (Http code)
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    // Cerramos la sesión
    curl_close($ch);

    return ([
        "httpCode" => $httpCode,
        "response" => $response,
    ]);
}

/**
 * getPokemon
 *
 * @param string $responsePokemonInfo
 * @param array $responsePokemonRap
 * @return string
 */
function getPokemon(string $responsePokemonInfo, array $pokemonRap): string
{
    $pokemonInfo = getPokemonInfo($responsePokemonInfo);

    return json_encode(array_merge($pokemonInfo, $pokemonRap));
}

/**
 * getPokemonInfo
 *
 * @param string $responsePokemonInfo
 * @return array
 */
function getPokemonInfo(string $responsePokemonInfo): array
{
    $pokemonInfo = json_decode($responsePokemonInfo, true);
    return [
        "id" => $pokemonInfo["id"],
        "name" => $pokemonInfo["name"],
        "experience" => $pokemonInfo["base_experience"],
        "life" => $pokemonInfo["stats"][0]["base_stat"],
        "attack" => $pokemonInfo["stats"][1]["base_stat"],
        "defense" => $pokemonInfo["stats"][2]["base_stat"],
        "specialAttack" => $pokemonInfo["stats"][3]["base_stat"],
        "images" => [
            "front" => $pokemonInfo["sprites"]["front_default"],
            "back" => $pokemonInfo["sprites"]["back_default"],
        ],
    ];
}

/**
 * getPokemonRap
 *
 * @param string $pokemonName
 * @return array
 */
function getPokemonRap(string $pokemonName): array
{
    // Establecemos la ruta del storage donde se almacenaran los audios
    define("STORAGE_PATH", "F:\\xampp\htdocs\Proyectos-REDI\Pruebas\Pokeapi\server\storage");

    $audioName = "rap de " . strtolower($pokemonName);
    $audioPath = STORAGE_PATH . "\\" . $audioName . ".mp3";

    // Verificamos si el rap del pokemón que estamos buscando no esta descargada
    if (!file_exists($audioPath)) {
        // En caso de que no tengamos el rap del pokemón, lo descargamos
        $resultDownload = searchDownloadAudioVideoYoutube($audioName, STORAGE_PATH, $audioName);

        // Si no se encontrarón resultado para la busqueda, terminamos la ejecución de la función
        if ($resultDownload["httpCode"] == 404) {
            http_response_code(404);
        }

        /* Si por alguna razón el servidor no pudo conectarse con la API de youtube o la descarga del
        audio fallo devolvemos un error de servidor
         */
        if ($resultDownload["httpCode"] > 500 || $resultDownload["statusDownload"] != 0) {
            throw new Exception("Error al descargar el archivo de audio o al conectarse a la API de Youtube");
        }

    }

    // Obtenemos el contenido del archivo de audio y lo cofidicamos en BASE64
    try {
        $audioData = base64_encode(file_get_contents($audioPath));
    } catch (Exception $e) {
        throw new Exception("Error al cargar el archivo de audio.\n" . $e);
    }

    // Definimos el código http que devolvera la función
    if (isset($resultDownload["httpCode"])) {
        $httpCode = $resultDownload["httpCode"] == 404 ? 404 : 202;
    } else {
        $httpCode = 202;
    }

    return [
        "httpCode" => $httpCode,
        "response" => [
            "pokemonRap" => $audioData,
        ],
    ];
}

/**
 * serachDownloadAudioVideoYoutube
 *
 * @param string $searchParameter, $audioSavePath, $audioName
 * @return array
 */
function searchDownloadAudioVideoYoutube(string $searchParameter, string $audioSavePath, string $audioName): array
{
    // Función para buscar un video en youtube y descargar el audio del primer video que se obtenga como resultado

    // Definimos la clave para conectarnos a la API de Youtube
    define("YOUTUBE_API_KEY", "AIzaSyAYCpRBol56c-UqPjGxDcswoqAvNQXvjKw");

    // Buscamos el parametro en youtube mediante la API de youtube
    $searchParameter = str_replace(" ", "+", $searchParameter);
    $urlRequest = "https://www.googleapis.com/youtube/v3/search?part=snippet&q=" . $searchParameter . "&key=" . YOUTUBE_API_KEY;
    $responseRequest = getRequest($urlRequest);

    // Obtenemos id del video del primer resultado de la busqueda
    $videoResults = json_decode($responseRequest["response"], true);
    $videoId = $videoResults["items"][0]["id"]["videoId"];

    // Generamos el link al video de youtube
    $youtubeUrl = "https://www.youtube.com/watch?v=" . $videoId;

    // Descargamos el audio del video
    $statusDownload = downloadAudioVideoYoutube($youtubeUrl, $audioSavePath, $audioName);

    return [
        "httpCode" => $responseRequest["httpCode"],
        "statusDownload" => $statusDownload,
    ];
}

/**
 * downloadAudioVideoYoutube
 *
 * @param string $youtubeUrl, $audioSavePath, $audioName
 * @return int
 */
function downloadAudioVideoYoutube(string $youtubeUrl, string $audioSavePath, string $audioName): int
{
    // Utilizamos youtube-dl para descargar el audio del video de youtube

    // Establecemos el formato del archivo de audio
    $audioFormat = "mp3";

    // Agregamos la extensión para el formato del audio al nombre del audio
    $audioName = "\\" . $audioName . "." . $audioFormat;

    // Generamos el comando para descargar el audio del video
    $command = "youtube-dl --extract-audio --audio-format " . $audioFormat . " -o \"" . $audioSavePath . $audioName . "\" " . $youtubeUrl;

    // Ejecutamos el comando para que comience la descargar del audio
    exec($command, $output, $status);

    return $status;
}
