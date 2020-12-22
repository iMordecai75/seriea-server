<?php
class Connection {

    static $apiurl = 'https://raw.githubusercontent.com/openfootball/football.json/master';

    static function cURLdownload($endpoint, $url = null)
    {
        if($url == null) {
            $url = self::$apiurl . $endpoint;
        } else {
            $url = $url . $endpoint;
        }
        

        if (!self::cURLcheckBasicFunctions()) {
            throw new Exception("UNAVAILABLE: cURL Basic Functions");
            return false;
        }
        $ch = curl_init();
        if ($ch) {
            if (!curl_setopt($ch, CURLOPT_URL, $url)) {
                curl_close($ch); // to match curl_init()
                throw new Exception("FAIL: curl_setopt(CURLOPT_URL)");
                return false;
            }
            if (!curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.13) Gecko/20080311 Firefox/2.0.0.13')) {
                throw new Exception("FAIL: curl_setopt(CURLOPT_USERAGENT)");
                return false;
            }
            if (!curl_setopt($ch, CURLOPT_HEADER, 0)) {
                throw new Exception("FAIL: curl_setopt(CURLOPT_HEADER)");
                return false;
            }
            curl_setopt($ch, CURLOPT_VERBOSE, true);
            /*
			if( !curl_setopt($ch, CURLOPT_POST, 0) )
			{
				self::writeLog("FAIL: curl_setopt(CURLOPT_POST)", JLog::ERROR);
				return false;
			}
            */
            if (!curl_setopt($ch, CURLOPT_RETURNTRANSFER, true)) {
                throw new Exception("FAIL: curl_setopt(CURLOPT_RETURNTRANSFER)");
                return false;
            }
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);

            if (!$result = curl_exec($ch)) {
                throw new Exception("FAIL: curl_exec() " . curl_error($ch));
                return false;
            }
            //self::writeLog($result, JLog::ERROR);
            curl_close($ch);
            return $result;
        } else {
            throw new Exception("FAIL: curl_init()");
            return false;
        }
    }

    static function cURLcheckBasicFunctions()
    {
        if (
            !function_exists("curl_init") &&
            !function_exists("curl_setopt") &&
            !function_exists("curl_exec") &&
            !function_exists("curl_close")
        ) return false;
        else return true;
    }
    
}