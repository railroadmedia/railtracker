<?php

namespace Railroad\Railtracker\Console\Commands;

use Exception;
use Illuminate\Cookie\CookieJar;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Collection;
use Railroad\Railtracker\Events\RequestTracked;
use Railroad\Railtracker\Repositories\RequestRepository;
use Railroad\Railtracker\Services\BatchService;
use Railroad\Railtracker\Services\IpDataApiSdkService;
use Railroad\Railtracker\Trackers\ExceptionTracker;
use Railroad\Railtracker\ValueObjects\ExceptionVO;
use Railroad\Railtracker\ValueObjects\RequestVO;

class LegacyMigrate extends \Illuminate\Console\Command
{
    /**
     * @var string
     */
    protected $name = 'LegacyMigrate';

    /**
     * @var string
     */
    protected $description = 'Migrate data from legacy tables.';

    /**
     * @var string
     */
    protected $signature = 'legacyMigrate';

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * return true
     */
    public function handle()
    {
        $toRun = $this->promptForOption();

        $this->$toRun();
    }

    /**
     * @return bool|mixed
     */
    private function promptForOption()
    {
        $methodsAvailable = [
            'DrumeoLegacyTo4',
            'Drumeo3To4',
            'MusoraLegacyTo4',
            'Musora3To4',
            'Pianote3To4',
            'Guitareo3To4'
        ];

        $selection = false;
        while ($selection === false){

            foreach($methodsAvailable as $index => $methodAvailable){
                $this->info($index . '. ' . $methodAvailable);
            }

            $selection = $this->ask('Run which operation?');

            $notNumeric = !is_numeric($selection);
            $tooHigh = $selection > (count($methodsAvailable) - 1);

            if($notNumeric || $tooHigh){
                $this->info('Invalid. Try again' . PHP_EOL);
                $selection = false;
            }
        }

        return $methodsAvailable[$selection];
    }

    private function DrumeoLegacyTo4()
    {
        $this->info('------ STARTING DrumeoLegacyTo4 ------');


    }

    private function Drumeo3To4()
    {
        $this->info('------ STARTING Drumeo3To4 ------');

        $this->info('TO DO');
    }

    private function MusoraLegacyTo4()
    {
        $this->info('------ STARTING MusoraLegacyTo4 ------');

        $this->info('TO DO');
    }

    private function Musora3To4()
    {
        $this->info('------ STARTING Musora3To4 ------');

        $this->info('TO DO');
    }

    private function Pianote3To4()
    {
        $this->info('------ STARTING Pianote3To4 ------');

        $this->info('TO DO');
    }

    private function Guitareo3To4()
    {
        $this->info('------ STARTING Guitareo3To4 ------');

        $this->info('TO DO');
    }

}
