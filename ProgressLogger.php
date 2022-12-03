<?php

namespace vannieuwenhovej\ProgressLogger;

class ProgressLogger
{

    /**
     * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
     * ██████╗░██████╗░░█████╗░░██████╗░██████╗░███████╗░██████╗░██████╗
     * ██╔══██╗██╔══██╗██╔══██╗██╔════╝░██╔══██╗██╔════╝██╔════╝██╔════╝
     * ██████╔╝██████╔╝██║░░██║██║░░██╗░██████╔╝█████╗░░╚█████╗░╚█████╗░
     * ██╔═══╝░██╔══██╗██║░░██║██║░░╚██╗██╔══██╗██╔══╝░░░╚═══██╗░╚═══██╗
     * ██║░░░░░██║░░██║╚█████╔╝╚██████╔╝██║░░██║███████╗██████╔╝██████╔╝
     * ╚═╝░░░░░╚═╝░░╚═╝░╚════╝░░╚═════╝░╚═╝░░╚═╝╚══════╝╚═════╝░╚═════╝░
     * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
     * ██╗░░░░░░█████╗░░██████╗░░██████╗░███████╗██████╗░
     * ██║░░░░░██╔══██╗██╔════╝░██╔════╝░██╔════╝██╔══██╗
     * ██║░░░░░██║░░██║██║░░██╗░██║░░██╗░█████╗░░██████╔╝
     * ██║░░░░░██║░░██║██║░░╚██╗██║░░╚██╗██╔══╝░░██╔══██╗
     * ███████╗╚█████╔╝╚██████╔╝╚██████╔╝███████╗██║░░██║
     * ╚══════╝░╚════╝░░╚═════╝░░╚═════╝░╚══════╝╚═╝░░╚═╝
     * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
     *
     * ADVANCED LOGGER : @url https://github.com/vannieuwenhovej/advancedphplogger
     * USAGE: instantiate object at beginning of script or before loop like $logger = new ProgressLogger(...)
     * In the loop run $logger->log("optional message") to log whenver the batchsize is reached.
     * The logger will only log at each batchSize you give in the constructor. It won't log every time it is called.
     * @author Jonathan Van Nieuwenhove on GitHub
     *
     * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
     */

    /**
     * @var int
     * Total amount to process. Is set once.
     */
    protected $total;

    /**
     * @var int
     */
    protected $totalDone;
    /**
     * @var int
     * unix
     */
    protected $timeStarted;

    /**
     * @var array
     * [amountDone => unix seconds]
     */
    protected $trackedTimes;

    /**
     * @var array
     * [amountDone => 0,
     * amountDone => 18,
     * amountDone => 17 (seconds since previous amountDone)]
     */
    protected $trackedDurations;

    /**
     * @var int
     * Amount/ Batch Size to log progress with, for example 500
     */
    protected $logEveryX = null;

    /**
     * @var int
     * Next amount to log progress at, for example 200500
     */
    protected $logAtNextAmount = null;

    /**
     * @var int
     * Next amount to track progress at, for example 200500
     */
    protected $trackAtNextAmount = null;

    /**
     * @var int
     * Amount/ Batch Size to track progress with, for example 500
     */
    protected $trackEveryX = null;

    /**
     * @var float
     * Log at every percentage, for example 5 or 0.1
     */
    protected $logPercentageBlock = null;

    /**
     * @var float
     * Track at every percentage, for example 5 or 0.1
     */
    protected $trackPercentageBlock = null;

    /**
     * @var int
     * Precision of percentage output
     */
    protected $percentagePrecision = null;

    /**
     * @var string
     * For message output, for ex. "Fetched", "Converted", ...
     */
    protected $pastVerb = "Done";

    /**
     * @var string
     * For message output, for ex. "Parcel", "Address", ...
     */
    protected $subjectSingle = "Record";

    /**
     * @var string
     * For message output, for ex. "Parcels", "Addresses", ...
     */
    protected $subjectPlurar = "Records";

    /**
     * @var int
     * Duration in seconds since last tracked batch
     */
    protected $duration;


    /**
     * @param int $totalAmountToDo a.k.a. total amount of rows which the script will have to process
     * @param int $logEveryBatch The amount of processed rows after which to log progress
     * @param int $precision of percentage-points
     * @param int $trackEveryBatch the amount of processed rows to track progress at (recommended = $logEveryBatch / 2)
     * @param float $logEveryPercentage @optional to log at percentage-point (ex. 1) instead of amount
     * @param float $trackEveryPercentage @optional to track at percentage-point (ex. 0.5) instead of amount
     */
    public function __construct(int $totalAmountToDo, int $logEveryBatch = 1000, int $precision = 2, int $trackEveryBatch = null, float $logEveryPercentage = null, float $trackEveryPercentage = null){
        $this->timeStarted = time();
        $this->total = $totalAmountToDo;
        $this->percentagePrecision = $precision;
        $this->logPercentageBlock = $logEveryPercentage;
        $this->logEveryX = $logEveryBatch;
        $trackEveryBatch = empty($trackEveryBatch) ? floor($logEveryBatch/2) : $trackEveryBatch;
        if(!empty($totalAmountToDo)){
            if(!empty($logEveryPercentage) && empty($logEveryBatch)){
                // Calculate Log At Percentage based of Log At Batch
                $logEveryBatch = ($totalAmountToDo<100) ? 1 : floor($totalAmountToDo/(100/$logEveryPercentage));
            } elseif(!empty($logEveryBatch) && empty($logEveryPercentage)){
                // Calculate Log At Batch based of Log At Percentage
                $logEveryPercentage = round($logEveryBatch/$totalAmountToDo*100, 8);
            }
            if(!empty($trackEveryPercentage) && empty($trackEveryBatch)){
                // Calculate Track At Percentage based of Track At Batch
                $trackEveryBatch = ($totalAmountToDo<100) ? 1 : floor($totalAmountToDo/(100/$trackEveryPercentage));
            } elseif(!empty($trackEveryPercentage) && empty($trackEveryBatch)){
                // Calculate Track At Batch based of Track At Percentage
                $trackEveryPercentage = round($trackEveryBatch/$totalAmountToDo*100, 8);
            }
        }
        $this->trackPercentageBlock = $trackEveryPercentage ?? $logEveryPercentage;
        $this->trackEveryX = $trackEveryBatch ?? $logEveryBatch;
        $this->logAtNextAmount = $logEveryBatch;
        $this->trackAtNextAmount = $trackEveryBatch;
        $this->setSubjects();
    }

    /**
     * @param int $amountDone
     * Place this in code to track. When amountDone reaches trackEveryX it will track.
     */
    public function track(int $amountDone){
        $this->totalDone = $amountDone;
        if($amountDone >= $this->trackAtNextAmount){
        /* old way: if(!empty($this->trackEveryX) && $amountDone % $this->trackEveryX === 0){ */
            $this->trackTime($amountDone);
            $this->trackAtNextAmount = $amountDone + $this->trackEveryX;
        }
    }

    /**
     * @param int $amountDone
     * @param string|null $message
     * General function - place this in your code to log whenever the $amountDone reaches batch Size
     */
    public function log(int $amountDone, string $message = null){
        $this->track($amountDone);
        if($amountDone >= $this->logAtNextAmount){
        /* old way: if (!empty($this->logEveryX) && ($amountDone % $this->logEveryX) === 0) { */
            $this->logProgress($message);
            $this->logAtNextAmount = $amountDone + $this->logEveryX;
        }
    }

    public function setSubjects(string $pastVerb = "Stored", string $subjectSingle = "Record", string $subjectPlurar = "Records"){
        $this->pastVerb = $pastVerb;
        $this->subjectSingle = $subjectSingle;
        $this->subjectPlurar = $subjectPlurar;
    }

    protected function calculatePercentageDone(){
        return round(($this->totalDone/$this->total)*100, $this->percentagePrecision);
    }

    // Track time & duration of last batch
    protected function trackTime($amountDone){
        $time = time();
        $previousTime = $this->trackedTimes ? end($this->trackedTimes) : 0;
        $this->trackedTimes[$amountDone] = $time;
        if(!empty($this->trackedDurations) && !isset($this->trackedDurations[$amountDone])){
            $this->trackedDurations[$amountDone] = $this->duration = $time - $previousTime;
        } elseif(empty($this->trackedDurations)) {
            $this->trackedDurations[$amountDone] = 0;
        }
    }

    /**
     * Calculate time left based off previous duration of batches, amount left to do and total amount.
     * @return int time left in seconds
     */
    protected function calculateEstimatedTimeLeft(){
        $amountLeft = $this->total - $this->totalDone;
        $trackedDurations = array_slice($this->trackedDurations, -100, 101, true); // calculate only of last 100 entries
        if(sizeof($trackedDurations)<3){
            return null; // need a minimum
        }
        $totalDurationsFor100=0;
        $i=0;
        $prevAmountDone = 0;
        foreach($trackedDurations as $amountDone => $duration){
            if($amountDone===$this->trackEveryX){
                $prevAmountDone = $amountDone;
                continue; // skip first one
            }
            $amountDoneInThisDuration = ($amountDone-$prevAmountDone);
            $durationFor100 = $duration * (100/$amountDoneInThisDuration);
            $totalDurationsFor100 += $durationFor100;
            $prevAmountDone = $amountDone;
            $i++;
        }
        $averageDurationPer100 = $totalDurationsFor100/$i;
        $timeLeft = $amountLeft/100*$averageDurationPer100;
        return $timeLeft;
    }


    protected function convertSecondsToTimeFormat($seconds){
        $days = floor($seconds / 86400);
        $seconds = $seconds-($days*86400);
        $hours = floor($seconds / 3600);
        $seconds = $seconds-($hours*3600);
        $mins = floor($seconds / 60);
        $seconds = $seconds-($mins*60);
        $seconds = floor($seconds);
        return [$days, $hours, $mins, $seconds];
    }


    /**
     * @param string|null $message
     * logs progress as "20% Fetched 2000 Records in 15s - (18 mins & 1 sec left)"
     */
    protected function logProgress(string $message = null){
        $str = $this->calculatePercentageDone() . "% ";
        if(!empty($message)){
            $str .= $message;
        } elseif(!empty($this->pastVerb) && !empty($this->subjectPlurar)){
            $str .= $this->pastVerb." ".$this->totalDone." ".$this->subjectPlurar;
        }
        $str .= $this->duration ? " in " . $this->duration."s" : '';
        $seconds = $this->calculateEstimatedTimeLeft();
        if($seconds){
            $str .= " - (";
            $str .= $this->getTimeLeftStringInShortFormat($seconds).")";
        }
        Datahub_Utils_Logger::logMessage($str);
    }

    /**
     * Returns estimated time left in days, hours, minutes and/or seconds based off seconds
     * @param $seconds
     * @return string|null
     */
    protected function getTimeLeftStringInShortFormat($seconds){
        if($seconds <= 0){
            return null;
        }
        list($days, $hours, $mins, $secs) = $this->convertSecondsToTimeFormat($seconds);
        $str = "";
        if($days>0){
            $str .= ($days==1) ? ($days . " day") : ($days . " days");
        }
        if($hours>0){
            if($days>0) {
                //if ($mins > 0 || $secs > 0) {
                //    $str .= ', ';
                //} else {
                    $str .= ' & ';
                //}
            }
            $str .= ($hours==1) ? ($hours." hour") : ($hours." hours");
        }
        if($mins>0 && !($days>0)){ // if no days left, show minutes
            if($days>0 || $hours>0) {
                //if ($secs > 0) {
                //    $str .= ', ';
                //} else {
                    $str .= ' & ';
                //}
            }
            $str .= ($mins==1) ? ($mins." min") : ($mins." mins");
        }
        if($secs>0 && !($hours>0)) {  // if no hours left, show seconds
            if($days>0 || $hours>0 || $mins > 0) {
                $str .= ' & ';
            }
            $str .= ($secs == 1) ? ($secs . " sec") : ($secs . " secs");
        }
        $str .= ' left';
        return $str;
    }

    protected function setLogEveryX(int $x){
        $this->logEveryX = $x;
    }

}
