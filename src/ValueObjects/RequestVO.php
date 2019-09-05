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
    public $routeNameHash;
    public $routeActionHash;
    public $agentStringHash;
    public $exceptionClassHash;
    public $exceptionFileHash;

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

        $this->urlProtocol = substr(parse_url($fullUrl)['scheme'], 0, 32);
        $this->urlDomain = substr(parse_url($fullUrl)['host'], 0, 128);
        $this->urlPath = !empty(parse_url($fullUrl)['path']) ? substr(parse_url($fullUrl)['path'], 0, 191) : '/';
        $this->urlQuery = !empty(parse_url($fullUrl)['query']) ? substr(parse_url($fullUrl)['query'], 0, 1280) : null;

        // method
        $this->method = substr($httpRequest->method(), 0, 10);

        // route
        if (!empty($httpRequest->route())) {
            $this->routeName = substr($httpRequest->route()->getName(), 0, 840);
            $this->routeAction = substr($httpRequest->route()->getActionName(), 0, 840);
        }

        // device
        $this->deviceKind = $this->getDeviceKind($userAgentObject);
        $this->deviceModel = substr($userAgentObject->device(), 0, 64);
        $this->devicePlatform = substr($userAgentObject->platform(), 0, 64);
        $platform = substr($userAgentObject->version($userAgentObject->platform()), 0, 64);
        $this->deviceVersion = !empty($platform) ? $platform : null;
        $this->deviceIsMobile = $userAgentObject->isMobile() ? 1 : 0;

        // agent
        $this->agentString = substr($userAgentObject->getUserAgent() ?: 'Other', 0, 560);
        $this->agentBrowser = substr($userAgentObject->browser(), 0, 64);
        $this->agentBrowserVersion = substr($userAgentObject->version($userAgentObject->browser()), 0, 64);
        $this->isRobot = $userAgentObject->isRobot() ? 1 : 0;

        // referer url
        $fullRefererUrl = $httpRequest->headers->get('referer');

        if(!empty($fullRefererUrl)){
            $this->refererUrlProtocol = substr(parse_url($fullRefererUrl)['scheme'], 0, 32);
            $this->refererUrlDomain = substr(parse_url($fullRefererUrl)['host'], 0, 128);
            $this->refererUrlPath = !empty(parse_url($fullRefererUrl)['path']) ?
                substr(parse_url($fullRefererUrl)['path'], 0, 191) : '/';
            $this->refererUrlQuery = !empty(parse_url($fullRefererUrl)['query']) ?
                substr(parse_url($fullRefererUrl)['query'], 0, 1280) : null;
        }else{
            $this->refererUrlProtocol = null;
            $this->refererUrlDomain = null;
            $this->refererUrlPath = null;
            $this->refererUrlQuery = null;
        }

        // language
        $this->languagePreference = substr($userAgentObject->languages()[0] ?? 'en', 0, 10);
        $this->languageRange = substr(implode(',', $userAgentObject->languages()), 0, 64);

        // ip address
        $this->ipAddress = $this->getClientIp($httpRequest);

        // requested on
        $this->requestedOn = Carbon::now()->format(self::$TIME_FORMAT);

        // set hash fields
        $this->urlQueryHash = md5($this->urlQuery);
        $this->refererUrlQueryHash = md5($this->refererUrlQuery);
        $this->routeNameHash = md5($this->routeName);
        $this->routeActionHash = md5($this->routeAction);
        $this->agentStringHash = md5($this->agentString);
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
        if (!empty(config('railtracker.ip-api.test-ip'))) {
            return config('railtracker.ip-api.test-ip');
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
        $array = [
            'uuid' => $this->uuid,
            'cookie_id' => $this->cookieId,
            'user_id' => $this->userId,
            'url_protocol' => $this->urlProtocol,
            'url_domain' => $this->urlDomain,
            'url_path' => $this->urlPath,
            'url_query' => $this->urlQuery,
            'method' => $this->method,
            'route_name' => $this->routeName,
            'route_action' => $this->routeAction,
            'device_kind' => $this->deviceKind,
            'device_model' => $this->deviceModel,
            'device_platform' => $this->devicePlatform,
            'device_version' => $this->deviceVersion,
            'device_is_mobile' => $this->deviceIsMobile,
            'agent_string' => $this->agentString,
            'agent_browser' => $this->agentBrowser,
            'agent_browser_version' => $this->agentBrowserVersion,
            'referer_url_protocol' => $this->refererUrlProtocol,
            'referer_url_domain' => $this->refererUrlDomain,
            'referer_url_path' => $this->refererUrlPath,
            'referer_url_query' => $this->refererUrlQuery,
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
            'exception_class' => $this->exceptionClass,
            'exception_file' => $this->exceptionFile,
            'exception_message' => $this->exceptionMessage,
            'exception_trace' => $this->exceptionTrace,

            'requested_on' => $this->requestedOn,
            'response_status_code' => $this->responseStatusCode,
            'response_duration_ms' => $this->responseDurationMs,
            'responded_on' => $this->respondedOn,

            'url_query_hash' => $this->urlQueryHash,
            'referer_url_query_hash' => $this->refererUrlQueryHash,
            'route_name_hash' => $this->routeNameHash,
            'route_action_hash' => $this->routeActionHash,
            'agent_string_hash' => $this->agentStringHash,
            'exception_class_hash' => $this->exceptionClassHash,
            'exception_file_hash' => $this->exceptionFileHash,
        ];

        return $array;
    }
}