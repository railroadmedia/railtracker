<?php

namespace Railroad\Railtracker\Services;

use Illuminate\Http\Request;
use Jenssegers\Agent\Agent;
use Railroad\Railtracker\Models\Device;

class Tracker
{
    /**
     * @var Request
     */
    protected $request;

    public function track(Request $request)
    {
        $this->request = $request;

        dd($this->getDeviceId());

        echo $this->getUserId();
    }

    /**
     * @return int|null
     */
    protected function getUserId()
    {
        return $this->request->user()->id ?? null;
    }

    protected function getDeviceId()
    {
        $agent = new Agent($this->request->server->all());

        return Device::query()->updateOrCreate(
            [
                'platform' => $agent->platform(),
                'platform_version' => $agent->version($agent->platform()),
                'kind' => $this->getDeviceKind($agent),
                'model' => $agent->device(),
                'is_mobile' => $agent->isMobile(),
                'is_robot' => $agent->isRobot(),
            ]
        );
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
            $kind = 'computer';
        }

        return $kind;
    }
}