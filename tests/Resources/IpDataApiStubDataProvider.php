<?php

namespace Railroad\Railtracker\Tests\Resources;


use Illuminate\Support\Collection;
use Railroad\Railtracker\ValueObjects\RequestVO;

class IpDataApiStubDataProvider
{
    public static $INPUT = [
                '1.1.1.1',
                '2.2.2.2',
                '3.3.3.3',
                '4.4.4.4',
                '5.5.5.5',
                '6.6.6.6',
                '7.7.7.7',
                '8.8.8.8',
                '9.9.9.9',
            ];

    public static function output()
    {
        return array(
            0 =>
                array(
                    'ip' => '1.1.1.1',
                    'is_eu' => false,
                    'city' => '',
                    'region' => '',
                    'region_code' => '',
                    'country_name' => 'Australia',
                    'country_code' => 'AU',
                    'continent_name' => 'Oceania',
                    'continent_code' => 'OC',
                    'latitude' => -33.493999999999999772626324556767940521240234375,
                    'longitude' => 143.21039999999999281499185599386692047119140625,
                    'asn' => 'AS13335',
                    'organisation' => 'Cloudflare Inc',
                    'postal' => '',
                    'calling_code' => '61',
                    'flag' => 'https://ipdata.co/flags/au.png',
                    'emoji_flag' => '🇦🇺',
                    'emoji_unicode' => 'U+1F1E6 U+1F1FA',
                    'languages' =>
                        (object) array(
                            0 =>
                                array(
                                    'name' => 'English',
                                    'native' => 'English',
                                ),
                        ),
                    'currency' =>
                        (object) array(
                            'name' => 'Australian Dollar',
                            'code' => 'AUD',
                            'symbol' => 'AU$',
                            'native' => '$',
                            'plural' => 'Australian dollars',
                        ),
                    'time_zone' =>
                        (object) array(
                            'name' => '',
                            'abbr' => '',
                            'offset' => '',
                            'is_dst' => '',
                            'current_time' => '',
                        ),
                    'threat' =>
                        (object) array(
                            'is_tor' => false,
                            'is_proxy' => false,
                            'is_anonymous' => false,
                            'is_known_attacker' => false,
                            'is_known_abuser' => true,
                            'is_threat' => true,
                            'is_bogon' => false,
                        ),
                    'count' => '695',
                ),
            1 =>
                array(
                    'ip' => '2.2.2.2',
                    'is_eu' => true,
                    'city' => '',
                    'region' => '',
                    'region_code' => '',
                    'country_name' => 'France',
                    'country_code' => 'FR',
                    'continent_name' => 'Europe',
                    'continent_code' => 'EU',
                    'latitude' => 48.8581999999999965211827657185494899749755859375,
                    'longitude' => 2.33870000000000022311041902867145836353302001953125,
                    'asn' => 'AS3215',
                    'organisation' => 'Orange',
                    'postal' => '',
                    'calling_code' => '33',
                    'flag' => 'https://ipdata.co/flags/fr.png',
                    'emoji_flag' => '🇫🇷',
                    'emoji_unicode' => 'U+1F1EB U+1F1F7',
                    'carrier' =>
                        array(
                            'name' => 'Orange',
                            'mcc' => '208',
                            'mnc' => '01',
                        ),
                    'languages' =>
                        (object) array(
                            0 =>
                                array(
                                    'name' => 'French',
                                    'native' => 'Français',
                                ),
                        ),
                    'currency' =>
                        (object) array(
                            'name' => 'Euro',
                            'code' => 'EUR',
                            'symbol' => '€',
                            'native' => '€',
                            'plural' => 'euros',
                        ),
                    'time_zone' =>
                        (object) array(
                            'name' => 'Europe/Paris',
                            'abbr' => 'CEST',
                            'offset' => '+0200',
                            'is_dst' => true,
                            'current_time' => '2018-10-01T10:37:55.031677+02:00',
                        ),
                    'threat' =>
                        (object) array(
                            'is_tor' => false,
                            'is_proxy' => false,
                            'is_anonymous' => false,
                            'is_known_attacker' => false,
                            'is_known_abuser' => false,
                            'is_threat' => false,
                            'is_bogon' => false,
                        ),
                    'count' => '695',
                ),
            2 =>
                array(
                    'ip' => '3.3.3.3',
                    'is_eu' => false,
                    'city' => 'Seattle',
                    'region' => 'Washington',
                    'region_code' => 'WA',
                    'country_name' => 'United States',
                    'country_code' => 'US',
                    'continent_name' => 'North America',
                    'continent_code' => 'NA',
                    'latitude' => 47.634399999999999408828443847596645355224609375,
                    'longitude' => -122.3422000000000053887561080045998096466064453125,
                    'asn' => '',
                    'organisation' => '',
                    'postal' => '98109',
                    'calling_code' => '1',
                    'flag' => 'https://ipdata.co/flags/us.png',
                    'emoji_flag' => '🇺🇸',
                    'emoji_unicode' => 'U+1F1FA U+1F1F8',
                    'languages' =>
                        (object) array(
                            0 =>
                                array(
                                    'name' => 'English',
                                    'native' => 'English',
                                ),
                        ),
                    'currency' =>
                        (object) array(
                            'name' => 'US Dollar',
                            'code' => 'USD',
                            'symbol' => '$',
                            'native' => '$',
                            'plural' => 'US dollars',
                        ),
                    'time_zone' =>
                        (object) array(
                            'name' => 'America/Los_Angeles',
                            'abbr' => 'PDT',
                            'offset' => '-0700',
                            'is_dst' => true,
                            'current_time' => '2018-10-01T01:37:55.032920-07:00',
                        ),
                    'threat' =>
                        (object) array(
                            'is_tor' => false,
                            'is_proxy' => false,
                            'is_anonymous' => false,
                            'is_known_attacker' => false,
                            'is_known_abuser' => false,
                            'is_threat' => false,
                            'is_bogon' => false,
                        ),
                    'count' => '695',
                ),
            3 =>
                array(
                    'ip' => '4.4.4.4',
                    'is_eu' => false,
                    'city' => '',
                    'region' => '',
                    'region_code' => '',
                    'country_name' => 'United States',
                    'country_code' => 'US',
                    'continent_name' => 'North America',
                    'continent_code' => 'NA',
                    'latitude' => 37.75099999999999766941982670687139034271240234375,
                    'longitude' => -97.8220000000000027284841053187847137451171875,
                    'asn' => 'AS3356',
                    'organisation' => 'Level 3 Parent, LLC',
                    'postal' => '',
                    'calling_code' => '1',
                    'flag' => 'https://ipdata.co/flags/us.png',
                    'emoji_flag' => '🇺🇸',
                    'emoji_unicode' => 'U+1F1FA U+1F1F8',
                    'languages' =>
                        (object) array(
                            0 =>
                                array(
                                    'name' => 'English',
                                    'native' => 'English',
                                ),
                        ),
                    'currency' =>
                        (object) array(
                            'name' => 'US Dollar',
                            'code' => 'USD',
                            'symbol' => '$',
                            'native' => '$',
                            'plural' => 'US dollars',
                        ),
                    'time_zone' =>
                        (object) array(
                            'name' => '',
                            'abbr' => '',
                            'offset' => '',
                            'is_dst' => '',
                            'current_time' => '',
                        ),
                    'threat' =>
                        (object) array(
                            'is_tor' => false,
                            'is_proxy' => false,
                            'is_anonymous' => false,
                            'is_known_attacker' => false,
                            'is_known_abuser' => false,
                            'is_threat' => false,
                            'is_bogon' => false,
                        ),
                    'count' => '695',
                ),
            4 =>
                array(
                    'ip' => '5.5.5.5',
                    'is_eu' => true,
                    'city' => '',
                    'region' => '',
                    'region_code' => '',
                    'country_name' => 'Germany',
                    'country_code' => 'DE',
                    'continent_name' => 'Europe',
                    'continent_code' => 'EU',
                    'latitude' => 51.2993000000000023419488570652902126312255859375,
                    'longitude' => 9.4909999999999996589394868351519107818603515625,
                    'asn' => 'AS12638',
                    'organisation' => 'Telefonica Germany',
                    'postal' => '',
                    'calling_code' => '49',
                    'flag' => 'https://ipdata.co/flags/de.png',
                    'emoji_flag' => '🇩🇪',
                    'emoji_unicode' => 'U+1F1E9 U+1F1EA',
                    'languages' =>
                        (object) array(
                            0 =>
                                array(
                                    'name' => 'German',
                                    'native' => 'Deutsch',
                                ),
                        ),
                    'currency' =>
                        (object) array(
                            'name' => 'Euro',
                            'code' => 'EUR',
                            'symbol' => '€',
                            'native' => '€',
                            'plural' => 'euros',
                        ),
                    'time_zone' =>
                        (object) array(
                            'name' => '',
                            'abbr' => '',
                            'offset' => '',
                            'is_dst' => '',
                            'current_time' => '',
                        ),
                    'threat' =>
                        (object) array(
                            'is_tor' => false,
                            'is_proxy' => false,
                            'is_anonymous' => false,
                            'is_known_attacker' => false,
                            'is_known_abuser' => false,
                            'is_threat' => false,
                            'is_bogon' => false,
                        ),
                    'count' => '695',
                ),
            5 =>
                array(
                    'ip' => '6.6.6.6',
                    'is_eu' => false,
                    'city' => '',
                    'region' => '',
                    'region_code' => '',
                    'country_name' => 'United States',
                    'country_code' => 'US',
                    'continent_name' => 'North America',
                    'continent_code' => 'NA',
                    'latitude' => 37.75099999999999766941982670687139034271240234375,
                    'longitude' => -97.8220000000000027284841053187847137451171875,
                    'asn' => '',
                    'organisation' => '',
                    'postal' => '',
                    'calling_code' => '1',
                    'flag' => 'https://ipdata.co/flags/us.png',
                    'emoji_flag' => '🇺🇸',
                    'emoji_unicode' => 'U+1F1FA U+1F1F8',
                    'languages' =>
                        (object) array(
                            0 =>
                                array(
                                    'name' => 'English',
                                    'native' => 'English',
                                ),
                        ),
                    'currency' =>
                        (object) array(
                            'name' => 'US Dollar',
                            'code' => 'USD',
                            'symbol' => '$',
                            'native' => '$',
                            'plural' => 'US dollars',
                        ),
                    'time_zone' =>
                        (object) array(
                            'name' => '',
                            'abbr' => '',
                            'offset' => '',
                            'is_dst' => '',
                            'current_time' => '',
                        ),
                    'threat' =>
                        (object) array(
                            'is_tor' => false,
                            'is_proxy' => false,
                            'is_anonymous' => false,
                            'is_known_attacker' => false,
                            'is_known_abuser' => false,
                            'is_threat' => false,
                            'is_bogon' => false,
                        ),
                    'count' => '695',
                ),
            6 =>
                array(
                    'ip' => '7.7.7.7',
                    'is_eu' => false,
                    'city' => '',
                    'region' => '',
                    'region_code' => '',
                    'country_name' => 'United States',
                    'country_code' => 'US',
                    'continent_name' => 'North America',
                    'continent_code' => 'NA',
                    'latitude' => 37.75099999999999766941982670687139034271240234375,
                    'longitude' => -97.8220000000000027284841053187847137451171875,
                    'asn' => 'AS27651',
                    'organisation' => 'ENTEL CHILE S.A.',
                    'postal' => '',
                    'calling_code' => '1',
                    'flag' => 'https://ipdata.co/flags/us.png',
                    'emoji_flag' => '🇺🇸',
                    'emoji_unicode' => 'U+1F1FA U+1F1F8',
                    'languages' =>
                        (object) array(
                            0 =>
                                array(
                                    'name' => 'English',
                                    'native' => 'English',
                                ),
                        ),
                    'currency' =>
                        (object) array(
                            'name' => 'US Dollar',
                            'code' => 'USD',
                            'symbol' => '$',
                            'native' => '$',
                            'plural' => 'US dollars',
                        ),
                    'time_zone' =>
                        (object) array(
                            'name' => '',
                            'abbr' => '',
                            'offset' => '',
                            'is_dst' => '',
                            'current_time' => '',
                        ),
                    'threat' =>
                        (object) array(
                            'is_tor' => false,
                            'is_proxy' => false,
                            'is_anonymous' => false,
                            'is_known_attacker' => false,
                            'is_known_abuser' => false,
                            'is_threat' => false,
                            'is_bogon' => false,
                        ),
                    'count' => '695',
                ),
            7 =>
                array(
                    'ip' => '8.8.8.8',
                    'is_eu' => false,
                    'city' => '',
                    'region' => '',
                    'region_code' => '',
                    'country_name' => 'United States',
                    'country_code' => 'US',
                    'continent_name' => 'North America',
                    'continent_code' => 'NA',
                    'latitude' => 37.75099999999999766941982670687139034271240234375,
                    'longitude' => -97.8220000000000027284841053187847137451171875,
                    'asn' => 'AS15169',
                    'organisation' => 'Google LLC',
                    'postal' => '',
                    'calling_code' => '1',
                    'flag' => 'https://ipdata.co/flags/us.png',
                    'emoji_flag' => '🇺🇸',
                    'emoji_unicode' => 'U+1F1FA U+1F1F8',
                    'languages' =>
                        (object) array(
                            0 =>
                                array(
                                    'name' => 'English',
                                    'native' => 'English',
                                ),
                        ),
                    'currency' =>
                        (object) array(
                            'name' => 'US Dollar',
                            'code' => 'USD',
                            'symbol' => '$',
                            'native' => '$',
                            'plural' => 'US dollars',
                        ),
                    'time_zone' =>
                        (object) array(
                            'name' => '',
                            'abbr' => '',
                            'offset' => '',
                            'is_dst' => '',
                            'current_time' => '',
                        ),
                    'threat' =>
                        (object) array(
                            'is_tor' => false,
                            'is_proxy' => false,
                            'is_anonymous' => false,
                            'is_known_attacker' => false,
                            'is_known_abuser' => true,
                            'is_threat' => true,
                            'is_bogon' => false,
                        ),
                    'count' => '695',
                ),
            8 =>
                array(
                    'ip' => '9.9.9.9',
                    'is_eu' => true,
                    'city' => '',
                    'region' => '',
                    'region_code' => '',
                    'country_name' => 'France',
                    'country_code' => 'FR',
                    'continent_name' => 'Europe',
                    'continent_code' => 'EU',
                    'latitude' => 48.8581999999999965211827657185494899749755859375,
                    'longitude' => 2.33870000000000022311041902867145836353302001953125,
                    'asn' => 'AS19281',
                    'organisation' => 'Quad9',
                    'postal' => '',
                    'calling_code' => '33',
                    'flag' => 'https://ipdata.co/flags/fr.png',
                    'emoji_flag' => '🇫🇷',
                    'emoji_unicode' => 'U+1F1EB U+1F1F7',
                    'languages' =>
                        (object) array(
                            0 =>
                                array(
                                    'name' => 'French',
                                    'native' => 'Français',
                                ),
                        ),
                    'currency' =>
                        (object) array(
                            'name' => 'Euro',
                            'code' => 'EUR',
                            'symbol' => '€',
                            'native' => '€',
                            'plural' => 'euros',
                        ),
                    'time_zone' =>
                        (object) array(
                            'name' => 'Europe/Paris',
                            'abbr' => 'CEST',
                            'offset' => '+0200',
                            'is_dst' => true,
                            'current_time' => '2018-10-01T10:37:55.043064+02:00',
                        ),
                    'threat' =>
                        (object) array(
                            'is_tor' => false,
                            'is_proxy' => false,
                            'is_anonymous' => false,
                            'is_known_attacker' => false,
                            'is_known_abuser' => false,
                            'is_threat' => false,
                            'is_bogon' => false,
                        ),
                    'count' => '695',
                ),
        );
    }

    /**
     * @param Collection|RequestVO[] $expected
     * @param array $stubOutput
     * @return array
     */
    public static function expectedInDatabase(Collection $expected, $stubOutput)
    {
        $outputKeyedByIp = [];
        $expectedInDatabase = [];

        foreach($stubOutput as $dataForIp){
            $outputKeyedByIp[$dataForIp['ip']] = $dataForIp;
        }

        foreach($expected as $requestVO){

            foreach($outputKeyedByIp as $ip => $outputForIp){
                if($ip === $requestVO->ipAddress){
                    $requestVO->ipLatitude = $outputForIp['latitude'] ?? null;
                    $requestVO->ipLongitude = $outputForIp['longitude'] ?? null;
                    $requestVO->ipCountryCode = $outputForIp['country_code'] ?? null;
                    $requestVO->ipCountryName = $outputForIp['country_name'] ?? null;
                    $requestVO->ipRegion = $outputForIp['region_code'] ?? null;
                    $requestVO->ipCity = $outputForIp['city'] ?? null;
                    $requestVO->ipPostalZipCode = $outputForIp['postal'] ?? null;

                    $requestVO->ipTimezone = $outputForIp['time_zone'] ? $outputForIp['time_zone']->name : null;
                    $requestVO->ipCurrency = $outputForIp['currency'] ? $outputForIp['currency']->code : null;
                }
            }

            $arrayOfExpected = $requestVO->returnArrayForDatabaseInteraction();

            unset($arrayOfExpected['uuid']);
            unset($arrayOfExpected['cookie_id']);
            unset($arrayOfExpected['user_id']);
            unset($arrayOfExpected['response_status_code']);
            unset($arrayOfExpected['response_duration_ms']);
            unset($arrayOfExpected['responded_on']);

            $expectedInDatabase[] = $arrayOfExpected;
        }

        return $expectedInDatabase;
    }
}
