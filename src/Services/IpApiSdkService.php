<?php

namespace Railroad\Railtracker\Services;

/*
 * SDK for ip-api.com
 *
 * See their docs:
 * http://ip-api.com/docs/api:batch
 */
class IpApiSdkService
{
    /**
     * @param array $ips
     * @return array|bool
     */
    public function bulkRequest($ips)
    {
        $ipsNestedInArrays = [];

        foreach($ips as $ip){
            $ipsNestedInArrays[] = [$ip];
        }

        $ips = json_encode($ipsNestedInArrays);

        try{
            $response = $this->curl($ips);
        }catch(\Exception $exception){
            error_log($exception);
            return false;
        }

        $response = json_decode($response);

        if (is_array($response)) {
            foreach($response as $r){
                $responseAllArrays[] = (array) $r;
            }
        }

        return $responseAllArrays ?? [];
    }

    // internally-used helper functions --------------------------------------------------------------------------------

    private function curl($postFields)
    {
        $apiKey = config('railtracker.ip_api_key');

        $url = 'https://api.ipdata.co/bulk?api-key=your-paid-api-key' . $apiKey;

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postFields);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_BINARYTRANSFER, true);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
        $output = curl_exec($ch);
        curl_close($ch);

        return $output;
    }
}