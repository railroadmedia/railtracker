<?php

namespace Railroad\Railtracker\Services;

use Illuminate\Http\Request;
use Jenssegers\Agent\Agent;
use Railroad\Railtracker\Models\Device;
use Railroad\Railtracker\Models\Agent as AgentModel;

class Tracker
{
    /**
     * @var Request
     */
    protected $request;

    /**
     * @var Agent
     */
    protected $agent;

    public function track(Request $request)
    {
        $this->request = $request;
        $this->agent = new Agent($this->request->server->all());

        $deviceId = $this->getDeviceId();
        $agentId = $this->getAgentId();
    }

    /**
     * @return int|null
     */
    protected function getUserId()
    {
        return $this->request->user()->id ?? null;
    }

    /**
     * @return int
     */
    protected function getDeviceId()
    {
        return Device::query()->updateOrCreate(
            [
                'platform' => $this->agent->platform(),
                'platform_version' => $this->agent->version($this->agent->platform()),
                'kind' => $this->getDeviceKind($this->agent),
                'model' => $this->agent->device(),
                'is_mobile' => $this->agent->isMobile(),
            ]
        )->id;
    }

    /**
     * @return int
     */
    public function getAgentId()
    {
        return AgentModel::query()->updateOrCreate(
            [
                'name' => $this->agent->getUserAgent() ?: 'Other',
                'browser' => $this->agent->browser(),
                'browser_version' => $this->agent->version($this->agent->browser()),
            ]
        )->id;
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
}