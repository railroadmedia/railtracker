<?php

namespace Railroad\Railtracker\Services;

/*
 * SDK for ipdata.co
 *
 * See their docs:
 * https://docs.ipdata.co/api-reference/bulk-lookup
 */
class IpDataApiSdkService
{
    private static $apiBulkLimit = 100;

    /**
     * @param array $ips
     * @return array|bool
     */
    public function bulkRequest($ips)
    {
        if(empty($ips)){
            return [];
        }

        $chunksOfIps = array_chunk($ips, self::$apiBulkLimit);

        foreach($chunksOfIps as $chunkOfIps){
            $ipsInCurrentChunk = array_values($chunkOfIps);

            try{
                $response = $this->curl(json_encode($ipsInCurrentChunk));
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
        }

        return $responseAllArrays ?? [];
    }

    // internally-used helper functions --------------------------------------------------------------------------------

    private function curl($postFields)
    {
        $apiKey = config('railtracker.ip_data_api_key');

        $url = 'https://api.ipdata.co/bulk?api-key=' . $apiKey;

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