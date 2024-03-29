<?php

namespace Railroad\Railtracker\ValueObjects;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Jenssegers\Agent\Agent;
use Ramsey\Uuid\Uuid;
use Symfony\Component\HttpFoundation\Response as BaseResponse;

class RequestVO
{
    public static $visitorCookieKey = 'railtracker_visitor';

    public $id;
    public $uuid;
    public $cookieId;
    public $userId;
    public $urlProtocol;
    public $urlDomain;
    public $urlPath;
    public $urlQuery;
    public $method;
    public $routeName;
    public $routeAction;
    public $deviceKind;
    public $deviceModel;
    public $devicePlatform;
    public $deviceVersion;
    public $deviceIsMobile;
    public $agentString;
    public $agentBrowser;
    public $agentBrowserVersion;
    public $refererUrlProtocol;
    public $refererUrlDomain;
    public $refererUrlPath;
    public $refererUrlQuery;
    public $languagePreference;
    public $languageRange;
    public $ipAddress;
    public $ipLatitude;
    public $ipLongitude;
    public $ipCountryCode;
    public $ipCountryName;
    public $ipRegion;
    public $ipCity;
    public $ipPostalZipCode;
    public $ipTimezone;
    public $ipCurrency;
    public $isRobot;
    public $responseStatusCode;
    public $responseDurationMs;

    public $exceptionCode;
    public $exceptionLine;
    public $exceptionClass;
    public $exceptionFile;
    public $exceptionMessage;
    public $exceptionTrace;

    public $urlQueryHash;
    public $refererUrlQueryHash;
    public $routeActionHash;
    public $agentStringHash;
    public $exceptionClassHash;
    public $exceptionFileHash;
    public $exceptionMessageHash;
    public $exceptionTraceHash;

    public $requestedOn;
    public $respondedOn;

    public $tStart;

    public static $TIME_FORMAT = 'Y-m-d H:i:s.u';

    public static $UUID;

    /**
     * RequestVO constructor.
     * @param Request $httpRequest
     * @throws \Exception
     */
    public function __construct(Request $httpRequest)
    {
        /* - - - - - - - - - - - - - - - - - - - -
            all fields nullable *except* these:
                1. uuid
                2. url_protocol
                3. url_domain
                4. url_path
                5. device_is_mobile
                6. is_robot
                7. requested_on
         - - - - - - - - - - - - - - - - - - - - */

        // todo
//        $setUnlessNull = function($value, $maxLength){
//            return !empty($value) ? substr($value, 0, $maxLength) : null;
//        };

        $userAgentObject = new Agent($httpRequest->server->all());

        // start time in microseconds
        $this->tStart = microtime(true);

        // uuid
        $this->uuid = Uuid::uuid4()->toString();
        self::$UUID = $this->uuid;

        // cookie id
        $this->cookieId = $httpRequest->cookie(self::$visitorCookieKey);

        // user id
        $this->userId = auth()->id();

        // url
        $fullUrl = $httpRequest->fullUrl();

        if(empty($fullUrl)) {
            $msg = 'Url not available in RequestVO constructor. (Request object: ' .
                var_export($httpRequest, true) . ')';
            error_log($msg);
            throw new \Exception($msg);
        }

        $this->urlProtocol = substr(parse_url($fullUrl)['scheme'], 0, 32);
        $this->urlDomain = substr(parse_url($fullUrl)['host'], 0, 128);
        $this->urlPath = !empty(parse_url($fullUrl)['path']) ? substr(parse_url($fullUrl)['path'], 0, 191) : '/';
        $this->urlQuery = !empty(parse_url($fullUrl)['query']) ? substr(parse_url($fullUrl)['query'], 0, 1280) : null;

        // method
        if(!empty($httpRequest->method())){
            $this->method = substr($httpRequest->method(), 0, 10);
        }

        // route
        if (!empty($httpRequest->route())) {
            if(!empty($httpRequest->route()->getName())){
                $this->routeName = substr($httpRequest->route()->getName(), 0, 191);
            }
            if(!empty($httpRequest->route()->getActionName())){
                $this->routeAction = substr($httpRequest->route()->getActionName(), 0, 840);
            }
        }

        // device
        if(!empty($this->getDeviceKind($userAgentObject))){
            $this->deviceKind = $this->getDeviceKind($userAgentObject);
        }

        if(!empty($userAgentObject->device())){
            $this->deviceModel = substr($userAgentObject->device(), 0, 64);
        }
        if(!empty($userAgentObject->platform())){
            $this->devicePlatform = substr($userAgentObject->platform(), 0, 64);
        }

        if(!empty($userAgentObject->version($userAgentObject->platform()))){
            $platform = substr($userAgentObject->version($userAgentObject->platform()), 0, 64);
            $this->deviceVersion = !empty($platform) ? $platform : null;
        }

        if(is_bool($userAgentObject->isMobile())) {
            $this->deviceIsMobile = $userAgentObject->isMobile() ? 1 : 0;
        }else{
            error_log(
                '"$userAgentObject->isMobile()" is not a boolean in RequestVO constructor. We\'ll set it as true ' .
                'here (Request object: ' . var_export($httpRequest, true) . ')'
            );
            $this->deviceIsMobile = 1;
        }

        // agent
        if(!empty($userAgentObject->getUserAgent())){
            $this->agentString = substr($userAgentObject->getUserAgent() ?: 'Other', 0, 560);
        }
        if(!empty($userAgentObject->browser())){
            $this->agentBrowser = substr($userAgentObject->browser(), 0, 64);
        }
        if(!empty($userAgentObject->version($userAgentObject->browser()))){
            $this->agentBrowserVersion = substr($userAgentObject->version($userAgentObject->browser()), 0, 64);
        }

        if(is_bool($userAgentObject->isRobot())) {
            $this->isRobot = $userAgentObject->isRobot() ? 1 : 0;
        }else{
            error_log(
                '"$userAgentObject->isRobot()" is not a boolean in RequestVO constructor. We\'ll set it as false ' .
                'here (Request object: ' . var_export($httpRequest, true) . ')'
            );
            $this->isRobot = 0;
        }

        if(!empty($httpRequest->headers->get('referer'))){

            $fullRefererUrl = $httpRequest->headers->get('referer');

            if(!empty($fullRefererUrl)){
                if(!empty(parse_url($fullRefererUrl)['scheme'])){
                    $this->refererUrlProtocol = substr(parse_url($fullRefererUrl)['scheme'], 0, 32);
                }
                if(!empty(parse_url($fullRefererUrl)['host'])){
                    $this->refererUrlDomain = substr(parse_url($fullRefererUrl)['host'], 0, 128);
                }
                if(!empty(parse_url($fullRefererUrl)['path'])){
                   $this->refererUrlPath = !empty(parse_url($fullRefererUrl)['path']) ?
                    substr(parse_url($fullRefererUrl)['path'], 0, 191) : '/';
                }
                if(!empty(parse_url($fullRefererUrl)['query'])){
                   $this->refererUrlQuery = !empty(parse_url($fullRefererUrl)['query']) ?
                    substr(parse_url($fullRefererUrl)['query'], 0, 1280) : null;
                }
            }else{
                $this->refererUrlProtocol = null;
                $this->refererUrlDomain = null;
                $this->refererUrlPath = null;
                $this->refererUrlQuery = null;
            }
        }

        // language

        if(!empty($userAgentObject->languages()[0])){
            $this->languagePreference = substr($userAgentObject->languages()[0] ?? 'en', 0, 10);
        }

        if(!empty($userAgentObject->languages())){
            $this->languageRange = substr(implode(',', $userAgentObject->languages()), 0, 64);
        }

        // ip address
        if(!empty($this->getClientIp($httpRequest))){
            $this->ipAddress = $this->getClientIp($httpRequest);
        }

        // requested on
        $this->requestedOn = Carbon::now()->format(self::$TIME_FORMAT);

        // set hash fields
        $setHashUnlessNull = function($value){
            return !empty($value) ? md5($value) : null;
        };

        $this->urlQueryHash         = $setHashUnlessNull($this->urlQuery);
        $this->refererUrlQueryHash  = $setHashUnlessNull($this->refererUrlQuery);
        $this->routeActionHash      = $setHashUnlessNull($this->routeAction);
        $this->agentStringHash      = $setHashUnlessNull($this->agentString);
    }

    /**
     * @param BaseResponse $response
     */
    public function setResponseData(BaseResponse $response)
    {
        $this->responseStatusCode = $response->getStatusCode();
        $this->responseDurationMs = round((microtime(true) - $this->tStart) * 1000);
        $this->respondedOn = Carbon::now()->format('Y-m-d H:i:s.u');
    }

    /**
     * @param Agent $agent
     * @return string|null
     */
    protected function getDeviceKind(Agent $agent)
    {
        $kind = null;

        if ($agent->isTablet()) {
            $kind = 'tablet';
        }
        elseif ($agent->isPhone()) {
            $kind = 'phone';
        }
        elseif ($agent->isDesktop()) {
            $kind = 'desktop';
        }

        return $kind;
    }

    /**
     * @param Request $request
     * @return string|null
     */
    protected function getClientIp(Request $request)
    {
        if (!empty(config('railtracker.test-ip'))) {
            return config('railtracker.test-ip');
        }

        if (!empty($request->server('HTTP_X_ORIGINAL_FORWARDED_FOR'))) {
            $ip = $request->server('HTTP_X_ORIGINAL_FORWARDED_FOR');
        }
        elseif (!empty($request->server('HTTP_X_FORWARDED_FOR'))) {
            $ip = $request->server('HTTP_X_FORWARDED_FOR');
        }
        elseif (!empty($request->server('HTTP_CLIENT_IP'))) {
            $ip = $request->server('HTTP_CLIENT_IP');
        }
        else {
            $ip = $request->server('REMOTE_ADDR');
        }

        return explode(',', $ip)[0] ?? null;
    }

    /**
     * @return array
     */
    public function returnArrayForDatabaseInteraction()
    {
        /*
         * Note that url_query, route_action, agent_string, referer_url_query, exception_class, exception_file,
         * exception_message, and exception_trace are not included. They're too long to index, and thus cannot be
         * linked via a foreign key constraint. Thus, in the requests table, we save them as a hash linked to a table
         * that more more efficiently contains the full value (because the hashes are a unique index in those tables)
         *
         * Jonathan, September 2019
         */

        $array = [
            'uuid' => $this->uuid,
            'cookie_id' => $this->cookieId,
            'user_id' => $this->userId,
            'url_protocol' => $this->urlProtocol,
            'url_domain' => $this->urlDomain,
            'url_path' => $this->urlPath,
            'method' => $this->method,
            'route_name' => $this->routeName,
            'device_kind' => $this->deviceKind,
            'device_model' => $this->deviceModel,
            'device_platform' => $this->devicePlatform,
            'device_version' => $this->deviceVersion,
            'device_is_mobile' => $this->deviceIsMobile,
            'agent_browser' => $this->agentBrowser,
            'agent_browser_version' => $this->agentBrowserVersion,
            'referer_url_protocol' => $this->refererUrlProtocol,
            'referer_url_domain' => $this->refererUrlDomain,
            'referer_url_path' => $this->refererUrlPath,
            'language_preference' => $this->languagePreference,
            'language_range' => $this->languageRange,
            'ip_address' => $this->ipAddress,
            'ip_latitude' => $this->ipLatitude,
            'ip_longitude' => $this->ipLongitude,
            'ip_country_code' => $this->ipCountryCode,
            'ip_country_name' => $this->ipCountryName,
            'ip_region' => $this->ipRegion,
            'ip_city' => $this->ipCity,
            'ip_postal_zip_code' => $this->ipPostalZipCode,
            'ip_timezone' => $this->ipTimezone,
            'ip_currency' => $this->ipCurrency,
            'is_robot' => $this->isRobot,

            'exception_code' => $this->exceptionCode,
            'exception_line' => $this->exceptionLine,

            'requested_on' => $this->requestedOn,
            'response_status_code' => $this->responseStatusCode,
            'response_duration_ms' => $this->responseDurationMs,
            'responded_on' => $this->respondedOn,

            'url_query_hash' => $this->urlQueryHash,
            'referer_url_query_hash' => $this->refererUrlQueryHash,
            'route_action_hash' => $this->routeActionHash,
            'agent_string_hash' => $this->agentStringHash,
            'exception_class_hash' => $this->exceptionClassHash,
            'exception_file_hash' => $this->exceptionFileHash,
            'exception_message_hash' => $this->exceptionMessageHash,
            'exception_trace_hash' => $this->exceptionTraceHash,
        ];

        return $array;
    }
}