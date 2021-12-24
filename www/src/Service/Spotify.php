<?php
namespace App\Service;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;

class Spotify {

    private $token; // Array asociativo con los datos del token
    private $api_url; // URL de la API
    private $auth_url; // URL de autenticación de la API
    private $client_id; 
    private $client_secret;
    private $default_headers; // Encabezados por defecto de la API

    /**
     * Los parámetros son obtenidos por inyeccion de dependencias
     *
     * @param string $auth_url
     * @param string $api_url
     * @param string $client_id
     * @param string $client_secret
     */
    function __construct(string $auth_url, string $api_url, string $client_id, string $client_secret){
        $this->client          = new Client();
        $this->api_url         = $api_url;
        $this->auth_url        = $auth_url;
        $this->client_id       = $client_id;
        $this->client_secret   = $client_secret;
        $this->token           = [
                                    'token' => null,
                                    'expires_in' => null,
                                    'timestamp' => null,
                                 ];
        $this->default_headers = [
                                    'Content-Type'  => 'application/json',
                                    'Accept'        => 'application/json',
                                 ];
    }


    /**
     * Undocumented function
     *
     * @return null|string En caso de obtener el token, lo devuelve como una cadena.
     *              O devuelve null en caso de que el token no sea válido.
     */
    private function getToken(): ?string{
        if($this->tokenIsExpired()){  // Si el token está vencido
            $this->requestToken(); // solicitamos un nuevo token
        }

        if (!$this->tokenIsValid()){ // Si el token no es válido
            return null; // retornamos nulo
        }

        return $this->token['token']; // Si todo salió bien, retornamos el token
    } // getToken


    /**
     * Se basa en la fecha creación y vigencia del token para calcular si está vencido
     *
     * @return bool  true si el token está vencido, false en caso contrario
     */
    private function tokenIsExpired(): bool{
        if( is_null($this->token['timestamp']) ){
            return true;
        }

        $token_expires = $this->token['timestamp'] + $this->token['expires_in'];
        
        if( $token_expires <= time()){
            return true;
        }

        return false;
    } // tokenIsExpired

    /**
     * Verifica la validez de un token basandose en el tiempo de expiración del mismo
     * y si hay un token en la propiedad token
     *
     * @return bool  true si el token is valid, false en caso contrario
     */
    private function tokenIsValid(): bool{
        if($this->tokenIsExpired()){ // Si el token está vencido
            return false; 
        }

        if(is_null($this->token['token'])){ // Si el token es nulo
            return false;
        }

        // todo Realizar una consulta tipo ping a la API para validar la validez del token

        return true; // Pasó todas las pruebas
    } // tokenIsValid

    /**
     * Solicita a la API, el Bearer token para futuras solicitudes.  Haciendo
     * uso del client_id y cient_secret parametrizados en el .env arma la 
     * autorización básica para solicitar el token.  Si lo obtiene carga los 
     * valores en la propiedad token.
     *
     * @return void
     */
    private function requestToken(): void{
        $headers = [
            'Authorization' => 'Basic ' . base64_encode($this->client_id . ":" . $this->client_secret),
            'content-type' => 'application/x-www-form-urlencoded',
        ];

        $response = $this->client->request('POST', $this->auth_url, [
            'headers' => $headers,
            'body' => 'grant_type=client_credentials',
        ]);

        if($response->getStatusCode() == 200){ // si la respuesta fue exitosa
            $data = json_decode($response->getBody(), true);
            if(isset($data['access_token'])){
                $this->token["token"] = $data['token_type'] . " " . $data['access_token'];
                $this->token["expires_in"] = $data['expires_in'];
                $this->token["timestamp"] = time();
            }
        }
    } // requestToken

    /**
     * Obtiene/arma los headers para las peticiones a la API
     *
     * @param array $headers Array con headers adicionales a agregar/reemplazar
     * @param boolean $with_auth_token Incluye el header de Autorización
     *              para los casos que lo requieran.  Si no es posible
     *              obtenerlo la función retornará nulo
     * @return array|null Array con headers por defecto, adicionales y autorización
     *              si se solicitó en caso de éxito.
     *              Null cuando no se pueda obtener el token de autorización,
     *              siempre y cuando sea requerido
     */
    private function getHeaders(Array $headers = [], $with_auth_token = true): ?array{
        $auth_header = [];
        if($with_auth_token){ // Si lleva token de autorización
            $token = $this->getToken(); // Obetenemos el token
            
            if(is_null($token)){ // Si es nulo
                return null; 
            }
            $auth_header = [
                'Authorization' => $token, // Obtiene el token de autorización
            ];
        }
        return array_merge($this->default_headers, $auth_header, $headers);
    } // getHeaders

    /**
     * Obtiene los 10 lanzamientos más recientes de Spotify
     *
     * @return object|null Cuando tiene exito, retorna un objeto, que contiene 
     *              la información de los últimos lanzamientos.
     *              Retorna null en caso de error de autenticación con la API
     */
    public function getNewReleases(int $page = null): ?object{
        $headers = $this->getHeaders();
        $page = $this->getPageInfo($page);
        
        if(is_null($headers)){ // No obtuvo el token de autorización
            return null;
        }

        $response = $this->client->request(
                'GET', 
                $this->api_url . "/browse/new-releases?limit={$page->limit}&offset={$page->offset}",
                ['headers' => $headers],
        );
        return (object)[
            'page' => $page,
            'data' => json_decode($response->getBody()->getContents())
        ];
    } // getNewReleases


    private function getPageInfo(int $page = null): object{
        $limit = 12; // De momento lo tengo fijo
        if(is_null($page)){
            $page = 1; // La página por defecto
        }
        $res = (object)[
            'number' => $page,
            'offset' => ($page-1) * $limit, // La página por la cantidad de items por página
            'limit' => $limit,
        ];

        return $res;
    } // getPageInfo


    /**
     * Obtiene la información del artista desde la API de Spotify
     * haciendo uso del id del artista de Spotity
     *
     * @param String|null $artist_id El id del artista
     * @return object|null Retorna null cuando el $artist_id es nulo,
     *              o cuando no pudo obtener los headers.  
     *              Retorna un objeto error si el $artist_id no es válido.
     *              Retorna un objeto con la información del artista
     *              cuando logra obtener la infromación de la API
     */
    public function getArtistById(String $artist_id = null): ?object {
        if(is_null($artist_id)){
            return null;
        }
        
        $headers = $this->getHeaders();
        if ( is_null($headers)){ // No obtuvo el token de autorización
                return null;
        }

        try {
            $response = $this->client->request(
                'GET',
                $this->api_url . "/artists/{$artist_id}/",
                ['headers' => $headers],
            );
            if($response->getStatusCode() == 200){ // si la respuesta fue exitosa
                return json_decode($response->getBody()->getContents());
            }
        } catch (ClientException $e) {
            if ($e->hasResponse()){
                return json_decode($e->getResponse()->getBody()->getContents());
            }
        }

        return null;
    } // getArtistById


    /**
     * Obtiene la lista de los top_tracks del artista desde la API de Spotify
     * haciendo uso del id del artista de Spotity
     *
     * @param String|null $artist_id El id del artista
     * @return object|null Retorna null cuando el $artist_id es nulo,
     *              o cuando no pudo obtener los headers.  
     *              Retorna un objeto error si el $artist_id no es válido.
     *              Retorna un objeto con la información del artista
     *              cuando logra obtener la infromación de la API
     */
    public function getTopTracksByArtistId(String $artist_id = null): ?object {
        if(is_null($artist_id)){
            return null;
        }

        $headers = $this->getHeaders();
        if ( is_null($headers)){ // No obtuvo el token de autorización
                return null;
        }

        try {
            $response = $this->client->request(
                'GET',
                $this->api_url . "/artists/{$artist_id}/top-tracks?market=co",
                ['headers' => $headers],
            );
            if ($response->getStatusCode() == 200) { // si la respuesta fue exitosa
                return json_decode($response->getBody()->getContents());
            }
        } catch (ClientException $e) {
            if ($e->hasResponse()){
                return json_decode($e->getResponse()->getBody()->getContents());
            }
        }

        return null;
    } // getArtigetTopTracksByArtistIdstaById

} // Spotify