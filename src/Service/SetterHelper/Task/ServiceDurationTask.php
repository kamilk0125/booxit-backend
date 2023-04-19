<?php

namespace App\Service\SetterHelper\Task;

use App\Entity\Service;
use App\Exceptions\InvalidRequestException;
use App\Service\SetterHelper\Trait\SetterTaskTrait;
use DateInterval;
use Exception;

/** @property Service $object */
class ServiceDurationTask implements SetterTaskInterface
{
    use SetterTaskTrait;

    public function runPreValidation(string $duration)
    {
        try{
            $interval = new DateInterval($duration);
        }
        catch(Exception){
            throw new InvalidRequestException("Invalid duration format");
        }

        $this->object->setDuration($interval);
    }




}