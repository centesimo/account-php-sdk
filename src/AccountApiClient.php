<?php

namespace BetterDev\AccountApiClient;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
// use Illuminate\Config;
use Illuminate\Support\Facades\Config;
use Illuminate\Session;
use Carbon\Carbon;

class AccountApiClient
{
	public static function appId()
	{
		return Config::get('account_client.client-app-id');
	}
	public static function appSecret()
	{
		return Config::get('account_client.client-app-secret');
	}
		public static function serverApiUrl()
	{
		return Config::get('account_client.server-api-url');
	}
	public static function serverApiUrlUserGetToken()
	{
		return Config::get('account_client.server-api-url').'/access_token';
	}
	public static function serverApiUrlUserGetall()
	{
		return Config::get('account_client.server-api-url').'/user/getall';
	}
	public static function serverApiUrlUserMe()
	{
		return Config::get('account_client.server-api-url').'/user/me';
	}

	public static function getToken()
	{
		if (session()->has('token_response'))
		{
			$token_response = session()->get('token_response');
			if (session()->has('token_datetime')){
				$token_datetime = session()->get('token_datetime');
				$seconds_left = ($token_response->expires_in - $token_datetime->diffInSeconds(Carbon::now()));
				if (($token_datetime) && ($seconds_left <= 0))
				{
					return AccountApiClient::refreshToken($token_response->refresh_token);
				}
			}
			if ((property_exists($token_response, 'access_token')) && (property_exists($token_response, 'refresh_token')))
			{
				return $token_response;
			}
		}
		throw new AccountApiClientException('Erro recuperando o token.');
	}

	public static function refreshToken($refresh_token)
	{
		try {
	        $client = new Client();
	        $res = $client->request('POST', AccountApiClient::serverApiUrlUserGetToken(), [
	        	'form_params' =>
	        	[
	        		"grant_type" => "refresh_token",
	        		"refresh_token" => $refresh_token,
	        		"client_id" => AccountApiClient::appId(),
	        		"client_secret" => AccountApiClient::appSecret()
	            ]
	        ]);
			$token_response = json_decode($res->getBody());
			AccountApiClient::saveTokenSession($token_response);
	        return $token_response;
		} catch (ClientException $e) {
			$error_messages = null;
			if ($e->getCode() == 401){
				$error_messages = json_decode($e->getResponse()->getBody());
			}

			throw new AccountApiClientException('Erro atualizando o token.', $error_messages);
		}
	}

	public static function doLogin($user_name, $password)
	{
		try {
	        $client = new Client();
	        $res = $client->request('POST', AccountApiClient::serverApiUrlUserGetToken(), [
	        	'form_params' =>
	        	[
	        		"grant_type" => "password",
	        		"client_id" => AccountApiClient::appId(),
	        		"client_secret" => AccountApiClient::appSecret(),
	        		"username" => $user_name,
	        		"password" => $password
	            ]
	        ]);
			$token_response = json_decode($res->getBody());
			AccountApiClient::saveTokenSession($token_response);
	        return $token_response;
		} catch (ClientException $e) {
			$error_messages = null;
			if ($e->getCode() == 401){
				$error_messages = json_decode($e->getResponse()->getBody());
			}

			throw new AccountApiClientException('Erro fazendo login, sem token.', $error_messages);
		}
	}

	public static function getAllUsers($token)
	{
		try {
	        $client = new Client();
	        $res = $client->request('POST', AccountApiClient::serverApiUrlUserGetall(), [
	        	'form_params' =>
	        	[
	        		'access_token' => $token
	            ]
	        ]);
			$allUsers_response = json_decode($res->getBody());
	        return $allUsers_response;
		} catch (ClientException $e) {
			$error_messages = null;
			if ($e->getCode() == 401){
				$error_messages = json_decode($e->getResponse()->getBody());
			}

			throw new AccountApiClientException('Erro pegando todos os users', $error_messages);
		}
	}

	public static function me($token)
	{
		try {
	        $client = new Client();
	        $res = $client->request('POST', AccountApiClient::serverApiUrlUserMe(), [
	        	'form_params' =>
	        	[
	        		'access_token' => $token
	            ]
	        ]);
			$allUsers_response = json_decode($res->getBody());
	        return $allUsers_response;
		} catch (ClientException $e) {
			$error_messages = null;
			if ($e->getCode() == 401){
				$error_messages = json_decode($e->getResponse()->getBody());
			}

			throw new AccountApiClientException('Erro pegando meus dados.', $error_messages);
		}
	}

	public static function logout(){
		AccountApiClient::removeTokenSession();
	}

	public static function saveTokenSession($token){
		session()->put('token_response', $token);
		session()->put('token_datetime', Carbon::now());
	}

	public static function removeTokenSession(){
		session()->forget('token_response');
		session()->forget('token_datetime');
	}
}