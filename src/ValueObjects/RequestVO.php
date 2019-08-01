<?php

namespace Railroad\Railtracker\ValueObjects;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
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
    public $requestedOn;
    public $respondedOn;

    public $tStart;

    /**
     * RequestVO constructor.
     */
    public function __construct(Request $httpRequest)
    {
        $userAgentObject = new Agent($httpRequest->server->all());

        // start time in microseconds
        $this->tStart = microtime(true);

        // uuid
        $this->uuid = Uuid::uuid4()->toString();

        // cookie id
        $this->cookieId = $httpRequest->cookie(self::$visitorCookieKey);

        // user id
        $this->userId = auth()->id();

        // url
        $fullUrl = $httpRequest->fullUrl();

        $this->urlProtocol = substr(parse_url($fullUrl)['scheme'], 0, 32);
        $this->urlDomain = substr(parse_url($fullUrl)['host'], 0, 128);
        $this->urlPath = substr(parse_url($fullUrl)['path'] ?? null, 0, 512);
        $this->urlQuery = substr(parse_url($fullUrl)['query'] ?? null, 0, 1280);

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
        $this->deviceVersion = substr($userAgentObject->version($userAgentObject->platform()), 0, 64);
        $this->deviceIsMobile = $userAgentObject->isMobile();

        // agent
        $this->agentString = substr($userAgentObject->getUserAgent() ?: 'Other', 0, 560);
        $this->agentBrowser = substr($userAgentObject->browser(), 0, 64);
        $this->agentBrowserVersion = substr($userAgentObject->version($userAgentObject->browser()), 0, 64);
        $this->isRobot = $userAgentObject->isRobot();

        // referer url
        $fullRefererUrl = $httpRequest->headers->get('referer');

        $this->refererUrlProtocol = substr(parse_url($fullRefererUrl)['scheme'], 0, 32);
        $this->refererUrlDomain = substr(parse_url($fullRefererUrl)['host'], 0, 128);
        $this->refererUrlPath = substr(parse_url($fullRefererUrl)['path'] ?? null, 0, 512);
        $this->refererUrlQuery = substr(parse_url($fullRefererUrl)['query'] ?? null, 0, 1280);

        // language
        $this->languagePreference = substr($userAgentObject->languages()[0] ?? 'en', 0, 10);
        $this->languageRange = substr(implode(',', $userAgentObject->languages()), 0, 64);

        // ip address
        $this->ipAddress = $this->getClientIp($httpRequest);

        // requested on
        $this->requestedOn = Carbon::now()->format('Y-m-d H:i:s.u');

    }

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
}