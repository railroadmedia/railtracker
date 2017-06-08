<?php

namespace Railroad\Railtracker\Services;

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
use Railroad\Railtracker\Models\Referer;
use Railroad\Railtracker\Models\Request as RequestModel;
use Railroad\Railtracker\Models\Route;
use Railroad\Railtracker\Models\Url;
use Ramsey\Uuid\Uuid;

class Tracker
{
    const CACHE_TIME = 60 * 60 * 24 * 30; // 30 days

    /**
     * @param Request $request
     * @return mixed
     */
    public function trackRequest(Request $request, Repository $cache = null): int
    {
        $agent = new Agent($request->server->all());

        $urlId = $this->trackUrl($request->fullUrl());

//        return RequestModel::query()->updateOrCreate(
//            [
//                'uuid' => Uuid::uuid4(),
//                'user_id' => $userId,
//                'domain_id' => $domainId,
//                'device_id' => $deviceId,
//                'client_ip' => $request->getClientIp(),
//                'geoip_id' => null, // this can be calculated afterwards during data analysis
//                'agent_id' => $agentId,
//                'referer_id' => $referrerId,
//                'language_id' => $languageId,
//                'is_robot' => $agent->isRobot()
//            ]
//        )->id;

        return 1;
    }

    /**
     * @param $url
     * @param Repository|null $cache
     * @return int
     */
    public function trackUrl($url, Repository $cache = null)
    {
        $protocolId = $this->trackProtocol($url, $cache);
        $domainId = $this->trackDomain($url, $cache);
        $pathId = $this->trackPath($url, $cache);
        $queryId = $this->trackQuery($url, $cache);

        $callback = function () use ($protocolId, $domainId, $pathId, $queryId) {
            return Url::query()->updateOrCreate(
                [
                    'protocol_id' => $protocolId,
                    'domain_id' => $domainId,
                    'path_id' => $pathId,
                    'query_id' => $queryId,
                ]
            )->id;
        };

        if (!is_null($cache)) {
            return $cache->remember(
                'url-id-' . $protocolId . '-' . $domainId . '-' . $pathId . '-' . $queryId,
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
        $urlParts = parse_url($url);
        $protocol = $urlParts['scheme'] ?? '';

        $callback = function () use ($protocol) {
            return Protocol::query()->updateOrCreate(
                [
                    'protocol' => $protocol
                ]
            )->id;
        };

        if (!is_null($cache)) {
            return $cache->remember(
                'protocol-id-' . $protocol,
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
        $urlParts = parse_url($url);
        $domain = $urlParts['host'] ?? '';

        $callback = function () use ($domain) {
            return Domain::query()->updateOrCreate(
                [
                    'name' => $domain
                ]
            )->id;
        };

        if (!is_null($cache)) {
            return $cache->remember(
                'domain-id-' . $domain,
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
        $urlParts = parse_url($url);
        $path = $urlParts['path'] ?? '';

        if (empty($path)) {
            return null;
        }

        $callback = function () use ($path) {
            return Path::query()->updateOrCreate(
                [
                    'path' => $path
                ]
            )->id;
        };

        if (!is_null($cache)) {
            return $cache->remember(
                'path-id-' . preg_replace('/[^a-z0-9]+/', '-', strtolower($path)),
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
        $urlParts = parse_url($url);
        $query = $urlParts['query'] ?? '';

        if (empty($query)) {
            return null;
        }

        $callback = function () use ($query) {
            return Query::query()->updateOrCreate(
                [
                    'string' => $query
                ]
            )->id;
        };

        if (!is_null($cache)) {
            return $cache->remember(
                'query-id-' . preg_replace('/[^a-z0-9]+/', '-', strtolower($query)),
                self::CACHE_TIME,
                $callback
            );
        }

        return $callback();
    }

    /**
     * @param Request $request
     * @return int
     */
    protected function trackRoute(Request $request): int
    {
        return Route::query()->updateOrCreate(
            [
                'name' => $request->route()->getName(),
                'action' => $request->route()->getActionName(),
            ]
        )->id;
    }

    /**
     * @param Agent $agent
     * @return int
     */
    protected function trackDevice(Agent $agent): int
    {
        return Device::query()->updateOrCreate(
            [
                'platform' => $agent->platform(),
                'platform_version' => $agent->version($agent->platform()),
                'kind' => $this->getDeviceKind($agent),
                'model' => $agent->device(),
                'is_mobile' => $agent->isMobile(),
            ]
        )->id;
    }

    /**
     * @param Agent $agent
     * @return int
     */
    public function trackAgent(Agent $agent): int
    {
        return AgentModel::query()->updateOrCreate(
            [
                'name' => $agent->getUserAgent() ?: 'Other',
                'browser' => $agent->browser(),
                'browser_version' => $agent->version($agent->browser()),
            ]
        )->id;
    }

    /**
     * @param string $refererUri
     * @param int $refererDomainId
     * @return int
     */
    public function trackReferer(string $refererUri, int $refererDomainId): int
    {
        $urlParts = parse_url($refererUri);

        // medium, source, and search terms should be calculated later based on needs during data analysis

        return Referer::query()->updateOrCreate(
            [
                'url' => $refererUri,
                'host' => $urlParts['host'],
                'domain_id' => $refererDomainId,
                'medium' => null,
                'source' => null,
                'search_terms_hash' => null,
            ]
        )->id;
    }

    /**
     * @param Agent $agent
     * @return int
     */
    public function trackLanguage(Agent $agent): int
    {
        return Language::query()->updateOrCreate(
            [
                'preference' => $agent->languages()[0] ?? 'en',
                'language-range' => implode(',', $agent->languages()),
            ]
        )->id;
    }

    /**
     * @param Request $request
     * @return int|null
     */
    protected function getAuthenticatedUserId(Request $request): ?int
    {
        return $request->user()->id ?? null;
    }

    /**
     * @param Agent $agent
     * @return string
     */
    protected function getDeviceKind(Agent $agent): string
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
}