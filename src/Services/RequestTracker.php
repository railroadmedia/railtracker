<?php

namespace Railroad\Railtracker\Services;

use Carbon\Carbon;
use Illuminate\Cache\Repository;
use Illuminate\Http\Request;
use Jenssegers\Agent\Agent;
use Railroad\Railtracker\Models\Agent as AgentModel;
use Railroad\Railtracker\Models\Device;
use Railroad\Railtracker\Models\Domain;
use Railroad\Railtracker\Models\Language;
use Railroad\Railtracker\Models\Path;
use Railroad\Railtracker\Models\Protocol;
use Railroad\Railtracker\Models\Query;
use Railroad\Railtracker\Models\Request as RequestModel;
use Railroad\Railtracker\Models\Route;
use Railroad\Railtracker\Models\Url;
use Ramsey\Uuid\Uuid;

class RequestTracker
{
    const CACHE_TIME = 60 * 60 * 24 * 30; // 30 days

    /**
     * @param Request $request
     * @param Repository|null $cache
     * @return int|mixed
     */
    public function trackRequest(Request $request, Repository $cache = null)
    {
        $agent = new Agent($request->server->all());

        $userId = $this->getAuthenticatedUserId($request);
        $urlId = $this->trackUrl($request->fullUrl(), $cache);
        $refererUrlId = $this->trackUrl($request->headers->get('referer'), $cache);
        $routeId = $this->trackRoute($request, $cache);
        $agentId = $this->trackAgent($agent, $cache);
        $deviceId = $this->trackDevice($agent, $cache);
        $languageId = $this->trackLanguage($agent, $cache);

        return RequestModel::query()->updateOrCreate(
            [
                'uuid' => Uuid::uuid4(),
                'user_id' => $userId,
                'url_id' => $urlId,
                'route_id' => $routeId,
                'device_id' => $deviceId,
                'agent_id' => $agentId,
                'referer_url_id' => $refererUrlId,
                'language_id' => $languageId,
                'geoip_id' => null,
                'client_ip' => substr($this->getClientIp($request), 0, 34),
                'is_robot' => $agent->isRobot(),
                'request_duration_ms' => (microtime(true) - LARAVEL_START) * 100,
                'request_time' => Carbon::now()->timestamp,
            ]
        )->id;
    }

    /**
     * @param $url
     * @param Repository|null $cache
     * @return int|null
     */
    public function trackUrl($url, Repository $cache = null)
    {
        if (empty($url) || parse_url($url) === false) {
            return null;
        }

        $data = [
            'protocol_id' => $this->trackProtocol($url, $cache),
            'domain_id' => $this->trackDomain($url, $cache),
            'path_id' => $this->trackPath($url, $cache),
            'query_id' => $this->trackQuery($url, $cache),
        ];

        $callback = function () use ($data) {
            return Url::query()->updateOrCreate($data)->id;
        };

        if (!is_null($cache)) {
            return $cache->remember(
                'railtracker_url_id_' . serialize($data),
                self::CACHE_TIME,
                $callback
            );
        }

        return $callback();
    }

    /**
     * @param $url
     * @param Repository|null $cache
     * @return int
     */
    public function trackProtocol($url, Repository $cache = null)
    {
        $protocol = parse_url($url)['scheme'] ?? '';

        $data = ['protocol' => substr($protocol, 0, 6)];

        $callback = function () use ($data) {
            return Protocol::query()->updateOrCreate($data)->id;
        };

        if (!is_null($cache)) {
            return $cache->remember(
                md5('railtracker_protocol_id_' . serialize($data)),
                self::CACHE_TIME,
                $callback
            );
        }

        return $callback();
    }

    /**
     * @param $url
     * @param Repository|null $cache
     * @return int
     */
    public function trackDomain($url, Repository $cache = null)
    {
        $domain = parse_url($url)['host'] ?? '';

        $data = ['name' => substr($domain, 0, 170)];

        $callback = function () use ($data) {
            return Domain::query()->updateOrCreate($data)->id;
        };

        if (!is_null($cache)) {
            return $cache->remember(
                md5('railtracker_domain_id_' . serialize($data)),
                self::CACHE_TIME,
                $callback
            );
        }

        return $callback();
    }

    /**
     * @param $url
     * @param Repository|null $cache
     * @return int
     */
    public function trackPath($url, Repository $cache = null)
    {
        $path = parse_url($url)['path'] ?? '';

        if (empty($path)) {
            return null;
        }

        $data = ['path' => substr($path, 0, 170)];

        $callback = function () use ($data) {
            return Path::query()->updateOrCreate($data)->id;
        };

        if (!is_null($cache)) {
            return $cache->remember(
                md5('railtracker_path_id_' . serialize($data)),
                self::CACHE_TIME,
                $callback
            );
        }

        return $callback();
    }

    /**
     * @param $url
     * @param Repository|null $cache
     * @return int
     */
    public function trackQuery($url, Repository $cache = null)
    {
        $query = parse_url($url)['query'] ?? '';

        if (empty($query)) {
            return null;
        }

        $data = ['string' => substr($query, 0, 840)];

        $callback = function () use ($data) {
            return Query::query()->updateOrCreate($data)->id;
        };

        if (!is_null($cache)) {
            return $cache->remember(
                md5('railtracker_query_id_' . serialize($data)),
                self::CACHE_TIME,
                $callback
            );
        }

        return $callback();
    }

    /**
     * @param Request $request
     * @param Repository|null $cache
     * @return int|null
     */
    public function trackRoute(Request $request, Repository $cache = null)
    {
        if (empty($request->route()) ||
            empty($request->route()->getName()) ||
            empty($request->route()->getActionName())
        ) {
            return null;
        }

        $data = [
            'name' => substr($request->route()->getName(), 0, 170),
            'action' => substr($request->route()->getActionName(), 0, 170),
        ];

        $callback = function () use ($data) {
            return Route::query()->updateOrCreate($data)->id;
        };

        if (!is_null($cache)) {
            return $cache->remember(
                md5('railtracker_route_id_' . serialize($data)),
                self::CACHE_TIME,
                $callback
            );
        }

        return $callback();
    }

    /**
     * @param Agent $agent
     * @param Repository|null $cache
     * @return int
     */
    public function trackDevice(Agent $agent, Repository $cache = null)
    {
        $data = [
            'platform' => substr($agent->platform(), 0, 64),
            'platform_version' => substr($agent->version($agent->platform()), 0, 16),
            'kind' => substr($this->getDeviceKind($agent), 0, 16),
            'model' => substr($agent->device(), 0, 64),
            'is_mobile' => $agent->isMobile(),
        ];

        $callback = function () use ($data) {
            return Device::query()->updateOrCreate($data)->id;
        };

        if (!is_null($cache)) {
            return $cache->remember(
                md5('railtracker_device_id_' . serialize($data)),
                self::CACHE_TIME,
                $callback
            );
        }

        return $callback();
    }

    /**
     * @param Agent $agent
     * @param Repository|null $cache
     * @return int
     */
    public function trackAgent(Agent $agent, Repository $cache = null)
    {
        $data = [
            'name' => substr($agent->getUserAgent() ?: 'Other', 0, 180),
            'browser' => substr($agent->browser(), 0, 64),
            'browser_version' => substr($agent->version($agent->browser()), 0, 32),
        ];

        $callback = function () use ($data) {
            return AgentModel::query()->updateOrCreate($data)->id;
        };

        if (!is_null($cache)) {
            return $cache->remember(
                md5('railtracker_agent_id_' . serialize($data)),
                self::CACHE_TIME,
                $callback
            );
        }

        return $callback();
    }

    /**
     * @param Agent $agent
     * @param Repository|null $cache
     * @return int
     */
    public function trackLanguage(Agent $agent, Repository $cache = null)
    {
        $data = [
            'preference' => substr($agent->languages()[0] ?? 'en', 0, 12),
            'language_range' => substr(implode(',', $agent->languages()), 0, 180),
        ];

        $callback = function () use ($data) {
            return Language::query()->updateOrCreate($data)->id;
        };

        if (!is_null($cache)) {
            return $cache->remember(
                md5('railtracker_language_id_' . serialize($data)),
                self::CACHE_TIME,
                $callback
            );
        }

        return $callback();
    }

    /**
     * @param Request $request
     * @return int|null
     */
    protected function getAuthenticatedUserId(Request $request)
    {
        return $request->user()->id ?? null;
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
     * @param Request $request
     * @return string
     */
    protected function getClientIp(Request $request)
    {
        if (!empty($request->server('HTTP_CLIENT_IP'))) {
            $ip = $request->server('HTTP_CLIENT_IP');
        } elseif (!empty($request->server('HTTP_X_FORWARDED_FOR'))) {
            $ip = $request->server('HTTP_X_FORWARDED_FOR');
        } else {
            $ip = $request->server('REMOTE_ADDR');
        }

        return explode(',', $ip)[0] ?? '';
    }
}