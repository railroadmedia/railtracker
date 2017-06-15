<?php

namespace Railroad\Railtracker\Trackers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Jenssegers\Agent\Agent;
use Railroad\Railtracker\Services\ConfigService;
use Ramsey\Uuid\Uuid;

class RequestTracker extends TrackerBase
{
    /**
     * @var int|null
     */
    public static $lastTrackedRequestId;

    /**
     * @param Request $serverRequest
     * @return int|mixed
     */
    public function trackRequest(Request $serverRequest)
    {
        $agent = new Agent($serverRequest->server->all());

        $userId = $this->getAuthenticatedUserId($serverRequest);
        $urlId = $this->trackUrl($serverRequest->fullUrl());
        $refererUrlId = $this->trackUrl($serverRequest->headers->get('referer'));
        $routeId = $this->trackRoute($serverRequest);
        $methodId = $this->trackMethod($serverRequest->method());
        $agentId = $this->trackAgent($agent);
        $deviceId = $this->trackDevice($agent);
        $languageId = $this->trackLanguage($agent);

        $requestId = $this->query(ConfigService::$tableRequests)->insertGetId(
            [
                'uuid' => Uuid::uuid4(),
                'user_id' => $userId,
                'url_id' => $urlId,
                'route_id' => $routeId,
                'device_id' => $deviceId,
                'agent_id' => $agentId,
                'method_id' => $methodId,
                'referer_url_id' => $refererUrlId,
                'language_id' => $languageId,
                'geoip_id' => null,
                'client_ip' => substr($this->getClientIp($serverRequest), 0, 64),
                'is_robot' => $agent->isRobot(),
                'requested_on' => Carbon::now()->toDateTimeString(),
            ]
        );

        self::$lastTrackedRequestId = $requestId;

        return $requestId;
    }

    /**
     * @param string $url
     * @return int|null
     */
    public function trackUrl($url)
    {
        if (empty($url) || parse_url($url) === false) {
            return null;
        }

        $data = [
            'protocol_id' => $this->trackProtocol($url),
            'domain_id' => $this->trackDomain($url),
            'path_id' => $this->trackPath($url),
            'query_id' => $this->trackQuery($url),
        ];

        return $this->store($data, ConfigService::$tableUrls);
    }

    /**
     * @param $url
     * @return int
     */
    public function trackProtocol($url)
    {
        $protocol = parse_url($url)['scheme'] ?? '';

        $data = ['protocol' => substr($protocol, 0, 6)];

        return $this->store($data, ConfigService::$tableUrlProtocols);
    }

    /**
     * @param $url
     * @return int
     */
    public function trackDomain($url)
    {
        $domain = parse_url($url)['host'] ?? '';

        $data = ['name' => substr($domain, 0, 180)];

        return $this->store($data, ConfigService::$tableUrlDomains);
    }

    /**
     * @param $url
     * @return int|null
     */
    public function trackPath($url)
    {
        $path = parse_url($url)['path'] ?? '';

        if (empty($path)) {
            return null;
        }

        $data = ['path' => substr($path, 0, 180)];

        return $this->store($data, ConfigService::$tableUrlPaths);
    }

    /**
     * @param $url
     * @return int|null
     */
    public function trackQuery($url)
    {
        $query = parse_url($url)['query'] ?? '';

        if (empty($query)) {
            return null;
        }

        $data = ['string' => substr($query, 0, 840)];

        return $this->store($data, ConfigService::$tableUrlQueries);
    }

    /**
     * @param Request $serverRequest
     * @return int|null
     */
    public function trackRoute(Request $serverRequest)
    {
        if (empty($serverRequest->route()) ||
            empty($serverRequest->route()->getName()) ||
            empty($serverRequest->route()->getActionName())
        ) {
            return null;
        }

        $data = [
            'name' => substr($serverRequest->route()->getName(), 0, 170),
            'action' => substr($serverRequest->route()->getActionName(), 0, 170),
        ];

        return $this->store($data, ConfigService::$tableRoutes);
    }

    /**
     * @param Agent $agent
     * @return int
     */
    public function trackDevice(Agent $agent)
    {
        $data = [
            'platform' => substr($agent->platform(), 0, 64),
            'platform_version' => substr($agent->version($agent->platform()), 0, 16),
            'kind' => substr($this->getDeviceKind($agent), 0, 16),
            'model' => substr($agent->device(), 0, 64),
            'is_mobile' => $agent->isMobile(),
        ];

        return $this->store($data, ConfigService::$tableRequestDevices);
    }

    /**
     * @param Agent $agent
     * @return int
     */
    public function trackAgent(Agent $agent)
    {
        $data = [
            'name' => substr($agent->getUserAgent() ?: 'Other', 0, 180),
            'browser' => substr($agent->browser(), 0, 64),
            'browser_version' => substr($agent->version($agent->browser()), 0, 32),
        ];

        return $this->store($data, ConfigService::$tableRequestAgents);
    }

    /**
     * @param $method
     * @return int
     */
    public function trackMethod($method)
    {
        $data = [
            'method' => substr($method, 0, 8),
        ];

        return $this->store($data, ConfigService::$tableRequestMethods);
    }

    /**
     * @param Agent $agent
     * @return int
     */
    public function trackLanguage(Agent $agent)
    {
        $data = [
            'preference' => substr($agent->languages()[0] ?? 'en', 0, 12),
            'language_range' => substr(implode(',', $agent->languages()), 0, 180),
        ];

        return $this->store($data, ConfigService::$tableRequestLanguages);
    }

    /**
     * @param Request $serverRequest
     * @return int|null
     */
    protected function getAuthenticatedUserId(Request $serverRequest)
    {
        return $serverRequest->user()->id ?? null;
    }

    /**
     * @param Agent $agent
     * @return string
     */
    protected function getDeviceKind(Agent $agent)
    {
        $kind = 'unavailable';

        if ($agent->isTablet()) {
            $kind = 'tablet';
        } elseif ($agent->isPhone()) {
            $kind = 'phone';
        } elseif ($agent->isDesktop()) {
            $kind = 'desktop';
        }

        return $kind;
    }

    /**
     * @param Request $serverRequest
     * @return string
     */
    protected function getClientIp(Request $serverRequest)
    {
        if (!empty($serverRequest->server('HTTP_CLIENT_IP'))) {
            $ip = $serverRequest->server('HTTP_CLIENT_IP');
        } elseif (!empty($serverRequest->server('HTTP_X_FORWARDED_FOR'))) {
            $ip = $serverRequest->server('HTTP_X_FORWARDED_FOR');
        } else {
            $ip = $serverRequest->server('REMOTE_ADDR');
        }

        return explode(',', $ip)[0] ?? '';
    }
}