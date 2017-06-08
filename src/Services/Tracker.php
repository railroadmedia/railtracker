<?php

namespace Railroad\Railtracker\Services;

use Illuminate\Http\Request;
use Jenssegers\Agent\Agent;
use Railroad\Railtracker\Models\Agent as AgentModel;
use Railroad\Railtracker\Models\Device;
use Railroad\Railtracker\Models\Domain;
use Railroad\Railtracker\Models\Language;
use Railroad\Railtracker\Models\Path;
use Railroad\Railtracker\Models\Query;
use Railroad\Railtracker\Models\Referer;
use Railroad\Railtracker\Models\Request as RequestModel;
use Railroad\Railtracker\Models\Route;
use Ramsey\Uuid\Uuid;

class Tracker
{
    /**
     * @param Request $request
     * @return mixed
     */
    public function trackRequest(Request $request): int
    {
        $agent = new Agent($request->server->all());

        $userId = $this->getAuthenticatedUserId($request);
        $routeId = $this->trackRoute($request);

        $deviceId = $this->trackDevice($agent);
        $agentId = $this->trackAgent($agent);
        $languageId = $this->trackLanguage($agent);

        $domainId = $this->trackDomain($request->root());
        $pathId = $this->trackPath($request->root());
        $queryId = $this->trackQuery($request->root());

        $refererDomainId = $this->trackDomain($request->headers->get('referer'));
        $referrerId = $this->trackReferer($request->headers->get('referer'), $refererDomainId);

        return RequestModel::query()->updateOrCreate(
            [
                'uuid' => Uuid::uuid4(),
                'user_id' => $userId,
                'domain_id' => $domainId,
                'device_id' => $deviceId,
                'client_ip' => $request->getClientIp(),
                'geoip_id' => null, // this can be calculated afterwards during data analysis
                'agent_id' => $agentId,
                'referer_id' => $referrerId,
                'language_id' => $languageId,
                'is_robot' => $agent->isRobot()
            ]
        )->id;
    }

    /**
     * @param string $uri
     * @return int
     */
    protected function trackPath(string $uri): int
    {
        $urlParts = parse_url($uri);

        return Path::query()->updateOrCreate(
            [
                'path' => $urlParts['path'] ?? ''
            ]
        )->id;
    }

    /**
     * @param string $uri
     * @return int
     */
    protected function trackQuery(string $uri): int
    {
        $urlParts = parse_url($uri);

        return Query::query()->updateOrCreate(
            [
                'string' => $urlParts['query'] ?? ''
            ]
        )->id;
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
     * @param string $uri
     * @return int
     */
    public function trackDomain(string $uri): int
    {
        $urlParts = parse_url($uri);

        return Domain::query()->updateOrCreate(
            [
                'name' => $urlParts['host']
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