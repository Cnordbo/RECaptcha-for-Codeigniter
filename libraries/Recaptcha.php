<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

class Recaptcha {
    /*
    * This is a PHP library that handles calling reCAPTCHA.
    *    - Documentation and latest version
    *          http://recaptcha.net/plugins/php/
    *    - Get a reCAPTCHA API Key
    *          https://www.google.com/recaptcha/admin/create
    *    - Discussion group
    *          http://groups.google.com/group/recaptcha
    *
    * Copyright (c) 2007 reCAPTCHA -- http://recaptcha.net
    * AUTHORS:
    *   Mike Crawford
    *   Ben Maurer
    *
    * CONTRIBUTION:
    * Codeigniter version - 23.08.2012 by Christer Nordbø, Norway.
    *
    * Permission is hereby granted, free of charge, to any person obtaining a copy
    * of this software and associated documentation files (the "Software"), to deal
    * in the Software without restriction, including without limitation the rights
    * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
    * copies of the Software, and to permit persons to whom the Software is
    * furnished to do so, subject to the following conditions:
    *
    * The above copyright notice and this permission notice shall be included in
    * all copies or substantial portions of the Software.
    *
    * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
    * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
    * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
    * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
    * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
    * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
    * THE SOFTWARE.
    */

    /**
     * The reCAPTCHA server URL's
     */

    const RECAPTCHA_API_SERVER = "http://www.google.com/recaptcha/api";
    const RECAPTCHA_API_SECURE_SERVER = "https://www.google.com/recaptcha/api";
    const RECAPTCHA_VERIFY_SERVER = "www.google.com";

    protected $is_valid;
    protected $error;

    //Remember to obtain the Public and Private key @ https://www.google.com/recaptcha/admin/create
    protected $public_key = "YOUR PUBLIC KEY";
    protected $privkey = "YOUR PRIVATE KEY";
    protected $theme = "RECAPTCHA THEME";

    function __construct() {
            log_message('debug', "RECAPTCHA Class Initialized.");
            
            $this->_ci =& get_instance();
            
            //Load the CI Config file for recaptcha
            $this->_ci->load->config('recaptcha');
            //load in the values from the config file. 
            $this->public_key   = $this->_ci->config->item('public_key');
            $this->privkey  = $this->_ci->config->item('private_key');
            
            //Lets do some basic error handling to see if the configuration is A-OK.
            $temp_error_msg = '';
            if ($this->public_key === 'YOUR PUBLIC KEY')
            {
                $temp_error_msg .= 'You need to set your public key in the config file <br />';
            }
            
            if ($this->privkey === 'YOUR PRIVATE KEY')
            {
                $temp_error_msg .= 'You need to set your private key in the config file <br />';
            }
            
            if ($temp_error_msg != '')
            {
                show_error($temp_error_msg);
            }
            
    }
    
    /**
     * Encodes the given data into a query string format
     * @param $data - array of string elements to be encoded
     * @return string - encoded request
     */
    function recaptcha_qsencode ($data) {
        $req = "";
        foreach ( $data as $key => $value )
            $req .= $key . '=' . urlencode( stripslashes($value) ) . '&';

        // Cut the last '&'
        $req=substr($req,0,strlen($req)-1);
        return $req;
    }



    /**
     * Submits an HTTP POST to a reCAPTCHA server
     * @param string $host
     * @param string $path
     * @param array $data
     * @param int port
     * @return array response
     */
    function recaptcha_http_post($host, $path, $data, $port = 80) {

        $req = $this->recaptcha_qsencode ($data);

        $http_request  = "POST $path HTTP/1.0\r\n";
        $http_request .= "Host: $host\r\n";
        $http_request .= "Content-Type: application/x-www-form-urlencoded;\r\n";
        $http_request .= "Content-Length: " . strlen($req) . "\r\n";
        $http_request .= "User-Agent: reCAPTCHA/PHP\r\n";
        $http_request .= "\r\n";
        $http_request .= $req;

        $response = '';
        if( false == ( $fs = @fsockopen($host, $port, $errno, $errstr, 10) ) ) {
            die ('Could not open socket');
        }

        fwrite($fs, $http_request);

        while ( !feof($fs) )
            $response .= fgets($fs, 1160); // One TCP-IP packet
        fclose($fs);
        $response = explode("\r\n\r\n", $response, 2);

        return $response;
    }




    function recaptcha_get_html ($error = null, $use_ssl = false)
    {
        if ($this->public_key == null || $this->public_key == '') {
            die ("To use reCAPTCHA you must get an API key from <a href='https://www.google.com/recaptcha/admin/create'>https://www.google.com/recaptcha/admin/create</a>");
        }

        if ($use_ssl) {
            $server = self::RECAPTCHA_API_SECURE_SERVER;
        } else {
            $server = self::RECAPTCHA_API_SERVER;
        }

        $errorpart = "";
        if ($error) {
            $errorpart = "&amp;error=" . $error;
        }
        return '<script type="text/javascript"> var RecaptchaOptions = { theme : ' . $this->theme . ' }; </script>
        		<script type="text/javascript" src="'. $server . '/challenge?k=' . $this->public_key . $errorpart . '"></script>

            <noscript>
                    <iframe src="'. $server . '/noscript?k=' . $this->public_key . $errorpart . '" height="300" width="500" frameborder="0"></iframe><br/>
                    <textarea name="recaptcha_challenge_field" rows="3" cols="40"></textarea>
                    <input type="hidden" name="recaptcha_response_field" value="manual_challenge"/>
            </noscript>';
    }







    /**
     * Calls an HTTP POST function to verify if the user's guess was correct
     * @param string $remoteip
     * @param string $challenge
     * @param string $response
     * @param array $extra_params an array of extra variables to post to the server
     * @return ReCaptchaResponse
     */
    function recaptcha_check_answer ($remoteip, $challenge, $response, $extra_params = array())
    {
        if ($this->privkey == null || $this->privkey == '') {
            die ("To use reCAPTCHA you must get an API key from <a href='https://www.google.com/recaptcha/admin/create'>https://www.google.com/recaptcha/admin/create</a>");
        }

        if ($remoteip == null || $remoteip == '') {
            die ("For security reasons, you must pass the remote ip to reCAPTCHA");
        }



        //discard spam submissions
        if ($challenge == null || strlen($challenge) == 0 || $response == null || strlen($response) == 0) {

            $this->is_valid = false;
            $this->error = 'incorrect-captcha-sol';

        }

        $response = $this->recaptcha_http_post (self::RECAPTCHA_VERIFY_SERVER, "/recaptcha/api/verify",
            array (
                'privatekey' => $this->privkey,
                'remoteip' => $remoteip,
                'challenge' => $challenge,
                'response' => $response
            ) + $extra_params
        );

        $answers = explode ("\n", $response [1]);


        if (trim ($answers [0]) == 'true') {
            $this->is_valid = true;
        }
        else {
            $this->is_valid = false;
            $this->error = $answers [1];
        }


    }

    /**
     * gets a URL where the user can sign up for reCAPTCHA. If your application
     * has a configuration page where you enter a key, you should provide a link
     * using this function.
     * @param string $domain The domain where the page is hosted
     * @param string $appname The name of your application
     */
    function recaptcha_get_signup_url ($domain = null, $appname = 'Codeigniter') {
        return "https://www.google.com/recaptcha/admin/create?" .  $this->recaptcha_qsencode (array ('domains' => $domain, 'app' => $appname));
    }

    function recaptcha_aes_pad($val) {
        $block_size = 16;
        $numpad = $block_size - (strlen ($val) % $block_size);
        return str_pad($val, strlen ($val) + $numpad, chr($numpad));
    }

    /* Mailhide related code */

    function recaptcha_aes_encrypt($val,$ky) {
        if (! function_exists ("mcrypt_encrypt")) {
            die ("To use reCAPTCHA Mailhide, you need to have the mcrypt php module installed.");
        }
        $mode=MCRYPT_MODE_CBC;
        $enc=MCRYPT_RIJNDAEL_128;
        $val=$this->recaptcha_aes_pad($val);
        return mcrypt_encrypt($enc, $ky, $val, $mode, "\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0");
    }


    function recaptcha_mailhide_urlbase64 ($x) {
        return strtr(base64_encode ($x), '+/', '-_');
    }

    /* gets the reCAPTCHA Mailhide url for a given email, public key and private key */
    function recaptcha_mailhide_url($email) {
        if ($this->public_key == '' || $this->public_key == null || $this->privkey == "" || $this->privkey == null) {
            die ("To use reCAPTCHA Mailhide, you have to sign up for a public and private key, " .
                "you can do so at <a href='http://www.google.com/recaptcha/mailhide/apikey'>http://www.google.com/recaptcha/mailhide/apikey</a>");
        }


        $ky = pack('H*', $this->privkey);
        $cryptmail = $this->recaptcha_aes_encrypt ($email, $ky);

        return "http://www.google.com/recaptcha/mailhide/d?k=" . $this->public_key . "&c=" . $this->recaptcha_mailhide_urlbase64 ($cryptmail);
    }

    /**
     * gets the parts of the email to expose to the user.
     * eg, given johndoe@example,com return ["john", "example.com"].
     * the email is then displayed as john...@example.com
     */
    function recaptcha_mailhide_email_parts ($email) {
        $arr = preg_split("/@/", $email );

        if (strlen ($arr[0]) <= 4) {
            $arr[0] = substr ($arr[0], 0, 1);
        } else if (strlen ($arr[0]) <= 6) {
            $arr[0] = substr ($arr[0], 0, 3);
        } else {
            $arr[0] = substr ($arr[0], 0, 4);
        }
        return $arr;
    }

    /**
     * Gets html to display an email address given a public an private key.
     * to get a key, go to:
     *
     * http://www.google.com/recaptcha/mailhide/apikey
     */
    function recaptcha_mailhide_html($email) {
        $emailparts = $this->recaptcha_mailhide_email_parts ($email);
        $url = $this->recaptcha_mailhide_url ($this->public_key, $this->privkey, $email);

        return htmlentities($emailparts[0]) . "<a href='" . htmlentities ($url) .
            "' onclick=\"window.open('" . htmlentities ($url) . "', '', 'toolbar=0,scrollbars=0,location=0,statusbar=0,menubar=0,resizable=0,width=500,height=300'); return false;\" title=\"Reveal this e-mail address\">...</a>@" . htmlentities ($emailparts [1]);

    }

    function checkIfIsValid()
    {
        if( $this->getIsValid() )
        {
            return $this->getIsValid();
        }
        else
        {
            return array( $this->getIsValid(), $this->getError() );
        }
    }

    function getIsValid()
    {
        return $this->is_valid;
    }

    function getError()
    {
        return $this->error;
    }

}
