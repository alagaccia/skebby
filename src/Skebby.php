<?php
/*
 * Skebby Class
 */

namespace alagaccia\skebby;

class Skebby
{
    protected $username;
    protected $password;
    protected $alias;
    protected $quality;

    const SKEBBY_BASEURL = "https://api.skebby.it/API/v1.0/REST/";
    const MESSAGE_CLASSIC_PLUS = "GP";
    const MESSAGE_CLASSIC = "TI";
    const MESSAGE_BASIC = "SI";
    const MESSAGE_EXPORT = "EE";
    const MESSAGE_ADVERTISING = "AD";

    public function __construct()
    {
        $this->username = config('skebby.SKEBBY_USER') ?? env('SKEBBY_USER');
        $this->password = config('skebby.SKEBBY_PWD') ?? env('SKEBBY_PWD');
        $this->alias = config('skebby.SKEBBY_ALIAS') ?? env('SKEBBY_ALIAS');
        $this->quality = config('skebby.SKEBBY_QUALITY', 'TI') ?? env('SKEBBY_QUALITY');
    }

    /**
    * Authenticates the user given its username and password.
    * Returns the pair user_key, session_key
    */
    public function login()
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, self::SKEBBY_BASEURL .
                    'login?username=' . $this->username .
                    '&password=' . $this->password);

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $response = curl_exec($ch);
        $info = curl_getinfo($ch);
        curl_close($ch);

        $auth = explode(";", $response);

        return $auth;
    }

    /*
    * Sends SMS giving phone number and text message.
    * Inherits login function.
    */
    public function send($phone, $message)
    {
        if ( $auth = $this->login() ) {
            $body = [
    			"message" => $message,
    			"message_type" => $this->quality,
    			"returnRemaining" => true,
    			"recipient" => [$phone],
    			"sender" => $this->alias,
    		];

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, self::SKEBBY_BASEURL . 'sms');
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-type: application/json',
                'user_key: ' . $auth[0],
                'Session_key: ' . $auth[1]
            ]);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
            $response = curl_exec($ch);
            $info = curl_getinfo($ch);

            curl_close($ch);
        }

        return json_decode($response);
    }

    public function getInfo()
    {
        if ( $auth = $this->login() ) {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, self::SKEBBY_BASEURL . 'status');
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-type: application/json',
                'user_key: ' . $auth[0],
                'Session_key: ' . $auth[1]
            ]);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            $response = curl_exec($ch);
            $info = curl_getinfo($ch);
            curl_close($ch);
        }

        return json_decode($response);
    }

    public function getRemaining()
    {
        $info = $this->getInfo();
        // return $info->sms[0]->quantity; // GP
        return $info->sms[1]->quantity; // TI
        // return $info->sms[2]->quantity; // SI
        // return $info->sms[3]->quantity; // EE
        // return $info->sms[4]->quantity; // AD
    }
}
