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
        $requests = [];

        $fields = config('railtracker.ip-api.default-fields');

        foreach($ips as $ip){
            $requests[] = [
                'query' => $ip,
                'fields' => $fields,
            ];
        }

        $postFields = json_encode($requests);

        try{
            $response = $this->curl($postFields);
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
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'http://ip-api.com/batch');
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