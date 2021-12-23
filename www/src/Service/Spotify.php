<?php
namespace App\Service;

use GuzzleHttp\Client;

class Spotify {

    private $token;
    private $api_url;
    private $auth_url;
    private $client_id;
    private $client_secret;
    private $default_headers;

    function __construct($auth_url, $api_url, $client_id, $client_secret){
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

    private function getToken(){
        
        if($this->tokenIsExpired()){  // Si el token está vencido
            $this->requestToken(); // solicitamos un nuevo token
        }
        // var_dump(['this->token',$this->token]);exit;
        if (!$this->tokenIsValid()){ // Si el token no es válido
            return null; // retornamos nulo
        }

        return $this->token['token']; // Si todo salió bien, retornamos el token
    } // getToken


    private function tokenIsExpired(){
        if( is_null($this->token['timestamp']) ){
            return true;
        }

        $token_expires = $this->token['timestamp'] + $this->token['expires_in'];
        //var_dump(['token_expires', $token_expires, 'time' => time()]);exit;
        if( $token_expires <= time()){
            return true;
        }

        return false;
    } // tokenIsExpired


    private function tokenIsValid(){
        if($this->tokenIsExpired()){ // Si el token está vencido
            return false; 
        }

        if(is_null($this->token['token'])){ // Si el token es nulo
            return false;
        }

        return true; // Pasó todas las pruebas
    } // tokenIsValid

    private function requestToken(){
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


    private function getHeaders(Array $headers = [], $with_auth_token = true){
        $auth_header = [];
        if($with_auth_token){ // Si lleva token de autorización
            $token = $this->getToken(); // Obetenemos el token
            //var_dump(['token',$token]);exit;
            if(is_null($token)){ // Si es nulo
                return null; 
            }
            $auth_header = [
                'Authorization' => $token, // Obtiene el token de autorización
            ];
        }
        return array_merge($this->default_headers, $auth_header, $headers);
    } // getHeaders

    public function getLanzamientos(){
        $headers = $this->getHeaders();
        // var_dump(['headers', $headers]);exit;
        if(is_null($headers)){ // No obtuvo el token de autorización
            return ;// todo Devolver error de autorización
        }

        $response = $this->client->request('GET', $this->api_url . '/browse/new-releases?limit=10&offset=0',
        [
            'headers' => $headers
        ]
        );
        return json_decode($response->getBody()->getContents());
    }

} // Spotify