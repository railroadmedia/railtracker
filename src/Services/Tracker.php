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

class Tracker
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
                'client_ip' => $request->getClientIp(),
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

        $data = ['protocol' => $protocol];

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

        $data = ['string' => $query];

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
    protected function trackRoute(Request $request, Repository $cache = null)
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
    protected function trackDevice(Agent $agent, Repository $cache = null)
    {
        $data = [
            'platform' => $agent->platform(),
            'platform_version' => $agent->version($agent->platform()),
            'kind' => $this->getDeviceKind($agent),
            'model' => $agent->device(),
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
            'name' => substr($agent->getUserAgent() ?: 'Other', 0, 170),
            'browser' => $agent->browser(),
            'browser_version' => $agent->version($agent->browser()),
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
            'preference' => $agent->languages()[0] ?? 'en',
            'language_range' => implode(',', $agent->languages()),
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

    private function alphaNumDashOnly($string)
    {
        return preg_replace('/[^a-z0-9]+/', '-', strtolower($string));
    }
}